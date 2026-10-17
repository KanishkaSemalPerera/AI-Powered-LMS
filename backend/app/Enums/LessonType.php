<?php

namespace App\Enums;

enum LessonType: string
{
    case Video = 'video';
    case Text = 'text';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
