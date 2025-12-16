<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Expresso Café</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
<link rel="stylesheet" href="../css/login-register.css">
</head>

<body>
<div class="container-fluid d-flex align-items-center justify-content-center vh-100">
  <div class="wrapper">

    <div class="left-container">
      <h2>Welcome to Expresso Coffee</h2>
      <p>Start your day with a perfect cup delivered fast.</p>
    </div>

    <form class="signup-form shadow-lg" id="loginForm">
      <h2 class="mb-2 text-center">Login</h2>
      <div id="alertBox"></div>

      <div class="signup-box">
        <input type="email" id="email" name="email" placeholder=" " required>
        <label for="email">Email</label>
        <ion-icon name="person-circle-outline"></ion-icon>
      </div>

      <div class="signup-box">
        <input type="password" id="password" name="password" placeholder=" " required>
        <label for="password">Password</label>
        <ion-icon id="togglePassword" name="eye-off-outline"></ion-icon>
      </div>

      <button type="submit" class="btn w-100 btn-login">Login</button>

      <p class="mt-3 text-center">Don't have an account? 
        <a href="register.php">Register</a>
      </p>
    </form>

  </div>
</div>

<script>
document.getElementById("togglePassword").addEventListener("click", function (){
  const pw = document.getElementById("password");
  pw.type = pw.type === "password" ? "text" : "password";
  this.setAttribute("name", pw.type === "password" ? "eye-off-outline" : "eye-outline");
});

document.getElementById("loginForm").addEventListener("submit", async (e) => {
  e.preventDefault();

  const payload = {
    action: "login",
    email: email.value,
    password: password.value
  };

  const res = await fetch("api/auth/validate.php", {
    method: "POST",
    headers: {"Content-Type":"application/json"},
    body: JSON.stringify(payload)
  });

  const data = await res.json();
  document.getElementById("alertBox").innerHTML =
    `<div class="alert ${data.success?'alert-success':'alert-danger'}">${data.message}</div>`;

  if (data.success) {
    setTimeout(() => window.location.href = "index.php", 800);
  }
});
</script>

</body>
</html>