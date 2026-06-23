<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function require_admin_login(): void
{
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: admin-login.php');
        exit;
    }
}

function set_flash_message(string $type, string $message): void
{
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash_message(): ?array
{
    if (!isset($_SESSION['flash_message'])) {
        return null;
    }

    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);

    return $flash;
}
