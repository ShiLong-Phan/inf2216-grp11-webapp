<?php
include "utils/session.php"; // Ensure session is started
include "utils/dbconnect.php";

// Check if user is logged in with correct role
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 0 && $_SESSION['user_role'] != 1)) {
    echo json_encode(['success' => false, 'message' => 'User not logged in or unauthorized']);
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

    // Get product price and stock from the products table
    $stmt = $conn->prepare("SELECT prod_price, prod_stock FROM ssdgroup11db.products WHERE prod_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    $product = $result->fetch_assoc();
    $unit_price = $product['prod_price'];
    $stock_available = $product['prod_stock'];

    // Calculate total quantity in all carts (including current user's cart)
    $stmt = $conn->prepare("SELECT SUM(cart_quantity) as total_in_carts FROM ssdgroup11db.cart WHERE cart_prod_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_in_carts = $result->fetch_assoc()['total_in_carts'] ?? 0;

    // Check if the product is already in the user's cart
    $stmt = $conn->prepare("SELECT cart_quantity FROM ssdgroup11db.cart WHERE cart_user_id = ? AND cart_prod_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing cart item quantity
        $row = $result->fetch_assoc();
        $current_quantity = $row['cart_quantity'];
        $new_quantity = $current_quantity + $quantity;
        
        // Calculate how many other users have in their carts
        $others_quantity = $total_in_carts - $current_quantity;
        
        // Check if adding to cart would exceed stock
        $effective_available = $stock_available - $others_quantity;
        
        if ($new_quantity > $effective_available) {
            if ($effective_available <= 0) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'This item is no longer available (other users have it in their carts)'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => "Only {$effective_available} items available. Other users have some in their carts."
                ]);
            }
            exit;
        }
        
        // Update the cart with new quantity
        $new_total_price = $unit_price * $new_quantity;

        $stmt = $conn->prepare("UPDATE ssdgroup11db.cart SET cart_quantity = ?, cart_subtotal = ? WHERE cart_user_id = ? AND cart_prod_id = ?");
        $stmt->bind_param("idii", $new_quantity, $new_total_price, $user_id, $product_id);
        $success = $stmt->execute();

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Cart updated successfully',
                'quantity' => $new_quantity,
                'price' => $new_total_price,
                'stock_remaining' => $stock_available - $total_in_carts
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update cart: ' . $stmt->error]);
        }
    } else {
        // Check if adding to cart would exceed stock
        if ($quantity > ($stock_available - $total_in_carts)) {
            $available = $stock_available - $total_in_carts;
            if ($available <= 0) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'This item is no longer available (other users have it in their carts)'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => "Only {$available} items available. Other users have some in their carts."
                ]);
            }
            exit;
        }
        
        // Insert new cart item
        $total_price = $unit_price * $quantity;
        
        $stmt = $conn->prepare("INSERT INTO ssdgroup11db.cart (cart_user_id, cart_prod_id, cart_quantity, cart_subtotal) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $user_id, $product_id, $quantity, $total_price);
        $success = $stmt->execute();

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Product added to cart successfully',
                'quantity' => $quantity,
                'price' => $total_price,
                'stock_remaining' => $stock_available - ($total_in_carts + $quantity)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add to cart: ' . $stmt->error]);
        }
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>