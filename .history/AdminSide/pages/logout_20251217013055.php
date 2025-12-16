<?php
/**
 * Logout Script
 */
session_start();
session_unset();
session_destroy();

header('Location: /EXpresso/coffee/AdminSide/includes/admin-login.php');
exit;
?>
