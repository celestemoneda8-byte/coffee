<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';

// Protect page
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: ../includes/admin-login.php');
    exit;
}

// Fetch categories
$categories = [];
$res = $conn->query("SELECT id, name, slug FROM categories ORDER BY name ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) $categories[] = $r;
}

// Fetch products with category name
$products = [];
$sql = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC";
$res = $conn->query($sql);
if ($res) {
    while ($r = $res->fetch_assoc()) $products[] = $r;
}

// Summary statistics (counts)
$totalCategories = 0;
$totalProducts = 0;
$uncategorized = 0;

$res = $conn->query("SELECT COUNT(*) AS cnt FROM categories");
if ($res) $totalCategories = (int)$res->fetch_assoc()['cnt'];

$res = $conn->query("SELECT COUNT(*) AS cnt FROM products");
if ($res) $totalProducts = (int)$res->fetch_assoc()['cnt'];

$res = $conn->query("SELECT COUNT(*) AS cnt FROM products WHERE category_id IS NULL OR category_id = 0");
if ($res) $uncategorized = (int)$res->fetch_assoc()['cnt'];

// Recent products (5)
$recentProducts = [];
$res = $conn->query("SELECT id, name, price, created_at FROM products ORDER BY created_at DESC LIMIT 5");
if ($res) {
    while ($r = $res->fetch_assoc()) $recentProducts[] = $r;
}

$currentUser = htmlspecialchars($_SESSION['username'] ?? 'Admin');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Menu Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  
  <!-- Admin Layout Styles -->
  <link rel="stylesheet" href="../css/admin-sidebar-layout.css">
</head>
<body>
  <div class="d-flex min-vh-100">
    
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-fill p-4">
      <div class="container-fluid">
        
        <!-- HEADER with user badge -->
        <?php include 'header.php'; ?>

    <?php if (isset($_SESSION['flash_msg'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['flash_msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Left Column: Stats & Categories -->
      <div class="col-md-4">
        <!-- Statistics -->
        <div class="card mb-3">
          <div class="card-body">
            <h6 class="card-title mb-3">Statistics</h6>
            <div class="d-flex justify-content-between mb-2">
              <span><i class="bi bi-tags me-2"></i>Categories</span>
              <strong><?php echo number_format($totalCategories); ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span><i class="bi bi-box-seam me-2"></i>Products</span>
              <strong><?php echo number_format($totalProducts); ?></strong>
            </div>
            <div class="d-flex justify-content-between">
              <span><i class="bi bi-question-circle me-2"></i>Uncategorized</span>
              <strong><?php echo number_format($uncategorized); ?></strong>
            </div>
          </div>
        </div>

        <!-- Add Category -->
        <div class="card mb-3">
          <div class="card-body">
            <h6 class="card-title mb-3">Add Category</h6>
            <form action="menu_actions.php" method="POST">
              <input type="hidden" name="action" value="add_category">
              <div class="mb-3">
                <input type="text" name="category_name" class="form-control" placeholder="Category name" required>
              </div>
              <button class="btn btn-primary w-100" type="submit">Add Category</button>
            </form>
          </div>
        </div>

        <!-- Existing Categories -->
        <div class="card">
          <div class="card-body">
            <h6 class="card-title mb-3">Existing Categories</h6>
            <ul class="list-group list-group-flush">
              <?php if (empty($categories)): ?>
                <li class="list-group-item text-muted">No categories yet.</li>
              <?php else: foreach ($categories as $c): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span><?php echo htmlspecialchars($c['name']); ?></span>
                  <form method="POST" action="menu_actions.php" onsubmit="return confirm('Delete this category?');" class="d-inline">
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="category_id" value="<?php echo (int)$c['id']; ?>">
                    <button type="submit" name="force_delete" value="0" class="btn btn-sm btn-outline-danger">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </li>
              <?php endforeach; endif; ?>
            </ul>
          </div>
        </div>
      </div>

      <!-- Right Column: Products -->
      <div class="col-md-8">
        <!-- Add Product Form -->
        <div class="card mb-3">
          <div class="card-body">
            <h6 class="card-title mb-3">Add Product</h6>
            <form action="menu_actions.php" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="action" value="add_product">
              <div class="row g-3">
                <div class="col-md-7">
                  <label class="form-label">Product Name</label>
                  <input type="text" name="product_name" class="form-control" placeholder="Product name" required>
                </div>
                <div class="col-md-5">
                  <label class="form-label">Price (₱)</label>
                  <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Category</label>
                  <select name="category_id" class="form-select">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $c): ?>
                      <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label">Description</label>
                  <textarea name="description" class="form-control" rows="3" placeholder="Short description (optional)"></textarea>
                </div>
                <div class="col-12">
                  <label class="form-label">Product Image</label>
                  <input type="file" name="image" accept="image/*" class="form-control">
                </div>
                <div class="col-12 text-end">
                  <button class="btn btn-primary" type="submit">Add Product</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Products Table -->
        <div class="card">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="ps-4">ID</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Image</th>
                    <th class="text-end pe-4">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($products)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No products found.</td></tr>
                  <?php else: foreach ($products as $p): ?>
                    <tr>
                      <td class="ps-4"><?php echo (int)$p['id']; ?></td>
                      <td>
                        <div class="fw-bold"><?php echo htmlspecialchars($p['name']); ?></div>
                        <?php if (!empty($p['description'])): ?>
                          <div class="small text-muted"><?php echo htmlspecialchars($p['description']); ?></div>
                        <?php endif; ?>
                      </td>
                      <td><?php echo htmlspecialchars($p['category_name'] ?? 'Uncategorized'); ?></td>
                      <td class="fw-bold">₱<?php echo number_format($p['price'], 2); ?></td>
                      <td>
                        <?php if (!empty($p['image']) && file_exists(__DIR__ . '/uploads/products/' . $p['image'])): ?>
                          <img src="<?php echo 'uploads/products/' . htmlspecialchars($p['image']); ?>" 
                               alt="<?php echo htmlspecialchars($p['name']); ?>" 
                               style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end pe-4">
                        <form method="POST" action="menu_actions.php" onsubmit="return confirm('Delete this product?');" class="d-inline">
                          <input type="hidden" name="action" value="delete_product">
                          <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                          <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

        <!-- FOOTER -->
        <?php include 'footer.php'; ?>

      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>