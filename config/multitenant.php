<?php

return [
    'enabled' => true,
    
    'company_model' => App\Models\Company::class,
    
    'tenant_column' => 'company_id',
    
    'middleware' => [
        'apply_scope' => true,
        'verify_company' => true,
    ],
    
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
    ],
];
