<?php

namespace App\Models;

use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use HasFactory;

    /** Issued only by CertificateService. */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Certificate $certificate) {
            $certificate->code ??= static::generateCode();
            $certificate->issued_at ??= now();
        });
    }

    /**
     * Unique, hard-to-guess verification code like "LH-7Q2K-9XDM-4TPA".
     * Ambiguous characters (0/O, 1/I) are excluded so codes are easy to type.
     */
    public static function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $groups = [];
            for ($i = 0; $i < 3; $i++) {
                $groups[] = collect(range(1, 4))
                    ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
                    ->implode('');
            }
            $code = 'LH-'.implode('-', $groups);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function verificationUrl(): string
    {
        return config('services.frontend_url').'/certificates/verify/'.Str::upper($this->code);
    }
}
