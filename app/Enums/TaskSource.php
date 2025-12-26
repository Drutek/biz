<?php

namespace App\Enums;

enum TaskSource: string
{
    case Ai = 'ai';
    case Manual = 'manual';
    case Standup = 'standup';

    public function label(): string
    {
        return match ($this) {
            self::Ai => 'AI Suggested',
            self::Manual => 'Manual',
            self::Standup => 'From Standup',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Ai => 'sparkles',
            self::Manual => 'pencil',
            self::Standup => 'clipboard-document-list',
        };
    }
}
