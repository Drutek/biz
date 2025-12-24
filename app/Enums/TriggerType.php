<?php

namespace App\Enums;

enum TriggerType: string
{
    case Scheduled = 'scheduled';
    case Threshold = 'threshold';
    case Event = 'event';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Threshold => 'Threshold',
            self::Event => 'Event-Triggered',
            self::Manual => 'Manual',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'blue',
            self::Threshold => 'yellow',
            self::Event => 'purple',
            self::Manual => 'zinc',
        };
    }
}
