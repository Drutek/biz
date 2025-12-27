<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Idea = 'idea';
    case Planning = 'planning';
    case InDevelopment = 'in_development';
    case Testing = 'testing';
    case Launched = 'launched';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Idea => 'Idea',
            self::Planning => 'Planning',
            self::InDevelopment => 'In Development',
            self::Testing => 'Testing',
            self::Launched => 'Launched',
            self::Retired => 'Retired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Idea => 'zinc',
            self::Planning => 'amber',
            self::InDevelopment => 'blue',
            self::Testing => 'purple',
            self::Launched => 'green',
            self::Retired => 'red',
        };
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::Idea, self::Planning, self::InDevelopment, self::Testing, self::Launched => true,
            self::Retired => false,
        };
    }

    public function isLaunched(): bool
    {
        return $this === self::Launched;
    }

    public function isInDevelopment(): bool
    {
        return in_array($this, [self::Idea, self::Planning, self::InDevelopment, self::Testing]);
    }
}
