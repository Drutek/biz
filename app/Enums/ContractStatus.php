<?php

namespace App\Enums;

enum ContractStatus: string
{
    case Confirmed = 'confirmed';
    case Pipeline = 'pipeline';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmed',
            self::Pipeline => 'Pipeline',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Confirmed => 'green',
            self::Pipeline => 'yellow',
            self::Completed => 'blue',
            self::Cancelled => 'red',
        };
    }
}
