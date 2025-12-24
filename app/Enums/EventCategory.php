<?php

namespace App\Enums;

enum EventCategory: string
{
    case Financial = 'financial';
    case Market = 'market';
    case Advisory = 'advisory';
    case Milestone = 'milestone';

    public function label(): string
    {
        return match ($this) {
            self::Financial => 'Financial',
            self::Market => 'Market',
            self::Advisory => 'Advisory',
            self::Milestone => 'Milestone',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Financial => 'green',
            self::Market => 'purple',
            self::Advisory => 'indigo',
            self::Milestone => 'yellow',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Financial => 'currency-dollar',
            self::Market => 'globe-alt',
            self::Advisory => 'chat-bubble-left-right',
            self::Milestone => 'flag',
        };
    }
}
