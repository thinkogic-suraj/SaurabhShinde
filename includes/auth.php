<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function send_no_cache_headers(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Mon, 01 Jan 1990 00:00:00 GMT');
}

send_no_cache_headers();

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_logged_in']);
}

function require_admin_login(): void
{
    if (!is_admin_logged_in()) {
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
