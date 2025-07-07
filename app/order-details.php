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
    header("Location: account-orders.php"); // Redirect to orders page instead of index
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
        header("Location: account-orders.php");
        exit;
    }

    $orderDetails = $orderResult->fetch_assoc();
    $orderStmt->close();

    // Get order items
    $itemsStmt = $conn->prepare("SELECT i.*, p.prod_name, p.prod_image, p.prod_price 
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
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="account-orders.php">My Orders</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Order #<?php echo $orderDetails['order_id']; ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Order Receipt Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Order Receipt</h3>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class="fa fa-print me-1"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title border-bottom pb-2">Order Details</h5>
                            <div class="mb-1"><strong>Order Number:</strong> #<?php echo $orderDetails['order_id']; ?></div>
                            <div class="mb-1"><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($orderDetails['order_date'])); ?></div>
                            <div class="mb-1">
                                <strong>Order Status:</strong> 
                                
                                <span><?php echo ucfirst($orderDetails['order_status']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title border-bottom pb-2">Customer Information</h5>
                            <div class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($orderDetails['user_firstname'] . ' ' . $orderDetails['user_lastname']); ?></div>
                            <div class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($orderDetails['user_email']); ?></div>
                            <div class="mb-1"><strong>Delivery Address:</strong> <?php echo htmlspecialchars($orderDetails['order_delivery_address']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items Section -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Order Items</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50%">Product</th>
                                    <th class="text-center">Unit Price</th>
                                    <th class="text-center">Quantity</th>
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
                                                    alt="<?php echo htmlspecialchars($item['prod_name']); ?>"
                                                    class="me-3 rounded"
                                                    style="width:60px; height:60px; object-fit:cover;">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['prod_name']); ?></h6>
                                                    <small class="text-muted"><?php echo substr($item['order_item_id'], -6); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">$<?php echo number_format($item['prod_subtotal'] / $item['prod_item_quantity'], 2); ?></td>
                                        <td class="text-center align-middle"><?php echo $item['prod_item_quantity']; ?></td>
                                        <td class="text-end align-middle">$<?php echo number_format($item['prod_subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 offset-md-6">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <td>Subtotal</td>
                                        <td class="text-end">$<?php echo number_format($orderDetails['order_total'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Shipping</td>
                                        <td class="text-end">Free</td>
                                    </tr>
                                    <tr>
                                        <td>Tax</td>
                                        <td class="text-end">Included</td>
                                    </tr>
                                    <tr>
                                        <td class="border-top pt-2"><strong>Total</strong></td>
                                        <td class="text-end border-top pt-2"><strong>$<?php echo number_format($orderDetails['order_total'], 2); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Payment Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Payment Status:</strong> <span class="badge bg-success">Paid</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Payment Date:</strong> <?php echo date('F j, Y', strtotime($orderDetails['order_date'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Section -->
            <div class="d-flex justify-content-between mt-4">
                <a href="viewcustomerorders.php" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Back to Orders
                </a>
                <div>
                    <button class="btn btn-outline-primary me-2" onclick="window.print()">
                        <i class="fa fa-print me-1"></i> Print Receipt
                    </button>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fa fa-shopping-bag me-1"></i> Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Print-specific styles */
@media print {
    .breadcrumb, nav, footer, .btn, .navbar {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
    
    body {
        padding: 0;
        margin: 0;
    }
    
    .container {
        max-width: 100% !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
}
</style>

<?php
// Include footer
include "utils/footer.php";
?>