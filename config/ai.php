<?php
// config/ai.php
return [
    'provider'   => env('AI_PROVIDER', 'openrouter'),
    'base_url'   => env('AI_BASE_URL', 'https://openrouter.ai/api/v1'),
    // accept any of these names
    'api_key'    => env('AI_API_KEY')
                 ?: env('OPENROUTER_API_KEY')
                 ?: env('OPENAI_API_KEY'),
    'model'      => env('AI_MODEL', 'openai/gpt-4o-mini'),
    'http_ref'   => env('AI_HTTP_REFERRER', env('APP_URL')),
    'app_name'   => env('AI_APP_NAME', env('APP_NAME', 'i-HR')),
];
