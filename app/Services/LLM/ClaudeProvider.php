<?php

namespace App\Services\LLM;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class ClaudeProvider implements LLMProvider
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    private const MODEL = 'claude-sonnet-4-20250514';

    private const MAX_TOKENS = 4096;

    /**
     * @param  array<array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, ?string $system = null): LLMResponse
    {
        $apiKey = Setting::get(Setting::KEY_ANTHROPIC_API_KEY) ?? config('services.anthropic.key');

        if (! $apiKey) {
            throw new \RuntimeException('Anthropic API key not configured');
        }

        $payload = [
            'model' => self::MODEL,
            'max_tokens' => self::MAX_TOKENS,
            'messages' => $messages,
        ];

        if ($system) {
            $payload['system'] = $system;
        }

        $response = Http::timeout(120)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post(self::API_URL, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Claude API request failed: '.$response->body());
        }

        $data = $response->json();

        $content = '';
        foreach ($data['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $content .= $block['text'];
            }
        }

        return new LLMResponse(
            content: $content,
            provider: 'claude',
            model: self::MODEL,
            tokensUsed: ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
        );
    }
}
