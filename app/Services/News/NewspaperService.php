<?php

namespace App\Services\News;

use App\Models\NewsItem;
use App\Models\NewspaperEdition;
use App\Models\TrackedEntity;
use App\Models\User;
use App\Services\AdvisoryContextBuilder;
use App\Services\LLM\LLMManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NewspaperService
{
    public function __construct(
        protected LLMManager $llm,
        protected AdvisoryContextBuilder $contextBuilder
    ) {}

    public function generateForUser(User $user): ?NewspaperEdition
    {
        $newsItems = $this->getRecentNewsForUser($user);

        if ($newsItems->isEmpty()) {
            return null;
        }

        $businessContext = $this->contextBuilder->build($user);
        $prompt = $this->buildPrompt($newsItems, $businessContext);

        try {
            $response = $this->llm->driver()->chat(
                messages: [['role' => 'user', 'content' => $prompt]],
                system: $this->getSystemPrompt()
            );

            $parsed = $this->parseResponse($response->content);

            if (! $parsed) {
                Log::error('Failed to parse newspaper response', ['response' => $response->content]);

                return null;
            }

            return NewspaperEdition::create([
                'user_id' => $user->id,
                'edition_date' => now()->toDateString(),
                'headline' => $parsed['headline'],
                'summary' => $parsed['summary'],
                'sections' => $parsed['sections'],
                'generated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to generate newspaper edition', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return Collection<int, NewsItem>
     */
    protected function getRecentNewsForUser(User $user): Collection
    {
        $trackedEntityIds = TrackedEntity::active()->pluck('id');

        return NewsItem::query()
            ->whereIn('tracked_entity_id', $trackedEntityIds)
            ->with('trackedEntity')
            ->relevant()
            ->where('fetched_at', '>=', now()->subHours(48))
            ->orderByDesc('fetched_at')
            ->limit(50)
            ->get();
    }

    protected function getSystemPrompt(): string
    {
        return <<<'EOT'
You are a business news editor creating a personalized daily briefing. Your task is to curate and summarize news articles into a compelling newspaper-style digest that's relevant to the reader's business.

You MUST respond with valid JSON only. No other text or explanation.

The JSON structure must be:
{
  "headline": "A catchy headline summarizing the most important theme of the day",
  "summary": "A 3-4 sentence executive summary of the day's most important business news and what it means for the reader",
  "sections": [
    {
      "title": "Section title (e.g., 'Competitor Watch', 'Market Trends', 'Industry News')",
      "icon": "heroicon name (e.g., 'chart-bar', 'building-office', 'newspaper', 'light-bulb', 'arrow-trending-up')",
      "articles": [
        {
          "news_item_id": 123,
          "headline": "Rewritten headline that's clearer and more compelling",
          "summary": "2-3 sentence summary of the article",
          "insight": "One sentence explaining why this matters to the reader's business specifically"
        }
      ]
    }
  ]
}

Guidelines:
- Create 3-5 thematic sections based on the news content
- Each section should have 2-5 articles
- Prioritize news most relevant to the reader's business context
- Write headlines that are clear and actionable
- Insights should connect the news to the reader's specific business situation
- Use the business context to personalize insights
- If there's not enough news for a section, omit it
EOT;
    }

    /**
     * @param  Collection<int, NewsItem>  $newsItems
     */
    protected function buildPrompt(Collection $newsItems, string $businessContext): string
    {
        $newsData = $newsItems->map(function (NewsItem $item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'snippet' => $item->snippet,
                'source' => $item->source,
                'entity' => $item->trackedEntity->name,
                'entity_type' => $item->trackedEntity->entity_type->value ?? 'company',
                'thumbnail' => $item->thumbnail,
                'published_at' => $item->published_at?->toDateTimeString(),
            ];
        })->toArray();

        $newsJson = json_encode($newsData, JSON_PRETTY_PRINT);

        return <<<EOT
Create a personalized newspaper edition from these news items. Use the business context to make insights relevant to the reader.

BUSINESS CONTEXT:
{$businessContext}

NEWS ITEMS:
{$newsJson}

Remember to respond with valid JSON only.
EOT;
    }

    /**
     * @return array{headline: string, summary: string, sections: array}|null
     */
    protected function parseResponse(string $response): ?array
    {
        // Clean up potential markdown code blocks
        $response = preg_replace('/^```(?:json)?\n?/m', '', $response);
        $response = preg_replace('/\n?```$/m', '', $response);
        $response = trim($response);

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('JSON parse error in newspaper response', [
                'error' => json_last_error_msg(),
                'response' => substr($response, 0, 500),
            ]);

            return null;
        }

        if (! isset($data['headline'], $data['summary'], $data['sections'])) {
            Log::warning('Missing required fields in newspaper response', ['data' => $data]);

            return null;
        }

        return $data;
    }

    public function regenerateForUser(User $user): ?NewspaperEdition
    {
        // Delete today's existing edition if any
        NewspaperEdition::query()
            ->where('user_id', $user->id)
            ->today()
            ->delete();

        return $this->generateForUser($user);
    }
}
