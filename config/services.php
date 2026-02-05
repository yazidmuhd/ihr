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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ai' => [
        'provider'  => env('AI_PROVIDER', 'openrouter'),
        'base_url'  => env('AI_BASE_URL', 'https://openrouter.ai/api/v1'),
        // read any of these env names (first non-empty wins)
        'key'       => env('AI_API_KEY')
                       ?: env('OPENROUTER_API_KEY')
                       ?: env('OPENAI_API_KEY'),
        'model'     => env('AI_MODEL', 'openai/gpt-4o-mini'),
        'referer'   => env('AI_HTTP_REFERRER', env('APP_URL')),
        'title'     => env('AI_APP_NAME', env('APP_NAME', 'i-HR')),
    ],

    'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],



];
