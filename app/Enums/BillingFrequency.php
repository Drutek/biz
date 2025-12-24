<?php

namespace App\Enums;

enum BillingFrequency: string
{
    case OneTime = 'one_time';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Annual = 'annual';

    public function monthlyMultiplier(): float
    {
        return match ($this) {
            self::OneTime => 0,
            self::Monthly => 1,
            self::Quarterly => 1 / 3,
            self::Annual => 1 / 12,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::OneTime => 'One Time',
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Annual => 'Annual',
        };
    }
}
