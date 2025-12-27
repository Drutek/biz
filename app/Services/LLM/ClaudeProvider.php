<?php

namespace App\Services\LLM;

use App\Models\Setting;
use App\Services\LLM\Tools\Tool;
use Illuminate\Support\Facades\Http;

class ClaudeProvider implements LLMProvider
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    private const MODEL = 'claude-sonnet-4-20250514';

    private const MAX_TOKENS = 4096;

    private const MAX_TOOL_ITERATIONS = 10;

    /**
     * @param  array<array{role: string, content: string|array}>  $messages
     * @param  Tool[]  $tools
     */
    public function chat(array $messages, ?string $system = null, array $tools = []): LLMResponse
    {
        $apiKey = $this->getApiKey();
        $toolsById = $this->indexToolsByName($tools);

        $totalTokens = 0;
        $iterations = 0;

        while ($iterations < self::MAX_TOOL_ITERATIONS) {
            $iterations++;

            $payload = $this->buildPayload($messages, $system, $tools);
            $response = $this->makeRequest($apiKey, $payload);
            $data = $response->json();

            $totalTokens += ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0);

            // Check if the model wants to use tools
            if (($data['stop_reason'] ?? '') === 'tool_use') {
                $messages = $this->handleToolUse($messages, $data, $toolsById);

                continue;
            }

            // Extract text content
            $content = $this->extractTextContent($data);

            return new LLMResponse(
                content: $content,
                provider: 'claude',
                model: self::MODEL,
                tokensUsed: $totalTokens,
            );
        }

        throw new \RuntimeException('Max tool iterations exceeded');
    }

    /**
     * @param  array<array{role: string, content: string|array}>  $messages
     * @param  callable(string): void  $onChunk
     * @param  Tool[]  $tools
     */
    public function chatStream(array $messages, callable $onChunk, ?string $system = null, array $tools = []): LLMResponse
    {
        $apiKey = $this->getApiKey();
        $toolsById = $this->indexToolsByName($tools);

        $totalInputTokens = 0;
        $totalOutputTokens = 0;
        $iterations = 0;
        $fullContent = '';

        while ($iterations < self::MAX_TOOL_ITERATIONS) {
            $iterations++;

            $result = $this->streamRequest($apiKey, $messages, $system, $tools, $onChunk);

            $totalInputTokens += $result['input_tokens'];
            $totalOutputTokens += $result['output_tokens'];

            // Check if the model wants to use tools
            if ($result['stop_reason'] === 'tool_use') {
                // Notify user that tools are being used
                $toolNames = collect($result['tool_uses'])->pluck('name')->join(', ');
                $onChunk("\n\n*[Looking up: {$toolNames}...]*\n\n");

                $messages = $this->handleToolUseFromStream($messages, $result, $toolsById);

                continue;
            }

            $fullContent .= $result['content'];

            return new LLMResponse(
                content: $fullContent,
                provider: 'claude',
                model: self::MODEL,
                tokensUsed: $totalInputTokens + $totalOutputTokens,
            );
        }

        throw new \RuntimeException('Max tool iterations exceeded');
    }

    private function getApiKey(): string
    {
        $apiKey = Setting::get(Setting::KEY_ANTHROPIC_API_KEY) ?? config('services.anthropic.key');

        if (! $apiKey) {
            throw new \RuntimeException('Anthropic API key not configured');
        }

        return $apiKey;
    }

    /**
     * @param  Tool[]  $tools
     * @return array<string, Tool>
     */
    private function indexToolsByName(array $tools): array
    {
        $indexed = [];
        foreach ($tools as $tool) {
            $indexed[$tool->name()] = $tool;
        }

        return $indexed;
    }

    /**
     * @param  array<array{role: string, content: string|array}>  $messages
     * @param  Tool[]  $tools
     * @return array<string, mixed>
     */
    private function buildPayload(array $messages, ?string $system, array $tools): array
    {
        $payload = [
            'model' => self::MODEL,
            'max_tokens' => self::MAX_TOKENS,
            'messages' => $messages,
        ];

        if ($system) {
            $payload['system'] = $system;
        }

        if (! empty($tools)) {
            $payload['tools'] = array_map(fn (Tool $t) => $t->toClaudeFormat(), $tools);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function makeRequest(string $apiKey, array $payload): \Illuminate\Http\Client\Response
    {
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

        return $response;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractTextContent(array $data): string
    {
        $content = '';
        foreach ($data['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $content .= $block['text'];
            }
        }

        return $content;
    }

    /**
     * @param  array<array{role: string, content: string|array}>  $messages
     * @param  array<string, mixed>  $data
     * @param  array<string, Tool>  $toolsById
     * @return array<array{role: string, content: string|array}>
     */
    private function handleToolUse(array $messages, array $data, array $toolsById): array
    {
        // Add assistant message with tool use
        $messages[] = [
            'role' => 'assistant',
            'content' => $data['content'],
        ];

        // Execute each tool and collect results
        $toolResults = [];
        foreach ($data['content'] as $block) {
            if ($block['type'] === 'tool_use') {
                $toolName = $block['name'];
                $toolInput = $block['input'] ?? [];
                $toolUseId = $block['id'];

                $result = $this->executeTool($toolName, $toolInput, $toolsById);

                $toolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $toolUseId,
                    'content' => $result,
                ];
            }
        }

        // Add user message with tool results
        $messages[] = [
            'role' => 'user',
            'content' => $toolResults,
        ];

        return $messages;
    }

    /**
     * @param  array<array{role: string, content: string|array}>  $messages
     * @param  array<string, mixed>  $result
     * @param  array<string, Tool>  $toolsById
     * @return array<array{role: string, content: string|array}>
     */
    private function handleToolUseFromStream(array $messages, array $result, array $toolsById): array
    {
        // Build assistant content blocks
        $assistantContent = [];

        if (! empty($result['content'])) {
            $assistantContent[] = [
                'type' => 'text',
                'text' => $result['content'],
            ];
        }

        foreach ($result['tool_uses'] as $toolUse) {
            $assistantContent[] = [
                'type' => 'tool_use',
                'id' => $toolUse['id'],
                'name' => $toolUse['name'],
                'input' => $toolUse['input'],
            ];
        }

        $messages[] = [
            'role' => 'assistant',
            'content' => $assistantContent,
        ];

        // Execute tools and collect results
        $toolResults = [];
        foreach ($result['tool_uses'] as $toolUse) {
            // Convert stdClass back to array for tool execution
            $input = $toolUse['input'] instanceof \stdClass ? (array) $toolUse['input'] : $toolUse['input'];
            $toolResult = $this->executeTool($toolUse['name'], $input, $toolsById);

            $toolResults[] = [
                'type' => 'tool_result',
                'tool_use_id' => $toolUse['id'],
                'content' => $toolResult,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $toolResults,
        ];

        return $messages;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, Tool>  $toolsById
     */
    private function executeTool(string $toolName, array $input, array $toolsById): string
    {
        if (! isset($toolsById[$toolName])) {
            return "Error: Unknown tool '{$toolName}'";
        }

        try {
            return $toolsById[$toolName]->execute($input);
        } catch (\Throwable $e) {
            return "Error executing tool: {$e->getMessage()}";
        }
    }

    /**
     * @param  array<array{role: string, content: string|array}>  $messages
     * @param  Tool[]  $tools
     * @param  callable(string): void  $onChunk
     * @return array{content: string, stop_reason: string, tool_uses: array, input_tokens: int, output_tokens: int}
     */
    private function streamRequest(string $apiKey, array $messages, ?string $system, array $tools, callable $onChunk): array
    {
        $payload = $this->buildPayload($messages, $system, $tools);
        $payload['stream'] = true;

        $content = '';
        $inputTokens = 0;
        $outputTokens = 0;
        $stopReason = 'end_turn';
        $toolUses = [];
        $currentToolUse = null;
        $toolInputJson = '';
        $buffer = '';
        $responseBody = '';

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
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$content, &$inputTokens, &$outputTokens, &$stopReason, &$toolUses, &$currentToolUse, &$toolInputJson, &$buffer, &$responseBody, $onChunk) {
                $buffer .= $data;
                $responseBody .= $data;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);

                    if (str_starts_with($line, 'data: ')) {
                        $json = substr($line, 6);
                        $event = json_decode($json, true);

                        if ($event) {
                            $this->processStreamEvent(
                                $event,
                                $content,
                                $inputTokens,
                                $outputTokens,
                                $stopReason,
                                $toolUses,
                                $currentToolUse,
                                $toolInputJson,
                                $onChunk
                            );
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
            $errorDetail = $error ?: $responseBody;
            throw new \RuntimeException("Claude streaming API request failed (HTTP {$httpCode}): {$errorDetail}");
        }

        return [
            'content' => $content,
            'stop_reason' => $stopReason,
            'tool_uses' => $toolUses,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
        ];
    }

    /**
     * @param  array<string, mixed>  $event
     * @param  array<array{id: string, name: string, input: array}>  $toolUses
     * @param  array{id: string, name: string}|null  $currentToolUse
     * @param  callable(string): void  $onChunk
     */
    private function processStreamEvent(
        array $event,
        string &$content,
        int &$inputTokens,
        int &$outputTokens,
        string &$stopReason,
        array &$toolUses,
        ?array &$currentToolUse,
        string &$toolInputJson,
        callable $onChunk
    ): void {
        $type = $event['type'] ?? '';

        switch ($type) {
            case 'message_start':
                $inputTokens = $event['message']['usage']['input_tokens'] ?? 0;
                break;

            case 'content_block_start':
                $block = $event['content_block'] ?? [];
                if (($block['type'] ?? '') === 'tool_use') {
                    $currentToolUse = [
                        'id' => $block['id'],
                        'name' => $block['name'],
                    ];
                    $toolInputJson = '';
                }
                break;

            case 'content_block_delta':
                $delta = $event['delta'] ?? [];
                if (($delta['type'] ?? '') === 'text_delta' && isset($delta['text'])) {
                    $text = $delta['text'];
                    $content .= $text;
                    $onChunk($text);
                } elseif (($delta['type'] ?? '') === 'input_json_delta' && isset($delta['partial_json'])) {
                    $toolInputJson .= $delta['partial_json'];
                }
                break;

            case 'content_block_stop':
                if ($currentToolUse !== null) {
                    $parsedInput = json_decode($toolInputJson, true);
                    // Use stdClass for empty input so it serializes as {} not []
                    $toolUses[] = [
                        'id' => $currentToolUse['id'],
                        'name' => $currentToolUse['name'],
                        'input' => ! empty($parsedInput) ? $parsedInput : new \stdClass,
                    ];
                    $currentToolUse = null;
                    $toolInputJson = '';
                }
                break;

            case 'message_delta':
                $stopReason = $event['delta']['stop_reason'] ?? $stopReason;
                $outputTokens = $event['usage']['output_tokens'] ?? $outputTokens;
                break;

            case 'error':
                $errorMessage = $event['error']['message'] ?? 'Unknown error';
                throw new \RuntimeException("Claude API error: {$errorMessage}");
        }
    }
}
