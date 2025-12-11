<?php
$password = "password123";
$hashed = password_hash($password, PASSWORD_DEFAULT);
echo "Hashed Password: " . $hashed;
?>