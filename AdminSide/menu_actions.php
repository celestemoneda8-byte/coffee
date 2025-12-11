<?php
// menu_actions.php
// Handles add category, add product, delete product, delete category
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: admin-login.php');
    exit;
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'n-a';
}

$action = $_POST['action'] ?? '';

if ($action === 'add_category') {
    $name = trim($_POST['category_name'] ?? '');
    if ($name === '') {
        $_SESSION['flash_msg'] = 'Category name is required.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: menu.php');
        exit;
    }
    $slug = slugify($name);
    // ensure unique slug by appending number if exists
    $base = $slug;
    $i = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM categories WHERE slug = ? LIMIT 1");
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            break;
        }
        $stmt->close();
        $slug = $base . '-' . $i;
        $i++;
    }

    $ins = $conn->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
    $ins->bind_param('ss', $name, $slug);
    if ($ins->execute()) {
        $_SESSION['flash_msg'] = 'Category added.';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_msg'] = 'Error adding category.';
        $_SESSION['flash_type'] = 'danger';
    }
    $ins->close();
    header('Location: menu.php');
    exit;
}

if ($action === 'add_product') {
    $name = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

    if ($name === '') {
        $_SESSION['flash_msg'] = 'Product name is required.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: menu.php');
        exit;
    }

    $slug = slugify($name);
    $base = $slug;
    $i = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM products WHERE slug = ? LIMIT 1");
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) { $stmt->close(); break; }
        $stmt->close();
        $slug = $base . '-' . $i;
        $i++;
    }

    // Handle optional image upload
    $imageName = null;
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        $tmp = $_FILES['image']['tmp_name'];
        $mime = mime_content_type($tmp);
        if (!in_array($mime, $allowed)) {
            $_SESSION['flash_msg'] = 'Invalid image type. Allowed: jpg, png, webp, gif.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: menu.php');
            exit;
        }
        // sanitize and generate filename
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = 'prod_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destDir = __DIR__ . '/uploads/products';
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
        $dest = $destDir . '/' . $imageName;
        if (!move_uploaded_file($tmp, $dest)) {
            $_SESSION['flash_msg'] = 'Failed to upload image.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: menu.php');
            exit;
        }
    }

    $ins = $conn->prepare("INSERT INTO products (name, slug, description, price, category_id, image) VALUES (?, ?, ?, ?, ?, ?)");
    $ins->bind_param('sssdis', $name, $slug, $description, $price, $category_id, $imageName);
    if ($ins->execute()) {
        $_SESSION['flash_msg'] = 'Product added.';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_msg'] = 'Error adding product.';
        $_SESSION['flash_type'] = 'danger';
        // cleanup image if inserted failed
        if ($imageName) @unlink(__DIR__ . '/uploads/products/' . $imageName);
    }
    $ins->close();
    header('Location: menu.php');
    exit;
}

if ($action === 'delete_product') {
    $id = (int)($_POST['product_id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['flash_msg'] = 'Invalid product id.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: menu.php');
        exit;
    }
    // fetch image name for cleanup
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($img);
    $stmt->fetch();
    $stmt->close();

    $del = $conn->prepare("DELETE FROM products WHERE id = ?");
    $del->bind_param('i', $id);
    if ($del->execute()) {
        if ($img) {
            @unlink(__DIR__ . '/uploads/products/' . $img);
        }
        $_SESSION['flash_msg'] = 'Product deleted.';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_msg'] = 'Error deleting product.';
        $_SESSION['flash_type'] = 'danger';
    }
    $del->close();
    header('Location: menu.php');
    exit;
}

if ($action === 'delete_category') {
    $id = (int)($_POST['category_id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['flash_msg'] = 'Invalid category id.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: menu.php');
        exit;
    }

    // check if category has products
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0 && empty($_POST['force_delete'])) {
        $_SESSION['flash_msg'] = 'Category has products. Remove or reassign products first, or use force delete.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: menu.php');
        exit;
    }

    // If force_delete requested, set category_id to NULL before deletion, then delete
    if (!empty($_POST['force_delete'])) {
        $u = $conn->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?");
        $u->bind_param('i', $id);
        $u->execute();
        $u->close();
    }

    $del = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $del->bind_param('i', $id);
    if ($del->execute()) {
        $_SESSION['flash_msg'] = 'Category deleted.';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_msg'] = 'Error deleting category.';
        $_SESSION['flash_type'] = 'danger';
    }
    $del->close();
    header('Location: menu.php');
    exit;
}

// fallback redirect
header('Location: menu.php');
exit;
?>