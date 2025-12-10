<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rider Register</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
    <form id="registerForm">
        <h2>Rider Registration</h2>
        <input type="text" name="rider_name" placeholder="Full Name" required><br>
        <input type="email" name="email" placeholder="Email" required><br>

        <div class="password-wrapper">
            <input type="password" name="password" placeholder="Password" id="registerPassword" required>
            <span class="toggle-password" onclick="togglePassword('registerPassword')">&#128065;</span>
        </div>

        <input type="text" name="phone" placeholder="Phone"><br>
        <button type="submit">Register</button>
        <p id="message"></p>
        <p class="switch-page">Already have an account? <a href="login.php">Login</a></p>
    </form>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        const form = document.getElementById('registerForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = Object.fromEntries(new FormData(form).entries());

            const res = await fetch('api/rider_auth/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const data = await res.json();
            document.getElementById('message').innerText = data.message;
            if(data.status === 'success') form.reset();
        });
    </script>
</body>
</html>