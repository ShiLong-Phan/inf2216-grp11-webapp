<?php
session_start();

// Check if database connection is missing
if (!isset($conn) || !$conn) {
    // Include database connection
    require_once 'utils/dbconnect.php';
}

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$active_tab = isset($_GET['status']) ? $_GET['status'] : 'all';
$userid = $_SESSION['userid'];
$orders = [];

// Define status types
$status_types = [
    'all' => 'All Orders',
    'to_ship' => 'To Ship',
    'to_receive' => 'To Receive',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];

// Fetch orders based on selected status
$status_condition = "";
if ($active_tab !== 'all') {
    $status_condition = "AND o.status = ?";
}

$sql = "SELECT o.order_id, o.order_date, o.total_amount, o.status, 
               COUNT(oi.item_id) as item_count,
               (SELECT image_url FROM products p WHERE p.product_id = 
                  (SELECT product_id FROM order_items WHERE order_id = o.order_id LIMIT 1)
               ) as thumbnail
        FROM orders o 
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.user_id = ? $status_condition
        GROUP BY o.order_id
        ORDER BY o.order_date DESC";

try {
    $stmt = $conn->prepare($sql);
    
    if ($active_tab === 'all') {
        $stmt->bind_param("i", $userid);
    } else {
        $status = $active_tab;
        $stmt->bind_param("is", $userid, $status);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
} catch (Exception $e) {
    // Handle error
    $error_message = "Error fetching orders: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Orders - FoodMart</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="author" content="">
    <meta name="keywords" content="">
    <meta name="description" content="">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="css/vendor.css">
    <link rel="stylesheet" type="text/css" href="style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    
    <style>
        .order-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        }
        .status-badge {
            font-size: 0.8rem;
        }
        .status-to_ship {
            background-color: #ffc107;
        }
        .status-to_receive {
            background-color: #17a2b8;
        }
        .status-completed {
            background-color: #28a745;
        }
        .status-cancelled {
            background-color: #dc3545;
        }
        .order-items {
            font-size: 0.9rem;
        }
        .nav-tabs .nav-link {
            color: #495057;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }
        .nav-tabs .nav-link.active {
            color: #ff6b6b;
            border-color: #ff6b6b;
            background-color: transparent;
        }
        .nav-tabs .nav-link:hover:not(.active) {
            border-color: rgba(255, 107, 107, 0.5);
        }
        .empty-orders {
            padding: 4rem 0;
            text-align: center;
        }
        .empty-orders i {
            font-size: 5rem;
            color: #e0e0e0;
            margin-bottom: 1rem;
        }
        .order-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .order-date {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
        <defs>
            <symbol xmlns="http://www.w3.org/2000/svg" id="user" viewBox="0 0 24 24">
                <path fill="currentColor" d="M15.71 12.71a6 6 0 1 0-7.42 0a10 10 0 0 0-6.22 8.18a1 1 0 0 0 2 .22a8 8 0 0 1 15.9 0a1 1 0 0 0 1 .89h.11a1 1 0 0 0 .88-1.1a10 10 0 0 0-6.25-8.19ZM12 12a4 4 0 1 1 4-4a4 4 0 0 1-4 4Z" />
            </symbol>
            <symbol xmlns="http://www.w3.org/2000/svg" id="cart" viewBox="0 0 24 24">
                <path fill="currentColor" d="M8 18a2 2 0 1 0 2 2a2 2 0 0 0-2-2Zm8 0a2 2 0 1 0 2 2a2 2 0 0 0-2-2Zm3.37-6.5L21 7H7.05L6.33 4H3v2h2.13l2.67 11h10.44L20 10.5Z"/>
            </symbol>
            <symbol xmlns="http://www.w3.org/2000/svg" id="cake" viewBox="0 0 24 24">
                <path fill="currentColor" d="M21 21H3a1 1 0 0 1-1-1v-6c0-1.1.9-2 2-2h16a2 2 0 0 1 2 2v6a1 1 0 0 1-1 1M5 12a1 1 0 0 0-1 1v4h16v-4a1 1 0 0 0-1-1zM8 5c0-1.66 1.34-3 3-3c.57 0 1.09.16 1.5.43c.43-.27.93-.43 1.5-.43c1.66 0 3 1.34 3 3v3H8zm11 2v2H5V7z"/>
            </symbol>
        </defs>
    </svg>

    <div class="preloader-wrapper">
        <div class="preloader"></div>
    </div>

    <?php include 'utils/header.php'; ?>

    <main>
        <section class="py-5">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <h1 class="display-6 mb-4">My Orders</h1>
                        
                        <!-- Order Status Tabs -->
                        <ul class="nav nav-tabs mb-4" id="orderTabs" role="tablist">
                            <?php foreach($status_types as $status_key => $status_label): ?>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link <?php echo ($active_tab === $status_key) ? 'active' : ''; ?>" 
                                       href="?status=<?php echo $status_key; ?>"
                                       role="tab">
                                        <?php echo $status_label; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <!-- Orders List -->
                        <div class="tab-content">
                            <div class="tab-pane fade show active">
                                <?php if(empty($orders)): ?>
                                    <div class="empty-orders">
                                        <i class="bi bi-basket"></i>
                                        <h3>No orders found</h3>
                                        <p class="text-muted">You don't have any <?php echo strtolower($status_types[$active_tab]); ?> yet.</p>
                                        <a href="index.php" class="btn btn-primary mt-3">Start Shopping</a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($orders as $order): ?>
                                        <div class="card order-card mb-4 border-0 shadow-sm">
                                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="text-muted">Order #<?php echo $order['order_id']; ?></span>
                                                    <span class="order-date ms-3"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                                                </div>
                                                <span class="badge status-<?php echo $order['status']; ?> text-white">
                                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-md-2 col-sm-3 mb-3 mb-md-0">
                                                        <img src="<?php echo !empty($order['thumbnail']) ? $order['thumbnail'] : 'images/thumb-cake-default.png'; ?>" 
                                                             alt="Order thumbnail" 
                                                             class="order-thumbnail">
                                                    </div>
                                                    <div class="col-md-7 col-sm-9">
                                                        <h5 class="card-title"><?php echo $order['item_count']; ?> <?php echo $order['item_count'] > 1 ? 'items' : 'item'; ?></h5>
                                                        <p class="card-text order-items text-muted">
                                                            Delicious pastries and cakes
                                                        </p>
                                                    </div>
                                                    <div class="col-md-3 mt-3 mt-md-0 text-md-end">
                                                        <p class="mb-2">Total: <span class="fw-bold"><?php echo '$' . number_format($order['total_amount'], 2); ?></span></p>
                                                        <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                                        
                                                        <?php if($order['status'] === 'to_receive'): ?>
                                                            <button class="btn btn-sm btn-success ms-2">Received</button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if($order['status'] === 'to_ship'): ?>
                                                            <button class="btn btn-sm btn-outline-danger ms-2">Cancel</button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Featured Products Section -->
        <section class="py-5 bg-light">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center mb-4">
                        <h2>You might also like</h2>
                        <p class="text-muted">Discover our delicious treats</p>
                    </div>
                </div>
                
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                    <!-- Featured Product Item 1 -->
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm">
                            <img src="images/thumb-cake1.jpg" class="card-img-top" alt="Chocolate Cake">
                            <div class="card-body">
                                <h5 class="card-title">Chocolate Fudge Cake</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary">$24.99</span>
                                    <button class="btn btn-sm btn-outline-primary">Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Featured Product Item 2 -->
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm">
                            <img src="images/thumb-cake2.jpg" class="card-img-top" alt="Strawberry Cheesecake">
                            <div class="card-body">
                                <h5 class="card-title">Strawberry Cheesecake</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary">$22.99</span>
                                    <button class="btn btn-sm btn-outline-primary">Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Featured Product Item 3 -->
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm">
                            <img src="images/thumb-cake3.jpg" class="card-img-top" alt="Blueberry Muffins">
                            <div class="card-body">
                                <h5 class="card-title">Blueberry Muffins (6pc)</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary">$12.99</span>
                                    <button class="btn btn-sm btn-outline-primary">Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Featured Product Item 4 -->
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm">
                            <img src="images/thumb-cake4.jpg" class="card-img-top" alt="Red Velvet Cupcakes">
                            <div class="card-body">
                                <h5 class="card-title">Red Velvet Cupcakes (4pc)</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary">$16.99</span>
                                    <button class="btn btn-sm btn-outline-primary">Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'utils/footer.php'; ?>

    <script src="js/jquery-1.11.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="js/plugins.js"></script>
    <script src="js/script.js"></script>
</body>
</html>