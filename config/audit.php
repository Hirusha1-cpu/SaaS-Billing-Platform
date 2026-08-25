<?php

return [
    'enabled' => true,
    
    'model' => App\Models\AuditLog::class,
    
    'user_model' => App\Models\User::class,
    
    'events' => [
        'created' => true,
        'updated' => true,
        'deleted' => true,
        'restored' => true,
        'force_deleted' => true,
    ],
    
    'except' => [
        'audit_logs',
        'sessions',
        'cache',
    ],
    
    'channels' => [
        'database' => true,
        'file' => false,
    ],
];
