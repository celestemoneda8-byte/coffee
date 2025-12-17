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
            $allowed = ['pending', 'preparing', 'ready', 'on-delivery', 'completed', 'cancelled'];
            
            // Map lowercase to proper case for order_status ENUM
            $status_map = [
                'pending' => 'Pending',
                'preparing' => 'Preparing', 
                'ready' => 'Ready',
                'on-delivery' => 'On-Delivery',
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

        } elseif ($action === 'assign_rider') {
            // Assign Rider to Order
            $rider_id = (int)($_POST['rider_id'] ?? 0);
            
            if ($rider_id > 0) {
                // Check if already assigned
                $check = $conn->prepare("SELECT * FROM rider_orders WHERE order_id = ?");
                $check->bind_param('i', $order_id);
                $check->execute();
                $existing = $check->get_result();
                
                if ($existing->num_rows > 0) {
                    // Update existing assignment
                    $stmt = $conn->prepare("UPDATE rider_orders SET rider_id = ?, order_status = 'Assigned' WHERE order_id = ?");
                    $stmt->bind_param('ii', $rider_id, $order_id);
                } else {
                    // Create new assignment
                    // Get delivery address from order
                    $orderInfo = $conn->query("SELECT delivery_address FROM orders WHERE order_id = $order_id")->fetch_assoc();
                    $address = $orderInfo['delivery_address'] ?? '';
                    
                    $stmt = $conn->prepare("INSERT INTO rider_orders (rider_id, order_id, order_status, dropoff_address) VALUES (?, ?, 'Assigned', ?)");
                    $stmt->bind_param('iis', $rider_id, $order_id, $address);
                }
                
                if ($stmt->execute()) {
                    // Also update order status to on-delivery
                    $conn->query("UPDATE orders SET status = 'on-delivery', order_status = 'On-Delivery' WHERE order_id = $order_id");
                    
                    // Get rider name for message
                    $riderInfo = $conn->query("SELECT rider_name FROM rider_accounts WHERE rider_id = $rider_id")->fetch_assoc();
                    $msg = "Order #$order_id assigned to rider " . ($riderInfo['rider_name'] ?? 'Unknown');
                } else {
                    $msg = "Error assigning rider.";
                    $type = 'danger';
                }
                $stmt->close();
                $check->close();
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