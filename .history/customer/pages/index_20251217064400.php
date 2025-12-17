<?php
session_start(); 
require_once "../config/db_connect.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome to Expresso Café</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/index.css">
  <link rel="stylesheet" href="../css/foot.css">
</head>

<body>

<header>
  <nav class="navbar navbar-expand-lg navbar-menu" style="padding: 12px 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.15);">
    <div class="container-fluid">

      <a class="navbar-brand navbar-brand-custom d-flex align-items-center gap-2" href="index.php">
        <img src="../images/expresso-logo.png" alt="Expresso Logo" style="height: 55px; width: auto;">
        <span style="font-weight: 700; font-size: 1.3rem;">EXpresso Caffe</span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-2">
          <li class="nav-item"><a class="nav-btn btn nav-link" href="index.php">Home</a></li>
          <li class="nav-item"><a class="nav-btn btn nav-link" href="menu.php">Menu</a></li>
          <li class="nav-item"><a class="nav-btn btn nav-link" href="#contact">About Us</a></li>
          <li class="nav-item"><a class="nav-btn btn nav-link" href="#contact">Contact</a></li>
        </ul>

        <a href="<?= isset($_SESSION['customer_id']) ? 'logout.php' : 'login.php'; ?>" class="btn-login text-decoration-none">
          <?= isset($_SESSION['customer_id']) ? 'Logout' : 'Login'; ?>
        </a>


      </div>
    </div>
  </nav>
</header>
<!-- HOME CAROUSEL -->
<div id="homeCarousel" class="carousel slide" data-bs-ride="carousel">

  <div class="carousel-inner">

    <!-- FIRST SLIDE (tagline + button on left side, no logo) -->
    <div class="carousel-item active">
      <img src="../images/expresso-background.jpg" class="d-block w-100" style="height: 93vh; object-fit: cover;">

      <div class="carousel-caption d-flex flex-column align-items-start justify-content-center"
           style="top: 50%; left: 8%; transform: translateY(-50%); text-align: left; max-width: 450px;">

        <h1 class="home-title mb-3" style="font-size: 2.5rem; font-weight: 700; color: #3e2723; line-height: 1.2;">
          Want a productive day?
        </h1>
        
        <p class="home-tagline mb-4" style="font-size: 1.3rem; color: #5d4037;">
          Get a shot of Expresso Coffee.
        </p>

        <a href="<?= isset($_SESSION['customer_id']) ? 'menu.php' : 'register.php' ?>" id="orderNowBtn" class="cssbuttonsIoButton text-decoration-none">
          Order Now!
          <div class="icon">
            <svg height="24" width="24" viewBox="0 0 24 24">
              <path d="M16.172 11l-5.364-5.364 1.414-1.414L20 12l-7.778 7.778-1.414-1.414L16.172 13H4v-2z"
                fill="currentColor"></path>
            </svg>
          </div>
        </a>

      </div>
    </div>

    <!-- SECOND SLIDE -->
    <div class="carousel-item">
      <img src="../images/carousel-image2.jpg" class="d-block w-100" style="height: 93vh; object-fit: cover;">
    </div>

    <!-- THIRD SLIDE -->
    <div class="carousel-item">
      <img src="../images/carousel-image3.jpg" class="d-block w-100" style="height: 93vh; object-fit: cover;">
    </div>

  </div>

  <!-- CONTROLS -->
  <button class="carousel-control-prev" type="button" data-bs-target="#homeCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>

  <button class="carousel-control-next" type="button" data-bs-target="#homeCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
  </button>

</div>
<!-- MENU SECTION -->
<section id="menu" class="menu-section mt-5 mb-5">

  <div class="text-center">
    <h2 class="menu-title-custom">Our Menu Categories</h2>
    <p class="menu-subtitle">Choose a category you love to explore</p>
  </div>

  <!-- Categories (unchanged) -->
  <div class="menu-categories d-flex justify-content-center flex-wrap gap-4 mb-5">
    <!-- SAME AS YOUR HTML -->
    <div class="menu-item text-center category-btn" data-category="cappuccino">
      <img src="../images/cappuccino.jpg"><p>Cappuccino</p>
    </div>

    <div class="menu-item text-center category-btn" data-category="espresso">
      <img src="../images/espresso.jpg"><p>Espresso</p>
    </div>

    <div class="menu-item text-center category-btn" data-category="mocha">
      <img src="../images/mocha.jpg"><p>Mocha</p>
    </div>

    <div class="menu-item text-center category-btn" data-category="latte">
      <img src="../images/latte.jpg"><p>Latte</p>
    </div>

    <div class="menu-item text-center category-btn" data-category="ice-coffee">
      <img src="../images/ice-coffee.jpg"><p>Ice Coffee</p>
    </div>

    <div class="menu-item text-center category-btn" data-category="americano">
      <img src="../images/americano.jpg"><p>Americano</p>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Handle category button clicks to navigate to menu with selected category
document.querySelectorAll(".category-btn").forEach(btn => {
  btn.addEventListener("click", function() {
    const category = this.getAttribute("data-category");
    // Redirect to menu page with category parameter
    window.location.href = `menu.php?category=${encodeURIComponent(category)}`;
  });
});
</script>
<?php require_once 'footer.php'; ?>
</body>
</html>