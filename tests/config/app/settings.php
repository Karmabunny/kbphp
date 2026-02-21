<?php

$config['app_name'] = 'My Application';
$config['version'] = '1.0.0';
$config['random'] = random_int(0, 1000000);

$config['database'] = [
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'app_db',
    'lost-in-merge' => true,
];

$config['features'] = [
    'enabled' => true,
    'modules' => [
        'auth' => true,
        'cache' => false,
    ],
];

$config['merge'] = [
    'enabled' => false,
    'settings1' => 'value1',
    'settings2' => 'value2',
];
