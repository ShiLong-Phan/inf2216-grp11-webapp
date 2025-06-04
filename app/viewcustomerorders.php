<?php
session_start();

// Check if database connection is missing
if (!isset($conn) || !$conn) {
    // Include database connection
    require_once 'utils/dbconnect.php';
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$active_tab = isset($_GET['status']) ? $_GET['status'] : 'all';
$user_id = $_SESSION['user_id'];
$orders = [];

// Define status types
$status_types = [
    'all' => 'All',
    'pending' => 'Pending',
    'to_receive' => 'To Receive',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];

// Fetch orders based on selected status
$status_condition = "";
if ($active_tab !== 'all') {
    $status_condition = "AND o.order_status = ?";
}

$sql = "SELECT o.order_id, o.order_date, o.order_total, o.order_status, 
               COUNT(oi.order_item_id) as item_count,
               (SELECT p.prod_image FROM products p 
                INNER JOIN order_item oi2 ON p.prod_id = oi2.order_item_prod_id 
                WHERE oi2.order_item_order_id = o.order_id 
                LIMIT 1) as thumbnail
        FROM orders o 
        LEFT JOIN order_item oi ON o.order_id = oi.order_item_order_id
        WHERE o.order_user_id = ? $status_condition
        GROUP BY o.order_id
        ORDER BY o.order_date DESC";


try {
    $stmt = $conn->prepare($sql);

    if ($active_tab === 'all') {
        $stmt->bind_param("i", $user_id);
    } else {
        $status = $active_tab;
        $stmt->bind_param("is", $user_id, $status);
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
    <?php include 'utils/header.php'; ?>
    <style>
        .order-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
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
            padding: 0.75rem 1.25rem;
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
    <?php include 'utils/navbar.php'; ?>

    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
        <defs>
            <symbol xmlns="http://www.w3.org/2000/svg" id="user" viewBox="0 0 24 24">
                <path fill="currentColor" d="M15.71 12.71a6 6 0 1 0-7.42 0a10 10 0 0 0-6.22 8.18a1 1 0 0 0 2 .22a8 8 0 0 1 15.9 0a1 1 0 0 0 1 .89h.11a1 1 0 0 0 .88-1.1a10 10 0 0 0-6.25-8.19ZM12 12a4 4 0 1 1 4-4a4 4 0 0 1-4 4Z" />
            </symbol>
            <symbol xmlns="http://www.w3.org/2000/svg" id="cart" viewBox="0 0 24 24">
                <path fill="currentColor" d="M8 18a2 2 0 1 0 2 2a2 2 0 0 0-2-2Zm8 0a2 2 0 1 0 2 2a2 2 0 0 0-2-2Zm3.37-6.5L21 7H7.05L6.33 4H3v2h2.13l2.67 11h10.44L20 10.5Z" />
            </symbol>
            <symbol xmlns="http://www.w3.org/2000/svg" id="cake" viewBox="0 0 24 24">
                <path fill="currentColor" d="M21 21H3a1 1 0 0 1-1-1v-6c0-1.1.9-2 2-2h16a2 2 0 0 1 2 2v6a1 1 0 0 1-1 1M5 12a1 1 0 0 0-1 1v4h16v-4a1 1 0 0 0-1-1zM8 5c0-1.66 1.34-3 3-3c.57 0 1.09.16 1.5.43c.43-.27.93-.43 1.5-.43c1.66 0 3 1.34 3 3v3H8zm11 2v2H5V7z" />
            </symbol>
        </defs>
    </svg>

    <div class="preloader-wrapper">
        <div class="preloader"></div>
    </div>

    <main>
        <div class="container-fluid px-0">
            <div class="row justify-content-center">
                <div class="col-md-12 col-lg-12 col-xl-10">
                    <div class="row">
                        <!-- Left side: Profile header with navigation buttons -->
                        <div class="col-md-4 mb-4 mb-md-0">
                            <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-primary text-white" style="box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-white text-primary rounded-circle p-2 me-3" style="filter: drop-shadow(0 2px 3px rgba(0,0,0,0.1));">
                                            <svg width="40" height="40">
                                                <use xlink:href="#user"></use>
                                            </svg>
                                        </div>
                                        <div class="text-start">
                                            <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($_SESSION['user_firstname']); ?></h5>
                                            <p class="text-secondary-50 small mb-0"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                                        </div>
                                    </div>

                                    <!-- Navigation buttons in horizontal layout -->
                                    <div class="d-grid gap-2 mt-2">
                                        <a href="profile.php" class="btn btn-outline-light py-2 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-gear me-2" viewBox="0 0 16 16">
                                                <path d="M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0M8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m.256 7a4.5 4.5 0 0 1-.229-1.004H3c.001-.246.154-.986.832-1.664C4.484 10.68 5.711 10 8 10q.39 0 .74.025c.226-.341.496-.65.804-.918Q8.844 9.002 8 9c-5 0-6 3-6 4s1 1 1 1z" />
                                                <path d="M11.886 9.46c.18-.613 1.048-.613 1.229 0l.043.148a.64.64 0 0 0 .921.382l.136-.074c.561-.306 1.175.308.87.869l-.075.136a.64.64 0 0 0 .382.92l.149.045c.612.18.612 1.048 0 1.229l-.15.043a.64.64 0 0 0-.38.921l.074.136c.305.561-.309 1.175-.87.87l-.136-.075a.64.64 0 0 0-.92.382l-.045.149c-.18.612-1.048.612-1.229 0l-.043-.15a.64.64 0 0 0-.921-.38l-.136.074c-.561.305-1.175-.309-.87-.87l.075-.136a.64.64 0 0 0-.382-.92l-.148-.045c-.613-.18-.613-1.048 0-1.229l.148-.043a.64.64 0 0 0 .382-.921l-.074-.136c-.306-.561.308-1.175.869-.87l.136.075a.64.64 0 0 0 .92-.382zM14 12.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0z" />
                                            </svg>
                                            Edit Profile
                                        </a>
                                        <a href="viewcustomerorders.php" class="btn btn-light py-2 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-seam me-2" viewBox="0 0 16 16">
                                                <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2zm3.564 1.426L5.596 5 8 5.961 14.154 3.5zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464z" />
                                            </svg>
                                            My Orders
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Stats Card -->
                            <div class="card border-0 shadow mt-3 rounded-4">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Order Summary</h6>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>Total Orders</div>
                                        <span class="badge bg-primary rounded-pill"><?php echo count($orders); ?></span>
                                    </div>
                                    <?php if (!empty($orders)): ?>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>Latest Order</div>
                                            <small class="text-muted"><?php echo date('M d, Y', strtotime($orders[0]['order_date'])); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right side: Orders content -->
                        <div class="col-md-8">
                            <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;">
                                <div class="card-header bg-white p-4 border-0">
                                    <div class="d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-box-seam text-primary me-2" viewBox="0 0 16 16">
                                            <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2zm3.564 1.426L5.596 5 8 5.961 14.154 3.5zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464z" />
                                        </svg>
                                        <h4 class="mb-0">My Orders</h4>
                                    </div>
                                </div>
                                <div class="card-body p-4">
                                    <!-- Tab navigation for order statuses -->
                                    <ul class="nav nav-tabs mb-4">
                                        <?php foreach ($status_types as $status_key => $status_label): ?>
                                            <li class="nav-item">
                                                <a class="nav-link <?php echo ($active_tab === $status_key) ? 'active' : ''; ?>"
                                                    href="viewcustomerorders.php?status=<?php echo $status_key; ?>">
                                                    <?php echo $status_label; ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>

                                    <!-- Orders list -->
                                    <div class="orders-list">
                                        <?php if (empty($orders)): ?>
                                            <div class="text-center py-5">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-bag text-muted mb-3" viewBox="0 0 16 16">
                                                    <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1zm3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4h-3.5zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V5z" />
                                                </svg>
                                                <h5 class="text-muted">No orders found</h5>
                                                <p class="text-muted">You haven't placed any orders in this category yet.</p>
                                                <a href="shop.php" class="btn btn-primary mt-2">Continue Shopping</a>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($orders as $order): ?>
                                                <div class="card order-card mb-3 border-0 shadow-sm">
                                                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <span class="text-muted">Order #<?php echo $order['order_id']; ?></span>
                                                            <span class="order-date ms-3"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                                                        </div>
                                                        <span class="badge status-<?php echo $order['order_status']; ?> text-white">
                                                            <?php echo ucfirst(str_replace('_', ' ', $order['order_status'])); ?>
                                                        </span>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-2 col-sm-3 mb-3 mb-md-0">
                                                                <img src="<?php echo !empty($order['thumbnail']) ? $order['thumbnail'] : 'images/product-placeholder.png'; ?>"
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
                                                                <p class="mb-2">Total: <span class="fw-bold"><?php echo '$' . number_format($order['order_total'], 2); ?></span></p>
                                                                <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>

                                                                <?php if ($order['order_status'] === 'to_receive'): ?>
                                                                    <button class="btn btn-sm btn-success ms-2">Received</button>
                                                                <?php endif; ?>

                                                                <?php if ($order['order_status'] === 'to_ship'): ?>
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
                </div>
            </div>
        </div>
    </main>

    <?php include 'utils/footer.php'; ?>

    <script src="js/jquery-1.11.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="js/plugins.js"></script>
    <script src="js/script.js"></script>
</body>

</html>