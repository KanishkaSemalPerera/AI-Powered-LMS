<?php

namespace App\Enums;

/** AI-generated quizzes start as drafts; only published quizzes are visible to students. */
enum QuizStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
