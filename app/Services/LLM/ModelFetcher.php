<?php

namespace App\Services\LLM;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ModelFetcher
{
    private const ANTHROPIC_MODELS_URL = 'https://api.anthropic.com/v1/models';

    private const OPENAI_MODELS_URL = 'https://api.openai.com/v1/models';

    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Fetch available models from Anthropic API.
     *
     * @return array<array{id: string, name: string}>
     */
    public function fetchAnthropicModels(string $apiKey): array
    {
        $cacheKey = $this->getCacheKey('anthropic', $apiKey);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($apiKey) {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                ])
                ->get(self::ANTHROPIC_MODELS_URL);

            if (! $response->successful()) {
                throw new \RuntimeException('Failed to fetch Anthropic models: '.$response->body());
            }

            $data = $response->json();

            return $this->parseAnthropicModels($data['data'] ?? []);
        });
    }

    /**
     * Fetch available models from OpenAI API.
     *
     * @return array<array{id: string, name: string}>
     */
    public function fetchOpenAIModels(string $apiKey): array
    {
        $cacheKey = $this->getCacheKey('openai', $apiKey);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($apiKey) {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                ])
                ->get(self::OPENAI_MODELS_URL);

            if (! $response->successful()) {
                throw new \RuntimeException('Failed to fetch OpenAI models: '.$response->body());
            }

            $data = $response->json();

            return $this->parseOpenAIModels($data['data'] ?? []);
        });
    }

    /**
     * Clear cached models for a provider.
     */
    public function clearCache(string $provider, string $apiKey): void
    {
        $cacheKey = $this->getCacheKey($provider, $apiKey);
        Cache::forget($cacheKey);
    }

    /**
     * Generate a cache key using a hash of the API key.
     */
    private function getCacheKey(string $provider, string $apiKey): string
    {
        $keyHash = hash('sha256', $apiKey);

        return "models.{$provider}.{$keyHash}";
    }

    /**
     * Parse and filter Anthropic models.
     *
     * @param  array<array<string, mixed>>  $models
     * @return array<array{id: string, name: string}>
     */
    private function parseAnthropicModels(array $models): array
    {
        $parsed = [];

        foreach ($models as $model) {
            $id = $model['id'] ?? '';
            if (empty($id)) {
                continue;
            }

            // Filter to only Claude models (exclude any non-chat models)
            if (! str_starts_with($id, 'claude')) {
                continue;
            }

            $parsed[] = [
                'id' => $id,
                'name' => $this->formatAnthropicModelName($id),
            ];
        }

        // Sort by model name (newest/best first)
        usort($parsed, fn ($a, $b) => $this->compareModelVersions($b['id'], $a['id']));

        return $parsed;
    }

    /**
     * Parse and filter OpenAI models.
     *
     * @param  array<array<string, mixed>>  $models
     * @return array<array{id: string, name: string}>
     */
    private function parseOpenAIModels(array $models): array
    {
        $parsed = [];
        $chatModelPrefixes = ['gpt-4', 'gpt-3.5', 'o1', 'o3', 'chatgpt'];

        foreach ($models as $model) {
            $id = $model['id'] ?? '';
            if (empty($id)) {
                continue;
            }

            // Filter to only chat models (exclude embeddings, whisper, dall-e, etc.)
            $isChatModel = false;
            foreach ($chatModelPrefixes as $prefix) {
                if (str_starts_with($id, $prefix)) {
                    $isChatModel = true;
                    break;
                }
            }

            if (! $isChatModel) {
                continue;
            }

            // Exclude audio, realtime, and search variants
            if (str_contains($id, 'audio') || str_contains($id, 'realtime') || str_contains($id, 'search')) {
                continue;
            }

            $parsed[] = [
                'id' => $id,
                'name' => $this->formatOpenAIModelName($id),
            ];
        }

        // Sort by model name (newest/best first)
        usort($parsed, fn ($a, $b) => $this->compareModelVersions($b['id'], $a['id']));

        return $parsed;
    }

    /**
     * Format Anthropic model ID into a human-readable name.
     */
    private function formatAnthropicModelName(string $id): string
    {
        // claude-sonnet-4-20250514 -> Claude Sonnet 4
        // claude-3-5-sonnet-20241022 -> Claude 3.5 Sonnet
        $name = str_replace('claude-', 'Claude ', $id);

        // Remove date suffix
        $name = preg_replace('/-\d{8}$/', '', $name);

        // Handle dashes and formatting
        $name = str_replace('-', ' ', $name);
        $name = ucwords($name);

        return $name;
    }

    /**
     * Format OpenAI model ID into a human-readable name.
     */
    private function formatOpenAIModelName(string $id): string
    {
        // gpt-4o -> GPT-4o
        // gpt-4o-mini -> GPT-4o Mini
        $name = strtoupper(substr($id, 0, 3)).substr($id, 3);
        $name = str_replace('-', ' ', $name);
        $name = ucwords($name);

        // Fix "Gpt" -> "GPT"
        $name = str_replace('Gpt', 'GPT', $name);

        return $name;
    }

    /**
     * Compare model versions for sorting (newer/better models first).
     */
    private function compareModelVersions(string $a, string $b): int
    {
        // Extract version info for comparison
        // Prefer models with higher numbers (4 > 3.5 > 3)
        // Prefer opus > sonnet > haiku for Claude
        $priorityA = $this->getModelPriority($a);
        $priorityB = $this->getModelPriority($b);

        if ($priorityA !== $priorityB) {
            return $priorityA <=> $priorityB;
        }

        return strcmp($a, $b);
    }

    /**
     * Get a numeric priority for a model (higher is better).
     */
    private function getModelPriority(string $id): int
    {
        $priority = 0;

        // Claude model tier priorities
        if (str_contains($id, 'opus')) {
            $priority += 300;
        } elseif (str_contains($id, 'sonnet')) {
            $priority += 200;
        } elseif (str_contains($id, 'haiku')) {
            $priority += 100;
        }

        // OpenAI model priorities
        if (str_contains($id, 'o1') || str_contains($id, 'o3')) {
            $priority += 400;
        } elseif (str_contains($id, 'gpt-4o')) {
            $priority += 350;
        } elseif (str_contains($id, 'gpt-4')) {
            $priority += 300;
        } elseif (str_contains($id, 'gpt-3.5')) {
            $priority += 100;
        }

        // Version number boost
        if (preg_match('/(\d+)/', $id, $matches)) {
            $priority += (int) $matches[1] * 10;
        }

        // Mini/preview deduction
        if (str_contains($id, 'mini')) {
            $priority -= 50;
        }
        if (str_contains($id, 'preview')) {
            $priority -= 25;
        }

        return $priority;
    }
}
