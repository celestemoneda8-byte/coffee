<?php
session_start();
require_once __DIR__ . "/../../config/db_connect.php";

header('Content-Type: application/json; charset=utf-8');

/*
  This script uses the "customers" and "customer_accounts" tables:

  customers:
    customer_id, fullname, email (unique), password, created_at

  customer_accounts:
    account_id, customer_id, address, phone, updated_at

  Behavior:
  - action=login: authenticates against customers.email + password
  - action=register: creates a customers row and a customer_accounts row (address/phone optional)
*/

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

$conn = getConnection();
$response = ['success' => false, 'message' => 'Invalid request'];

/* ======= Helpers ======= */
function jsonError($msg) {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}
function jsonSuccess($msg, $extra = []) {
    $resp = array_merge(['success' => true, 'message' => $msg], $extra);
    echo json_encode($resp);
    exit;
}

/* ======= LOGIN ======= */
if ($action === 'login') {
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    if ($email === '' || $password === '') {
        jsonError('Email and password required');
    }

    // Normalize email
    $email = mb_strtolower($email);

    $stmt = $conn->prepare('SELECT customer_id, fullname, email, password FROM customers WHERE email = ? LIMIT 1');
    if (!$stmt) jsonError('Server error (prepare failed)');

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $stmt->close();

    if (!$customer) {
        jsonError('Email not registered');
    }

    if (!password_verify($password, $customer['password'])) {
        // Consider adding logging / rate limiting here
        jsonError('Incorrect password');
    }

    // Successful login: regenerate session id to prevent fixation
    session_regenerate_id(true);
    $_SESSION['customer_id'] = $customer['customer_id'];
    $_SESSION['fullname'] = $customer['fullname'];
    $_SESSION['email'] = $customer['email'];

    // Optionally return minimal user data
    jsonSuccess('Login successful', ['customer_id' => (int)$customer['customer_id'], 'fullname' => $customer['fullname']]);
}

/* ======= REGISTER ======= */
if ($action === 'register') {
    $fullname = trim($input['fullname'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $conpass = trim($input['conpass'] ?? '');
    $address = isset($input['address']) ? trim($input['address']) : null;
    $phone = isset($input['phone']) ? trim($input['phone']) : null;

    if ($fullname === '' || $email === '' || $password === '' || $conpass === '') {
        jsonError('Full name, email, password and confirmation are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Invalid email format');
    }

    if ($password !== $conpass) {
        jsonError('Passwords do not match');
    }

    // Normalize email
    $email = mb_strtolower($email);

    // Check if email exists
    $stmt = $conn->prepare('SELECT customer_id FROM customers WHERE email = ? LIMIT 1');
    if (!$stmt) jsonError('Server error (prepare failed)');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        jsonError('Email already registered');
    }
    $stmt->close();

    // Hash password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Use transaction: insert into customers, then customer_accounts
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('INSERT INTO customers (fullname, email, password) VALUES (?, ?, ?)');
        if (!$stmt) throw new Exception('Server error (prepare failed)');

        $stmt->bind_param('sss', $fullname, $email, $hash);
        if (!$stmt->execute()) {
            throw new Exception('Failed to create customer');
        }
        $customer_id = $conn->insert_id;
        $stmt->close();

        // Create an empty customer_accounts row (address/phone may be null)
        $stmt2 = $conn->prepare('INSERT INTO customer_accounts (customer_id, address, phone) VALUES (?, ?, ?)');
        if (!$stmt2) throw new Exception('Server error (prepare failed)');
        // MySQL will accept nulls for address/phone if provided as null
        $addrParam = $address !== '' ? $address : null;
        $phoneParam = $phone !== '' ? $phone : null;
        $stmt2->bind_param('iss', $customer_id, $addrParam, $phoneParam);
        if (!$stmt2->execute()) {
            throw new Exception('Failed to create customer account');
        }
        $stmt2->close();

        $conn->commit();

        // Log the user in
        session_regenerate_id(true);
        $_SESSION['customer_id'] = $customer_id;
        $_SESSION['fullname'] = $fullname;
        $_SESSION['email'] = $email;

        jsonSuccess('Registration successful', ['customer_id' => (int)$customer_id]);
    } catch (Exception $e) {
        $conn->rollback();
        // Do not leak technical error messages to client in production
        jsonError('Registration failed: ' . $e->getMessage());
    }
}

echo json_encode($response);
exit;
?>