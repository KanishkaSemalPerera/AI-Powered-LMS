<?php

namespace App\Enums;

enum AiRequestStatus: string
{
    case Success = 'success';
    case Error = 'error';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
