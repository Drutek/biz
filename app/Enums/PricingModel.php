<?php

namespace App\Enums;

enum PricingModel: string
{
    case OneTime = 'one_time';
    case Subscription = 'subscription';
    case Freemium = 'freemium';
    case PayWhatYouWant = 'pay_what_you_want';

    public function label(): string
    {
        return match ($this) {
            self::OneTime => 'One Time',
            self::Subscription => 'Subscription',
            self::Freemium => 'Freemium',
            self::PayWhatYouWant => 'Pay What You Want',
        };
    }

    public function hasRecurringRevenue(): bool
    {
        return $this === self::Subscription;
    }
}
