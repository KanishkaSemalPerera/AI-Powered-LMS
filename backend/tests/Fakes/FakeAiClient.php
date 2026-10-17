<?php

namespace Tests\Fakes;

use App\Contracts\AiClient;
use App\Exceptions\AiException;
use App\Support\Ai\AiCompletion;
use App\Support\Ai\AiPrompt;
use RuntimeException;

/** Scripted stand-in for Claude: queue replies (strings) or AiExceptions. */
class FakeAiClient implements AiClient
{
    public bool $configured = true;

    /** @var array<int, string|AiException> */
    private array $queue = [];

    /** @var array<int, AiPrompt> prompts received, in order */
    public array $prompts = [];

    public function reply(string|array $text): self
    {
        $this->queue[] = is_array($text) ? json_encode($text) : $text;

        return $this;
    }

    public function fail(AiException $exception): self
    {
        $this->queue[] = $exception;

        return $this;
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function model(): string
    {
        return 'claude-test-model';
    }

    public function complete(AiPrompt $prompt): AiCompletion
    {
        $this->prompts[] = $prompt;
        $next = array_shift($this->queue) ?? throw new RuntimeException('FakeAiClient: no reply queued');

        if ($next instanceof AiException) {
            throw $next;
        }

        return new AiCompletion($next, 'claude-test-model', 1200, 350, 'end_turn');
    }
}
