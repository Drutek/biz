<?php

namespace App\Services\News;

use App\Models\NewsItem;
use App\Models\Setting;
use App\Models\TrackedEntity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SerpApiService
{
    private const BASE_URL = 'https://serpapi.com/search';

    public function fetchForEntity(TrackedEntity $entity): Collection
    {
        $apiKey = Setting::get(Setting::KEY_SERPAPI_KEY) ?? config('services.serpapi.key');

        if (! $apiKey) {
            Log::warning('SerpAPI key not configured');

            return collect();
        }

        try {
            $params = [
                'engine' => 'google_news',
                'q' => $entity->search_query,
                'api_key' => $apiKey,
            ];

            $recency = Setting::get(Setting::KEY_NEWS_RECENCY, Setting::DEFAULT_NEWS_RECENCY);
            if ($recency !== '') {
                $params['tbs'] = "qdr:{$recency}";
            }

            $response = Http::timeout(30)->get(self::BASE_URL, $params);

            if (! $response->successful()) {
                Log::error('SerpAPI request failed', [
                    'entity' => $entity->name,
                    'status' => $response->status(),
                ]);

                return collect();
            }

            $data = $response->json();
            $newsResults = $data['news_results'] ?? [];

            $items = collect();

            foreach ($newsResults as $result) {
                $existingItem = NewsItem::query()
                    ->where('tracked_entity_id', $entity->id)
                    ->where('url', $result['link'])
                    ->first();

                if ($existingItem) {
                    continue;
                }

                $newsItem = NewsItem::create([
                    'tracked_entity_id' => $entity->id,
                    'title' => $result['title'] ?? 'No title',
                    'snippet' => $result['snippet'] ?? '',
                    'url' => $result['link'],
                    'source' => $result['source']['name'] ?? 'Unknown',
                    'published_at' => $this->parseDate($result['date'] ?? null),
                    'fetched_at' => now(),
                ]);

                $items->push($newsItem);
            }

            return $items;
        } catch (\Exception $e) {
            Log::error('SerpAPI request exception', [
                'entity' => $entity->name,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    public function fetchAll(): void
    {
        $entities = TrackedEntity::active()->get();

        foreach ($entities as $entity) {
            $this->fetchForEntity($entity);
        }
    }

    private function parseDate(?string $dateString): ?\Carbon\Carbon
    {
        if (! $dateString) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }
}
