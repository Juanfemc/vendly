<?php

return [
    'currency' => 'COP',
    'cycles' => [
        'monthly' => [
            'label' => 'Mensual',
            'months' => 1,
            'days' => 30,
            'discount' => 0,
            'period' => '/mes',
        ],
        'quarterly' => [
            'label' => 'Trimestral',
            'months' => 3,
            'days' => 90,
            'discount' => 10,
            'period' => '/trimestre',
        ],
        'annual' => [
            'label' => 'Anual',
            'months' => 12,
            'days' => 365,
            'discount' => 15,
            'period' => '/año',
        ],
    ],
    'prices' => [
        'basic' => [
            'monthly' => 0,
            'quarterly' => 0,
            'annual' => 0,
        ],
        'pro' => [
            'monthly' => 10000,
            'quarterly' => 27000,
            'annual' => 102000,
        ],
        'premium' => [
            'monthly' => 25000,
            'quarterly' => 67500,
            'annual' => 255000,
        ],
    ],
];
