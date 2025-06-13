<?php
session_start();
include "utils/dbconnect.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Delete all cart items for this user
$stmt = $conn->prepare("DELETE FROM ssdgroup11db.cart WHERE cart_user_id = ?");
$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Cart cleared successfully'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to clear cart: ' . $conn->error
    ]);
}
?>