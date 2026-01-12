<?php

namespace App\Enums;

enum LinkedInPostFrequency: string
{
    case Daily = 'daily';
    case TwiceWeekly = 'twice_weekly';
    case Weekly = 'weekly';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Daily',
            self::TwiceWeekly => 'Twice Weekly',
            self::Weekly => 'Weekly',
        };
    }
}
