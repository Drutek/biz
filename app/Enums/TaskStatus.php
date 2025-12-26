<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Suggested = 'suggested';
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Suggested => 'Suggested',
            self::Accepted => 'Accepted',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Suggested => 'blue',
            self::Accepted => 'green',
            self::InProgress => 'amber',
            self::Completed => 'emerald',
            self::Rejected => 'red',
            self::Cancelled => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Suggested => 'light-bulb',
            self::Accepted => 'check',
            self::InProgress => 'arrow-path',
            self::Completed => 'check-circle',
            self::Rejected => 'x-circle',
            self::Cancelled => 'minus-circle',
        };
    }

    public function isPending(): bool
    {
        return in_array($this, [self::Accepted, self::InProgress]);
    }

    public function isActionable(): bool
    {
        return in_array($this, [self::Suggested, self::Accepted, self::InProgress]);
    }
}
