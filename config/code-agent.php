<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure your Telegram bot settings.
    |
    */
    'telegram' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
        'allowed_users' => explode(',', env('TELEGRAM_ALLOWED_USERS', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your LLM provider settings here.
    |
    */
    'llm' => [
        'provider' => env('LLM_PROVIDER', 'openai'),
        'api_key' => env('LLM_API_KEY'),
        'model' => env('LLM_MODEL', 'gpt-4o'),
        'temperature' => (float) env('LLM_TEMPERATURE', 0.7),
        'max_tokens' => (int) env('LLM_MAX_TOKENS', 4000),
    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your GitHub integration settings here.
    |
    */
    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'username' => env('GITHUB_USERNAME'),
        'repository' => env('GITHUB_REPOSITORY'),
        'branch' => env('GITHUB_BRANCH', 'main'),
    ],
];
