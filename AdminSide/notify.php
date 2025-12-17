<?php
// lib/notify.php
// Minimal notification helper. Insert this at: C:\xampp\htdocs\EXPRESSO\lib\notify.php

declare(strict_types=1);

/**
 * Insert a notification row. user_id NULL = broadcast (all admins).
 */
function insert_notification($conn, ?int $userId, string $type, string $title, string $message = '', ?int $orderId = null): bool {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, order_id) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("insert_notification prepare failed: " . $conn->error);
        return false;
    }
    // If $userId is null pass NULL as integer param (bind param requires a variable)
    $uid = $userId === null ? null : $userId;
    $stmt->bind_param('isssi', $uid, $type, $title, $message, $orderId);
    $ok = $stmt->execute();
    if (!$ok) error_log("insert_notification execute failed: " . $stmt->error);
    $stmt->close();
    return (bool)$ok;
}

function notify_admins($conn, string $title, string $message = '', ?int $orderId = null): bool {
    return insert_notification($conn, null, 'info', $title, $message, $orderId);
}

// Optional email notification to admins (requires users.email column + mail configured)
function notify_admins_email($conn, string $subject, string $body, ?int $orderId = null): void {
    $res = $conn->query("SHOW COLUMNS FROM `users` LIKE 'email'");
    if (!$res || $res->num_rows === 0) return;
    $stmt = $conn->prepare("SELECT email FROM users WHERE role = 'admin' AND email IS NOT NULL AND email <> ''");
    if (!$stmt) return;
    $stmt->execute();
    $rs = $stmt->get_result();
    while ($row = $rs->fetch_assoc()) {
        @mail($row['email'], $subject, $body, "From: no-reply@local\r\nContent-Type: text/plain; charset=utf-8");
    }
    $stmt->close();
}