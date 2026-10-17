<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

/**
 * A failed AI request, carrying a message that is safe to show the user and
 * the HTTP status to return. Technical details stay in the logs.
 */
class AiException extends RuntimeException
{
    public function __construct(
        string $userMessage,
        public readonly int $status = 503,
        public readonly string $reason = 'ai_error',
        ?Throwable $previous = null,
    ) {
        parent::__construct($userMessage, 0, $previous);
    }

    public static function notConfigured(): self
    {
        return new self('AI features are not configured on this server yet.', 503, 'not_configured');
    }

    public static function busy(?Throwable $previous = null): self
    {
        return new self('The AI service is busy right now. Please try again in a minute.', 503, 'busy', $previous);
    }

    public static function timeout(?Throwable $previous = null): self
    {
        return new self('The AI took too long to respond. Please try again.', 504, 'timeout', $previous);
    }

    public static function unavailable(?Throwable $previous = null): self
    {
        return new self('The AI service is temporarily unavailable. Please try again later.', 503, 'unavailable', $previous);
    }

    public static function refused(): self
    {
        return new self('The AI could not help with this request. Try rephrasing it.', 422, 'refused');
    }

    public static function invalidOutput(string $detail = ''): self
    {
        return new self(
            'The AI returned a response we could not use. Please try again.'.($detail ? " ({$detail})" : ''),
            502,
            'invalid_output',
        );
    }

    /** Laravel calls this automatically, giving the standard JSON envelope. */
    public function render(): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), $this->status, ['ai' => [$this->reason]]);
    }
}
