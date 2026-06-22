<?php
session_start();

// Database connection setup (Update with your credentials)
$host = 'localhost';
$dbname = 'ssf_db';
$username = 'root';
$password = '';

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = $_POST['mobile'] ?? '';
    $pass = $_POST['password'] ?? '';

    if (empty($mobile) || empty($pass)) {
        $error_msg = 'Please fill in all fields.';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error_msg = 'Mobile number must be exactly 10 digits.';
    } else {
        try {
            // Replace with your actual database connection logic when deploying
            /*
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT * FROM admins WHERE mobile_number = :mobile LIMIT 1");
            $stmt->execute(['mobile' => $mobile]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($pass, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error_msg = 'Invalid mobile number or password.';
            }
            */
            
            // Dummy logic for demonstration
            if ($mobile === '1234567890' && $pass === 'admin') {
                $_SESSION['admin_logged_in'] = true;
                header('Location: dashboard.php');
                exit;
            } else {
                $error_msg = 'Invalid mobile number or password. (Hint: use 1234567890 / admin)';
            }
        } catch (Exception $e) {
            $error_msg = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel Login - Saurabh Shinde Foundation</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #002B6B;
            --secondary-gold: #F5A623;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 43, 107, 0.08);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            padding: 3rem 2.5rem;
            position: relative;
        }
        
        /* Top accent line */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-blue), var(--secondary-gold));
        }

        .logo-container {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        /* Temporary logo styling */
        .logo-placeholder {
            width: 80px;
            height: 80px;
            background: var(--primary-blue);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            font-weight: 700;
            border: 3px solid var(--secondary-gold);
            box-shadow: 0 4px 10px rgba(245, 166, 35, 0.3);
            margin-bottom: 1rem;
        }

        .foundation-name {
            color: var(--primary-blue);
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
            text-align: center;
            letter-spacing: -0.5px;
        }

        .admin-title {
            color: #6c757d;
            font-weight: 500;
            font-size: 1rem;
            margin-bottom: 2.5rem;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }

        .form-control:focus {
            background-color: #ffffff;
            border-color: var(--secondary-gold);
            box-shadow: 0 0 0 0.25rem rgba(245, 166, 35, 0.25);
        }

        .btn-login {
            background-color: var(--primary-blue);
            color: white;
            font-weight: 600;
            padding: 0.85rem 1.5rem;
            border: none;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 1rem;
            font-size: 1.05rem;
        }

        .btn-login:hover {
            background-color: #001f4d;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 43, 107, 0.25);
            color: white;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.4rem;
            display: none;
        }
        
        .form-label {
            color: #495057;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>

    <div class="container px-3 d-flex justify-content-center">
        <div class="login-card">
            <div class="logo-container">
                <!-- Foundation Logo Placeholder (replace img tag when actual logo is available) -->
                <div class="logo-placeholder">SSF</div>
            </div>
            
            <h1 class="foundation-name">Saurabh Shinde Foundation</h1>
            <h2 class="admin-title">Admin Panel Login</h2>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="" novalidate>
                <div class="mb-3">
                    <label for="mobile" class="form-label fw-semibold">Mobile Number</label>
                    <input type="text" class="form-control" id="mobile" name="mobile" placeholder="Enter 10-digit mobile number" value="<?php echo htmlspecialchars($mobile ?? ''); ?>" autocomplete="off">
                    <div id="mobileError" class="error-message">Valid 10-digit mobile number is required.</div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password">
                    <div id="passwordError" class="error-message">Password is required.</div>
                </div>

                <button type="submit" class="btn btn-login">Log In</button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Client-side Validation -->
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Mobile Validation
            const mobile = document.getElementById('mobile').value.trim();
            const mobileError = document.getElementById('mobileError');
            const mobileRegex = /^[0-9]{10}$/;
            
            if (!mobile || !mobileRegex.test(mobile)) {
                mobileError.style.display = 'block';
                document.getElementById('mobile').classList.add('is-invalid');
                isValid = false;
            } else {
                mobileError.style.display = 'none';
                document.getElementById('mobile').classList.remove('is-invalid');
            }

            // Password Validation
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
                e.preventDefault(); // Prevent form submission if validation fails
            }
        });

        // Hide error message on input and format mobile
        document.getElementById('mobile').addEventListener('input', function() {
            document.getElementById('mobileError').style.display = 'none';
            this.classList.remove('is-invalid');
            // Allow only numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            // Limit to 10 digits
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            document.getElementById('passwordError').style.display = 'none';
            this.classList.remove('is-invalid');
        });
    </script>
</body>
</html>
