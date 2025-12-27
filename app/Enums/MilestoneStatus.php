<?php

namespace App\Enums;

enum MilestoneStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not Started',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Blocked => 'Blocked',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotStarted => 'zinc',
            self::InProgress => 'blue',
            self::Completed => 'green',
            self::Blocked => 'red',
        };
    }
}
