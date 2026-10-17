<?php

namespace App\Enums;

enum AiFeature: string
{
    case QuizGenerator = 'quiz_generator';
    case StudyAssistant = 'study_assistant';
    case DescriptionHelper = 'description_helper';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
