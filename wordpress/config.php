<?php

return [
    'rate_limits' => [
        'signup' => [
            'attempts' => 6,
            'interval' => 3600,  
            'daily_limit' => 8,
        ],
        'login' => [
            'attempts' => 8, 
            'interval' => 3600,  
            'daily_limit' => 10,  
        ],
        'otp_resend' => [
            'attempts' => 4,
            'interval' => 3600,
            'cooldown' => 60,
            'daily_limit' => 5,
        ],
        'otp_verify' => [
            'attempts' => 6,
            'interval' => 3600,
            'daily_limit' => 7,
        ],
        'password_reset' => [
            'attempts' => 6,
            'interval' => 3600,
            'daily_limit' => 7,
        ],
        // حماية النشرة البريدية
        'subscribe' => [
            'attempts' => 3,
            'interval' => 3600,
            'daily_limit' => 10
        ],
    ],
    'forbidden_countries' => [
        'CN' => 'China', 
        'RU' => 'Russia'
    ],
];