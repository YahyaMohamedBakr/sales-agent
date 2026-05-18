<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    |
    | اختر مزود الذكاء الاصطناعي الأساسي.
    | الخيارات: openai, anthropic, google, groq, ollama, openrouter
    | أو "smart" للاختيار التلقائي الذكي
    |
    */

    'default' => env('AGENT_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | Router Strategy
    |--------------------------------------------------------------------------
    |
    | smart:         يبدأ بالأفضل، لو rate limit → أرخص، لو الكل fail → local
    | priority:     حسب ترتيب الـ providers في config
    | cost_optimized: الأرخص أولاً (local > free > standard > premium)
    | performance:  الأسرع أولاً (حسب الـ latency)
    |
    */

    'router_strategy' => env('AGENT_ROUTER_STRATEGY', 'smart'),

    /*
    |--------------------------------------------------------------------------
    | Default Model Settings
    |--------------------------------------------------------------------------
    */

    'temperature' => env('AGENT_TEMPERATURE', 0.7),
    'max_tokens' => env('AGENT_MAX_TOKENS', 2048),

    /*
    |--------------------------------------------------------------------------
    | Model Overrides
    |--------------------------------------------------------------------------
    |
    | عيّن موديل معين لكل مزود لو عاوز
    | مثال: 'openai' => 'gpt-4o-mini'  (عشان توفير الفلوس)
    |
    */

    'models' => [
        'openai' => env('OPENAI_MODEL', 'gpt-4o'),
        'anthropic' => env('ANTHROPIC_MODEL', 'claude-3.5-sonnet'),
        'google' => env('GOOGLE_MODEL', 'gemini-1.5-pro'),
        'groq' => env('GROQ_MODEL', 'llama-3.1-70b-versatile'),
        'ollama' => env('OLLAMA_MODEL', 'llama3'),
        'openrouter' => env('OPENROUTER_MODEL', 'openai/gpt-4o-mini'),
        'zen' => env('ZEN_MODEL', 'big-pickle'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Credentials
    |--------------------------------------------------------------------------
    |
    | كل مزود محتاج API key.
    | ممكن تحطها هنا أو في ملف .env
    |
    */

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],

        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
        ],

        'google' => [
            'api_key' => env('GOOGLE_API_KEY'),
            'base_url' => env('GOOGLE_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        ],

        'groq' => [
            'api_key' => env('GROQ_API_KEY'),
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        ],

        'ollama' => [
            'api_key' => env('OLLAMA_API_KEY'),
            'base_url' => env('OLLAMA_URL', 'http://localhost:11434'),
        ],

        'openrouter' => [
            'api_key' => env('OPENROUTER_API_KEY'),
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        ],

        'mistral' => [
            'api_key' => env('MISTRAL_API_KEY'),
            'base_url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1'),
        ],

        'deepseek' => [
            'api_key' => env('DEEPSEEK_API_KEY'),
            'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        ],

        'together' => [
            'api_key' => env('TOGETHER_API_KEY'),
            'base_url' => env('TOGETHER_BASE_URL', 'https://api.together.xyz/v1'),
        ],

        'cohere' => [
            'api_key' => env('COHERE_API_KEY'),
            'base_url' => env('COHERE_BASE_URL', 'https://api.cohere.ai/v1'),
        ],

        'zen' => [
            'api_key' => env('ZEN_API_KEY'),
            'base_url' => env('ZEN_BASE_URL', 'https://opencode.ai/zen/v1'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent Orchestration
    |--------------------------------------------------------------------------
    */

    'agents' => [
        'comment_reply' => [
            'enabled' => true,
            'provider' => env('AGENT_COMMENT_PROVIDER', 'smart'),
        ],
        'lead_qualifier' => [
            'enabled' => true,
            'provider' => env('AGENT_QUALIFIER_PROVIDER', 'smart'),
        ],
        'support' => [
            'enabled' => true,
            'provider' => env('AGENT_SUPPORT_PROVIDER', 'gpt-4o-mini'),
        ],
        'email_agent' => [
            'enabled' => true,
            'provider' => env('AGENT_EMAIL_PROVIDER', 'gpt-4o-mini'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Configuration
    |--------------------------------------------------------------------------
    |
    | Time in seconds to wait before retrying a rate-limited provider
    |
    */

    'rate_limit_retry_seconds' => 60,

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */

    'log_agent_actions' => env('LOG_AGENT_ACTIONS', true),
    'log_tokens_usage' => env('LOG_TOKENS_USAGE', true),
];
