<?php

namespace App\Enums;

enum LinkedInTone: string
{
    case Professional = 'professional';
    case Conversational = 'conversational';
    case ThoughtLeadership = 'thought_leadership';
    case Casual = 'casual';

    public function label(): string
    {
        return match ($this) {
            self::Professional => 'Professional',
            self::Conversational => 'Conversational',
            self::ThoughtLeadership => 'Thought Leadership',
            self::Casual => 'Casual',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Professional => 'Formal and business-focused, suitable for corporate audiences',
            self::Conversational => 'Friendly and approachable while maintaining professionalism',
            self::ThoughtLeadership => 'Authoritative and insightful, positioning you as an expert',
            self::Casual => 'Relaxed and personable, good for building authentic connections',
        };
    }
}
