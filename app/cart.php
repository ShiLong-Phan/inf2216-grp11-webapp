<?php
// Start session if not already started
include "utils/session.php";

// Initialize variables
$cartItems = [];
$itemCount = 0;
$cartTotal = 0;

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Include database connection
    include_once "utils/dbconnect.php";

    if ($conn) {
        $userId = $_SESSION['user_id'];

        // Fetch cart items with product details
        $stmt = $conn->prepare("SELECT c.cart_id, c.cart_prod_id, c.cart_quantity, c.cart_subtotal, p.prod_name, p.prod_image, p.prod_description 
                               FROM ssdgroup11db.cart c 
                               JOIN ssdgroup11db.products p ON c.cart_prod_id = p.prod_id 
                               WHERE c.cart_user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        // Process results
        while ($row = $result->fetch_assoc()) {
            $cartItems[] = $row;
            $itemCount += $row['cart_quantity'];
            $cartTotal += $row['cart_subtotal'];
        }

        $stmt->close();
    }
}
?>

<div class="offcanvas offcanvas-end" data-bs-scroll="true" tabindex="-1" id="offcanvasCart" aria-labelledby="My Cart">
    <div class="offcanvas-header justify-content-center">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
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
                        <p>No items added in cart yet</p>
                        <a href="index.php" class="btn btn-primary">Shop Now</a>
                    </div>
                <?php else: ?>
                    <ul class="list-group mb-3">
                        <?php foreach ($cartItems as $item): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($item['prod_image'])): ?>
                                                <img src="images/products/<?php echo $item['prod_image']; ?>"
                                                    alt="<?php echo htmlspecialchars($item['prod_name']); ?>" class="me-2"
                                                    style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <img src="images/product-placeholder.png" alt="Product placeholder" class="me-2"
                                                    style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="my-0"><?php echo htmlspecialchars($item['prod_name']); ?></h6>
                                                <small
                                                    class="text-body-secondary">$<?php echo number_format($item['cart_subtotal'] / $item['cart_quantity'], 2); ?>
                                                    each</small>
                                            </div>
                                        </div>
                                        <span
                                            class="text-body-secondary">$<?php echo number_format($item['cart_subtotal'], 2); ?></span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <!-- Quantity controls -->
                                        <div class="d-flex align-items-center">
                                            <button class="btn btn-sm btn-outline-secondary cart-quantity-update"
                                                data-cart-id="<?php echo $item['cart_id']; ?>" data-action="decrease">
                                                <i class="fa fa-minus" aria-hidden="true"></i>
                                            </button>
                                            <span class="mx-2"><?php echo $item['cart_quantity']; ?></span>
                                            <button class="btn btn-sm btn-outline-secondary cart-quantity-update"
                                                data-cart-id="<?php echo $item['cart_id']; ?>" data-action="increase">
                                                <i class="fa fa-plus" aria-hidden="true"></i>
                                            </button>
                                        </div>

                                        <!-- Remove button -->
                                        <button class="btn btn-sm cart-item-remove" data-cart-id="<?php echo $item['cart_id']; ?>">
                                            <svg width="16" height="16">
                                                <use xlink:href="#trash"></use>
                                            </svg>
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>

                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total (USD)</span>
                            <strong>$<?php echo number_format($cartTotal, 2); ?></strong>
                        </li>
                    </ul>

                    <button class="w-100 btn btn-primary btn-lg" onclick="window.location.href='checkout.php'">Continue to
                        checkout</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add this script for cart functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Update quantity buttons
        const quantityButtons = document.querySelectorAll('.cart-quantity-update');
        quantityButtons.forEach(button => {
            button.addEventListener('click', function () {
                const cartId = this.getAttribute('data-cart-id');
                const action = this.getAttribute('data-action');

                // AJAX request to update cart
                fetch('update-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cart_id=${cartId}&action=${action}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload the cart to show updated items
                            window.location.reload();
                        } else {
                            alert('Error updating cart: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        });

        // Remove item buttons
        const removeButtons = document.querySelectorAll('.cart-item-remove');
        removeButtons.forEach(button => {
            button.addEventListener('click', function () {
                if (confirm('Are you sure you want to remove this item from your cart?')) {
                    const cartId = this.getAttribute('data-cart-id');

                    // AJAX request to remove item
                    fetch('update-cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `cart_id=${cartId}&action=remove`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Reload the cart to show updated items
                                window.location.reload();
                            } else {
                                alert('Error removing item: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                }
            });
        });
    });
</script>