<?php
require __DIR__ . '/includes/auth.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'] ?? '/',
        $params['domain'] ?? '',
        (bool) ($params['secure'] ?? false),
        (bool) ($params['httponly'] ?? false)
    );
}

session_unset();
session_destroy();
send_no_cache_headers();

header('Location: admin-login.php');
exit;
