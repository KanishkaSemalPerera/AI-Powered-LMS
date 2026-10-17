<?php

namespace App\Enums;

enum CourseLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
    case AllLevels = 'all_levels';

    public function label(): string
    {
        return match ($this) {
            self::AllLevels => 'All levels',
            default => ucfirst($this->value),
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
