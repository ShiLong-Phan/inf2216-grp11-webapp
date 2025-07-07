<?php
// Start session if not already started
include "utils/session.php";

// Initialize variables
$cartItems = [];
$itemCount = 0;
$cartTotal = 0;
$stockIssues = [];

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Include database connection
    include_once "utils/dbconnect.php";

    if ($conn) {
        $userId = $_SESSION['user_id'];

        // Fetch cart items with product details including stock
        $stmt = $conn->prepare("SELECT c.cart_id, c.cart_prod_id, c.cart_quantity, c.cart_subtotal, 
                                p.prod_name, p.prod_image, p.prod_description, p.prod_stock
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
            
            // Add stock info to cart item
            $row['in_other_carts'] = $inOtherCarts;
            $row['effective_stock'] = $row['prod_stock'] - $inOtherCarts;
            
            $cartItems[] = $row;
            $itemCount += $row['cart_quantity'];
            $cartTotal += $row['cart_subtotal'];
            
            $otherCartStmt->close();
        }

        $stmt->close();
    }
}

// Check if any cart item quantities exceed their effective stock
foreach ($cartItems as $item) {
    if ($item['cart_quantity'] > $item['effective_stock']) {
        if ($item['effective_stock'] <= 0) {
            $stockIssues[] = [
                'product' => $item['prod_name'],
                'message' => 'is no longer available and will be removed at checkout.',
                'cart_id' => $item['cart_id'],
                'severity' => 'danger'
            ];
        } else {
            $stockIssues[] = [
                'product' => $item['prod_name'],
                'message' => "only has {$item['effective_stock']} items available (you have {$item['cart_quantity']}). " . 
                             "Quantity will be adjusted at checkout.",
                'cart_id' => $item['cart_id'],
                'effective_stock' => $item['effective_stock'],
                'severity' => 'warning'
            ];
        }
    }
}
?>

<div class="order-md-last">
    <?php if (!isset($_SESSION['user_id'])): ?>
        <!-- Message for guests -->
        <div class="text-center py-5">
            <h4 class="mb-4">Please log in to view your cart</h4>
            <a href="login.php" class="btn btn-primary">Log In</a>
            <p class="mt-3">Don't have an account? <a href="register.php">Register</a></p>
        </div>
    <?php else: ?>
        <h4 class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-primary">Your cart</span>
            <span class="badge bg-primary rounded-pill"><?php echo $itemCount; ?></span>
        </h4>

        <?php if (empty($cartItems)): ?>
            <div class="text-center py-5">
                <svg width="80" height="80" class="text-muted mb-3">
                    <use xlink:href="#cart"></use>
                </svg>
                <h4 class="mb-3">Your cart is empty</h4>
                <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                <a href="index.php" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php else: ?>
            <ul class="list-group mb-3">
                <?php foreach ($cartItems as $item): ?>
                    <li class="list-group-item py-3">
                        <div class="row align-items-center">
                            <!-- Product image and info -->
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($item['prod_image'])): ?>
                                        <img src="/<?php echo $item['prod_image']; ?>"
                                            alt="<?php echo htmlspecialchars($item['prod_name']); ?>" 
                                            class="me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                        <img src="images/product-placeholder.png" 
                                            alt="Product placeholder" 
                                            class="me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php endif; ?>
                                    <div class="overflow-hidden">
                                        <h6 class="my-0 text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($item['prod_name']); ?>">
                                            <?php echo htmlspecialchars($item['prod_name']); ?>
                                        </h6>
                                        <small class="text-body-secondary">
                                            $<?php echo number_format($item['cart_subtotal'] / $item['cart_quantity'], 2); ?> each
                                        </small>                                                
                                        <?php if ($item['cart_quantity'] > $item['effective_stock'] && $item['effective_stock'] > 0): ?>
                                            <div class="stock-warning low-stock">
                                                <i class="fa fa-exclamation-triangle me-1"></i>
                                                <strong>Only <?php echo $item['effective_stock']; ?> available</strong>
                                            </div>
                                        <?php elseif ($item['effective_stock'] <= 0): ?>
                                            <div class="stock-warning out-of-stock">
                                                <i class="fa fa-ban me-1"></i>
                                                <strong>No longer available</strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Subtotal -->
                            <div class="col-2 text-center">
                                <span class="d-block fw-bold">
                                    $<?php echo number_format($item['cart_subtotal'], 2); ?>
                                </span>
                            </div>
                            
                            <!-- Quantity controls -->
                            <div class="col-3 text-center">
                                <div class="d-flex justify-content-center align-items-center">
                                    <button class="btn btn-sm btn-outline-secondary cart-quantity-update"
                                        data-cart-id="<?php echo $item['cart_id']; ?>" data-action="decrease">
                                        <i class="fa fa-minus" aria-hidden="true"></i>
                                    </button>
                                    <span class="mx-2 fw-medium" style="min-width: 24px; display: inline-block;">
                                        <?php echo $item['cart_quantity']; ?>
                                    </span>
                                    <button class="btn btn-sm btn-outline-secondary cart-quantity-update"
                                        data-cart-id="<?php echo $item['cart_id']; ?>" data-action="increase"
                                        <?php echo ($item['cart_quantity'] >= $item['effective_stock']) ? 'disabled' : ''; ?>>
                                        <i class="fa fa-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Remove button -->
                            <div class="col-1 text-end">
                                <button class="btn btn-sm btn-outline-danger cart-item-remove" 
                                    data-cart-id="<?php echo $item['cart_id']; ?>">
                                    <i class="fa fa-trash" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            
                <!-- Total line -->
                <li class="list-group-item d-flex justify-content-between py-3">
                    <span class="fs-5">Total (SGD)</span>
                    <strong class="fs-5">$<?php echo number_format($cartTotal, 2); ?></strong>
                </li>
            </ul>
                                        
            <div class="d-grid gap-2 mt-4">
                <?php if (!empty($stockIssues)): ?>
                    <button class="btn btn-primary btn-lg checkout-btn" id="checkoutWithWarning">
                        <i class="fa fa-shopping-bag me-2"></i> Continue to checkout
                    </button>
                <?php else: ?>
                    <button class="btn btn-primary btn-lg" onclick="window.location.href='checkout.php'">
                        <i class="fa fa-shopping-bag me-2"></i> Continue to checkout
                    </button>
                <?php endif; ?>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-2"></i> Continue Shopping
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>