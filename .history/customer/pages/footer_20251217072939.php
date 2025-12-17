<?php
// Load website settings if not already loaded
if (!isset($website_name)) {
    $website_name = 'EXpresso Caffe';
    $website_logo = '../images/expresso-logo.png';
    
    try {
        if (!isset($conn)) {
            require_once __DIR__ . '/../config/db_connect.php';
        }
        if (isset($conn) && $conn) {
            $footerSettings = $conn->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('website_name', 'website_logo')");
            if ($footerSettings) {
                while ($row = $footerSettings->fetch_assoc()) {
                    if ($row['setting_key'] === 'website_name' && !empty($row['setting_value'])) {
                        $website_name = $row['setting_value'];
                    }
                    if ($row['setting_key'] === 'website_logo' && !empty($row['setting_value'])) {
                        $website_logo = '../../AdminSide/assets/uploads/' . $row['setting_value'];
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Use defaults
    }
}
?>
<footer id="contact" class="footer">
  <div class="footer-container">

    <div class="row">

      <!-- Brand -->
      <div class="col-md-4 footer-section">
        <h4 class="footer-title">
          <img src="<?= htmlspecialchars($website_logo) ?>" alt="Logo" style="height: 45px; width: auto;">
          <?= htmlspecialchars($website_name) ?>
        </h4>
        <p>
          Bringing premium coffee and fast delivery straight to your door.  
          Every cup is brewed to spark productivity.
        </p>
        <div class="footer-social">
          <a href="#" class="social-icon" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="#" class="social-icon" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="#" class="social-icon" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
          <a href="mailto:info@expressocaffe.com" class="social-icon" aria-label="Email"><i class="bi bi-envelope"></i></a>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="col-md-4 footer-section">
        <h5 class="footer-subtitle">Quick Links</h5>
        <ul class="footer-links">
          <li><a href="index.php" class="footer-link">Home</a></li>
          <li><a href="menu.php" class="footer-link">Menu</a></li>
          <li><a href="order.php" class="footer-link">My Orders</a></li>
          <li><a href="cart.php" class="footer-link">Cart</a></li>
          <li><a href="account.php" class="footer-link">My Account</a></li>
        </ul>
      </div>

      <!-- Contact Info -->
      <div class="col-md-4 footer-section text-center">
        <h5 class="footer-subtitle">Contact Us</h5>
        <p><i class="bi bi-geo-alt-fill me-2"></i>123 Brew Street, Manila, Philippines</p>
        <p><i class="bi bi-telephone-fill me-2"></i>+63 912 345 6789</p>
        <p><i class="bi bi-envelope-fill me-2"></i>info@expressocaffe.com</p>
        <p><i class="bi bi-clock-fill me-2"></i>Mon – Sun: 7:00 AM – 9:00 PM</p>
      </div>

    </div>

    <hr class="footer-divider">

    <p class="footer-bottom text-center">
      © <?php echo date('Y'); ?> <?= htmlspecialchars($website_name) ?>. All rights reserved.
    </p>

  </div>
</footer>