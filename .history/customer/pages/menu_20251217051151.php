<?php
// Load saved cart
session_start();
require_once "../config/db_connect.php";
if(!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}
$cart = $_SESSION['cart'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/menu.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <title>Menu | EXpresso Caffe</title>
</head>

<body>

<?php require_once 'header_nav.php'; ?>

<?php
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$selectedCategory = isset($_GET['category']) ? trim($_GET['category']) : 'all';

// Normalize to lowercase for consistency with JavaScript
$selectedCategory = strtolower($selectedCategory);
?>

<main id="menu-page">
    <h2 class="text-center mb-4">Order Your Coffee</h2>

    <!-- CATEGORY FILTER -->
    <div class="d-flex justify-content-center gap-2 mb-4 flex-wrap">
        <button class="btn filter-btn <?= $selectedCategory === 'all' ? 'active' : '' ?>" data-category="all">All</button>
        <button class="btn filter-btn <?= $selectedCategory === 'cappuccino' ? 'active' : '' ?>" data-category="cappuccino">Cappuccino</button>
        <button class="btn filter-btn <?= $selectedCategory === 'espresso' ? 'active' : '' ?>" data-category="espresso">Espresso</button>
        <button class="btn filter-btn <?= $selectedCategory === 'mocha' ? 'active' : '' ?>" data-category="mocha">Mocha</button>
        <button class="btn filter-btn <?= $selectedCategory === 'latte' ? 'active' : '' ?>" data-category="latte">Latte</button>
        <button class="btn filter-btn <?= $selectedCategory === 'ice coffee' ? 'active' : '' ?>" data-category="ice coffee">Ice Coffee</button>
        <button class="btn filter-btn <?= $selectedCategory === 'americano' ? 'active' : '' ?>" data-category="americano">Americano</button>
    </div>

    <!-- COFFEE LIST -->
    <!-- NOTE: Use .row inside container. Removed the extra 'container' class here to avoid double spacing. -->
    <div id="coffee-list"></div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const searchQuery = <?php echo json_encode($searchQuery); ?>;
  const selectedCategory = <?php echo json_encode($selectedCategory); ?>;
</script>
<script src="../js/main.js"></script>
<script>
  // Auto-select category from URL parameter
  document.addEventListener('DOMContentLoaded', function() {
    if (selectedCategory && selectedCategory !== 'all') {
      const categoryBtn = document.querySelector(`[data-category="${selectedCategory}"]`);
      if (categoryBtn) {
        // Ensure products are loaded first
        setTimeout(() => {
          categoryBtn.click();
        }, 100);
      }
    }
  });
</script>
<?php require_once 'footer.php'; ?>
</body>
</html>