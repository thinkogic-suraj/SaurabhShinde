<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/rbac.php';

if (is_admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$config = require __DIR__ . '/config/database.php';

$username = '';
$error_msg = '';

function is_valid_identifier(string $value): bool
{
    return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value) === 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($username === '' || $pass === '') {
        $error_msg = 'Please fill in all fields.';
    } else {
        $table = $config['admin_table'] ?? '';
        $usernameColumn = $config['username_column'] ?? '';
        $passwordColumn = $config['password_column'] ?? '';
        $idColumn = $config['id_column'] ?? '';

        if (
            !is_valid_identifier($table) ||
            !is_valid_identifier($usernameColumn) ||
            !is_valid_identifier($passwordColumn) ||
            !is_valid_identifier($idColumn)
        ) {
            $error_msg = 'Database configuration is invalid. Check config/database.php.';
        } else {
            try {
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

                $sql = sprintf(
                    'SELECT e.`%s`, e.`%s`, e.`MobileNo`, e.`UserName`, e.`RoleId`, COALESCE(r.`RoleName`, \'\') AS `RoleName`
                     FROM `%s` e
                     LEFT JOIN `RoleMaster` r ON r.`RoleId` = e.`RoleId`
                     WHERE e.`%s` = :username
                       AND e.`IsActive` = 1
                     LIMIT 1',
                    $idColumn,
                    $passwordColumn,
                    $table,
                    $usernameColumn
                );

                $stmt = $pdo->prepare($sql);
                $stmt->execute(['username' => $username]);
                $admin = $stmt->fetch();

                if (!$admin) {
                    $error_msg = 'Invalid username or password.';
                } else {
                    $storedPassword = (string) ($admin[$passwordColumn] ?? '');
                    $isPasswordValid = password_verify($pass, $storedPassword) || hash_equals($storedPassword, $pass);

                    if ($isPasswordValid) {
                        session_regenerate_id(true);
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = $admin[$idColumn] ?? null;
                        $_SESSION['admin_mobile'] = (string) ($admin['MobileNo'] ?? '');
                        $_SESSION['admin_name'] = (string) ($admin['UserName'] ?? $username);
                        $_SESSION['admin_role_id'] = isset($admin['RoleId']) ? (int) $admin['RoleId'] : 0;
                        $_SESSION['admin_role_name'] = (string) ($admin['RoleName'] ?? '');

                        header('Location: dashboard.php');
                        exit;
                    }

                    $error_msg = 'Invalid username or password.';
                }
            } catch (PDOException $e) {
                $error_msg = 'Database connection failed. Check config/database.php and your server credentials.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Admin Panel Login - Saurabh Shinde Foundation</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/png" href="uploads/favicon.png?v=1">
        <!-- Bootstrap Css -->
        <link href="themesdesign.in/nazox/layouts/assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
        <!-- Icons Css -->
        <link href="themesdesign.in/nazox/layouts/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
        <!-- App Css-->
        <link href="themesdesign.in/nazox/layouts/assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
        <style>
            .auth-body-bg,
            body {
                background:
                    radial-gradient(circle at top left, rgba(232, 199, 163, 0.35) 0%, rgba(232, 199, 163, 0) 42%),
                    linear-gradient(135deg, #f4eadc 0%, #f8f2ea 36%, #fbf7f2 100%) !important;
            }
            .error-message {
                color: #dc3545;
                font-size: 0.875rem;
                margin-top: 0.4rem;
                display: none;
            }
            .login-alert {
                opacity: 1;
                transition: opacity 0.5s ease;
            }
            .login-alert.is-fading-out {
                opacity: 0;
            }
            .login-form-label {
                color: #ffffff;
                display: inline-block;
                margin-bottom: 0.5rem;
                position: static !important;
                transform: none !important;
                top: auto !important;
                left: auto !important;
                background: transparent !important;
                padding: 0 !important;
                width: auto !important;
                z-index: auto !important;
            }
            .auth-form-group-custom {
                position: relative !important;
            }
            .auth-input-wrap {
                position: relative;
            }
            .auth-form-group-custom .form-control {
                height: 58px !important;
                padding-left: 3rem !important;
                padding-top: 0 !important;
                padding-bottom: 0 !important;
                line-height: 58px !important;
            }
            .auth-form-group-custom .auti-custom-input-icon {
                position: absolute !important;
                top: 50% !important;
                left: 1rem !important;
                transform: translateY(-50%) !important;
                z-index: 2 !important;
                line-height: 1 !important;
                font-size: 1.2rem !important;
                pointer-events: none !important;
            }
            .authentication-page-content .mt-5 {
                margin-top: 2rem !important;
            }
            .authentication-page-content .mt-5.text-center.text-white p {
                font-size: 0.7rem !important;
                margin-bottom: 0 !important;
            }
            .authentication-page-content {
                background: #002253;
            }
            .login-visual-panel .authentication-bg {
                min-height: 100vh;
                background-image: url('uploads/admin-login-side-panel.png');
                background-position: center;
                background-repeat: no-repeat;
                background-size: cover;
            }
            .login-visual-panel .bg-overlay {
                background: transparent;
            }
        </style>
    </head>
    <body class="auth-body-bg">
        <div>
            <div class="container-fluid p-0">
                <div class="row g-0">
                    <div class="col-lg-4">
                        <div class="authentication-page-content p-4 d-flex align-items-center min-vh-100">
                            <div class="w-100">
                                <div class="row justify-content-center">
                                    <div class="col-lg-9">
                                        <div>
                                            <div class="text-center">
                                                <div>
                                                    <a href="#" class="authentication-logo">
                                                        <img src="uploads/admin-login-logo-v2.svg" alt="Saurabh Shinde Foundation Logo" height="120" class="auth-logo mx-auto">
                                                    </a>
                                                </div>
    
                                                <!--<h4 class="font-size-18 mt-4 text-primary" style="color: #002b6b !important; font-weight: 700;">Saurabh Shinde Foundation</h4>-->
                                                <!--<p>Admin Panel Login</p>-->
                                            </div>

                                            <div class="p-2 mt-5">
                                                <?php if ($error_msg !== ''): ?>
                                                    <div class="alert alert-danger login-alert" id="loginAlert" role="alert">
                                                        <?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?>
                                                    </div>
                                                <?php endif; ?>

                                                <form id="loginForm" method="POST" action="" novalidate>
                    
                                                    <div class="mb-3 auth-form-group-custom mb-4">
                                                        <label for="username" class="fw-semibold login-form-label">Username</label>
                                                        <div class="auth-input-wrap">
                                                            <i class="ri-user-line auti-custom-input-icon"></i>
                                                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username">
                                                        </div>
                                                        <div id="usernameError" class="error-message">Username is required.</div>
                                                    </div>
                            
                                                    <div class="mb-3 auth-form-group-custom mb-4">
                                                        <label for="password" class="fw-semibold login-form-label">Password</label>
                                                        <div class="auth-input-wrap">
                                                            <i class="ri-lock-2-line auti-custom-input-icon"></i>
                                                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password">
                                                        </div>
                                                        <div id="passwordError" class="error-message">Password is required.</div>
                                                    </div>
                            
                                                    <div class="mt-4 text-center">
                                                        <button class="btn btn-primary w-md waves-effect waves-light" type="submit" style="background-color: #F6AF2E; border-color: #F6AF2E; color: #ffffff;">Log In</button>
                                                    </div>
                                                </form>
                                            </div>

                                            <div class="mt-5 text-center text-white">
                                                <p>© <script>document.write(new Date().getFullYear())</script> Saurabh Shinde Foundation.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8 login-visual-panel">
                        <div class="authentication-bg">
                            <div class="bg-overlay"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- JAVASCRIPT -->
        <script src="themesdesign.in/nazox/layouts/assets/libs/jquery/jquery.min.js"></script>
        <script src="themesdesign.in/nazox/layouts/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="themesdesign.in/nazox/layouts/assets/libs/metismenu/metisMenu.min.js"></script>
        <script src="themesdesign.in/nazox/layouts/assets/libs/simplebar/simplebar.min.js"></script>
        <script src="themesdesign.in/nazox/layouts/assets/libs/node-waves/waves.min.js"></script>
        <script src="themesdesign.in/nazox/layouts/assets/js/app.js"></script>
        
        <script>
            const loginAlert = document.getElementById('loginAlert');

            if (loginAlert) {
                window.setTimeout(function () {
                    loginAlert.classList.add('is-fading-out');

                    window.setTimeout(function () {
                        loginAlert.remove();
                    }, 500);
                }, 5000);
            }

            document.getElementById('loginForm').addEventListener('submit', function (e) {
                let isValid = true;
    
                const username = document.getElementById('username').value.trim();
                const usernameError = document.getElementById('usernameError');
    
                if (!username) {
                    usernameError.style.display = 'block';
                    document.getElementById('username').classList.add('is-invalid');
                    isValid = false;
                } else {
                    usernameError.style.display = 'none';
                    document.getElementById('username').classList.remove('is-invalid');
                }
    
                const password = document.getElementById('password').value;
                const passwordError = document.getElementById('passwordError');
    
                if (!password) {
                    passwordError.style.display = 'block';
                    document.getElementById('password').classList.add('is-invalid');
                    isValid = false;
                } else {
                    passwordError.style.display = 'none';
                    document.getElementById('password').classList.remove('is-invalid');
                }
    
                if (!isValid) {
                    e.preventDefault();
                }
            });
    
            document.getElementById('username').addEventListener('input', function () {
                document.getElementById('usernameError').style.display = 'none';
                this.classList.remove('is-invalid');
            });
    
            document.getElementById('password').addEventListener('input', function () {
                document.getElementById('passwordError').style.display = 'none';
                this.classList.remove('is-invalid');
            });
        </script>
    </body>
</html>
