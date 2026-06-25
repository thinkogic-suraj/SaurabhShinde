<?php
require __DIR__ . '/includes/db.php';

$pdo = app_pdo();

echo 'TABLE:FoundationBanner' . PHP_EOL;
foreach ($pdo->query('DESCRIBE FoundationBanner') as $row) {
    echo implode('|', [
        (string) $row['Field'],
        (string) $row['Type'],
        (string) $row['Null'],
        (string) $row['Key'],
        $row['Default'] === null ? 'NULL' : (string) $row['Default'],
    ]) . PHP_EOL;
}
