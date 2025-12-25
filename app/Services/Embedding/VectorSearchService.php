<?php

namespace App\Services\Embedding;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VectorSearchService
{
    public function __construct(
        protected EmbeddingService $embeddingService
    ) {}

    /**
     * Semantic search across advisory messages for a user.
     *
     * @return Collection<int, object>
     */
    public function searchAdvisoryMessages(
        int $userId,
        string $query,
        int $limit = 20,
        float $threshold = 0.3
    ): Collection {
        $queryEmbedding = $this->embeddingService->embed($query);

        if (! $queryEmbedding) {
            return collect();
        }

        $vectorString = $this->embeddingService->toVectorString($queryEmbedding);

        return DB::table('advisory_messages')
            ->join('advisory_threads', 'advisory_messages.advisory_thread_id', '=', 'advisory_threads.id')
            ->where('advisory_threads.user_id', $userId)
            ->whereNotNull('advisory_messages.embedding')
            ->selectRaw(
                'advisory_messages.id,
                 advisory_messages.content,
                 advisory_messages.role,
                 advisory_messages.created_at,
                 advisory_messages.advisory_thread_id,
                 advisory_threads.title as thread_title,
                 (advisory_messages.embedding <=> ?) as distance',
                [$vectorString]
            )
            ->having('distance', '<', $threshold)
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    /**
     * Find relevant news items semantically related to a query.
     *
     * @return Collection<int, object>
     */
    public function searchNewsItems(
        string $query,
        int $limit = 10,
        float $threshold = 0.3,
        ?int $daysBack = 30
    ): Collection {
        $queryEmbedding = $this->embeddingService->embed($query);

        if (! $queryEmbedding) {
            return collect();
        }

        $vectorString = $this->embeddingService->toVectorString($queryEmbedding);

        return DB::table('news_items')
            ->whereNotNull('embedding')
            ->when($daysBack, fn ($q) => $q->where('fetched_at', '>=', now()->subDays($daysBack)))
            ->selectRaw(
                'id, title, snippet, url, source, tracked_entity_id, published_at, fetched_at, (embedding <=> ?) as distance',
                [$vectorString]
            )
            ->having('distance', '<', $threshold)
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    /**
     * Find similar business events for pattern detection.
     *
     * @return Collection<int, object>
     */
    public function findSimilarEvents(
        int $userId,
        string $eventDescription,
        int $limit = 5,
        float $threshold = 0.4,
        ?int $excludeEventId = null
    ): Collection {
        $queryEmbedding = $this->embeddingService->embed($eventDescription);

        if (! $queryEmbedding) {
            return collect();
        }

        $vectorString = $this->embeddingService->toVectorString($queryEmbedding);

        return DB::table('business_events')
            ->where('user_id', $userId)
            ->whereNotNull('embedding')
            ->when($excludeEventId, fn ($q) => $q->where('id', '!=', $excludeEventId))
            ->selectRaw(
                'id, title, description, event_type, category, significance, occurred_at, metadata, (embedding <=> ?) as distance',
                [$vectorString]
            )
            ->having('distance', '<', $threshold)
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    /**
     * Find similar proactive insights to avoid duplicates.
     *
     * @return Collection<int, object>
     */
    public function findSimilarInsights(
        int $userId,
        string $content,
        int $limit = 5,
        float $threshold = 0.2,
        ?int $daysBack = 7
    ): Collection {
        $queryEmbedding = $this->embeddingService->embed($content);

        if (! $queryEmbedding) {
            return collect();
        }

        $vectorString = $this->embeddingService->toVectorString($queryEmbedding);

        return DB::table('proactive_insights')
            ->where('user_id', $userId)
            ->whereNotNull('embedding')
            ->when($daysBack, fn ($q) => $q->where('created_at', '>=', now()->subDays($daysBack)))
            ->selectRaw(
                'id, title, content, insight_type, priority, created_at, (embedding <=> ?) as distance',
                [$vectorString]
            )
            ->having('distance', '<', $threshold)
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    /**
     * Get relevant context from multiple sources for RAG.
     *
     * @return array{messages: Collection, events: Collection, insights: Collection}
     */
    public function getRelevantContext(
        int $userId,
        string $query,
        int $messagesLimit = 5,
        int $eventsLimit = 3,
        int $insightsLimit = 3
    ): array {
        return [
            'messages' => $this->searchAdvisoryMessages($userId, $query, $messagesLimit),
            'events' => $this->findSimilarEvents($userId, $query, $eventsLimit),
            'insights' => $this->findSimilarInsights($userId, $query, $insightsLimit, daysBack: 30),
        ];
    }
}
