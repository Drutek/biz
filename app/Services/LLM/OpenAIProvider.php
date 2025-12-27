<?php

namespace App\Services\LLM;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class OpenAIProvider implements LLMProvider
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';

    private const MODEL = 'gpt-4o';

    /**
     * @param  array<array{role: string, content: string|array}>  $messages
     * @param  Tool[]  $tools
     */
    public function chat(array $messages, ?string $system = null, array $tools = []): LLMResponse
    {
        $apiKey = Setting::get(Setting::KEY_OPENAI_API_KEY) ?? config('services.openai.key');

        if (! $apiKey) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        $formattedMessages = [];

        if ($system) {
            $formattedMessages[] = [
                'role' => 'system',
                'content' => $system,
            ];
        }

        foreach ($messages as $message) {
            $formattedMessages[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        $response = Http::timeout(120)
            ->withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post(self::API_URL, [
                'model' => self::MODEL,
                'messages' => $formattedMessages,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI API request failed: '.$response->body());
        }

        $data = $response->json();

        return new LLMResponse(
            content: $data['choices'][0]['message']['content'] ?? '',
            provider: 'openai',
            model: self::MODEL,
            tokensUsed: $data['usage']['total_tokens'] ?? null,
        );
    }

    /**
     * @param  array<array{role: string, content: string|array}>  $messages
     * @param  callable(string): void  $onChunk
     * @param  Tool[]  $tools
     */
    public function chatStream(array $messages, callable $onChunk, ?string $system = null, array $tools = []): LLMResponse
    {
        $apiKey = Setting::get(Setting::KEY_OPENAI_API_KEY) ?? config('services.openai.key');

        if (! $apiKey) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        $formattedMessages = [];

        if ($system) {
            $formattedMessages[] = [
                'role' => 'system',
                'content' => $system,
            ];
        }

        foreach ($messages as $message) {
            $formattedMessages[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        $payload = [
            'model' => self::MODEL,
            'messages' => $formattedMessages,
            'stream' => true,
            'stream_options' => ['include_usage' => true],
        ];

        $fullContent = '';
        $tokensUsed = null;
        $buffer = '';

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$fullContent, &$tokensUsed, &$buffer, $onChunk) {
                $buffer .= $data;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);

                    if ($line === '' || $line === 'data: [DONE]') {
                        continue;
                    }

                    if (str_starts_with($line, 'data: ')) {
                        $json = substr($line, 6);
                        $event = json_decode($json, true);

                        if ($event) {
                            if (isset($event['choices'][0]['delta']['content'])) {
                                $text = $event['choices'][0]['delta']['content'];
                                $fullContent .= $text;
                                $onChunk($text);
                            }

                            if (isset($event['usage']['total_tokens'])) {
                                $tokensUsed = $event['usage']['total_tokens'];
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
            throw new \RuntimeException('OpenAI streaming API request failed: '.$error);
        }

        return new LLMResponse(
            content: $fullContent,
            provider: 'openai',
            model: self::MODEL,
            tokensUsed: $tokensUsed,
        );
    }
}
