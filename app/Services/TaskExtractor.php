<?php

namespace App\Services;

use App\Enums\InsightType;
use App\Enums\TaskPriority;
use App\Enums\TaskSource;
use App\Enums\TaskStatus;
use App\Models\ProactiveInsight;
use App\Models\Task;
use App\Models\TaskSuggestion;
use App\Models\User;
use App\Services\LLM\LLMManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TaskExtractor
{
    public function __construct(
        protected LLMManager $llmManager
    ) {}

    /**
     * Extract actionable tasks from a ProactiveInsight.
     *
     * @return Collection<int, Task>
     */
    public function extractFromInsight(ProactiveInsight $insight): Collection
    {
        $user = $insight->user;
        $preferences = $user->preferences ?? $user->getOrCreatePreferences();

        if (! $preferences->task_suggestions_enabled) {
            return collect();
        }

        if (! $this->isActionableInsightType($insight->insight_type)) {
            return collect();
        }

        try {
            $extractedTasks = $this->extractTasksViaLLM($insight);

            return $this->createTasks($user, $insight, $extractedTasks);
        } catch (\Exception $e) {
            Log::error('Failed to extract tasks from insight', [
                'insight_id' => $insight->id,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Determine if the insight type typically contains actionable tasks.
     */
    protected function isActionableInsightType(InsightType $type): bool
    {
        return in_array($type, [
            InsightType::Recommendation,
            InsightType::Opportunity,
            InsightType::Warning,
        ]);
    }

    /**
     * Use LLM to extract tasks from insight content.
     *
     * @return array<int, array{title: string, description: string|null, priority: string, due_days: int|null}>
     */
    protected function extractTasksViaLLM(ProactiveInsight $insight): array
    {
        $prompt = <<<EOT
Analyze the following business insight and extract actionable tasks that the user should consider doing.

INSIGHT TITLE: {$insight->title}

INSIGHT CONTENT:
{$insight->content}

Extract up to 3 specific, actionable tasks from this insight. For each task, provide:
1. A clear, action-oriented title (starting with a verb like "Review", "Contact", "Analyze", "Schedule", etc.)
2. A brief description explaining what needs to be done
3. Priority: "low", "medium", "high", or "urgent"
4. Suggested days until due (null if no specific deadline)

Respond ONLY with valid JSON in this exact format:
{
  "tasks": [
    {
      "title": "Action-oriented task title",
      "description": "Brief explanation of what needs to be done",
      "priority": "medium",
      "due_days": 7
    }
  ]
}

If there are no clear actionable tasks, respond with:
{"tasks": []}
EOT;

        $response = $this->llmManager->driver()->chat(
            messages: [['role' => 'user', 'content' => $prompt]],
            system: 'You are a task extraction assistant. Extract specific, actionable tasks from business insights. Always respond with valid JSON only.'
        );

        return $this->parseTasksFromResponse($response->content);
    }

    /**
     * Parse the LLM response to extract task data.
     *
     * @return array<int, array{title: string, description: string|null, priority: string, due_days: int|null}>
     */
    protected function parseTasksFromResponse(string $content): array
    {
        $content = trim($content);

        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        } elseif (preg_match('/```\s*(.*?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        $content = trim($content);

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (! isset($decoded['tasks']) || ! is_array($decoded['tasks'])) {
                return [];
            }

            return array_filter($decoded['tasks'], function ($task) {
                return isset($task['title']) && is_string($task['title']) && strlen($task['title']) > 3;
            });
        } catch (\JsonException $e) {
            Log::warning('Failed to parse task extraction response', [
                'content' => $content,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Create Task models from extracted data, avoiding duplicates.
     *
     * @param  array<int, array{title: string, description: string|null, priority: string, due_days: int|null}>  $extractedTasks
     * @return Collection<int, Task>
     */
    protected function createTasks(User $user, ProactiveInsight $insight, array $extractedTasks): Collection
    {
        $createdTasks = collect();

        foreach ($extractedTasks as $taskData) {
            $title = $taskData['title'];
            $description = $taskData['description'] ?? null;

            $hash = TaskSuggestion::generateHash($title, $description);

            if (TaskSuggestion::existsForUser($user->id, $hash)) {
                continue;
            }

            $dueDate = null;
            if (isset($taskData['due_days']) && is_numeric($taskData['due_days'])) {
                $dueDate = now()->addDays((int) $taskData['due_days'])->toDateString();
            }

            $task = Task::create([
                'user_id' => $user->id,
                'proactive_insight_id' => $insight->id,
                'title' => $title,
                'description' => $description,
                'status' => TaskStatus::Suggested,
                'priority' => $this->mapPriority($taskData['priority'] ?? 'medium'),
                'source' => TaskSource::Ai,
                'due_date' => $dueDate,
                'suggested_at' => now(),
                'metadata' => [
                    'extracted_from' => 'proactive_insight',
                    'insight_type' => $insight->insight_type->value,
                ],
            ]);

            TaskSuggestion::create([
                'user_id' => $user->id,
                'proactive_insight_id' => $insight->id,
                'suggestion_hash' => $hash,
                'was_accepted' => false,
                'was_rejected' => false,
                'suggested_at' => now(),
            ]);

            $createdTasks->push($task);
        }

        return $createdTasks;
    }

    /**
     * Map priority string to TaskPriority enum.
     */
    protected function mapPriority(string $priority): TaskPriority
    {
        return match (strtolower($priority)) {
            'urgent' => TaskPriority::Urgent,
            'high' => TaskPriority::High,
            'low' => TaskPriority::Low,
            default => TaskPriority::Medium,
        };
    }
}
