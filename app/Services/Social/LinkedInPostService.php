<?php

namespace App\Services\Social;

use App\Enums\LinkedInPostFrequency;
use App\Enums\LinkedInPostType;
use App\Enums\LinkedInTone;
use App\Models\LinkedInPost;
use App\Models\NewsItem;
use App\Models\TrackedEntity;
use App\Models\User;
use App\Services\AdvisoryContextBuilder;
use App\Services\LLM\LLMManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class LinkedInPostService
{
    public function __construct(
        protected LLMManager $llm,
        protected AdvisoryContextBuilder $contextBuilder
    ) {}

    /**
     * Generate a batch of posts based on user preferences.
     *
     * @return Collection<int, LinkedInPost>
     */
    public function generateBatch(User $user): Collection
    {
        $preferences = $user->getOrCreatePreferences();
        $postsToGenerate = $preferences->linkedin_posts_per_generation ?? 3;
        $topics = $preferences->linkedin_topics ?? ['industry_trends', 'thought_leadership', 'news_commentary'];

        $posts = collect();

        // Generate news-based posts if enabled
        if (in_array('news_commentary', $topics)) {
            $newsPostCount = (int) ceil($postsToGenerate / 2);
            $newsPosts = $this->generateFromNews($user, $newsPostCount);
            $posts = $posts->merge($newsPosts);
        }

        // Generate thought leadership posts
        $remainingCount = $postsToGenerate - $posts->count();
        if ($remainingCount > 0) {
            $thoughtLeadershipTopics = array_filter($topics, fn ($t) => $t !== 'news_commentary');
            $thoughtPosts = $this->generateThoughtLeadership($user, $remainingCount, $thoughtLeadershipTopics);
            $posts = $posts->merge($thoughtPosts);
        }

        return $posts;
    }

    /**
     * Generate posts from recent news articles.
     *
     * @return Collection<int, LinkedInPost>
     */
    public function generateFromNews(User $user, int $count = 2): Collection
    {
        $newsItems = $this->getRecentNewsForUser();

        if ($newsItems->isEmpty()) {
            return collect();
        }

        $businessContext = $this->contextBuilder->build($user);
        $preferences = $user->getOrCreatePreferences();
        $tone = LinkedInTone::tryFrom($preferences->linkedin_default_tone ?? 'professional') ?? LinkedInTone::Professional;
        $includeHashtags = $preferences->linkedin_include_hashtags ?? true;
        $includeCta = $preferences->linkedin_include_cta ?? true;

        $posts = collect();

        // Select top news items to generate posts from
        $selectedNews = $newsItems->take($count);

        foreach ($selectedNews as $newsItem) {
            $post = $this->generateNewsPost($user, $newsItem, $businessContext, $tone, $includeHashtags, $includeCta);
            if ($post) {
                $posts->push($post);
            }
        }

        return $posts;
    }

    /**
     * Generate standalone thought leadership posts.
     *
     * @param  array<string>  $topics
     * @return Collection<int, LinkedInPost>
     */
    public function generateThoughtLeadership(User $user, int $count = 1, array $topics = []): Collection
    {
        $businessContext = $this->contextBuilder->build($user);
        $preferences = $user->getOrCreatePreferences();
        $tone = LinkedInTone::tryFrom($preferences->linkedin_default_tone ?? 'professional') ?? LinkedInTone::Professional;
        $includeHashtags = $preferences->linkedin_include_hashtags ?? true;
        $includeCta = $preferences->linkedin_include_cta ?? true;

        if (empty($topics)) {
            $topics = ['industry_trends', 'thought_leadership'];
        }

        $prompt = $this->buildThoughtLeadershipPrompt($businessContext, $topics, $tone, $count, $includeHashtags, $includeCta);

        try {
            $response = $this->llm->driver()->chat(
                messages: [['role' => 'user', 'content' => $prompt]],
                system: $this->getSystemPrompt($tone)
            );

            $parsed = $this->parseMultiPostResponse($response->content);

            if (! $parsed) {
                Log::error('Failed to parse thought leadership posts', ['response' => $response->content]);

                return collect();
            }

            $posts = collect();

            foreach ($parsed['posts'] as $postData) {
                $postType = match ($postData['type'] ?? 'thought_leadership') {
                    'industry_insight' => LinkedInPostType::IndustryInsight,
                    'company_update' => LinkedInPostType::CompanyUpdate,
                    default => LinkedInPostType::ThoughtLeadership,
                };

                $post = LinkedInPost::create([
                    'user_id' => $user->id,
                    'news_item_id' => null,
                    'post_type' => $postType,
                    'tone' => $tone,
                    'title' => $postData['title'],
                    'content' => $postData['content'],
                    'hashtags' => $postData['hashtags'] ?? [],
                    'call_to_action' => $postData['call_to_action'] ?? null,
                    'provider' => $response->provider,
                    'model' => $response->model,
                    'tokens_used' => $response->tokenCount,
                    'generated_at' => now(),
                ]);

                $posts->push($post);
            }

            return $posts;
        } catch (\Throwable $e) {
            Log::error('Failed to generate thought leadership posts', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Regenerate a single post with a fresh approach.
     */
    public function regenerate(LinkedInPost $post): ?LinkedInPost
    {
        $user = $post->user;
        $businessContext = $this->contextBuilder->build($user);
        $preferences = $user->getOrCreatePreferences();
        $includeHashtags = $preferences->linkedin_include_hashtags ?? true;
        $includeCta = $preferences->linkedin_include_cta ?? true;

        if ($post->newsItem) {
            $newPost = $this->generateNewsPost(
                $user,
                $post->newsItem,
                $businessContext,
                $post->tone,
                $includeHashtags,
                $includeCta
            );
        } else {
            $topics = match ($post->post_type) {
                LinkedInPostType::IndustryInsight => ['industry_trends'],
                LinkedInPostType::CompanyUpdate => ['company_updates'],
                default => ['thought_leadership'],
            };

            $newPosts = $this->generateThoughtLeadership($user, 1, $topics);
            $newPost = $newPosts->first();
        }

        if ($newPost) {
            $post->dismiss();
        }

        return $newPost;
    }

    protected function generateNewsPost(
        User $user,
        NewsItem $newsItem,
        string $businessContext,
        LinkedInTone $tone,
        bool $includeHashtags,
        bool $includeCta
    ): ?LinkedInPost {
        $prompt = $this->buildNewsPostPrompt($newsItem, $businessContext, $tone, $includeHashtags, $includeCta);

        try {
            $response = $this->llm->driver()->chat(
                messages: [['role' => 'user', 'content' => $prompt]],
                system: $this->getSystemPrompt($tone)
            );

            $parsed = $this->parseSinglePostResponse($response->content);

            if (! $parsed) {
                Log::error('Failed to parse news post response', [
                    'news_item_id' => $newsItem->id,
                    'response' => $response->content,
                ]);

                return null;
            }

            return LinkedInPost::create([
                'user_id' => $user->id,
                'news_item_id' => $newsItem->id,
                'post_type' => LinkedInPostType::NewsCommentary,
                'tone' => $tone,
                'title' => $parsed['title'],
                'content' => $parsed['content'],
                'hashtags' => $parsed['hashtags'] ?? [],
                'call_to_action' => $parsed['call_to_action'] ?? null,
                'provider' => $response->provider,
                'model' => $response->model,
                'tokens_used' => $response->tokenCount,
                'generated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to generate news post', [
                'user_id' => $user->id,
                'news_item_id' => $newsItem->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return Collection<int, NewsItem>
     */
    protected function getRecentNewsForUser(): Collection
    {
        $trackedEntityIds = TrackedEntity::active()->pluck('id');

        return NewsItem::query()
            ->whereIn('tracked_entity_id', $trackedEntityIds)
            ->with('trackedEntity')
            ->relevant()
            ->where('fetched_at', '>=', now()->subDays(3))
            ->orderByDesc('fetched_at')
            ->limit(10)
            ->get();
    }

    protected function getSystemPrompt(LinkedInTone $tone): string
    {
        $toneInstructions = match ($tone) {
            LinkedInTone::Professional => 'Write in a formal, business-focused tone. Use industry terminology appropriately and maintain a polished, corporate voice.',
            LinkedInTone::Conversational => 'Write in a friendly, approachable tone while maintaining professionalism. Use everyday language and connect with readers on a personal level.',
            LinkedInTone::ThoughtLeadership => 'Write with authority and insight. Position the author as an industry expert sharing valuable perspectives. Use confident, declarative statements.',
            LinkedInTone::Casual => 'Write in a relaxed, personable tone. Be authentic and relatable while still providing value. It\'s okay to be a bit informal.',
        };

        return <<<EOT
You are a LinkedIn content strategist creating engaging posts for a business professional. Your posts should be optimized for LinkedIn engagement and provide genuine value to readers.

{$toneInstructions}

You MUST respond with valid JSON only. No other text or explanation.

Guidelines:
- Keep posts between 150-300 words for optimal engagement
- Start with a hook that captures attention in the first line
- Use line breaks to improve readability
- Include a clear value proposition or insight
- End with engagement (question, call-to-action, or thought-provoking statement)
- Hashtags should be relevant and specific (3-5 is optimal)
- Avoid generic advice - be specific and actionable
- Don't be overly promotional or salesy
EOT;
    }

    protected function buildNewsPostPrompt(
        NewsItem $newsItem,
        string $businessContext,
        LinkedInTone $tone,
        bool $includeHashtags,
        bool $includeCta
    ): string {
        $hashtagInstructions = $includeHashtags
            ? 'Include 3-5 relevant hashtags.'
            : 'Do not include hashtags (set hashtags to empty array).';

        $ctaInstructions = $includeCta
            ? 'Include a compelling call to action.'
            : 'Do not include a call to action (set call_to_action to null).';

        return <<<EOT
Create a LinkedIn post commenting on this news article from an industry expert's perspective.

NEWS ARTICLE:
Title: {$newsItem->title}
Snippet: {$newsItem->snippet}
Source: {$newsItem->source}
Entity: {$newsItem->trackedEntity->name}

BUSINESS CONTEXT (use this to make the commentary relevant):
{$businessContext}

INSTRUCTIONS:
- Share your perspective on this news
- Connect it to broader industry trends
- Provide actionable insights for your audience
- {$hashtagInstructions}
- {$ctaInstructions}

Respond with JSON:
{
  "title": "Short hook or headline for the post (used internally, not in the post)",
  "content": "The full LinkedIn post content with proper formatting and line breaks",
  "hashtags": ["hashtag1", "hashtag2"],
  "call_to_action": "Optional engagement question or CTA"
}
EOT;
    }

    /**
     * @param  array<string>  $topics
     */
    protected function buildThoughtLeadershipPrompt(
        string $businessContext,
        array $topics,
        LinkedInTone $tone,
        int $count,
        bool $includeHashtags,
        bool $includeCta
    ): string {
        $topicsStr = implode(', ', $topics);
        $hashtagInstructions = $includeHashtags
            ? 'Include 3-5 relevant hashtags for each post.'
            : 'Do not include hashtags (set hashtags to empty array).';

        $ctaInstructions = $includeCta
            ? 'Include a compelling call to action for each post.'
            : 'Do not include a call to action (set call_to_action to null).';

        $topicTypeMapping = <<<'EOT'
For type field, use:
- "thought_leadership" for general insights and expertise sharing
- "industry_insight" for market trends and industry analysis
- "company_update" for business achievements or announcements
EOT;

        return <<<EOT
Create {$count} LinkedIn thought leadership post(s) based on the business context below.

BUSINESS CONTEXT:
{$businessContext}

TOPICS TO COVER: {$topicsStr}

INSTRUCTIONS:
- Create posts that position the author as a knowledgeable industry professional
- Share genuine insights, lessons learned, or predictions
- Make content valuable and shareable
- Each post should have a unique angle
- {$hashtagInstructions}
- {$ctaInstructions}

{$topicTypeMapping}

Respond with JSON:
{
  "posts": [
    {
      "type": "thought_leadership|industry_insight|company_update",
      "title": "Short hook or headline for the post",
      "content": "The full LinkedIn post content with proper formatting and line breaks",
      "hashtags": ["hashtag1", "hashtag2"],
      "call_to_action": "Optional engagement question or CTA"
    }
  ]
}
EOT;
    }

    /**
     * @return array{title: string, content: string, hashtags: array, call_to_action: string|null}|null
     */
    protected function parseSinglePostResponse(string $response): ?array
    {
        $response = $this->cleanJsonResponse($response);
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('JSON parse error in LinkedIn post response', [
                'error' => json_last_error_msg(),
                'response' => substr($response, 0, 500),
            ]);

            return null;
        }

        if (! isset($data['title'], $data['content'])) {
            Log::warning('Missing required fields in LinkedIn post response', ['data' => $data]);

            return null;
        }

        return $data;
    }

    /**
     * @return array{posts: array}|null
     */
    protected function parseMultiPostResponse(string $response): ?array
    {
        $response = $this->cleanJsonResponse($response);
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('JSON parse error in LinkedIn multi-post response', [
                'error' => json_last_error_msg(),
                'response' => substr($response, 0, 500),
            ]);

            return null;
        }

        if (! isset($data['posts']) || ! is_array($data['posts'])) {
            Log::warning('Missing posts array in LinkedIn response', ['data' => $data]);

            return null;
        }

        return $data;
    }

    protected function cleanJsonResponse(string $response): string
    {
        $response = preg_replace('/^```(?:json)?\n?/m', '', $response);
        $response = preg_replace('/\n?```$/m', '', $response);

        return trim($response);
    }

    public function shouldGenerateForUser(User $user): bool
    {
        $preferences = $user->getOrCreatePreferences();

        if (! ($preferences->linkedin_posts_enabled ?? true)) {
            return false;
        }

        $frequency = LinkedInPostFrequency::tryFrom($preferences->linkedin_post_frequency ?? 'weekly') ?? LinkedInPostFrequency::Weekly;

        $lastPost = $user->linkedInPosts()->latest('generated_at')->first();

        if (! $lastPost) {
            return true;
        }

        return match ($frequency) {
            LinkedInPostFrequency::Daily => $lastPost->generated_at->lt(now()->startOfDay()),
            LinkedInPostFrequency::TwiceWeekly => $lastPost->generated_at->lt(now()->subDays(3)),
            LinkedInPostFrequency::Weekly => $lastPost->generated_at->lt(now()->subWeek()),
        };
    }
}
