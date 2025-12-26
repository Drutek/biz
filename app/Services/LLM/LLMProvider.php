<?php

namespace App\Services\LLM;

interface LLMProvider
{
    /**
     * @param  array<array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, ?string $system = null): LLMResponse;

    /**
     * Stream a chat response, calling the callback with each text chunk.
     *
     * @param  array<array{role: string, content: string}>  $messages
     * @param  callable(string): void  $onChunk
     */
    public function chatStream(array $messages, callable $onChunk, ?string $system = null): LLMResponse;
}
