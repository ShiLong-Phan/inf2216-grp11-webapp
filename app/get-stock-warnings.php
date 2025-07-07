<?php
// Start session if not already started
include "utils/session.php";

// Initialize response array
$response = [
    'stockWarnings' => []
];

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Include database connection
    include_once "utils/dbconnect.php";

    if ($conn) {
        $userId = $_SESSION['user_id'];

        // Fetch cart items with product details including stock
        $stmt = $conn->prepare("SELECT c.cart_id, c.cart_prod_id, c.cart_quantity, 
                                p.prod_name, p.prod_stock
                                FROM ssdgroup11db.cart c 
                                JOIN ssdgroup11db.products p ON c.cart_prod_id = p.prod_id 
                                WHERE c.cart_user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        // Process results
        while ($row = $result->fetch_assoc()) {
            // Calculate how many of this product are in other carts
            $otherCartStmt = $conn->prepare("SELECT SUM(cart_quantity) as in_other_carts 
                                           FROM ssdgroup11db.cart 
                                           WHERE cart_prod_id = ? AND cart_user_id != ?");
            $otherCartStmt->bind_param("ii", $row['cart_prod_id'], $userId);
            $otherCartStmt->execute();
            $otherCartResult = $otherCartStmt->get_result();
            $inOtherCarts = $otherCartResult->fetch_assoc()['in_other_carts'] ?? 0;
            
            $effectiveStock = $row['prod_stock'] - $inOtherCarts;
            
            // Check for stock issues
            if ($row['cart_quantity'] > $effectiveStock) {
                if ($effectiveStock <= 0) {
                    $response['stockWarnings'][] = "• " . htmlspecialchars($row['prod_name']) . " is no longer available and will be removed at checkout.";
                } else {
                    $response['stockWarnings'][] = "• " . htmlspecialchars($row['prod_name']) . " only has {$effectiveStock} items available (you have {$row['cart_quantity']}). Quantity will be adjusted at checkout.";
                }
            }
            
            $otherCartStmt->close();
        }

        $stmt->close();
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>