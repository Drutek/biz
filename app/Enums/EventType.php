<?php

namespace App\Enums;

enum EventType: string
{
    case ContractSigned = 'contract_signed';
    case ContractRenewed = 'contract_renewed';
    case ContractExpired = 'contract_expired';
    case ContractEnding = 'contract_ending';
    case ExpenseChange = 'expense_change';
    case RunwayThreshold = 'runway_threshold';
    case NewsAlert = 'news_alert';
    case AiInsight = 'ai_insight';
    case Manual = 'manual';

    // Product events
    case ProductLaunched = 'product_launched';
    case ProductRevenueChange = 'product_revenue_change';
    case ProductMilestone = 'product_milestone';
    case ProductStatusChange = 'product_status_change';

    public function label(): string
    {
        return match ($this) {
            self::ContractSigned => 'Contract Signed',
            self::ContractRenewed => 'Contract Renewed',
            self::ContractExpired => 'Contract Expired',
            self::ContractEnding => 'Contract Ending Soon',
            self::ExpenseChange => 'Expense Change',
            self::RunwayThreshold => 'Runway Alert',
            self::NewsAlert => 'News Alert',
            self::AiInsight => 'AI Insight',
            self::Manual => 'Manual Entry',
            self::ProductLaunched => 'Product Launched',
            self::ProductRevenueChange => 'Product Revenue Change',
            self::ProductMilestone => 'Milestone Completed',
            self::ProductStatusChange => 'Product Status Change',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ContractSigned => 'green',
            self::ContractRenewed => 'green',
            self::ContractExpired => 'red',
            self::ContractEnding => 'yellow',
            self::ExpenseChange => 'blue',
            self::RunwayThreshold => 'red',
            self::NewsAlert => 'purple',
            self::AiInsight => 'indigo',
            self::Manual => 'zinc',
            self::ProductLaunched => 'green',
            self::ProductRevenueChange => 'blue',
            self::ProductMilestone => 'cyan',
            self::ProductStatusChange => 'amber',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ContractSigned => 'document-check',
            self::ContractRenewed => 'arrow-path',
            self::ContractExpired => 'document-minus',
            self::ContractEnding => 'clock',
            self::ExpenseChange => 'banknotes',
            self::RunwayThreshold => 'exclamation-triangle',
            self::NewsAlert => 'newspaper',
            self::AiInsight => 'light-bulb',
            self::Manual => 'pencil',
            self::ProductLaunched => 'rocket-launch',
            self::ProductRevenueChange => 'chart-bar',
            self::ProductMilestone => 'flag',
            self::ProductStatusChange => 'arrow-path-rounded-square',
        };
    }
}
