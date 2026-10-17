<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'usd'),
    ],

    // Claude API settings. The model is configurable so it can be changed without code changes.
    // Claude API (server-side only). Change ANTHROPIC_MODEL in .env to switch models without code changes.
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-5-5'),
        // Seconds before an AI request is abandoned (enforced by the HTTP client).
        'timeout' => (int) env('ANTHROPIC_TIMEOUT', 120),
        'max_retries' => (int) env('ANTHROPIC_MAX_RETRIES', 2),
        // Server-side retry on a substitute model if the request is declined for policy reasons.
        'refusal_fallback' => (bool) env('ANTHROPIC_REFUSAL_FALLBACK', true),
        // Thinking depth per feature: low | medium | high | xhigh | max.
        'effort' => [
            'quiz_generator' => env('ANTHROPIC_EFFORT_QUIZ', 'medium'),
            'study_assistant' => env('ANTHROPIC_EFFORT_ASSISTANT', 'low'),
            'description_helper' => env('ANTHROPIC_EFFORT_DESCRIPTION', 'low'),
        ],
        // Output token ceilings per feature (thinking counts toward these).
        'max_tokens' => [
            'quiz_generator' => (int) env('ANTHROPIC_MAX_TOKENS_QUIZ', 16000),
            'study_assistant' => (int) env('ANTHROPIC_MAX_TOKENS_ASSISTANT', 4000),
            'description_helper' => (int) env('ANTHROPIC_MAX_TOKENS_DESCRIPTION', 4000),
        ],
        'rate_limit_per_minute' => (int) env('AI_RATE_LIMIT_PER_MINUTE', 10),
        'rate_limit_per_day' => (int) env('AI_RATE_LIMIT_PER_DAY', 100),
    ],

    'frontend_url' => rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/'),

];
