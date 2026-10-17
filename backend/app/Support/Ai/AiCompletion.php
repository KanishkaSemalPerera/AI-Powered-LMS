<?php

namespace App\Support\Ai;

/** The model's reply plus the usage data logged in ai_requests. */
final class AiCompletion
{
    public function __construct(
        public readonly string $text,
        public readonly string $model,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
        public readonly ?string $stopReason = null,
    ) {}
}
