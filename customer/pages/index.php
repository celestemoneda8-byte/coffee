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
</head>

<body>

<header>
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">

      <a class="navbar-brand navbar-brand-custom" href="index.php">☕ EXpresso Caffe</a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-2">
          <li class="nav-item"><a class="nav-btn btn" href="index.php">Home</a></li>
          <li class="nav-item"><a class="nav-btn btn" href="menu.php">Menu</a></li>
          <li class="nav-item"><a class="nav-btn btn" href="footer.php">About Us</a></li>
          <li class="nav-item"><a class="nav-btn btn" href="footer.php">Contact</a></li>
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

    <!-- FIRST SLIDE (Logo + tagline + button) -->
    <div class="carousel-item active">
      <img src="../images/expresso-background.jpg" class="d-block w-100" style="height: 93vh; object-fit: cover;">

      <div class="carousel-caption d-flex flex-column justify-content-center align-items-start"
           style="top: 50%; transform: translateY(-50%); text-align: left;">

        <img src="../images/expresso-logo.png" class="home-img mb-1" style="width: 700px;">

        <p class="home-tagline fs-4 mb-3">
          “Want a productive day? Get a shot of Expresso Coffee.”
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

    <!-- SECOND SLIDE (Blank for now, you can change image) -->
    <div class="carousel-item">
      <img src="../images/carousel-image2.jpg" class="d-block w-100" style="height: 93vh; object-fit: cover;">
    </div>

    <!-- THIRD SLIDE (Blank for now, you can change image) -->
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