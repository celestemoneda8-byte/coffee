<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';

// Security check
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: ../includes/admin-login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $order_id = (int)($_POST['order_id'] ?? 0);

    if ($order_id > 0) {
        $msg = '';
        $type = 'success';

        if ($action === 'mark_paid') {
            // Mark as Paid
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE order_id = ?");
            $stmt->bind_param('i', $order_id);
            if ($stmt->execute()) $msg = "Order #$order_id marked as Paid.";
            else { $msg = "Error updating payment."; $type = 'danger'; }
            $stmt->close();

        } elseif ($action === 'update_status') {
            // Change Status (Dropdown)
            $new_status = $_POST['status'] ?? '';
            $allowed = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
            
            // Map lowercase to proper case for order_status ENUM
            $status_map = [
                'pending' => 'Pending',
                'preparing' => 'Preparing', 
                'ready' => 'Ready',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled'
            ];
            
            if (in_array($new_status, $allowed)) {
                $order_status_value = $status_map[$new_status] ?? 'Pending';
                $stmt = $conn->prepare("UPDATE orders SET status = ?, order_status = ? WHERE order_id = ?");
                $stmt->bind_param('ssi', $new_status, $order_status_value, $order_id);
                if ($stmt->execute()) $msg = "Order #$order_id updated to " . ucfirst($new_status) . ".";
                else { $msg = "Error updating status."; $type = 'danger'; }
                $stmt->close();
            }

        } elseif ($action === 'cancel') {
            // Quick Cancel
            $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', order_status = 'Cancelled' WHERE order_id = ?");
            $stmt->bind_param('i', $order_id);
            if ($stmt->execute()) $msg = "Order #$order_id has been cancelled.";
            else { $msg = "Error cancelling order."; $type = 'danger'; }
            $stmt->close();

        } elseif ($action === 'delete') {
            // Hard Delete
            $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
            $stmt->bind_param('i', $order_id);
            if ($stmt->execute()) $msg = "Order #$order_id deleted permanently.";
            else { $msg = "Error deleting order."; $type = 'danger'; }
            $stmt->close();
        }

        // Store message in session to show on the page
        if ($msg) {
            $_SESSION['flash_msg'] = $msg;
            $_SESSION['flash_type'] = $type;
        }
    }
}

// Redirect back to orders list
header('Location: orders.php');
exit;
?>