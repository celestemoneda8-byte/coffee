<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$loggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/globa.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<!--TOP NAVBAR -->
<header>
    <nav class="navbar-menu">
        <ul>
            <li class="left-area">
                <button id="toggle-btn-menu"><i class="bi bi-list"></i></button>
                <h1 class="brand-title">EXpresso Caffe</h1>
            </li>

            <li class="right-area">
                <div>
                  <form action="menu.php" method="get" style="display:inline-block; margin:0;">
                    <input type="text" name="q" placeholder="Search..." id="search-input" value="<?php echo isset($_GET['q'])?htmlspecialchars($_GET['q']):''; ?>">
                    <button type="submit" id="search-btn">Search</button>
                  </form>
                </div>
                
                <button id="icon-cart" style="position: relative;">
                    <i class="bi bi-cart3"></i>
                    <span id="cart-count" class="cart-badge" style="display:none;">0</span>
                </button>

            </li>
        </ul>
    </nav>
</header>

<!-- SIDEBAR -->
<nav id="sidebar">
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="menu.php">Menu</a></li>
        <li><a href="order.php">Orders</a></li>
        <li><a href="cart.php">Cart</a></li>
        <li><a href="account.php">Account</a></li>
    </ul>
</nav>

<!-- CART PREVIEW DROPDOWN -->
<div id="cart-preview" style="display:none; position:fixed; top:80px; right:10px; width:350px; max-height:400px; background:#fff; border:1px solid #ddd; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:500; overflow-y:auto;">
    <div style="padding:15px; border-bottom:1px solid #eee;">
        <h5 style="margin:0;">Cart Preview</h5>
    </div>
    <div id="cart-preview-body" style="padding:15px; min-height:100px;">
        <p class="text-center text-muted">Your cart is empty</p>
    </div>
    <div style="padding:15px; border-top:1px solid #eee; background:#f9f9f9; text-align:center;">
        <strong>Total: <span id="cart-preview-total">₱0.00</span></strong><br>
        <a href="cart.php" class="btn btn-sm btn-primary mt-2" style="width:100%;">View Cart</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/sidebah.js"></script>
<script src="../js/header_nav.js"></script>
<script>
  // update cart count on page load
  async function refreshCartCount() {
    try {
      const res = await fetch('api/cart/get_cart_count.php');
      const j = await res.json();
      const badge = document.getElementById('cart-count');
      if (j.status === 'success' && j.count > 0) {
        badge.style.display = 'flex';
        badge.innerText = j.count;
      } else {
        badge.style.display = 'none';
      }
    } catch (e) {
      console.error('Could not fetch cart count', e);
    }
  }
  refreshCartCount();
  // optional: refresh periodically or after actions
</script>
</body>
</html>