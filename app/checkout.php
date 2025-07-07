<?php
// Start session if not already started
include "utils/session.php";

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=checkout.php");
    exit;
}

// Initialize variables
$cartItems = [];
$itemCount = 0;
$cartTotal = 0;
$userInfo = null;
$orderId = null;
$errorMessage = null;
$successMessage = null;

// Include database connection
include_once "utils/dbconnect.php";

if ($conn) {
    $userId = $_SESSION['user_id'];

    // Get user information including address
    $userStmt = $conn->prepare("SELECT * FROM ssdgroup11db.users WHERE user_id = ?");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userInfo = $userResult->fetch_assoc();
    $userStmt->close();

    // Fetch cart items with product details
    $cartStmt = $conn->prepare("SELECT c.cart_id, c.cart_prod_id, c.cart_quantity, c.cart_subtotal, 
                                p.prod_name, p.prod_image, p.prod_description, p.prod_price
                                FROM ssdgroup11db.cart c 
                                JOIN ssdgroup11db.products p ON c.cart_prod_id = p.prod_id 
                                WHERE c.cart_user_id = ?");
    $cartStmt->bind_param("i", $userId);
    $cartStmt->execute();
    $cartResult = $cartStmt->get_result();

    // Process cart results
    while ($row = $cartResult->fetch_assoc()) {
        $cartItems[] = $row;
        $itemCount += $row['cart_quantity'];
        $cartTotal += $row['cart_subtotal'];
    }
    $cartStmt->close();

    // If form submitted, process the order
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order']) && !empty($cartItems)) {
        // Get form data
        $fullName = htmlspecialchars($_POST['full_name']);
        $email = htmlspecialchars($_POST['email']);
        $address = htmlspecialchars($_POST['address']);

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Create order
            $orderStmt = $conn->prepare("INSERT INTO ssdgroup11db.orders 
                                        (order_user_id, order_date, order_delivery_address, order_status, order_total) 
                                        VALUES (?, NOW(), ?, 'pending', ?)");
            $orderStmt->bind_param("isd", $userId, $address, $cartTotal);
            $orderStmt->execute();

            // Get the new order ID
            $orderId = $conn->insert_id;

            // Insert order items and update product stock
            $itemStmt = $conn->prepare("INSERT INTO ssdgroup11db.order_item 
                                        (order_item_order_id, order_item_prod_id, prod_item_quantity, prod_subtotal) 
                                        VALUES (?, ?, ?, ?)");

            // Prepare stock update statement
            $updateStockStmt = $conn->prepare("UPDATE ssdgroup11db.products 
                                   SET prod_stock = prod_stock - ? 
                                   WHERE prod_id = ?");

            $stockUpdated = true;
            $stockErrors = [];

            foreach ($cartItems as $item) {
                // First check available stock one more time (to handle race conditions)
                $checkStockStmt = $conn->prepare("SELECT prod_stock FROM ssdgroup11db.products WHERE prod_id = ?");
                $checkStockStmt->bind_param("i", $item['cart_prod_id']);
                $checkStockStmt->execute();
                $stockResult = $checkStockStmt->get_result();
                $currentStock = $stockResult->fetch_assoc()['prod_stock'];

                // Adjust quantity if it exceeds available stock
                $quantityToOrder = $item['cart_quantity'];
                if ($quantityToOrder > $currentStock) {
                    if ($currentStock <= 0) {
                        // Skip this item if no stock available
                        $stockErrors[] = "Sorry, {$item['prod_name']} is no longer in stock.";
                        continue;
                    }
                    $quantityToOrder = $currentStock;
                    $item['cart_subtotal'] = $item['prod_price'] * $quantityToOrder;
                    $stockErrors[] = "Only {$currentStock} units of {$item['prod_name']} were available. Adjusted your order.";
                }

                // Add item to order
                $itemStmt->bind_param(
                    "iiid",
                    $orderId,
                    $item['cart_prod_id'],
                    $quantityToOrder,
                    $item['cart_subtotal']
                );
                $itemStmt->execute();

                // Update product stock - MODIFIED: Changed to 2 parameters
                $updateStockStmt->bind_param(
                    "ii",
                    $quantityToOrder,
                    $item['cart_prod_id']
                );

                if (!$updateStockStmt->execute() || $updateStockStmt->affected_rows == 0) {
                    $stockUpdated = false;
                    $stockErrors[] = "Failed to update stock for {$item['prod_name']}.";
                }
            }
            // If we had stock errors but some items were processed, adjust the order total
            if (!empty($stockErrors)) {
                // Recalculate the total based on what was actually ordered
                $recalcStmt = $conn->prepare("SELECT SUM(prod_subtotal) as new_total 
                                             FROM ssdgroup11db.order_item 
                                             WHERE order_item_order_id = ?");
                $recalcStmt->bind_param("i", $orderId);
                $recalcStmt->execute();
                $result = $recalcStmt->get_result();
                $newTotal = $result->fetch_assoc()['new_total'];

                // Update the order total
                $updateTotalStmt = $conn->prepare("UPDATE ssdgroup11db.orders 
                                                  SET order_total = ? 
                                                  WHERE order_id = ?");
                $updateTotalStmt->bind_param("di", $newTotal, $orderId);
                $updateTotalStmt->execute();

                // Store messages to display after redirect
                $_SESSION['order_stock_messages'] = $stockErrors;
            }

            // Clear the cart
            $clearCartStmt = $conn->prepare("DELETE FROM ssdgroup11db.cart WHERE cart_user_id = ?");
            $clearCartStmt->bind_param("i", $userId);
            $clearCartStmt->execute();

            // Commit transaction
            $conn->commit();

            // Send order confirmation email
            try {
                // Include PHPMailer
                require_once 'PHPMailer/src/Exception.php';
                require_once 'PHPMailer/src/PHPMailer.php';
                require_once 'PHPMailer/src/SMTP.php';

                // Create a new PHPMailer instance
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Update with your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = getenv('MAIL_USERNAME'); // Update with your email
                $mail->Password = getenv('MAIL_PASSWORD'); // Update with your password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                // Get user email from database
                $userStmt = $conn->prepare("SELECT user_email, user_firstname, user_lastname FROM ssdgroup11db.users WHERE user_id = ?");
                $userStmt->bind_param("i", $userId);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                $userData = $userResult->fetch_assoc();
                $userEmail = $userData['user_email'];
                $userName = $userData['user_firstname'] . ' ' . $userData['user_lastname'];

                // Recipients
                $mail->setFrom('crumblymoo@gmail.com', 'Crumbly');
                $mail->addAddress($userEmail, $userName);

                // Prepare order items HTML for email
                $orderItemsHtml = '';
                $orderTotal = 0;

                // Get the order items to include in the email
                $emailItemsStmt = $conn->prepare("SELECT oi.*, p.prod_name, p.prod_image 
                                                FROM ssdgroup11db.order_item oi
                                                JOIN ssdgroup11db.products p ON oi.order_item_prod_id = p.prod_id
                                                WHERE oi.order_item_order_id = ?");
                $emailItemsStmt->bind_param("i", $orderId);
                $emailItemsStmt->execute();
                $emailItemsResult = $emailItemsStmt->get_result();

                while ($item = $emailItemsResult->fetch_assoc()) {
                    $itemTotal = $item['prod_subtotal'];
                    $orderTotal += $itemTotal;
                    $unitPrice = $itemTotal / $item['prod_item_quantity'];

                    $orderItemsHtml .= '
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">' . htmlspecialchars($item['prod_name']) . '</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: center;">$' . number_format($unitPrice, 2) . '</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: center;">' . $item['prod_item_quantity'] . '</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">$' . number_format($itemTotal, 2) . '</td>
                    </tr>';
                }

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Crumbly - Order Confirmation #' . $orderId;

                // HTML email body
                $mail->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background-color: #4CAF50; padding: 20px; text-align: center; color: white;">
                        <h1>Order Confirmation</h1>
                    </div>
                    <div style="padding: 20px; border: 1px solid #ddd; border-top: none;">
                        <div style="text-align: center; margin-bottom: 30px;">
                            <h2 style="color: #4CAF50;">Thank You for Your Order!</h2>
                            <p style="font-size: 16px;">Your order has been placed successfully.</p>
                        </div>
                        
                        <div style="margin-bottom: 20px; overflow: hidden;">
                            <div style="width: 48%; float: left;">
                                <h3 style="margin-top: 0; color: #333;">Order Information</h3>
                                <p><strong>Order Number:</strong> #' . $orderId . '</p>
                                <p><strong>Order Date:</strong> ' . date('F j, Y') . '</p>
                                <p><strong>Order Status:</strong> <span style="background-color: #ffc107; color: #212529; padding: 3px 8px; border-radius: 4px; font-size: 12px;">Pending</span></p>
                            </div>
                            <div style="width: 48%; float: right;">
                                <h3 style="margin-top: 0; color: #333;">Customer Information</h3>
                                <p><strong>Name:</strong> ' . htmlspecialchars($userName) . '</p>
                                <p><strong>Email:</strong> ' . htmlspecialchars($userEmail) . '</p>
                                <p><strong>Delivery Address:</strong> ' . htmlspecialchars($address) . '</p>
                            </div>
                        </div>
                        
                        <div style="clear: both; margin-top: 20px;">
                            <h3 style="color: #333;">Order Summary</h3>
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color: #f8f9fa;">
                                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">Product</th>
                                        <th style="padding: 10px; text-align: center; border-bottom: 2px solid #dee2e6;">Unit Price</th>
                                        <th style="padding: 10px; text-align: center; border-bottom: 2px solid #dee2e6;">Quantity</th>
                                        <th style="padding: 10px; text-align: right; border-bottom: 2px solid #dee2e6;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ' . $orderItemsHtml . '
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" style="padding: 10px; text-align: right; border-top: 2px solid #dee2e6;">Total:</th>
                                        <th style="padding: 10px; text-align: right; border-top: 2px solid #dee2e6;">$' . number_format($orderTotal, 2) . '</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div style="margin-top: 30px; text-align: center;">
                            <p>If you have any questions about your order, please contact our customer service.</p>
                            <p>Thank you for shopping with Crumbly!</p>
                        </div>
                    </div>
                    <div style="background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #6c757d;">
                        <p>This is an automated email, please do not reply to this message.</p>
                        <p>&copy; ' . date('Y') . ' Crumbly. All rights reserved.</p>
                    </div>
                </div>';

                // Plain text alternative
                $mail->AltBody = "
                ORDER CONFIRMATION #$orderId
                
                Thank you for your order!
                
                Order Information:
                Order Number: #$orderId
                Order Date: " . date('F j, Y') . "
                Order Status: Pending
                
                Customer Information:
                Name: $userName
                Email: $userEmail
                Delivery Address: $address
                
                Order Total: $" . number_format($orderTotal, 2) . "
                
                Thank you for shopping with Crumbly!
                ";

                $mail->send();

                // Set a flag for email sent
                $_SESSION['email_sent'] = true;
            } catch (Exception $e) {
                // Log the error but don't prevent order completion
                error_log("Order confirmation email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                $_SESSION['email_sent'] = false;
            }

            // If there were no items processed at all, inform the user
            if (empty($stockErrors)) {
                $successMessage = "Order #$orderId has been placed successfully!";
            }

            // Redirect to order confirmation page
            header("Location: order-confirmation.php?order_id=$orderId");
            exit;

        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $errorMessage = "Order processing failed: " . $e->getMessage();
        }
    }
}

// Include header
include "utils/header.php";
include "utils/navbar.php";
?>

<div class="container my-5">
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger mb-4"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success mb-4"><?php echo $successMessage; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Order Form Column -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Checkout</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($cartItems)): ?>
                        <div class="text-center py-5">
                            <p>Your cart is empty. Please add items before checking out.</p>
                            <a href="index.php" class="btn btn-primary">Shop Now</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="checkout.php">
                            <!-- Customer Information -->
                            <h5 class="mb-3">Customer Information</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name"
                                        value="<?php echo htmlspecialchars(($userInfo['user_firstname'] ?? '') . ' ' . ($userInfo['user_lastname'] ?? '')); ?>"
                                        required>
                                </div>
                                <div class="col-12">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?php echo htmlspecialchars($userInfo['user_email'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <!-- Shipping Address -->
                            <h5 class="mb-3">Shipping Address</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address"
                                        value="<?php echo htmlspecialchars($userInfo['user_address'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="row my-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cc-name">Name on card</label>
                                        <input type="text" class="form-control" id="cc-name"
                                            placeholder="Full name as displayed on card" required>
                                        <small class="text-muted">Full name as displayed on card</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cc-number">Credit card number</label>
                                        <input type="text" class="form-control" id="cc-number"
                                            placeholder="xxxx-xxxx-xxxx-xxxx" required
                                            pattern="\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}">
                                    </div>
                                </div>
                                <div class="col-md-3 mt-3">
                                    <div class="form-group">
                                        <label for="cc-expiration">Expiration</label>
                                        <input type="text" class="form-control" id="cc-expiration" placeholder="MM/YY"
                                            required pattern="(0[1-9]|1[0-2])\/\d{2}">
                                    </div>
                                </div>
                                <div class="col-md-3 mt-3">
                                    <div class="form-group">
                                        <label for="cc-cvv">CVV</label>
                                        <input type="text" class="form-control" id="cc-cvv" placeholder="123" required
                                            pattern="\d{3,4}">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <button class="w-100 btn btn-primary btn-lg" type="submit" name="place_order">Place
                                Order</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Order Summary Column -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group mb-3">
                        <?php foreach ($cartItems as $item): ?>
                            <li class="list-group-item d-flex justify-content-between lh-sm">
                                <div>
                                    <h6 class="my-0"><?php echo htmlspecialchars($item['prod_name']); ?></h6>
                                    <small class="text-muted">
                                        $<?php echo number_format($item['cart_subtotal'] / $item['cart_quantity'], 2); ?> ×
                                        <?php echo $item['cart_quantity']; ?>
                                    </small>
                                </div>
                                <span class="text-muted">$<?php echo number_format($item['cart_subtotal'], 2); ?></span>
                            </li>
                        <?php endforeach; ?>

                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total (USD)</span>
                            <strong>$<?php echo number_format($cartTotal, 2); ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include "utils/footer.php";
?>