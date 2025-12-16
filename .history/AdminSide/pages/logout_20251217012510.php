<?php
/**
 * Logout Script
 */
session_start();
session_unset();
session_destroy();

header('Location: ../includes/admin-login.php');
exit;
?>
