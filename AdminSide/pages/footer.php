<?php
/**
 * Admin Footer Component
 * Reusable footer for admin panel
 */

$currentYear = date('Y');
?>

<footer class="admin-footer mt-5 pt-4 pb-3 border-top">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
        <span class="text-muted">
          <i class="bi bi-cup-hot me-1"></i>
          <strong>EXpresso</strong> Admin Panel
        </span>
      </div>
      
      <div class="col-md-6 text-center text-md-end">
        <span class="text-muted small">
          &copy; <?php echo $currentYear; ?> EXpresso. All rights reserved. 
        </span>
      </div>
    </div>
    
    <div class="row mt-2">
      <div class="col-12 text-center">
        <small class="text-muted">
          Version 1.0 | Last updated: <?php echo date('F j, Y'); ?>
        </small>
      </div>
    </div>
  </div>
</footer>