<?php
session_start();
include "utils/dbconnect.php";

// Check if user is logged in
if (!isset($_SESSION['user_id']) && $_SESSION['user_role'] == 0) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Verify if it's a POST request and all required data is present
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['product_id']) &&
    isset($_POST['quantity'])
) {

    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $quantity = (int) $_POST['quantity'];

    // Input validation
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
        exit;
    }

    // Get product price from the products table
    $stmt = $conn->prepare("SELECT prod_price FROM ssdgroup11db.products WHERE prod_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    $product = $result->fetch_assoc();
    $unit_price = $product['prod_price']; // Store the unit price

    // Check if the product is already in the cart
    $stmt = $conn->prepare("SELECT cart_quantity FROM ssdgroup11db.cart WHERE cart_user_id = ? AND cart_prod_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing cart item quantity
        $row = $result->fetch_assoc();
        $new_quantity = $row['cart_quantity'] + $quantity;
        $new_total_price = $unit_price * $new_quantity; // Calculate new total price

        $stmt = $conn->prepare("UPDATE ssdgroup11db.cart SET cart_quantity = ?, cart_subtotal = ? WHERE cart_user_id = ? AND cart_prod_id = ?");
        $stmt->bind_param("idii", $new_quantity, $new_total_price, $user_id, $product_id);
        $success = $stmt->execute();

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Cart updated successfully',
                'quantity' => $new_quantity,
                'price' => $new_total_price
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update cart: ' . $stmt->error]);
        }
    } else {
        // Insert new cart item
        $total_price = $unit_price * $quantity; // Calculate total price for new item
        
        $stmt = $conn->prepare("INSERT INTO ssdgroup11db.cart (cart_user_id, cart_prod_id, cart_quantity, cart_subtotal) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $user_id, $product_id, $quantity, $total_price);
        $success = $stmt->execute();

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Product added to cart successfully',
                'quantity' => $quantity,
                'price' => $total_price
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add to cart: ' . $stmt->error]);
        }
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>