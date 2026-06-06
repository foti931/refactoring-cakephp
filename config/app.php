<?php
declare(strict_types=1);

return [
    'debug' => true,
    'App' => [
        'namespace' => 'App',
        'encoding' => 'UTF-8',
        'defaultLocale' => 'en_US',
        'defaultTimezone' => 'Asia/Tokyo',
        'fullBaseUrl' => false,
        'paths' => [
            'plugins' => [ROOT . DS . 'plugins' . DS],
            'templates' => [ROOT . DS . 'templates' . DS],
            'locales' => [RESOURCES . 'locales' . DS],
        ],
    ],
    'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'host' => 'db',
            'port' => 3306,
            'username' => 'cake_user',
            'password' => 'cake_password',
            'database' => 'cake_refactor',
            'encoding' => 'utf8mb4',
            'cacheMetadata' => false,
        ],
    ],
    'Cache' => [
        '_cake_core_' => [
            'className' => 'Array',
        ],
        '_cake_model_' => [
            'className' => 'Array',
        ],
        'default' => [
            'className' => 'Array',
        ],
    ],
    'Log' => [
        'debug' => [
            'className' => 'File',
            'path' => LOGS,
            'file' => 'debug',
            'levels' => ['notice', 'info', 'debug'],
        ],
        'error' => [
            'className' => 'File',
            'path' => LOGS,
            'file' => 'error',
            'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
        ],
    ],
];
