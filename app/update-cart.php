<?php
session_start();
include "utils/dbconnect.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if necessary parameters are provided
if (!isset($_POST['cart_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$cartId = $_POST['cart_id'];
$action = $_POST['action'];
$userId = $_SESSION['user_id'];

// First, verify that the cart item belongs to the current user
$stmt = $conn->prepare("SELECT cart_quantity, cart_prod_id, cart_subtotal FROM ssdgroup11db.cart WHERE cart_id = ? AND cart_user_id = ?");
$stmt->bind_param("ii", $cartId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found or does not belong to you']);
    exit;
}

$cartItem = $result->fetch_assoc();
$currentQuantity = $cartItem['cart_quantity'];
$productId = $cartItem['cart_prod_id'];

// Get the unit price from the products table
$stmt = $conn->prepare("SELECT prod_price FROM ssdgroup11db.products WHERE prod_id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$unitPrice = $product['prod_price'];

// Initialize response array
$response = ['success' => false, 'message' => 'Unknown error occurred'];

// Handle different actions
switch ($action) {
    case 'increase':
        $newQuantity = $currentQuantity + 1;
        $newTotalPrice = $unitPrice * $newQuantity;

        $updateStmt = $conn->prepare("UPDATE ssdgroup11db.cart SET cart_quantity = ?, cart_subtotal = ? WHERE cart_id = ?");
        $updateStmt->bind_param("idi", $newQuantity, $newTotalPrice, $cartId);

        if ($updateStmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Quantity increased';
            $response['quantity'] = $newQuantity;
            $response['total_price'] = $newTotalPrice;
        } else {
            $response['message'] = 'Failed to update quantity: ' . $conn->error;
        }
        break;

    case 'decrease':
        if ($currentQuantity <= 1) {
            // If quantity is 1, remove the item
            $deleteStmt = $conn->prepare("DELETE FROM ssdgroup11db.cart WHERE cart_id = ?");
            $deleteStmt->bind_param("i", $cartId);

            if ($deleteStmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Item removed from cart';
                $response['removed'] = true;
            } else {
                $response['message'] = 'Failed to remove item: ' . $conn->error;
            }
        } else {
            // Decrease quantity
            $newQuantity = $currentQuantity - 1;
            $newTotalPrice = $unitPrice * $newQuantity;

            $updateStmt = $conn->prepare("UPDATE ssdgroup11db.cart SET cart_quantity = ?, cart_subtotal = ? WHERE cart_id = ?");
            $updateStmt->bind_param("idi", $newQuantity, $newTotalPrice, $cartId);

            if ($updateStmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Quantity decreased';
                $response['quantity'] = $newQuantity;
                $response['total_price'] = $newTotalPrice;
            } else {
                $response['message'] = 'Failed to update quantity: ' . $conn->error;
            }
        }
        break;

    case 'remove':
        $deleteStmt = $conn->prepare("DELETE FROM ssdgroup11db.cart WHERE cart_id = ?");
        $deleteStmt->bind_param("i", $cartId);

        if ($deleteStmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Item removed from cart';
            $response['removed'] = true;
        } else {
            $response['message'] = 'Failed to remove item: ' . $conn->error;
        }
        break;

    default:
        $response['message'] = 'Invalid action';
}

// Return JSON response
echo json_encode($response);
?>