<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'wex-fuel-system-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'categories' => ['import'],
                    'logFile' => '@runtime/logs/import.log',
                ],
            ],
        ],
        'db' => $db,
        'wexApi' => [
            'class' => 'app\components\WexApiComponent',
            'username' => $params['wex']['username'],
            'password' => $params['wex']['password'],
            'isProduction' => $params['wex']['isProduction'],
            'enableLogging' => true,
        ],
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'nullDisplay' => '-',
            'dateFormat' => 'php:d.m.Y',
            'datetimeFormat' => 'php:d.m.Y H:i',
            'timeFormat' => 'php:H:i',
            'decimalSeparator' => '.',
            'thousandSeparator' => ',',
            'currencyCode' => 'USD',
        ],
    ],
    'params' => $params,
];

return $config;