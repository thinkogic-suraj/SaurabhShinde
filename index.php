<?php

declare(strict_types=1);

session_start();

$target = !empty($_SESSION['admin_logged_in']) ? 'dashboard.php' : 'admin-login.php';

header('Location: ' . $target);
exit;
