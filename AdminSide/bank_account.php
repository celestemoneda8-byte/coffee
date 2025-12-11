<?php
// Receives POST actions for adding/deleting banks and customer bank info.
// Place next to your menu.php. Uses same db_connect.php which defines $conn.

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

// Simple auth check (same as other pages)
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: admin-login.php');
    exit;
}

$action = $_POST['action'] ?? '';
$redirect = 'bankinfo.php';

function flash($type, $msg) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_msg'] = $msg;
}

try {
    if ($action === 'add_bank') {
        $name = trim($_POST['name'] ?? '');
        $account_name = trim($_POST['account_name'] ?? '');
        $account_number = trim($_POST['account_number'] ?? '');
        $bank_code = trim($_POST['bank_code'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') throw new Exception('Bank name is required.');

        $stmt = $conn->prepare("INSERT INTO banks (name, account_name, account_number, bank_code, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssi', $name, $account_name, $account_number, $bank_code, $is_active);
        $stmt->execute();
        $stmt->close();

        flash('success', 'Bank added.');
    } elseif ($action === 'delete_bank') {
        $id = (int)($_POST['bank_id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM banks WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            flash('success', 'Bank removed.');
        }
    } elseif ($action === 'add_customer_bank') {
        $customer_name = trim($_POST['customer_name'] ?? '');
        $bank_name = trim($_POST['bank_name'] ?? '');
        $account_name = trim($_POST['customer_account_name'] ?? '');
        $account_number = trim($_POST['customer_account_number'] ?? '');
        $contact = trim($_POST['contact'] ?? '');

        if ($customer_name === '' || $bank_name === '' || $account_name === '' || $account_number === '') {
            throw new Exception('Please fill all required customer bank fields.');
        }

        $created_by = $_SESSION['username'] ?? 'admin';

        $stmt = $conn->prepare("INSERT INTO customer_banks (customer_name, bank_name, account_name, account_number, contact, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $customer_name, $bank_name, $account_name, $account_number, $contact, $created_by);
        $stmt->execute();
        $stmt->close();

        flash('success', 'Customer bank added.');
    } elseif ($action === 'delete_customer_bank') {
        $id = (int)($_POST['customer_bank_id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM customer_banks WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            flash('success', 'Customer bank removed.');
        }
    } else {
        flash('info', 'Unknown action.');
    }
} catch (Exception $ex) {
    flash('danger', $ex->getMessage());
}

header('Location: ' . $redirect);
exit;
?>