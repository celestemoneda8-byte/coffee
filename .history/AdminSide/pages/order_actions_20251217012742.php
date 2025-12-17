<<<<<<< HEAD:.history/AdminSide/pages/order_actions_20251217012742.php
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
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
            $stmt->bind_param('i', $order_id);
            if ($stmt->execute()) $msg = "Order #$order_id marked as Paid.";
            else { $msg = "Error updating payment."; $type = 'danger'; }
            $stmt->close();

        } elseif ($action === 'update_status') {
            // Change Status (Dropdown)
            $new_status = $_POST['status'] ?? '';
            $allowed = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
            
            if (in_array($new_status, $allowed)) {
                $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->bind_param('si', $new_status, $order_id);
                if ($stmt->execute()) $msg = "Order #$order_id updated to " . ucfirst($new_status) . ".";
                else { $msg = "Error updating status."; $type = 'danger'; }
                $stmt->close();
            }

        } elseif ($action === 'cancel') {
            // Quick Cancel
            $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmt->bind_param('i', $order_id);
            if ($stmt->execute()) $msg = "Order #$order_id has been cancelled.";
            else { $msg = "Error cancelling order."; $type = 'danger'; }
            $stmt->close();

        } elseif ($action === 'delete') {
            // Hard Delete
            $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
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
=======
<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

// Security check
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: login.php');
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
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
            $stmt->bind_param('i', $order_id);
            if ($stmt->execute()) $msg = "Order #$order_id marked as Paid.";
            else { $msg = "Error updating payment."; $type = 'danger'; }
            $stmt->close();

        } elseif ($action === 'update_status') {
            // Change Status (Dropdown)
            $new_status = $_POST['status'] ?? '';
            $allowed = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
            
            if (in_array($new_status, $allowed)) {
                $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->bind_param('si', $new_status, $order_id);
                if ($stmt->execute()) $msg = "Order #$order_id updated to " . ucfirst($new_status) . ".";
                else { $msg = "Error updating status."; $type = 'danger'; }
                $stmt->close();
            }

        } elseif ($action === 'cancel') {
            // Quick Cancel
            $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmt->bind_param('i', $order_id);
            if ($stmt->execute()) $msg = "Order #$order_id has been cancelled.";
            else { $msg = "Error cancelling order."; $type = 'danger'; }
            $stmt->close();

        } elseif ($action === 'delete') {
            // Hard Delete
            $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
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
>>>>>>> 48c3441730d7f3bcc0f52162c85c9984cc42607c:AdminSide/order_actions.php
?>