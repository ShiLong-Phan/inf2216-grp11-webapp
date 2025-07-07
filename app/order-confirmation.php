<?php
// Start session if not already started
include "utils/session.php";

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    header("Location: index.php");
    exit;
}

$orderId = $_GET['order_id'];
$orderDetails = null;
$orderItems = [];

// Include database connection
include_once "utils/dbconnect.php";

if ($conn) {
    $userId = $_SESSION['user_id'];

    // Get order details
    $orderStmt = $conn->prepare("SELECT o.*, u.user_firstname, u.user_lastname, u.user_email 
                                FROM ssdgroup11db.orders o
                                JOIN ssdgroup11db.users u ON o.order_user_id = u.user_id
                                WHERE o.order_id = ? AND o.order_user_id = ?");
    $orderStmt->bind_param("ii", $orderId, $userId);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();

    if ($orderResult->num_rows === 0) {
        // Order not found or doesn't belong to user
        header("Location: index.php");
        exit;
    }

    $orderDetails = $orderResult->fetch_assoc();
    $orderStmt->close();

    // Get order items
    $itemsStmt = $conn->prepare("SELECT i.*, p.prod_name, p.prod_image 
                                FROM ssdgroup11db.order_item i
                                JOIN ssdgroup11db.products p ON i.order_item_prod_id = p.prod_id
                                WHERE i.order_item_order_id = ?");
    $itemsStmt->bind_param("i", $orderId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();

    while ($row = $itemsResult->fetch_assoc()) {
        $orderItems[] = $row;
    }
    $itemsStmt->close();
}

// Include header
include "utils/header.php";
include "utils/navbar.php";
?>

<div class="container my-5">
    <div class="card border-success">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0">Order Confirmation</h3>
        </div>
        <div class="card-body">
            <div class="text-center mb-4">
                <i class="fa fa-check-circle text-success" style="font-size: 64px;"></i>
                <h2 class="mt-3">Thank You for Your Order!</h2>
                <p class="lead">Your order has been placed successfully.</p>

                <?php if (isset($_SESSION['email_sent'])): ?>
                    <?php if ($_SESSION['email_sent']): ?>
                        <div class="alert alert-success mt-3">
                            <i class="fa fa-envelope me-2"></i> A confirmation email has been sent to your email address.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-3">
                            <i class="fa fa-exclamation-triangle me-2"></i> We couldn't send a confirmation email. Please keep
                            your order number for reference.
                        </div>
                    <?php endif; ?>
                    <?php unset($_SESSION['email_sent']); // Clear the flag ?>
                <?php endif; ?>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Order Information</h5>
                    <p><strong>Order Number:</strong> #<?php echo $orderDetails['order_id']; ?></p>
                    <p><strong>Order Date:</strong>
                        <?php echo date('F j, Y, g:i a', strtotime($orderDetails['order_date'])); ?></p>
                    <p><strong>Order Status:</strong>
                        <span>
                            <?php echo ucfirst($orderDetails['order_status']); ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <h5>Customer Information</h5>
                    <p><strong>Name:</strong>
                        <?php echo htmlspecialchars($orderDetails['user_firstname'] . ' ' . $orderDetails['user_lastname']); ?>
                    </p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($orderDetails['user_email']); ?></p>
                    <p><strong>Delivery Address:</strong>
                        <?php echo htmlspecialchars($orderDetails['order_delivery_address']); ?></p>
                </div>
            </div>

            <h5>Order Summary</h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Unit Price</th>
                            <th>Quantity</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php
                                        // Either the DB path or the placeholder
                                        $imgSrc = !empty($item['prod_image'])
                                            ? $item['prod_image']
                                            : 'images/product-placeholder.png';
                                        ?>
                                        <img src="<?php echo htmlspecialchars($imgSrc); ?>"
                                            alt="<?php echo htmlspecialchars($item['prod_name']); ?>" class="me-2"
                                            style="width:50px; height:50px; object-fit:cover;">
                                        <?php echo htmlspecialchars($item['prod_name']); ?>
                                    </div>
                                </td>
                                <td>$<?php echo number_format($item['prod_subtotal'] / $item['prod_item_quantity'], 2); ?>
                                </td>
                                <td><?php echo $item['prod_item_quantity']; ?></td>
                                <td class="text-end">$<?php echo number_format($item['prod_subtotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total:</th>
                            <th class="text-end">$<?php echo number_format($orderDetails['order_total'], 2); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include "utils/footer.php";
?>