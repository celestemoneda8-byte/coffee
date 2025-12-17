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

// Map friendly category names to exact database category names
$categoryMap = [
    'cappuccino' => 'Cappuccino',
    'espresso' => 'Espresso',
    'mocha' => 'Mocha',
    'latte' => 'Latte',
    'ice-coffee' => 'Ice Coffee',
    'americano' => 'Americano'
];

// Convert friendly URL param to exact category name
if (isset($categoryMap[$selectedCategory])) {
    $selectedCategory = $categoryMap[$selectedCategory];
}
?>

<main id="menu-page">
    <h2 class="text-center mb-4">Order Your Coffee</h2>

    <!-- CATEGORY FILTER -->
    <div class="d-flex justify-content-center gap-2 mb-4 flex-wrap">
        <button class="btn filter-btn active" data-category="all">All</button>
        <button class="btn filter-btn" data-category="Cappuccino">Cappuccino</button>
        <button class="btn filter-btn" data-category="Espresso">Espresso</button>
        <button class="btn filter-btn" data-category="Mocha">Mocha</button>
        <button class="btn filter-btn" data-category="Latte">Latte</button>
        <button class="btn filter-btn" data-category="Ice Coffee">Ice Coffee</button>
        <button class="btn filter-btn" data-category="Americano">Americano</button>
    </div>

    <!-- COFFEE LIST -->
    <!-- NOTE: Use .row inside container. Removed the extra 'container' class here to avoid double spacing. -->
    <div id="coffee-list"></div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const searchQuery = <?php echo json_encode($searchQuery); ?>;
</script>
<script src="../js/main.js"></script>
<?php require_once 'footer.php'; ?>
</body>
</html>