<?php

namespace App\Services\LLM;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class OpenAIProvider implements LLMProvider
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';

    private const MODEL = 'gpt-4o';

    /**
     * @param  array<array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, ?string $system = null): LLMResponse
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
}
