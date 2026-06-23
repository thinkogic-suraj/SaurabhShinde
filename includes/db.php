<?php

function app_config(): array
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/../config/database.php';
    }

    return $config;
}

function app_pdo(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $config = app_config();

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        );

        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    return $pdo;
}
