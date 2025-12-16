<?php
/**
 * Admin Header Component
 * Reusable header for admin panel with user badge
 */

// Get current user from session (fallback to 'admin')
$currentUser = $_SESSION['username'] ?? 'admin';
$userInitial = strtoupper($currentUser[0] ?? 'A');

// Optional: page title can be passed as variable
$pageTitle = $pageTitle ?? 'Dashboard';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="page-title mb-0"><?php echo htmlspecialchars($pageTitle); ?></h2>
  
  <div class="user-badge">
    <div class="avatar"><?php echo htmlspecialchars($userInitial); ?></div>
    <div class="ms-2 small text-muted"><?php echo htmlspecialchars($currentUser); ?></div>
  </div>
</div>