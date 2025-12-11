<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

// Protect page
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: admin-login.php');
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
$current = basename($_SERVER['PHP_SELF']);
function nav_active($href, $current) {
    return ($href === $current) ? 'nav-link active' : 'nav-link';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Menu Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="dashboard.css">
  <style>
    /* Admin dashboard background style + adjustments requested */
    :root{
      --accent: #7f5539;
      --muted: #7b6a58;
      --panel-bg: #ffffff;
      --btn-gray-hover: #bfbfbf;
      --btn-gray-active: #9e9e9e;
    }

    body.page-bg {
      background: linear-gradient(135deg, #f6e9d6 0%, #e6ccb2 100%);
      min-height: 100vh;
      font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      color: #33221a;
    }

    /* Sidebar */
    .sidebar { min-height: 100vh; width: 220px; }
    .sidebar .brand { font-weight: 800; color: #7f5539; font-size: 1.15rem; }
    .sidebar-nav a.nav-link { color: #6b4e3a; padding: 10px 14px; }
    .sidebar .nav-link.active {
      background: linear-gradient(90deg, #e7d4c6, #dec0ad);
      color: #3b2418;
      border-radius: 6px;
      box-shadow: 0 6px 16px rgba(127,85,57,0.06);
    }

    /* Panel/card look */
    .panel {
      background: var(--panel-bg);
      border-radius: 10px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.04);
      border: 1px solid rgba(0,0,0,0.03);
    }

    .thumb {
      width:72px;
      height:72px;
      object-fit:cover;
      border-radius:8px;
      border:1px solid rgba(0,0,0,0.04);
    }

    .stat-compact {
      background: rgba(255,255,255,1);
      border-radius: 8px;
      padding: 12px;
      display:flex;
      align-items:center;
      gap:12px;
      border: 1px solid rgba(0,0,0,0.03);
    }
    .stat-ico {
      width:42px;
      height:42px;
      border-radius:8px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-size:1.05rem;
    }
    .stat-ico.cat { background:#c98f6f; }
    .stat-ico.prod { background:#7f5539; }
    .stat-ico.unc { background:#8b5e4c; }

    .stat-text .label { color:var(--muted); font-size:0.85rem; }
    .stat-text .value { font-weight:700; font-size:1.15rem; color:#3b2318; }

    /* --- Add Product enlarged / accumulative space adjustments --- */
    .add-product-card { min-height: 340px; } /* taller add-product card */
    .add-product-card .card-body { padding: 22px; }
    .add-product-card .form-control, .add-product-card .form-select { height:48px; }
    .add-product-card textarea.form-control { min-height:140px; resize:vertical; }

    /* make inputs align mid-line in the columns */
    .add-product-card .row.g-2 > [class*="col-"] { display:flex; align-items:center; }

    /* File chooser box: separate, boxed row */
    .file-box {
      border: 1px solid rgba(0,0,0,0.06);
      background: #fff;
      border-radius: 6px;
      padding: 8px 10px;
      display: flex;
      align-items: center;
      gap: 12px;
      justify-content: flex-start; /* ensure children start at left */
    }
    /* Keep the native file input button on the left */
    .file-box input[type="file"] {
      border: 0;
      padding: 0;
      margin: 0;
      background: transparent;
      width: auto; /* allow natural button size */
    }
    /* Optional small file name text placed after the input */
    .file-box .file-name {
      color: var(--muted);
      font-size: 0.95rem;
      margin-left: 12px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: calc(100% - 160px);
    }

    /* Buttons: accent and gray hover/active behavior */
    .btn-accent {
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      transition: background .12s ease, transform .06s ease;
      box-shadow: 0 2px 6px rgba(127,85,57,0.08);
    }
    .btn-accent:hover {
      background: var(--btn-gray-hover);
      color: #fff;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    .btn-accent:active,
    .btn-accent:focus {
      background: var(--btn-gray-active) !important;
      color: #fff;
      transform: translateY(1px);
      box-shadow: none;
      outline: none;
    }

    /* Products table area: accumulate space and box the empty state */
    .products-table {
      min-height: 180px; /* ensures area even when no rows */
      padding: 14px;
      background: #fff;
      border-radius: 8px;
      border: 1px solid rgba(0,0,0,0.03);
      box-shadow: 0 6px 14px rgba(0,0,0,0.03);
    }
    .products-table .table thead th { vertical-align: middle; }
    .products-table .table tbody td { padding: 18px 12px; } /* more vertical spacing per row */

    .category-list .list-group-item { display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border:0; border-bottom:1px solid rgba(0,0,0,0.03); }
    .category-list .list-group-item:last-child { border-bottom:0; }

    /* Accessibility: pointer cursor for clickable items */
    button, .btn, input[type="submit"] { cursor: pointer; }

    @media (max-width: 991px) {
      .sidebar { display:none; }
      main.flex-fill { padding-left:12px; }
    }
  </style>
</head>
<body class="page-bg">
  <div class="d-flex min-vh-100">
    <!-- Sidebar (inline) -->
    <aside class="sidebar border-end d-flex flex-column">
      <div class="sidebar-top text-center py-4">
        <div class="brand">EXpresso</div>
        <div class="small text-muted">Admin Panel</div>
      </div>

      <nav class="nav flex-column sidebar-nav mt-4 px-2">
        <a href="dashboard.php" class="<?php echo nav_active('dashboard.php', $current); ?>">
          <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        <a href="orders.php" class="<?php echo nav_active('orders.php', $current); ?>">
          <i class="bi bi-cart me-2"></i> Orders
        </a>
        <a href="menu.php" class="<?php echo nav_active('menu.php', $current); ?>">
          <i class="bi bi-list-ul me-2"></i> Menu
        </a>
        <a href="delivery.php" class="<?php echo nav_active('delivery.php', $current); ?>">
          <i class="bi bi-truck me-2"></i> Delivery
        </a>
        <a href="users.php" class="<?php echo nav_active('users.php', $current); ?>">
          <i class="bi bi-people me-2"></i> Users
        </a>
        <a href="bankinfo.php" class="<?php echo nav_active('bankinfo.php', $current); ?>">
          <i class="bi bi-credit-card-2-front me-2"></i> Bank Info
        </a>
        <a href="sales_report.php" class="<?php echo nav_active('sales_report.php', $current); ?>">
          <i class="bi bi-graph-up me-2"></i> Sales Report
        </a>
        <a href="settings.php" class="<?php echo nav_active('settings.php', $current); ?>">
          <i class="bi bi-gear me-2"></i> Settings
        </a>

        <div class="sidebar-spacer"></div>

        <a href="admin-login.php" class="nav-link logout-link mt-auto">
          <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
      </nav>
    </aside>

    <main class="flex-fill p-4">
      <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="page-title mb-0">Menu Management</h2>
          <div class="user-badge">
            <div class="avatar rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;">
              <?php echo strtoupper(htmlspecialchars($currentUser[0] ?? 'A')); ?>
            </div>
            <div class="ms-2 small text-muted"><?php echo htmlspecialchars($currentUser); ?></div>
          </div>
        </div>

        <?php if (isset($_SESSION['flash_msg'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['flash_msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <div class="row g-4">
          <div class="col-md-4">
            <!-- MINI STATS (moved to top of left panel) -->
            <div class="card panel mb-3">
              <div class="card-body">
                <div class="d-grid gap-3">
                  <div class="stat-compact">
                    <div class="stat-ico cat"><i class="bi bi-tags"></i></div>
                    <div class="stat-text">
                      <div class="label">Categories</div>
                      <div class="value"><?php echo number_format($totalCategories); ?></div>
                    </div>
                  </div>

                  <div class="stat-compact">
                    <div class="stat-ico prod"><i class="bi bi-box-seam"></i></div>
                    <div class="stat-text">
                      <div class="label">Products</div>
                      <div class="value"><?php echo number_format($totalProducts); ?></div>
                    </div>
                  </div>

                  <div class="stat-compact">
                    <div class="stat-ico unc"><i class="bi bi-question-circle"></i></div>
                    <div class="stat-text">
                      <div class="label">Uncategorized</div>
                      <div class="value"><?php echo number_format($uncategorized); ?></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Add Category (below the mini stats) -->
            <div class="card panel mb-3">
              <div class="card-body">
                <h6 class="mb-3">Add Category</h6>
                <form action="menu_actions.php" method="POST">
                  <input type="hidden" name="action" value="add_category">
                  <div class="mb-3">
                    <input type="text" name="category_name" class="form-control" placeholder="New category" required>
                  </div>
                  <button class="btn btn-accent w-100" type="submit">Add Category</button>
                </form>
              </div>
            </div>

            <!-- Existing Categories -->
            <div class="card panel mb-0">
              <div class="card-body">
                <h6 class="mb-3">Existing Categories</h6>
                <ul class="list-group list-group-flush mt-2 category-list">
                  <?php if (empty($categories)): ?>
                    <li class="list-group-item small text-muted">No categories yet.</li>
                  <?php else: foreach ($categories as $c): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <div><?php echo htmlspecialchars($c['name']); ?></div>
                      <div class="btn-group">
                        <form method="POST" action="menu_actions.php" onsubmit="return confirm('Delete this category? If it has products, use force delete to remove.');">
                          <input type="hidden" name="action" value="delete_category">
                          <input type="hidden" name="category_id" value="<?php echo (int)$c['id']; ?>">
                          <button type="submit" name="force_delete" value="0" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                      </div>
                    </li>
                  <?php endforeach; endif; ?>
                </ul>
              </div>
            </div>

          </div>

          <div class="col-md-8">
            <div class="card panel mb-3 add-product-card">
              <div class="card-body">
                <h6 class="mb-3">Add Product</h6>
                <form action="menu_actions.php" method="POST" enctype="multipart/form-data">
                  <input type="hidden" name="action" value="add_product">
                  <div class="row g-2">
                    <div class="col-md-7">
                      <input type="text" name="product_name" class="form-control" placeholder="Product name" required>
                    </div>
                    <div class="col-md-2">
                      <input type="number" step="0.01" name="price" class="form-control" placeholder="Price (₱)" required>
                    </div>
                    <div class="col-md-3">
                      <select name="category_id" class="form-select">
                        <option value="">-- None --</option>
                        <?php foreach ($categories as $c): ?>
                          <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="col-12">
                      <textarea name="description" class="form-control" rows="3" placeholder="Short description (optional)"></textarea>
                    </div>

                    <!-- File chooser placed in its own boxed row; input first so the native button appears on the left -->
                    <div class="col-12">
                      <div class="file-box">
                        <input type="file" name="image" accept="image/*" class="file-input">
                        <div class="file-name">No file chosen</div>
                      </div>
                    </div>

                    <div class="col-12 text-end">
                      <button class="btn btn-accent mt-3" type="submit">Add Product</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>

            <!-- Products table area: boxed and accumulative space -->
            <div class="products-table mb-3">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th class="ps-4">#</th>
                      <th>Product</th>
                      <th>Category</th>
                      <th>Price</th>
                      <th>Image</th>
                      <th class="text-end pe-4">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($products)): ?>
                      <tr><td colspan="6" class="text-center py-4 small text-muted">No products found.</td></tr>
                    <?php else: foreach ($products as $p): ?>
                      <tr>
                        <td class="ps-4"><?php echo (int)$p['id']; ?></td>
                        <td>
                          <div class="fw-bold"><?php echo htmlspecialchars($p['name']); ?></div>
                          <div class="small text-muted"><?php echo htmlspecialchars($p['description']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($p['category_name'] ?? '-'); ?></td>
                        <td class="fw-bold"><?php echo '₱' . number_format($p['price'],2); ?></td>
                        <td>
                          <?php if (!empty($p['image']) && file_exists(__DIR__ . '/uploads/products/' . $p['image'])): ?>
                            <img src="<?php echo 'uploads/products/' . htmlspecialchars($p['image']); ?>" class="thumb" alt="">
                          <?php else: ?>
                            <div class="text-muted small">—</div>
                          <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                          <div class="btn-group">
                            <form method="POST" action="menu_actions.php" onsubmit="return confirm('Delete this product?');" class="d-inline">
                              <input type="hidden" name="action" value="delete_product">
                              <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                              <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete">
                                <i class="bi bi-trash"></i>
                              </button>
                            </form>
                          </div>
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
    </main>
  </div>

  <script>
    // Small enhancement: show selected file name next to the native input
    document.addEventListener('DOMContentLoaded', function(){
      var fileInput = document.querySelector('.file-box input[type="file"]');
      var fileName = document.querySelector('.file-box .file-name');
      if(fileInput && fileName){
        fileInput.addEventListener('change', function(e){
          var f = fileInput.files && fileInput.files[0];
          fileName.textContent = f ? f.name : 'No file chosen';
        });
      }
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>