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

    /**
     * @param  array<array{role: string, content: string}>  $messages
     * @param  callable(string): void  $onChunk
     */
    public function chatStream(array $messages, callable $onChunk, ?string $system = null): LLMResponse
    {
        $apiKey = Setting::get(Setting::KEY_ANTHROPIC_API_KEY) ?? config('services.anthropic.key');

        if (! $apiKey) {
            throw new \RuntimeException('Anthropic API key not configured');
        }

        $payload = [
            'model' => self::MODEL,
            'max_tokens' => self::MAX_TOKENS,
            'messages' => $messages,
            'stream' => true,
        ];

        if ($system) {
            $payload['system'] = $system;
        }

        $fullContent = '';
        $inputTokens = 0;
        $outputTokens = 0;
        $buffer = '';

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'x-api-key: '.$apiKey,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$fullContent, &$inputTokens, &$outputTokens, &$buffer, $onChunk) {
                $buffer .= $data;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);

                    if (str_starts_with($line, 'data: ')) {
                        $json = substr($line, 6);
                        $event = json_decode($json, true);

                        if ($event) {
                            if ($event['type'] === 'content_block_delta' && isset($event['delta']['text'])) {
                                $text = $event['delta']['text'];
                                $fullContent .= $text;
                                $onChunk($text);
                            } elseif ($event['type'] === 'message_start' && isset($event['message']['usage'])) {
                                $inputTokens = $event['message']['usage']['input_tokens'] ?? 0;
                            } elseif ($event['type'] === 'message_delta' && isset($event['usage'])) {
                                $outputTokens = $event['usage']['output_tokens'] ?? 0;
                            }
                        }
                    }
                }

                return strlen($data);
            },
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false || $httpCode !== 200) {
            throw new \RuntimeException('Claude streaming API request failed: '.$error);
        }

        return new LLMResponse(
            content: $fullContent,
            provider: 'claude',
            model: self::MODEL,
            tokensUsed: $inputTokens + $outputTokens,
        );
    }
}
