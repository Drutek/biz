<?php

namespace App\Services\LLM;

class LLMResponse
{
    public function __construct(
        public string $content,
        public string $provider,
        public string $model,
        public ?int $tokensUsed = null,
    ) {}
}
