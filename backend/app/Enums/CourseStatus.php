<?php

namespace App\Enums;

/**
 * Course lifecycle: draft -> pending (submitted for review) -> published | rejected.
 * A rejected course can be edited and resubmitted.
 */
enum CourseStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending review',
            self::Published => 'Published',
            self::Rejected => 'Rejected',
        };
    }

    /** Instructors may edit content only while the course is not under review. */
    public function isEditable(): bool
    {
        return $this !== self::Pending;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
