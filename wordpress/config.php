<?php

return [
    'rate_limits' => [
        'signup' => [
            'attempts' => 13,
            'interval' => 3600,  
            'daily_limit' => 14,
        ],
        'login' => [
            'attempts' => 13, 
            'interval' => 3600,  
            'daily_limit' => 14,  
        ],
        'otp_resend' => [
            'attempts' => 9,
            'interval' => 3600,
            'cooldown' => 60,
            'daily_limit' => 10,
        ],
        'otp_verify' => [
            'attempts' => 12,
            'interval' => 3600,
            'daily_limit' => 13,
        ],
        'password_reset' => [
            'attempts' => 11,
            'interval' => 3600,
            'daily_limit' => 12,
        ],
        // حماية النشرة البريدية
        'subscribe' => [
            'attempts' => 7,
            'interval' => 3600,
            'daily_limit' => 10
        ],
    ],
    'forbidden_countries' => [
        'CN' => 'China', 
        'RU' => 'Russia'
    ],
];