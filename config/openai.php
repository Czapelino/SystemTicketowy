<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your OpenAI settings. The API key is required
    | for ChatGPT integration. Set OPENAI_API_KEY in your .env file.
    |
    */

    'api_key' => env('OPENAI_API_KEY', ''),

    'organization' => env('OPENAI_ORGANIZATION', null),

    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),

    'max_tokens' => env('OPENAI_MAX_TOKENS', 1000),

    'request_timeout' => env('OPENAI_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Escalation Settings
    |--------------------------------------------------------------------------
    */

    'escalation_threshold' => env('AI_ESCALATION_THRESHOLD', 3),

    'max_conversation_messages' => env('AI_MAX_CONTEXT_MESSAGES', 10),

];
