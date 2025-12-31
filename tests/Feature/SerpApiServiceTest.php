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

    it('includes negative terms in the search query', function () {
        Http::fake([
            'serpapi.com/*' => Http::response([
                'news_results' => [],
            ]),
        ]);

        $entity = TrackedEntity::factory()->withNegativeTerms('stock price, earnings report')->create([
            'search_query' => 'Acme Corp news',
            'is_active' => true,
        ]);

        $service = new SerpApiService;
        $service->fetchForEntity($entity);

        Http::assertSent(function ($request) {
            $url = urldecode($request->url());

            return str_contains($url, 'Acme Corp news')
                && str_contains($url, '-"stock price"')
                && str_contains($url, '-"earnings report"');
        });
    });

    it('handles entities without negative terms', function () {
        Http::fake([
            'serpapi.com/*' => Http::response([
                'news_results' => [],
            ]),
        ]);

        $entity = TrackedEntity::factory()->create([
            'search_query' => 'test query',
            'negative_terms' => null,
            'is_active' => true,
        ]);

        $service = new SerpApiService;
        $service->fetchForEntity($entity);

        Http::assertSent(function ($request) {
            $url = urldecode($request->url());

            return str_contains($url, 'q=test query')
                && ! str_contains($url, '-"');
        });
    });
});

describe('TrackedEntity getEffectiveSearchQuery', function () {
    it('returns search query unchanged when no negative terms', function () {
        $entity = TrackedEntity::factory()->create([
            'search_query' => 'Acme Corp news',
            'negative_terms' => null,
        ]);

        expect($entity->getEffectiveSearchQuery())->toBe('Acme Corp news');
    });

    it('returns search query unchanged when negative terms is empty', function () {
        $entity = TrackedEntity::factory()->create([
            'search_query' => 'Acme Corp news',
            'negative_terms' => '',
        ]);

        expect($entity->getEffectiveSearchQuery())->toBe('Acme Corp news');
    });

    it('appends single negative term to search query', function () {
        $entity = TrackedEntity::factory()->create([
            'search_query' => 'Acme Corp news',
            'negative_terms' => 'stock price',
        ]);

        expect($entity->getEffectiveSearchQuery())->toBe('Acme Corp news -"stock price"');
    });

    it('appends multiple negative terms to search query', function () {
        $entity = TrackedEntity::factory()->create([
            'search_query' => 'Acme Corp news',
            'negative_terms' => 'stock price, earnings report, SEC filing',
        ]);

        expect($entity->getEffectiveSearchQuery())
            ->toBe('Acme Corp news -"stock price" -"earnings report" -"SEC filing"');
    });

    it('trims whitespace from negative terms', function () {
        $entity = TrackedEntity::factory()->create([
            'search_query' => 'Acme Corp news',
            'negative_terms' => '  stock price  ,  earnings  ',
        ]);

        expect($entity->getEffectiveSearchQuery())
            ->toBe('Acme Corp news -"stock price" -"earnings"');
    });

    it('ignores empty terms in comma-separated list', function () {
        $entity = TrackedEntity::factory()->create([
            'search_query' => 'Acme Corp news',
            'negative_terms' => 'stock price,,earnings report,',
        ]);

        expect($entity->getEffectiveSearchQuery())
            ->toBe('Acme Corp news -"stock price" -"earnings report"');
    });
});
