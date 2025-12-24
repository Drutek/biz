<?php

namespace App\Enums;

enum InsightType: string
{
    case Opportunity = 'opportunity';
    case Warning = 'warning';
    case Recommendation = 'recommendation';
    case Analysis = 'analysis';

    public function label(): string
    {
        return match ($this) {
            self::Opportunity => 'Opportunity',
            self::Warning => 'Warning',
            self::Recommendation => 'Recommendation',
            self::Analysis => 'Analysis',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Opportunity => 'green',
            self::Warning => 'red',
            self::Recommendation => 'blue',
            self::Analysis => 'purple',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Opportunity => 'sparkles',
            self::Warning => 'exclamation-triangle',
            self::Recommendation => 'light-bulb',
            self::Analysis => 'chart-bar',
        };
    }
}
