<?php

namespace App\Services;

use App\Enums\TaskPriority;
use App\Enums\TaskSource;
use App\Enums\TaskStatus;
use App\Models\DailyStandup;
use App\Models\StandupEntry;
use App\Models\Task;
use App\Services\LLM\LLMManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class InteractiveStandupService
{
    public function __construct(
        protected LLMManager $llmManager,
        protected AdvisoryContextBuilder $contextBuilder
    ) {}

    /**
     * Generate contextual follow-up questions based on standup entry.
     *
     * @return array<int, string>
     */
    public function generateFollowUpQuestions(StandupEntry $entry): array
    {
        $context = $this->contextBuilder->build($entry->user);
        $standup = $entry->dailyStandup;

        $prompt = $this->buildFollowUpPrompt($entry, $standup);

        try {
            $response = $this->llmManager->driver()->chat(
                messages: [['role' => 'user', 'content' => $prompt]],
                system: $context
            );

            return $this->parseQuestions($response->content);
        } catch (\Exception $e) {
            Log::error('Failed to generate follow-up questions', [
                'entry_id' => $entry->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Generate comprehensive analysis of the standup entry.
     */
    public function generateAnalysis(StandupEntry $entry): string
    {
        $context = $this->contextBuilder->build($entry->user);
        $standup = $entry->dailyStandup;

        $prompt = $this->buildAnalysisPrompt($entry, $standup);

        try {
            $response = $this->llmManager->driver()->chat(
                messages: [['role' => 'user', 'content' => $prompt]],
                system: $context
            );

            return $response->content;
        } catch (\Exception $e) {
            Log::error('Failed to generate standup analysis', [
                'entry_id' => $entry->id,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Extract tasks from the "today planned" section.
     *
     * @return Collection<int, Task>
     */
    public function extractTasksFromToday(StandupEntry $entry): Collection
    {
        if (empty($entry->today_planned)) {
            return collect();
        }

        $prompt = <<<EOT
Analyze the following "what I'm planning to do today" statement and extract specific tasks.

TODAY'S PLANS:
{$entry->today_planned}

Extract specific, actionable tasks. For each task, provide:
1. A clear, action-oriented title
2. Priority based on apparent urgency ("low", "medium", "high", "urgent")
3. Note: all tasks extracted here are due today

Respond ONLY with valid JSON:
{
  "tasks": [
    {
      "title": "Task title",
      "priority": "medium"
    }
  ]
}

If no clear tasks can be extracted, respond with {"tasks": []}
EOT;

        try {
            $response = $this->llmManager->driver()->chat(
                messages: [['role' => 'user', 'content' => $prompt]],
                system: 'Extract tasks from natural language plans. Respond with JSON only.'
            );

            return $this->createTasksFromPlanned($entry, $response->content);
        } catch (\Exception $e) {
            Log::error('Failed to extract tasks from today planned', [
                'entry_id' => $entry->id,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Build the follow-up questions prompt.
     */
    protected function buildFollowUpPrompt(StandupEntry $entry, DailyStandup $standup): string
    {
        $alertsContext = $this->formatAlerts($standup->alerts ?? []);

        return <<<EOT
The user has submitted their daily standup check-in. Based on their input and the current business context, generate 1-3 thoughtful follow-up questions.

USER'S STANDUP INPUT:
Yesterday: {$entry->yesterday_accomplished}
Today: {$entry->today_planned}
Blockers: {$entry->blockers}

CURRENT BUSINESS ALERTS:
{$alertsContext}

Generate follow-up questions that:
1. Connect their work to current business priorities or concerns
2. Help clarify blockers or obstacles
3. Identify opportunities to align daily work with strategic goals

Respond ONLY with valid JSON:
{
  "questions": [
    "Your question here?"
  ]
}

Generate 1-3 questions maximum. If no follow-up is needed, respond with {"questions": []}
EOT;
    }

    /**
     * Build the analysis prompt.
     */
    protected function buildAnalysisPrompt(StandupEntry $entry, DailyStandup $standup): string
    {
        $alertsContext = $this->formatAlerts($standup->alerts ?? []);
        $followUpContext = $this->formatFollowUp($entry);

        return <<<EOT
Provide a brief analysis of this standup check-in in the context of the business.

STANDUP INPUT:
Yesterday: {$entry->yesterday_accomplished}
Today: {$entry->today_planned}
Blockers: {$entry->blockers}

{$followUpContext}

CURRENT BUSINESS CONTEXT:
{$alertsContext}

Provide a brief (2-3 paragraph) analysis that:
1. Acknowledges progress and connects it to business goals
2. Identifies any alignment or misalignment with current priorities
3. Offers a specific suggestion or encouragement for today

Be conversational and supportive, not formal.
EOT;
    }

    /**
     * Format alerts for prompt context.
     */
    protected function formatAlerts(array $alerts): string
    {
        if (empty($alerts)) {
            return 'No current alerts.';
        }

        $lines = [];

        if (! empty($alerts['contracts_expiring'])) {
            $count = count($alerts['contracts_expiring']);
            $lines[] = "{$count} contract(s) expiring soon";
        }

        if (! empty($alerts['runway'])) {
            $runway = $alerts['runway']['current_runway'] ?? 'unknown';
            $lines[] = "Runway alert: {$runway} months";
        }

        if (! empty($alerts['overdue_tasks'])) {
            $count = count($alerts['overdue_tasks']);
            $lines[] = "{$count} overdue task(s)";
        }

        if (! empty($alerts['unread_insights'])) {
            $lines[] = "{$alerts['unread_insights']} unread AI insights";
        }

        return empty($lines) ? 'No current alerts.' : implode("\n", $lines);
    }

    /**
     * Format follow-up Q&A for prompt context.
     */
    protected function formatFollowUp(StandupEntry $entry): string
    {
        if (empty($entry->ai_follow_up_questions) || empty($entry->ai_follow_up_responses)) {
            return '';
        }

        $lines = ['FOLLOW-UP DISCUSSION:'];

        $questions = $entry->ai_follow_up_questions;
        $responses = $entry->ai_follow_up_responses;

        foreach ($questions as $index => $question) {
            $response = $responses[$index] ?? 'No response';
            $lines[] = "Q: {$question}";
            $lines[] = "A: {$response}";
        }

        return implode("\n", $lines);
    }

    /**
     * Parse questions from LLM response.
     *
     * @return array<int, string>
     */
    protected function parseQuestions(string $content): array
    {
        $content = trim($content);

        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (! isset($decoded['questions']) || ! is_array($decoded['questions'])) {
                return [];
            }

            return array_filter($decoded['questions'], function ($q) {
                return is_string($q) && strlen($q) > 5;
            });
        } catch (\JsonException $e) {
            Log::warning('Failed to parse follow-up questions', [
                'content' => $content,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Create tasks from parsed planned items.
     *
     * @return Collection<int, Task>
     */
    protected function createTasksFromPlanned(StandupEntry $entry, string $content): Collection
    {
        $content = trim($content);

        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (! isset($decoded['tasks']) || ! is_array($decoded['tasks'])) {
                return collect();
            }

            $createdTasks = collect();

            foreach ($decoded['tasks'] as $taskData) {
                if (! isset($taskData['title']) || strlen($taskData['title']) < 3) {
                    continue;
                }

                $task = Task::create([
                    'user_id' => $entry->user_id,
                    'daily_standup_id' => $entry->daily_standup_id,
                    'title' => $taskData['title'],
                    'description' => null,
                    'status' => TaskStatus::Suggested,
                    'priority' => $this->mapPriority($taskData['priority'] ?? 'medium'),
                    'source' => TaskSource::Standup,
                    'due_date' => now()->toDateString(),
                    'suggested_at' => now(),
                    'metadata' => [
                        'extracted_from' => 'standup_entry',
                        'standup_entry_id' => $entry->id,
                    ],
                ]);

                $createdTasks->push($task);
            }

            return $createdTasks;
        } catch (\JsonException $e) {
            Log::warning('Failed to parse tasks from planned', [
                'content' => $content,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
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
