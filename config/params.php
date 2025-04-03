<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'WEX Fuel System',
    'bsVersion' => '5.x',  // Bootstrap версия
    
    // Настройки WEX API
    'wex' => [
        'username' => 'your_wex_username',  // Замените на реальные данные
        'password' => 'your_wex_password',  // Замените на реальные данные
        'isProduction' => false,  // false для тестового окружения, true для продакшена
    ],
    
    // Настройки системы
    'system' => [
        'companyName' => 'Trucking Pluse LLC',
        'companyLogo' => '/img/logo.png',
        'defaultRebate' => 0.10,  // Значение скидки по умолчанию для новых клиентов ($0.10 с галлона)
    ],
    
    // Настройки импорта транзакций
    'import' => [
        'defaultMinutes' => 60,  // По умолчанию импортировать транзакции за последние 60 минут
        'maxMinutes' => 1440,    // Максимальное время для импорта за один запрос (24 часа)
    ],
];