<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | Expresso Café</title>
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
      <p>Start your day right with a perfect cup of coffee — fresh, fast, and delivered straight to your door.</p>
    </div>

    <form class="signup-form" id="registerForm">
      <h2>Sign Up</h2>
      <div id="alertBox"></div>

      <div class="signup-box">
        <input type="text" id="fullname" name="fullname" placeholder=" " required>
        <label for="fullname">Fullname</label>
        <ion-icon name="person-circle-outline"></ion-icon>
      </div>

      <div class="signup-box">
        <input type="email" id="email" name="email" placeholder=" " required>
        <label for="email">Email</label>
        <ion-icon name="mail-outline"></ion-icon>
      </div>

      <div class="signup-box">
        <input type="password" id="password" name="password" placeholder=" " required>
        <label for="password">Password</label>
        <ion-icon id="togglePassword" name="eye-off-outline"></ion-icon>
      </div>

      <div class="signup-box">
        <input type="password" id="conpass" name="conpass" placeholder=" " required>
        <label for="conpass">Confirm Password</label>
        <ion-icon id="togglePassword2" name="eye-off-outline"></ion-icon>
      </div>

      <button type="submit" class="btn w-100">Submit</button>
      <p class="mt-2">Already have an account? <a href="login.php">Login</a></p>
    </form>

  </div>
</div>

<script>
// Toggle password
document.getElementById("togglePassword").addEventListener("click", function (){
  const pw = document.getElementById("password");
  pw.type = pw.type === "password" ? "text" : "password";
  this.setAttribute("name", pw.type === "password" ? "eye-off-outline" : "eye-outline");
});
document.getElementById("togglePassword2").addEventListener("click", function (){
  const pw = document.getElementById("conpass");
  pw.type = pw.type === "password" ? "text" : "password";
  this.setAttribute("name", pw.type === "password" ? "eye-off-outline" : "eye-outline");
});

// Register submit
document.getElementById("registerForm").addEventListener("submit", async (e) => {
  e.preventDefault();

  const payload = {
    action: "register",
    fullname: fullname.value,
    email: email.value,
    password: password.value,
    conpass: conpass.value
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
    setTimeout(() => window.location.href = "index.php", 1500);
  }
});
</script>

</body>
</html>