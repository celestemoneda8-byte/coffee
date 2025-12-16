<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rider Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
    <form id="loginForm">
        <h2>Rider Login</h2>
        <input type="email" name="email" placeholder="Email" required><br>

        <div class="password-wrapper">
            <input type="password" name="password" placeholder="Password" id="loginPassword" required>
            <span class="toggle-password" onclick="togglePassword('loginPassword')">&#128065;</span>
        </div>

        <button type="submit">Login</button>
        <p id="message"></p>
        <p class="switch-page">Don't have an account? <a href="register.php">Register</a></p>
    </form>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        const form = document.getElementById('loginForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = Object.fromEntries(new FormData(form).entries());

            const res = await fetch('api/rider_auth/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const data = await res.json();
            document.getElementById('message').innerText = data.message;
            if(data.status === 'success') window.location.href = 'dashboard.php';
        });
    </script>
</body>
</html>
