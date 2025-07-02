<?php
session_start();
include "utils/dbconnect.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

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

// Get the product price and current stock
$stmt = $conn->prepare("SELECT prod_price, prod_stock FROM ssdgroup11db.products WHERE prod_id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$unitPrice = $product['prod_price'];
$stockAvailable = $product['prod_stock'];

// Calculate how many of this item are in other carts (temporary holds)
$stmt = $conn->prepare("SELECT SUM(cart_quantity) as reserved FROM ssdgroup11db.cart 
                       WHERE cart_prod_id = ? AND cart_user_id != ?");
$stmt->bind_param("ii", $productId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$reserved = $result->fetch_assoc()['reserved'] ?? 0;

// Calculate effectively available stock (actual stock minus items in other carts)
$effectiveStock = $stockAvailable - $reserved;

// Initialize response array
$response = ['success' => false, 'message' => 'Unknown error occurred'];

// Handle different actions
switch ($action) {
    case 'increase':
        $newQuantity = $currentQuantity + 1;

        // Check if increasing quantity would exceed available stock
        if ($newQuantity > $effectiveStock) {
            // If other users have some in their carts
            if ($effectiveStock < $stockAvailable) {
                $response['success'] = false;
                $response['message'] = "Only {$effectiveStock} items are available (others have some in their carts). Your quantity cannot be increased.";
            } else {
                $response['success'] = false;
                $response['message'] = 'Cannot add more of this item. Maximum stock reached.';
            }
            break;
        }

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

// Add stock information to the response
$response['stock_available'] = $stockAvailable;
$response['reserved_in_other_carts'] = $reserved;
$response['effective_stock'] = $effectiveStock;

// Return JSON response
echo json_encode($response);
?>