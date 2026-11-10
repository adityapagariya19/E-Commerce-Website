<?php

use Illuminate\Support\Str;

/*
 * Database configuration.
 *
 * PHP 8.5 deprecates PDO::MYSQL_ATTR_SSL_CA in favour of Pdo\Mysql::ATTR_SSL_CA. Laravel 11's bundled
 * config still uses the old constant (=> "Deprecated" warnings on every request under PHP 8.5), so this
 * project ships its own config that picks the right constant for the running PHP version.
 */
$sslCa = null;
if (extension_loaded('pdo_mysql')) {
    $sslCa = (PHP_VERSION_ID >= 80500 && class_exists(\Pdo\Mysql::class)) ? \Pdo\Mysql::ATTR_SSL_CA : \PDO::MYSQL_ATTR_SSL_CA;
}

$mysql = fn (string $driver) => [
    'driver' => $driver,
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
    'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'options' => $sslCa !== null ? array_filter([$sslCa => env('MYSQL_ATTR_SSL_CA')]) : [],
];

return [
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],
        'mysql' => $mysql('mysql'),
        'mariadb' => $mysql('mariadb'),
    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],
];
