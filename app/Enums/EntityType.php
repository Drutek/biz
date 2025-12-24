<?php

namespace App\Enums;

enum EntityType: string
{
    case Company = 'company';
    case Industry = 'industry';
    case Topic = 'topic';
    case Competitor = 'competitor';

    public function label(): string
    {
        return match ($this) {
            self::Company => 'Company',
            self::Industry => 'Industry',
            self::Topic => 'Topic',
            self::Competitor => 'Competitor',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Company => 'blue',
            self::Industry => 'purple',
            self::Topic => 'cyan',
            self::Competitor => 'amber',
        };
    }
}
