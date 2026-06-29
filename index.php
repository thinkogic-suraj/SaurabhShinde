<?php

declare(strict_types=1);

require __DIR__ . '/includes/auth.php';

$target = is_admin_logged_in() ? 'dashboard.php' : 'admin-login.php';

header('Location: ' . $target);
exit;
