<?php

namespace App\Enums;

enum LLMProvider: string
{
    case Claude = 'claude';
    case OpenAI = 'openai';

    public function label(): string
    {
        return match ($this) {
            self::Claude => 'Claude (Anthropic)',
            self::OpenAI => 'OpenAI',
        };
    }
}
