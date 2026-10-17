<?php

namespace App\Support\Ai;

/**
 * One request to the AI model, independent of the provider SDK.
 *
 * @param  array<int, array{role: 'user'|'assistant', content: string}>  $messages
 * @param  array<string, mixed>|null  $jsonSchema  when set, the reply must be JSON matching this schema
 */
final class AiPrompt
{
    public function __construct(
        public readonly string $system,
        public readonly array $messages,
        public readonly int $maxTokens,
        public readonly string $effort = 'medium',
        public readonly ?array $jsonSchema = null,
    ) {}
}
