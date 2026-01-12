<?php

namespace App\Enums;

enum LinkedInPostType: string
{
    case NewsCommentary = 'news_commentary';
    case ThoughtLeadership = 'thought_leadership';
    case IndustryInsight = 'industry_insight';
    case CompanyUpdate = 'company_update';

    public function label(): string
    {
        return match ($this) {
            self::NewsCommentary => 'News Commentary',
            self::ThoughtLeadership => 'Thought Leadership',
            self::IndustryInsight => 'Industry Insight',
            self::CompanyUpdate => 'Company Update',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NewsCommentary => 'blue',
            self::ThoughtLeadership => 'purple',
            self::IndustryInsight => 'green',
            self::CompanyUpdate => 'amber',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::NewsCommentary => 'newspaper',
            self::ThoughtLeadership => 'light-bulb',
            self::IndustryInsight => 'chart-bar',
            self::CompanyUpdate => 'building-office',
        };
    }
}
