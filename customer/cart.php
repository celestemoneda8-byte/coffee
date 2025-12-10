<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cart | Expresso Café</title>
<link rel="stylesheet" href="css/cart.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>
<?php require_once 'header_nav.php'; ?>

<main id="cart-page">
    <h2 class="text-center">My Cart</h2>

    <!-- Cart container specific for cart page only -->
    <div id="cart-page-items"></div>

    <div class="d-flex justify-content-center">
        <button class="btn btn-success mb-3 px-4" style="width: 300px;" onclick="checkoutSelected()">
            Checkout Selected
        </button>
    </div>
    <h4 class="mt-3">Total: ₱<span id="total">0.00</span></h4>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>

<?php require_once "footer.php"; ?>
</body>
</html>