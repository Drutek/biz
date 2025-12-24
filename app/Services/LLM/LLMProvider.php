<?php

namespace App\Services\LLM;

interface LLMProvider
{
    /**
     * @param  array<array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, ?string $system = null): LLMResponse;
}
