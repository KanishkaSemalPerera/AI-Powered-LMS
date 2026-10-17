<?php

namespace App\Contracts;

use App\Exceptions\AiException;
use App\Support\Ai\AiCompletion;
use App\Support\Ai\AiPrompt;

/**
 * Talks to the language model. App\Services\Ai\ClaudeClient is the real
 * implementation; tests bind a fake so no network calls are made.
 */
interface AiClient
{
    public function isConfigured(): bool;

    /** The model id requests are sent to (for logging). */
    public function model(): string;

    /**
     * @throws AiException on any provider error, timeout or refusal
     */
    public function complete(AiPrompt $prompt): AiCompletion;
}
