<?php

namespace App\Services\Embedding;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    private const API_URL = 'https://api.openai.com/v1/embeddings';

    private const MODEL = 'text-embedding-3-small';

    private const DIMENSIONS = 1536;

    private const MAX_TOKENS = 8000;

    /**
     * Generate embedding for a single text.
     *
     * @return array<float>|null
     */
    public function embed(string $text): ?array
    {
        $results = $this->embedBatch([$text]);

        return $results[0] ?? null;
    }

    /**
     * Generate embeddings for multiple texts in a single API call.
     *
     * @param  array<string>  $texts
     * @return array<array<float>>
     */
    public function embedBatch(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        $apiKey = Setting::get(Setting::KEY_OPENAI_API_KEY) ?? config('services.openai.key');

        if (! $apiKey) {
            Log::warning('OpenAI API key not configured for embeddings');

            return [];
        }

        $texts = array_map(fn ($t) => $this->truncateText($t), array_values($texts));

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post(self::API_URL, [
                    'model' => self::MODEL,
                    'input' => $texts,
                    'dimensions' => self::DIMENSIONS,
                ]);

            if (! $response->successful()) {
                Log::error('Embedding API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $data = $response->json();
            $embeddings = [];

            foreach ($data['data'] ?? [] as $item) {
                $embeddings[$item['index']] = $item['embedding'];
            }

            ksort($embeddings);

            return array_values($embeddings);

        } catch (\Exception $e) {
            Log::error('Embedding generation failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Convert an embedding array to a PostgreSQL vector string.
     *
     * @param  array<float>  $embedding
     */
    public function toVectorString(array $embedding): string
    {
        return '['.implode(',', $embedding).']';
    }

    /**
     * Get the configured embedding dimensions.
     */
    public function getDimensions(): int
    {
        return self::DIMENSIONS;
    }

    /**
     * Get the model being used for embeddings.
     */
    public function getModel(): string
    {
        return self::MODEL;
    }

    /**
     * Truncate text to fit within token limits.
     * Rough estimate: 1 token ~= 4 characters for English.
     */
    protected function truncateText(string $text): string
    {
        $maxChars = self::MAX_TOKENS * 4;

        if (strlen($text) <= $maxChars) {
            return $text;
        }

        return substr($text, 0, $maxChars);
    }
}
