<?php
// Include config BEFORE session_start
require_once __DIR__ . '/../config/config.php';

// Now start session (after config is loaded)
session_start();

// Optional DB connect if you have one (avoids fatal error if missing)
if (file_exists(__DIR__ . '/../config/db_connect.php')) {
    require_once __DIR__ . '/../config/db_connect.php';
}

// Fixed admin credentials (clear-text) as requested
const FIXED_ADMIN_USER = 'admin';
const FIXED_ADMIN_PASS = 'admin123';

$error = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } else {
        // Primary: accept fixed admin credentials
        if ($username === FIXED_ADMIN_USER && $password === FIXED_ADMIN_PASS) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = FIXED_ADMIN_USER;
            header('Location: ' . ADMIN_BASE_URL . '/pages/dashboard.php');
            exit;
        }

        // Optional secondary: try DB auth if $conn (mysqli) is available
        if (isset($conn) && $conn instanceof mysqli) {
            $stmt = $conn->prepare('SELECT id, username, password, user_type FROM users WHERE username = ? OR email = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('ss', $username, $username);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    $hash = $user['password'];
                    
                    // Verify hash AND check if user is admin
                    if (password_verify($password, $hash) || $hash === $password) { // Allows plain text passwords in DB for testing
                        if (isset($user['user_type']) && $user['user_type'] === 'admin') {
                            $_SESSION['loggedin'] = true;
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            header('Location: ' . ADMIN_BASE_URL . '/pages/dashboard.php');
                            exit;
                        } else {
                            $error = 'Access denied: Admin privileges required.';
                        }
                    } else {
                        $error = 'Invalid username or password.';
                    }
                } else {
                    $error = 'Invalid username or password.';
                }
                $stmt->close();
            } else {
                $error = 'Database error.';
            }
        } else {
            // If DB not present and fixed creds didn't match:
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Admin Login</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Main Admin Stylesheet -->
    <link rel="stylesheet" href="../css/main.css">
    
    <!-- Custom Styles included directly -->
    <style>
        :root {
            --primary-color: #7f5539;
            --primary-hover: #9c6644;
            --bg-gradient-start: #f6e9d6;
            --bg-gradient-end: #e6ccb2;
        }
        body.page-bg {
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        .login-wrapper {
            width: 100%;
            padding: 15px;
        }
        .login-box {
            max-width: 400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(127, 85, 57, 0.15);
        }
        .login-title {
            text-align: center;
            color: var(--primary-color);
            font-weight: 800;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .form-outline {
            position: relative;
        }
        .form-input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            outline: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        .form-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(127, 85, 57, 0.1);
        }
        /* Icon positioning */
        .field-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 1.1rem;
        }
        .btn-icon {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            color: var(--primary-color);
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        .btn-icon:hover {
            opacity: 1;
        }
        /* Submit Button */
        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: background-color 0.3s;
            cursor: pointer;
        }
        .btn-submit:hover {
            background-color: var(--primary-hover);
        }
        .card-footer {
            margin-top: 20px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body class="page-bg">

<main class="d-flex align-items-center justify-content-center min-vh-100">
    <section class="login-wrapper">
        <div class="login-box">
            <h4 class="login-title">Admin Login</h4>

            <?php if ($error): ?>
                <div class="alert alert-danger small text-center mb-4 shadow-sm border-0" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-1"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form" novalidate>
                <div class="mb-3 form-outline">
                    <input type="text" name="username" id="username" class="form-input" placeholder="Username" required autocomplete="username">
                    <span class="field-icon user-icon"><i class="bi bi-person-fill"></i></span>
                </div>

                <div class="mb-4 form-outline">
                    <input type="password" name="password" id="password" class="form-input" placeholder="Password" required autocomplete="current-password">
                    <!-- Toggle Button -->
                    <button type="button" class="field-icon btn-icon" id="togglePassword" aria-label="Toggle password visibility">
                        <i class="bi bi-eye-fill" id="toggleIcon"></i>
                    </button>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn-submit w-100">Sign In</button>
                </div>
                <div class="card-footer text-center"> 
                    <small class="text-muted">EXpresso &copy; <?php echo date("Y"); ?></small>
                </div>
            </form>
        </div>
    </section>
</main>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS for Eye Icon -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        if (toggleBtn && passwordInput && toggleIcon) {
            toggleBtn.addEventListener('click', function() {
                // Check current type
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Toggle Icon
                if (type === 'text') {
                    toggleIcon.classList.remove('bi-eye-fill');
                    toggleIcon.classList.add('bi-eye-slash-fill');
                } else {
                    toggleIcon.classList.remove('bi-eye-slash-fill');
                    toggleIcon.classList.add('bi-eye-fill');
                }
            });
        }
    });
</script>
</body>
</html>