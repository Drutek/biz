<?php

namespace App\Services\LLM;

use App\Services\LLM\Tools\Tool;

interface LLMProvider
{
    /**
     * @param  array<array{role: string, content: string|array}>  $messages
     * @param  Tool[]  $tools
     */
    public function chat(array $messages, ?string $system = null, array $tools = []): LLMResponse;

    /**
     * Stream a chat response, calling the callback with each text chunk.
     * Handles tool calls automatically and continues until a final response.
     *
     * @param  array<array{role: string, content: string|array}>  $messages
     * @param  callable(string): void  $onChunk
     * @param  Tool[]  $tools
     */
    public function chatStream(array $messages, callable $onChunk, ?string $system = null, array $tools = []): LLMResponse;
}
