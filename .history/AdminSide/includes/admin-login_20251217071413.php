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
    <title>Admin Login - Expresso Café</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --admin-primary: #3e2723;
            --admin-secondary: #5d4037;
            --admin-accent: #d4a574;
            --admin-text-light: #f5e6d8;
            --admin-text-dark: #3e2723;
            --admin-success: #28a745;
            --admin-danger: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-secondary) 100%);
            padding: 20px;
        }

        .auth-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            padding: 45px 40px;
            width: 100%;
            max-width: 420px;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            height: 90px;
            margin-bottom: 12px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
        }

        .logo h1 {
            font-size: 1.6rem;
            color: var(--admin-primary);
            font-weight: 700;
            margin: 0;
        }

        .logo span {
            color: var(--admin-accent);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .auth-card h2 {
            text-align: center;
            color: var(--admin-primary);
            margin-bottom: 28px;
            font-weight: 600;
            font-size: 1.4rem;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--admin-text-dark);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 15px 18px;
            padding-right: 50px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-group input:focus {
            border-color: var(--admin-accent);
            outline: none;
            box-shadow: 0 0 0 4px rgba(212, 165, 116, 0.2);
            background: #fff;
        }

        .form-group input::placeholder {
            color: #aaa;
        }

        .input-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.2rem;
        }

        .toggle-password {
            cursor: pointer;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: var(--admin-accent);
        }

        .btn-primary {
            width: 100%;
            padding: 15px;
            background: var(--admin-primary);
            color: var(--admin-text-light);
            border: none;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary:hover {
            background: var(--admin-secondary);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(62, 39, 35, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .auth-message {
            text-align: center;
            margin-bottom: 20px;
            padding: 12px 16px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .auth-message.error {
            background: #fde8e8;
            color: var(--admin-danger);
            border: 1px solid #f5c6cb;
        }

        .auth-message.success {
            background: #d4edda;
            color: var(--admin-success);
            border: 1px solid #c3e6cb;
        }

        .footer-text {
            text-align: center;
            margin-top: 25px;
            color: #888;
            font-size: 0.85rem;
        }

        .footer-text a {
            color: var(--admin-accent);
            text-decoration: none;
            font-weight: 600;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .auth-card {
                padding: 35px 25px;
            }
            
            .logo img {
                height: 70px;
            }
            
            .logo h1 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="logo">
            <img src="../../customer/images/expresso-logo.png" alt="Expresso Logo">
            <h1>EXpresso Caffe</h1>
            <span>Admin Portal</span>
        </div>
        
        <h2>Welcome Back, Admin!</h2>
        
        <?php if ($error): ?>
            <div class="auth-message error">
                <i class="bi bi-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" novalidate>
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" placeholder="Enter your username" required autocomplete="username">
                    <span class="input-icon"><i class="bi bi-person"></i></span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    <span class="input-icon toggle-password" onclick="togglePassword()">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </span>
                </div>
            </div>
            
            <button type="submit" class="btn-primary">
                <i class="bi bi-box-arrow-in-right"></i> Sign In
            </button>
        </form>
        
        <p class="footer-text">
            EXpresso Café &copy; <?php echo date("Y"); ?><br>
            <a href="../../customer/pages/index.php">← Back to Website</a>
        </p>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>