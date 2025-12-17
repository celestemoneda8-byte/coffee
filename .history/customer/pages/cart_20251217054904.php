<?php
session_start();
if(!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cart | Expresso Café</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="../css/cart.css">
</head>

<body>
<?php require_once 'header_nav.php'; ?>

<main id="cart-page">
    <h2 class="text-center mb-4">My Cart</h2>

    <!-- Cart container specific for cart page only -->
    <div id="cart-page-items">
        <div class="text-center text-muted py-4">Loading cart...</div>
    </div>

    <div class="cart-actions mt-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <a href="menu.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Continue Shopping
            </a>
            <div class="d-flex align-items-center gap-3">
                <h4 class="mb-0">Total: ₱<span id="total">0.00</span></h4>
                <button class="btn btn-success px-4" onclick="checkoutSelected()">
                    <i class="bi bi-bag-check me-2"></i>Checkout Selected
                </button>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>

<?php require_once "footer.php"; ?>
</body>
</html>