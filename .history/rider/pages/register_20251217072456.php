<?php
session_start();
// If already logged in, redirect to dashboard
if (isset($_SESSION['rider_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Define theme section for rider
if (!defined('THEME_SECTION')) {
    define('THEME_SECTION', 'rider');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Registration - Expresso Café</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/rider.css">
    
    <!-- Dynamic Theme Colors -->
    <?php include __DIR__ . '/../../includes/theme_styles.php'; ?>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="logo">
                <img src="../../customer/images/expresso-logo.png" alt="Expresso Logo">
                <h1>EXpresso Caffe</h1>
                <span>Rider Portal</span>
            </div>
            
            <h2>Join Our Delivery Team!</h2>
            
            <form id="registerForm">
                <div class="form-group">
                    <label for="rider_name">Full Name</label>
                    <input type="text" id="rider_name" name="rider_name" placeholder="Enter your full name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Create a password" required minlength="6">
                        <span class="toggle-password" onclick="togglePassword()">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="bi bi-person-plus"></i> Register
                </button>
                
                <div id="message" class="auth-message" style="display: none;"></div>
            </form>
            
            <p class="auth-switch">
                Already have an account? <a href="login.php">Sign in here</a>
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.querySelector('.toggle-password i');
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

        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const messageDiv = document.getElementById('message');
            const formData = {
                rider_name: document.getElementById('rider_name').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                password: document.getElementById('password').value
            };
            
            try {
                const res = await fetch('../api/rider_auth/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                const data = await res.json();
                
                messageDiv.style.display = 'block';
                messageDiv.textContent = data.message;
                
                if (data.status === 'success') {
                    messageDiv.className = 'auth-message success';
                    document.getElementById('registerForm').reset();
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 1500);
                } else {
                    messageDiv.className = 'auth-message error';
                }
            } catch (error) {
                messageDiv.style.display = 'block';
                messageDiv.className = 'auth-message error';
                messageDiv.textContent = 'Connection error. Please try again.';
            }
        });
    </script>
</body>
</html>