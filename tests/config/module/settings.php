<?php

$config['version'] = '2.0.0';

$config['database'] = [
    'host' => 'db.example.com',
    'port' => ($config['database']['port'] ?? 0) + 1111,
    'name' => 'module_db',
    'ssl' => 'yes',
];

$config['features'] = [
    'enabled' => true,
    'modules' => [
        'auth' => ['enabled' => true, 'password' => 'test'],
        'cache' => true,
        'api' => true,
    ],
];

if (isset($config['merge'])) {
    $config['merge'] = array_merge($config['merge'], [
        'enabled' => true,
    ]);
}

$config['extra'] = [
    'deep' => [
        ['nested1' => 'hiiii'],
        ['nested2' => 'hiiii'],
    ],
];
