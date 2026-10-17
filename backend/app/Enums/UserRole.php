<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Instructor = 'instructor';
    case Admin = 'admin';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Roles a visitor may pick when registering (admins are created by other admins). */
    public static function registrable(): array
    {
        return [self::Student->value, self::Instructor->value];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
