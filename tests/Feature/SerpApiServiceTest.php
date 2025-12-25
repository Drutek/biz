<?php

use App\Models\Setting;
use App\Models\TrackedEntity;
use App\Services\News\SerpApiService;
use Illuminate\Support\Facades\Http;

describe('SerpApiService', function () {
    beforeEach(function () {
        Setting::set(Setting::KEY_SERPAPI_KEY, 'test-api-key');
    });

    it('includes tbs parameter when news recency is set', function () {
        Setting::set(Setting::KEY_NEWS_RECENCY, 'd');

        Http::fake([
            'serpapi.com/*' => Http::response([
                'news_results' => [],
            ]),
        ]);

        $entity = TrackedEntity::factory()->create([
            'search_query' => 'test query',
            'is_active' => true,
        ]);

        $service = new SerpApiService;
        $service->fetchForEntity($entity);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'tbs=qdr%3Ad');
        });
    });

    it('includes tbs parameter with week recency by default', function () {
        Http::fake([
            'serpapi.com/*' => Http::response([
                'news_results' => [],
            ]),
        ]);

        $entity = TrackedEntity::factory()->create([
            'search_query' => 'test query',
            'is_active' => true,
        ]);

        $service = new SerpApiService;
        $service->fetchForEntity($entity);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'tbs=qdr%3Aw');
        });
    });

    it('omits tbs parameter when news recency is empty', function () {
        Setting::set(Setting::KEY_NEWS_RECENCY, '');

        Http::fake([
            'serpapi.com/*' => Http::response([
                'news_results' => [],
            ]),
        ]);

        $entity = TrackedEntity::factory()->create([
            'search_query' => 'test query',
            'is_active' => true,
        ]);

        $service = new SerpApiService;
        $service->fetchForEntity($entity);

        Http::assertSent(function ($request) {
            return ! str_contains($request->url(), 'tbs=');
        });
    });
});
