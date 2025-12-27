<?php

use App\Services\LLM\ModelFetcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    Http::preventStrayRequests();
});

describe('ModelFetcher', function () {
    describe('Anthropic models', function () {
        it('fetches and parses Anthropic models', function () {
            Http::fake([
                'https://api.anthropic.com/v1/models' => Http::response([
                    'data' => [
                        ['id' => 'claude-sonnet-4-20250514', 'type' => 'model'],
                        ['id' => 'claude-3-5-sonnet-20241022', 'type' => 'model'],
                        ['id' => 'claude-3-opus-20240229', 'type' => 'model'],
                        ['id' => 'claude-3-haiku-20240307', 'type' => 'model'],
                    ],
                ], 200),
            ]);

            $fetcher = new ModelFetcher;
            $models = $fetcher->fetchAnthropicModels('test-api-key');

            expect($models)->toBeArray();
            expect($models)->toHaveCount(4);
            expect($models[0])->toHaveKeys(['id', 'name']);
        });

        it('filters out non-Claude models', function () {
            Http::fake([
                'https://api.anthropic.com/v1/models' => Http::response([
                    'data' => [
                        ['id' => 'claude-sonnet-4-20250514', 'type' => 'model'],
                        ['id' => 'some-other-model', 'type' => 'model'],
                        ['id' => 'embedding-model', 'type' => 'model'],
                    ],
                ], 200),
            ]);

            $fetcher = new ModelFetcher;
            $models = $fetcher->fetchAnthropicModels('test-api-key');

            expect($models)->toHaveCount(1);
            expect($models[0]['id'])->toBe('claude-sonnet-4-20250514');
        });

        it('caches Anthropic models', function () {
            Http::fake([
                'https://api.anthropic.com/v1/models' => Http::response([
                    'data' => [
                        ['id' => 'claude-sonnet-4-20250514', 'type' => 'model'],
                    ],
                ], 200),
            ]);

            $fetcher = new ModelFetcher;

            // First call
            $fetcher->fetchAnthropicModels('test-api-key');

            // Second call should use cache
            $fetcher->fetchAnthropicModels('test-api-key');

            Http::assertSentCount(1);
        });

        it('throws exception on API failure', function () {
            Http::fake([
                'https://api.anthropic.com/v1/models' => Http::response(['error' => 'Invalid API key'], 401),
            ]);

            $fetcher = new ModelFetcher;

            expect(fn () => $fetcher->fetchAnthropicModels('invalid-key'))
                ->toThrow(\RuntimeException::class, 'Failed to fetch Anthropic models');
        });
    });

    describe('OpenAI models', function () {
        it('fetches and parses OpenAI models', function () {
            Http::fake([
                'https://api.openai.com/v1/models' => Http::response([
                    'data' => [
                        ['id' => 'gpt-4o', 'object' => 'model'],
                        ['id' => 'gpt-4o-mini', 'object' => 'model'],
                        ['id' => 'gpt-4-turbo', 'object' => 'model'],
                        ['id' => 'gpt-3.5-turbo', 'object' => 'model'],
                    ],
                ], 200),
            ]);

            $fetcher = new ModelFetcher;
            $models = $fetcher->fetchOpenAIModels('test-api-key');

            expect($models)->toBeArray();
            expect($models)->toHaveCount(4);
            expect($models[0])->toHaveKeys(['id', 'name']);
        });

        it('filters out non-chat models', function () {
            Http::fake([
                'https://api.openai.com/v1/models' => Http::response([
                    'data' => [
                        ['id' => 'gpt-4o', 'object' => 'model'],
                        ['id' => 'text-embedding-ada-002', 'object' => 'model'],
                        ['id' => 'whisper-1', 'object' => 'model'],
                        ['id' => 'dall-e-3', 'object' => 'model'],
                        ['id' => 'tts-1', 'object' => 'model'],
                    ],
                ], 200),
            ]);

            $fetcher = new ModelFetcher;
            $models = $fetcher->fetchOpenAIModels('test-api-key');

            expect($models)->toHaveCount(1);
            expect($models[0]['id'])->toBe('gpt-4o');
        });

        it('filters out audio and realtime models', function () {
            Http::fake([
                'https://api.openai.com/v1/models' => Http::response([
                    'data' => [
                        ['id' => 'gpt-4o', 'object' => 'model'],
                        ['id' => 'gpt-4o-audio-preview', 'object' => 'model'],
                        ['id' => 'gpt-4o-realtime-preview', 'object' => 'model'],
                    ],
                ], 200),
            ]);

            $fetcher = new ModelFetcher;
            $models = $fetcher->fetchOpenAIModels('test-api-key');

            expect($models)->toHaveCount(1);
            expect($models[0]['id'])->toBe('gpt-4o');
        });

        it('caches OpenAI models', function () {
            Http::fake([
                'https://api.openai.com/v1/models' => Http::response([
                    'data' => [
                        ['id' => 'gpt-4o', 'object' => 'model'],
                    ],
                ], 200),
            ]);

            $fetcher = new ModelFetcher;

            // First call
            $fetcher->fetchOpenAIModels('test-api-key');

            // Second call should use cache
            $fetcher->fetchOpenAIModels('test-api-key');

            Http::assertSentCount(1);
        });

        it('throws exception on API failure', function () {
            Http::fake([
                'https://api.openai.com/v1/models' => Http::response(['error' => 'Invalid API key'], 401),
            ]);

            $fetcher = new ModelFetcher;

            expect(fn () => $fetcher->fetchOpenAIModels('invalid-key'))
                ->toThrow(\RuntimeException::class, 'Failed to fetch OpenAI models');
        });
    });

    describe('cache management', function () {
        it('clears cache for specific provider and key', function () {
            Http::fake([
                'https://api.anthropic.com/v1/models' => Http::response([
                    'data' => [['id' => 'claude-sonnet-4-20250514']],
                ], 200),
            ]);

            $fetcher = new ModelFetcher;

            // First call
            $fetcher->fetchAnthropicModels('test-api-key');

            // Clear cache
            $fetcher->clearCache('anthropic', 'test-api-key');

            // Next call should hit API again
            $fetcher->fetchAnthropicModels('test-api-key');

            Http::assertSentCount(2);
        });

        it('uses different cache keys for different API keys', function () {
            Http::fake([
                'https://api.anthropic.com/v1/models' => Http::response([
                    'data' => [['id' => 'claude-sonnet-4-20250514']],
                ], 200),
            ]);

            $fetcher = new ModelFetcher;

            // Different API keys should hit the API separately
            $fetcher->fetchAnthropicModels('key-1');
            $fetcher->fetchAnthropicModels('key-2');

            Http::assertSentCount(2);
        });
    });
});
