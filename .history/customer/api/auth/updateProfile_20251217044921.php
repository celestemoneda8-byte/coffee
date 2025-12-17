<?php
session_start();
require_once __DIR__ . "/../../config/db_connect.php";

header('Content-Type: application/json; charset=utf-8');

// $conn is available from db_connect.php
$customerId = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? null;

if (!$customerId) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

function jsonResponse($data) {
    echo json_encode($data);
    exit;
}

/* ============================
   FETCH PROFILE (GET ?action=get)
   Returns combined data from customers and customer_accounts
   ============================ */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get') {
    $stmt = $conn->prepare("
        SELECT c.customer_id, c.fullname, c.email, c.created_at,
               a.address, a.phone
        FROM customers c
        LEFT JOIN customer_accounts a ON c.customer_id = a.customer_id
        WHERE c.customer_id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        jsonResponse(['success' => false, 'message' => 'Server error (prepare failed)']);
    }
    $stmt->bind_param('i', $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        // ensure nullable fields are explicit nulls instead of empty strings
        $user['address'] = $user['address'] !== null ? $user['address'] : null;
        $user['phone'] = $user['phone'] !== null ? $user['phone'] : null;
        jsonResponse(['success' => true, 'user' => $user]);
    } else {
        jsonResponse(['success' => false, 'message' => 'User not found']);
    }
}

/* ============================
   UPDATE PROFILE (POST)
   Currently updates address and optional phone.
   If customer_accounts row doesn't exist, it will be created.
   Expects JSON body: { "address": "...", "phone": "..." }
   ============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        jsonResponse(['success' => false, 'message' => 'Invalid JSON body']);
    }

    $address = isset($input['address']) ? trim($input['address']) : null;
    $phone = isset($input['phone']) ? trim($input['phone']) : null;

    // Basic validation
    if ($address !== null && mb_strlen($address) > 255) {
        jsonResponse(['success' => false, 'message' => 'Address too long (max 255 chars)']);
    }
    if ($phone !== null && mb_strlen($phone) > 50) {
        jsonResponse(['success' => false, 'message' => 'Phone too long (max 50 chars)']);
    }

    // Check if an account row exists
    $stmt = $conn->prepare("SELECT account_id FROM customer_accounts WHERE customer_id = ? LIMIT 1");
    if (!$stmt) {
        jsonResponse(['success' => false, 'message' => 'Server error (prepare failed)']);
    }
    $stmt->bind_param('i', $customerId);
    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows > 0;
    $stmt->close();

    // Use transaction to avoid race conditions between check/insert/update
    $conn->begin_transaction();
    try {
        if ($exists) {
            $stmt = $conn->prepare("UPDATE customer_accounts SET address = ?, phone = ?, updated_at = current_timestamp() WHERE customer_id = ?");
            if (!$stmt) throw new Exception('Server error (prepare failed)');
            // allow null values: bind_param requires variables, convert empty string to null where appropriate
            $addrParam = $address !== null ? $address : null;
            $phoneParam = $phone !== null ? $phone : null;
            // Since bind_param doesn't accept null directly for string types in some drivers,
            // use "s" and pass null (it will send SQL NULL)
            $stmt->bind_param('ssi', $addrParam, $phoneParam, $customerId);
            if (!$stmt->execute()) throw new Exception('Failed to update account');
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO customer_accounts (customer_id, address, phone) VALUES (?, ?, ?)");
            if (!$stmt) throw new Exception('Server error (prepare failed)');
            $addrParam = $address !== null ? $address : null;
            $phoneParam = $phone !== null ? $phone : null;
            $stmt->bind_param('iss', $customerId, $addrParam, $phoneParam);
            if (!$stmt->execute()) throw new Exception('Failed to create account');
            $stmt->close();
        }

        $conn->commit();

        // Return updated profile
        $stmt = $conn->prepare("
            SELECT c.customer_id, c.fullname, c.email, c.created_at,
                   a.address, a.phone
            FROM customers c
            LEFT JOIN customer_accounts a ON c.customer_id = a.customer_id
            WHERE c.customer_id = ?
            LIMIT 1
        ");
        if (!$stmt) {
            jsonResponse(['success' => true, 'message' => 'Address updated, but failed to retrieve profile']);
        }
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        jsonResponse(['success' => true, 'message' => 'Profile updated successfully', 'user' => $user ?? null]);
    } catch (Exception $e) {
        $conn->rollback();
        // In production, avoid exposing exception messages; log them server-side instead
        jsonResponse(['success' => false, 'message' => 'Failed to update profile']);
    }
}

jsonResponse(['success' => false, 'message' => 'Invalid request']);
?>