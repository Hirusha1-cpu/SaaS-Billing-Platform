<?php

return [
    'default' => env('AI_DRIVER', 'openai'),
    
    'drivers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'max_tokens' => env('OPENAI_MAX_TOKENS', 2000),
            'temperature' => env('OPENAI_TEMPERATURE', 0.7),
        ],
        
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-pro'),
        ],
    ],
    
    'features' => [
        'invoice_creation' => true,
        'smart_reminders' => true,
        'insights' => true,
        'parsing' => true,
    ],
    
    'safety' => [
        'human_in_loop' => true,
        'max_draft_invoices' => 10,
        'require_approval' => true,
    ],
];
