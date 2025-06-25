<?php
session_start();

// Check if database connection is missing
if (!isset($conn) || !$conn) {
    // Include database connection
    require_once 'utils/dbconnect.php';
}

// Function to sanitize input data
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 1) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$errors = [];

// Handle Product Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_product':
            $prod_name = sanitizeInput($_POST['prod_name']);
            $prod_description = sanitizeInput($_POST['prod_description']);
            $prod_price = floatval($_POST['prod_price']);
            $prod_category = sanitizeInput($_POST['prod_category']);
            $prod_stock = intval($_POST['prod_stock']);

            $prod_image = 'images/product-placeholder.png'; // Default fallback

            if (isset($_FILES['prod_image']) && $_FILES['prod_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'images/';

                // Ensure the upload dir exists
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $original_name = basename($_FILES['prod_image']['name']);
                $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                // Sanitize the filename (remove spaces, special chars, etc.)
                $safe_name = preg_replace("/[^a-zA-Z0-9_\-\.]/", "_", pathinfo($original_name, PATHINFO_FILENAME));
                $unique_filename = $safe_name . '_' . uniqid() . '.' . $extension;

                $target_file = $upload_dir . $unique_filename;

                // Allow only image file types
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $mime = mime_content_type($_FILES['prod_image']['tmp_name']);

                if (in_array($extension, $allowed_types) && strpos($mime, 'image/') === 0) {
                    if (move_uploaded_file($_FILES['prod_image']['tmp_name'], $target_file)) {
                        $prod_image = $target_file;
                    } else {
                        $errors[] = "Error uploading image. Please try again.";
                    }
                } else {
                    $errors[] = "Invalid image type. Only JPG, PNG, GIF, and WEBP are allowed.";
                }
            }
            
            // Validate product data
            if (empty($prod_name)) {
                $errors[] = "Product name is required";
            }
            if (empty($prod_description)) {
                $errors[] = "Product description is required";
            }
            if ($prod_price <= 0) {
                $errors[] = "Product price must be greater than 0";
            }
            // ADD THESE PRICE VALIDATIONS
            if ($prod_price > 999.99) {
                $errors[] = "Product price cannot exceed $999.99";
            }
            if ($prod_price < 0.01) {
                $errors[] = "Product price must be at least $0.01";
            }
            // Check for too many decimal places
            if (round($prod_price, 2) != $prod_price) {
                $errors[] = "Product price can only have up to 2 decimal places";
            }
            if (empty($prod_category)) {
                $errors[] = "Product category is required";
            }
            if ($prod_stock < 0) {
                $errors[] = "Stock quantity cannot be negative";
            }
            
            if (empty($errors)) {
                $sql = "INSERT INTO products (prod_name, prod_description, prod_price, prod_category, prod_image, prod_stock, prod_date_added) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdssi", $prod_name, $prod_description, $prod_price, $prod_category, $prod_image, $prod_stock);
                
                if ($stmt->execute()) {
                    $success_message = "Product added successfully!";
                } else {
                    $errors[] = "Failed to add product: " . $conn->error;
                }
            }
            break;
            
        case 'edit_product':
            $prod_id = intval($_POST['prod_id']);
            $prod_name = sanitizeInput($_POST['prod_name']);
            $prod_description = sanitizeInput($_POST['prod_description']);
            $prod_price = floatval($_POST['prod_price']);
            $prod_category = sanitizeInput($_POST['prod_category']);
            $prod_stock = intval($_POST['prod_stock']);

            $get_stmt = $conn->prepare("SELECT prod_image FROM products WHERE prod_id = ?");
            $get_stmt->bind_param("i", $prod_id);
            $get_stmt->execute();
            $get_stmt->bind_result($existing_image);
            $get_stmt->fetch();
            $get_stmt->close();

            // Store this flag
            $delete_old = false;

            $prod_image = $existing_image; // Use existing image as default

            if (isset($_FILES['prod_image']) && $_FILES['prod_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'images/';

                // Ensure the upload dir exists
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $original_name = basename($_FILES['prod_image']['name']);
                $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                // Sanitize the filename (remove spaces, special chars, etc.)
                $safe_name = preg_replace("/[^a-zA-Z0-9_\-\.]/", "_", pathinfo($original_name, PATHINFO_FILENAME));
                $unique_filename = $safe_name . '_' . uniqid() . '.' . $extension;

                $target_file = $upload_dir . $unique_filename;

                // Allow only image file types
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $mime = mime_content_type($_FILES['prod_image']['tmp_name']);

                if (in_array($extension, $allowed_types) && strpos($mime, 'image/') === 0) {
                    if (move_uploaded_file($_FILES['prod_image']['tmp_name'], $target_file)) {
                        // Delete old image if it's not the placeholder
                        if (!empty($existing_image) && $existing_image !== 'images/product-placeholder.png' && file_exists($existing_image)) {
                            unlink($existing_image); // delete the old file
                        }
                        $prod_image = $target_file;
                    } else {
                        $errors[] = "Error uploading image. Please try again.";
                    }
                } else {
                    $errors[] = "Invalid image type. Only JPG, PNG, GIF, and WEBP are allowed.";
                }
            }
            
            // Validate product data
            if (empty($prod_name)) {
                $errors[] = "Product name is required";
            }
            if (empty($prod_description)) {
                $errors[] = "Product description is required";
            }
            if ($prod_price <= 0) {
                $errors[] = "Product price must be greater than 0";
            }
            // ADD THE SAME PRICE VALIDATIONS HERE TOO
            if ($prod_price > 999.99) {
                $errors[] = "Product price cannot exceed $999.99";
            }
            if ($prod_price < 0.01) {
                $errors[] = "Product price must be at least $0.01";
            }
            if (round($prod_price, 2) != $prod_price) {
                $errors[] = "Product price can only have up to 2 decimal places";
            }
            
            if (empty($prod_category)) {
                $errors[] = "Product category is required";
            }
            if ($prod_stock < 0) {
                $errors[] = "Stock quantity cannot be negative";
            }
            
            if (empty($errors)) {
                $sql = "UPDATE products SET prod_name = ?, prod_description = ?, prod_price = ?, prod_category = ?, prod_image = ?, prod_stock = ? WHERE prod_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdssii", $prod_name, $prod_description, $prod_price, $prod_category, $prod_image, $prod_stock, $prod_id);
                
                if ($stmt->execute()) {
                    $success_message = "Product updated successfully!";
                } else {
                    $errors[] = "Failed to update product: " . $conn->error;
                }
            }
            break;

        case 'delete_product':
            $prod_id = intval($_POST['prod_id']);
            
            $sql = "DELETE FROM products WHERE prod_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $prod_id);
            if ($stmt->execute()) {
                $success_message = "Product deleted successfully!";
            } else {
                $errors[] = "Failed to delete product: " . $conn->error;
            }
            break;
            
            case 'update_order_status':
                $order_id = intval($_POST['order_id']);
                $order_status = sanitizeInput($_POST['order_status']);
                $order_delivery_address = sanitizeInput($_POST['order_delivery_address'] ?? '');
                
                // Validate order exists
                if ($order_id <= 0) {
                    $errors[] = "Invalid order ID";
                }
                
                // Validate status
                $valid_statuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled'];
                if (!in_array($order_status, $valid_statuses)) {
                    $errors[] = "Invalid order status. Please select a valid status.";
                }
                
                // Validate delivery address
                if (empty($order_delivery_address)) {
                    $errors[] = "Delivery address is required";
                }
                
                if (empty($errors)) {
                    // Check if order exists first
                    $check_sql = "SELECT order_id FROM orders WHERE order_id = ?";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param("i", $order_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows === 0) {
                        $errors[] = "Order not found";
                    } else {
                        // Update the order status and delivery address
                        $sql = "UPDATE orders SET order_status = ?, order_delivery_address = ? WHERE order_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssi", $order_status, $order_delivery_address, $order_id);
                        
                        if ($stmt->execute()) {
                            $success_message = "Order #" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . " updated successfully! Status: " . ucfirst($order_status);
                        } else {
                            $errors[] = "Failed to update order: " . $conn->error;
                        }
                    }
                }
                break;
    }
}

// Fetch all products
$products_sql = "SELECT * FROM products ORDER BY prod_date_added DESC";
$products_result = $conn->query($products_sql);

// Fetch all orders with user information
$orders_sql = "SELECT o.*, u.user_firstname, u.user_lastname, u.user_email 
               FROM orders o 
               JOIN users u ON o.order_user_id = u.user_id 
               ORDER BY o.order_date DESC";
$orders_result = $conn->query($orders_sql);

// Get product categories for dropdown
// $categories_sql = "SELECT DISTINCT prod_category FROM products ORDER BY prod_category";
// $categories_result = $conn->query($categories_sql);
// $categories = [];
// while ($row = $categories_result->fetch_assoc()) {
//     $categories[] = $row['prod_category'];
// }
?>

<!DOCTYPE html>
<html lang="en">

<?php include 'utils/header.php'; ?>

<head>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>
    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
        <defs>
            <symbol xmlns="http://www.w3.org/2000/svg" id="user" viewBox="0 0 24 24">
                <path fill="currentColor" d="M15.71 12.71a6 6 0 1 0-7.42 0a10 10 0 0 0-6.22 8.18a1 1 0 0 0 2 .22a8 8 0 0 1 15.9 0a1 1 0 0 0 1 .89h.11a1 1 0 0 0 .88-1.1a10 10 0 0 0-6.25-8.19ZM12 12a4 4 0 1 1 4-4a4 4 0 0 1-4 4Z" />
            </symbol>
            <symbol xmlns="http://www.w3.org/2000/svg" id="edit" viewBox="0 0 24 24">
                <path fill="currentColor" d="M19.045 7.401c.378-.378.586-.88.586-1.414s-.208-1.036-.586-1.414l-1.586-1.586c-.378-.378-.88-.586-1.414-.586s-1.036.208-1.413.585L4 13.585V18h4.413L19.045 7.401zm-3-3l1.587 1.585-1.59 1.584-1.586-1.585 1.589-1.584zM6 16v-1.585l7.04-7.018 1.586 1.586L7.587 16H6zm-2 4h16v2H4z" />
            </symbol>
        </defs>
    </svg>

    <div class="preloader-wrapper">
        <div class="preloader"></div>
    </div>

    <?php include 'utils/navbar.php'; ?>

    <main>
        <div class="container-fluid px-0">
            <div class="row justify-content-center">
                <div class="col-md-12 col-lg-12 col-xl-10">
                    <!-- Success and error messages -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success shadow-sm mb-4" id="successAlert">
                            <div class="d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check-circle-fill me-2" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                </svg>
                                <span><?php echo htmlspecialchars($success_message); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <!-- Your existing error message code here -->
                    <?php endif; ?>

                    <div class="row">
                        <!-- Left side: Admin navigation -->
                        <div class="col-md-3 col-lg-3 mb-4">
                            <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-primary text-white" style="box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-white text-primary rounded-circle p-2 me-3" style="filter: drop-shadow(0 2px 3px rgba(0,0,0,0.1));">
                                            <svg width="40" height="40">
                                                <use xlink:href="#user"></use>
                                            </svg>
                                        </div>
                                        <div class="text-start">
                                            <h5 class="fw-bold mb-0">Admin</h5>
                                            <p class="text-white-50 small mb-0"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                                        </div>
                                    </div>

                                    <!-- Navigation buttons -->
                                    <div class="d-grid gap-2 mt-2">
                                        <button class="btn btn-light py-2 shadow-sm nav-btn active" onclick="showSection('products')" data-section="products">
                                            <svg width="16" height="16" class="me-2">
                                                <use xlink:href="#product"></use>
                                            </svg>
                                            Manage Products
                                        </button>
                                        <button class="btn btn-outline-light py-2 shadow-sm nav-btn" onclick="showSection('orders')" data-section="orders">
                                            <svg width="16" height="16" class="me-2">
                                                <use xlink:href="#orders"></use>
                                            </svg>
                                            View Orders
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-9 col-lg-9">
                            <!-- Products Section -->
                            <div id="products-section" class="content-section">
                                <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;">
                                    <div class="card-header bg-white p-4 border-0">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <svg width="24" height="24" class="text-primary me-2">
                                                    <use xlink:href="#product"></use>
                                                </svg>
                                                <h4 class="mb-0">Product Management</h4>
                                            </div>
                                            <!-- Add Product Button -->
                                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-lg me-2" viewBox="0 0 16 16">
                                                <path d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/>
                                            </svg>
                                            Add Product
                                            </button>
                                            <!-- Add Product Modal -->
                                            <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                <form method="POST" action="" enctype="multipart/form-data">
                                                    <input type="hidden" name="action" value="add_product">
                                                    <div class="modal-header">
                                                    <h5 class="modal-title" id="addProductModalLabel">Add Product</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body row g-3">
                                                    <div class="col-md-6">
                                                        <label for="prod_name" class="form-label">Product Name</label>
                                                        <input type="text" class="form-control" name="prod_name" id="prod_name" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="prod_price" class="form-label">Price ($)</label>
                                                        <input type="number" step="0.01" min="0.01" max="999.99" class="form-control" name="prod_price" id="prod_price" required>
                                                    </div>
                                                    <div class="col-12">
                                                        <label for="prod_description" class="form-label">Description</label>
                                                        <textarea class="form-control" name="prod_description" id="prod_description" rows="3" required></textarea>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="prod_category" class="form-label">Category</label>
                                                        <select class="form-control" name="prod_category" id="prod_category" required>
                                                            <option value="">Select Category</option>
                                                            <option value="Cakes">Cakes</option>
                                                            <option value="Tarts">Tarts</option>
                                                            <option value="Cookies">Cookies</option>
                                                            <option value="Cupcakes">Cupcakes</option>
                                                            <option value="Muffins">Muffins</option>
                                                            <option value="Puffs">Puffs</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="prod_stock" class="form-label">Stock Quantity</label>
                                                        <input type="number" min="0" class="form-control" name="prod_stock" id="prod_stock" value="0" required>
                                                        <div class="form-text">Number of items in stock</div>
                                                    </div>
                                                    <div class="col-12">
                                                        <label for="prod_image_add" class="form-label">Product Image</label>
                                                        <div class="border border-primary border-2 rounded p-4 text-center">
                                                            <div id="preview_container_add" class="mb-3" style="display: none;">
                                                                <img id="preview_add" src="" alt="Image preview" class="img-fluid rounded shadow-sm" style="max-height: 100px;">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="prod_image_add" class="btn btn-primary btn-sm px-4">Choose Image File</label>
                                                                <input type="file" class="d-none" name="prod_image" id="prod_image_add" accept="image/*" onchange="previewImageAdd(event)">
                                                            </div>
                                                            <small class="text-muted d-block mt-2">Drag and drop or click to select. Supported: JPG, PNG, GIF, WEBP</small>
                                                        </div>
                                                    </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Add Product</button>
                                                    </div>
                                                </form>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="bg-light">
                                                <tr>
                                                    <th class="px-4 py-3">ID</th>
                                                    <th class="px-4 py-3">Name</th>
                                                    <th class="px-4 py-3">Category</th>
                                                    <th class="px-4 py-3">Price</th>
                                                    <th class="px-4 py-3">Stock</th>
                                                    <th class="px-4 py-3">Date Added</th>
                                                    <th class="px-4 py-3">Actions</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if ($products_result && $products_result->num_rows > 0): ?>
                                                        <?php while ($product = $products_result->fetch_assoc()): ?>
                                                            <tr>
                                                                <td class="px-4 py-3"><?php echo htmlspecialchars($product['prod_id']); ?></td>
                                                                <td class="px-4 py-3">
                                                                    <div class="d-flex align-items-center">
                                                                        <?php if (!empty($product['prod_image'])): ?>
                                                                            <img src="<?php echo htmlspecialchars($product['prod_image']); ?>" alt="Product" class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                                                        <?php endif; ?>
                                                                        <div>
                                                                            <div class="fw-medium"><?php echo htmlspecialchars($product['prod_name']); ?></div>
                                                                            <?php if (!empty($product['prod_description'])): ?>
                                                                                <div class="text-muted small"><?php echo htmlspecialchars(substr($product['prod_description'], 0, 50)) . '...'; ?></div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($product['prod_category']); ?></span>
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    <span class="fw-medium">$<?php echo number_format($product['prod_price'], 2); ?></span>
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    <?php 
                                                                    $stock = $product['prod_stock'];
                                                                    // Determine stock status text
                                                                    if ($stock < 10) {
                                                                        $stockStatus = 'Low Stock';
                                                                    } elseif ($stock <= 50) {
                                                                        $stockStatus = 'Medium Stock';
                                                                    } else {
                                                                        $stockStatus = 'High Stock';
                                                                    }
                                                                    ?>
                                                                    
                                                                    <div class="d-flex flex-column align-items-start" style="gap: 2px;">
                                                                        <span class="fw-medium"><?php echo $stock; ?></span>
                                                                        <small class="text-muted"><?php echo $stockStatus; ?></small>
                                                                    </div>
                                                                </td>
                                                                <td class="px-4 py-3 text-muted"><?php echo date('M j, Y', strtotime($product['prod_date_added'])); ?></td>
                                                                <td class="px-4 py-3">
                                                                <div class="d-flex align-items-center gap-2">
                                                                     <!-- Edit Button -->
                                                                    <button type="button"
                                                                            class="btn btn-outline-primary d-flex align-items-center justify-content-center"
                                                                            style="width: 36px; height: 36px; padding: 0;"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#editModal_<?php echo $product['prod_id']; ?>">
                                                                    <i class="bi bi-pencil" style="font-size: 1rem;"></i>
                                                                    </button>

                                                                    <!-- Delete Button -->
                                                                    <form method="POST"
                                                                        onsubmit="return confirm('Are you sure you want to delete this product?');"
                                                                        style="display: inline;">
                                                                    <input type="hidden" name="action" value="delete_product">
                                                                    <input type="hidden" name="prod_id" value="<?php echo $product['prod_id']; ?>">
                                                                    <button type="submit"
                                                                            class="btn btn-outline-danger d-flex align-items-center justify-content-center"
                                                                            style="width: 36px; height: 36px; padding: 0;">
                                                                        <i class="bi bi-trash" style="font-size: 1rem;"></i>
                                                                    </button>
                                                                    </form>
                                                                </div>

                                                                <!-- Edit Modal -->
                                                                <div class="modal fade"
                                                                    id="editModal_<?php echo $product['prod_id']; ?>"
                                                                    tabindex="-1"
                                                                    aria-labelledby="editModalLabel_<?php echo $product['prod_id']; ?>"
                                                                    aria-hidden="true">
                                                                    <div class="modal-dialog modal-lg">
                                                                        <form method="POST" action="" enctype="multipart/form-data">
                                                                            <input type="hidden" name="action" value="edit_product">
                                                                            <input type="hidden" name="prod_id" value="<?php echo $product['prod_id']; ?>">
                                                                            <div class="modal-content">
                                                                                <div class="modal-header">
                                                                                    <h5 class="modal-title" id="editModalLabel_<?php echo $product['prod_id']; ?>">Edit Product</h5>
                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                </div>
                                                                                <div class="modal-body">
                                                                                    <div class="row g-3">
                                                                                        <div class="col-md-6">
                                                                                            <label class="form-label">Product Name</label>
                                                                                            <input type="text" class="form-control" name="prod_name"
                                                                                                value="<?php echo htmlspecialchars($product['prod_name']); ?>" required>
                                                                                        </div>
                                                                                        <div class="col-md-6">
                                                                                            <label class="form-label">Price ($)</label>
                                                                                            <input type="number" min="0.01" max="999.99" class="form-control" name="prod_price" step="0.01"
                                                                                                value="<?php echo $product['prod_price']; ?>" required>
                                                                                        </div>
                                                                                        <div class="col-12">
                                                                                            <label class="form-label">Description</label>
                                                                                            <textarea class="form-control" name="prod_description" rows="3" required><?php echo htmlspecialchars($product['prod_description']); ?></textarea>
                                                                                        </div>
                                                                                        <div class="col-md-6">
                                                                                            <label class="form-label">Category</label>
                                                                                            <select class="form-control" name="prod_category" required>
                                                                                                <option value="">Select Category</option>
                                                                                                <option value="Cakes" <?php echo ($product['prod_category'] == 'Cakes') ? 'selected' : ''; ?>>Cakes</option>
                                                                                                <option value="Tarts" <?php echo ($product['prod_category'] == 'Tarts') ? 'selected' : ''; ?>>Tarts</option>
                                                                                                <option value="Cookies" <?php echo ($product['prod_category'] == 'Cookies') ? 'selected' : ''; ?>>Cookies</option>
                                                                                                <option value="Cupcakes" <?php echo ($product['prod_category'] == 'Cupcakes') ? 'selected' : ''; ?>>Cupcakes</option>
                                                                                                <option value="Muffins" <?php echo ($product['prod_category'] == 'Muffins') ? 'selected' : ''; ?>>Muffins</option>
                                                                                                <option value="Puffs" <?php echo ($product['prod_category'] == 'Puffs') ? 'selected' : ''; ?>>Puffs</option>
                                                                                            </select>
                                                                                        </div>
                                                                                        <div class="col-md-6">
                                                                                            <label class="form-label">Stock Quantity</label>
                                                                                            <input type="number" min="0" class="form-control" name="prod_stock"
                                                                                                value="<?php echo $product['prod_stock']; ?>" required>
                                                                                        </div>
                                                                                        <div class="col-12">
                                                                                            <label for="prod_image_edit_<?php echo $product['prod_id']; ?>" class="form-label">Update Product Image</label>
                                                                                            <div class="border border-primary border-2 rounded p-4 text-center">
                                                                                                <div id="preview_container_edit_<?php echo $product['prod_id']; ?>" class="mb-3">
                                                                                                    <img id="preview_edit_<?php echo $product['prod_id']; ?>" src="<?php echo htmlspecialchars($product['prod_image']); ?>" alt="Current image" class="img-fluid rounded shadow-sm" style="max-height: 100px;">
                                                                                                </div>
                                                                                                <div class="mb-3">
                                                                                                    <label for="prod_image_edit_<?php echo $product['prod_id']; ?>" class="btn btn-primary btn-sm px-4">Choose New Image</label>
                                                                                                    <input type="file" class="d-none" name="prod_image" id="prod_image_edit_<?php echo $product['prod_id']; ?>" accept="image/*" onchange="previewImageEdit(event, <?php echo $product['prod_id']; ?>)">
                                                                                                </div>
                                                                                                <small class="text-muted d-block mt-2">Current image shown above. Select new image to replace. Supported: JPG, PNG, GIF, WEBP</small>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="modal-footer">
                                                                                    <button type="submit" class="btn btn-primary">Update Product</button>
                                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                                </div>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php endwhile; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="7" class="text-center py-5 text-muted">
                                                                <svg width="48" height="48" class="mb-3 opacity-50">
                                                                    <use xlink:href="#product"></use>
                                                                </svg>
                                                                <div>No products found</div>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                             <!-- Orders Section -->
                             <div id="orders-section" class="content-section" style="display: none;">
                                <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;">
                                    <div class="card-header bg-white p-4 border-0">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <svg width="24" height="24" class="text-primary me-2">
                                                    <use xlink:href="#orders"></use>
                                                </svg>
                                                <h4 class="mb-0">Order Management</h4>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <select class="form-select" id="orderStatusFilter">
                                                    <option value="">All Status</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="shipped">Shipped</option>
                                                    <option value="delivered">Delivered</option>
                                                    <option value="cancelled">Cancelled</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th class="px-4 py-3">ID</th>
                                                        <th class="px-4 py-3">Customer</th>
                                                        <th class="px-4 py-3">Date</th>
                                                        <th class="px-4 py-3">Total</th>
                                                        <th class="px-4 py-3">Status</th>
                                                        <th class="px-4 py-3">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                                                        <?php while ($order = $orders_result->fetch_assoc()): ?>
                                                            <tr>
                                                                <td class="px-4 py-3">
                                                                    <span class="fw-medium">#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></span>
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    <div>
                                                                        <div class="fw-medium"><?php echo htmlspecialchars($order['user_firstname'] . ' ' . $order['user_lastname']); ?></div>
                                                                        <div class="text-muted small"><?php echo htmlspecialchars($order['user_email']); ?></div>
                                                                    </div>
                                                                </td>
                                                                <td class="px-4 py-3 text-muted"><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                                                                <td class="px-4 py-3">
                                                                    <span class="fw-medium">$<?php echo number_format($order['order_total'], 2); ?></span>
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    <?php
                                                                    $status = $order['order_status'];
                                                                    $statusClass = '';
                                                                    switch(strtolower($status)) {
                                                                        case 'Pending':
                                                                            $statusClass = 'bg-warning text-dark';
                                                                            break;
                                                                        case 'Shipped':
                                                                            $statusClass = 'bg-primary text-white';
                                                                            break;
                                                                        case 'Delivered':
                                                                            $statusClass = 'bg-success text-white';
                                                                            break;
                                                                        case 'Cancelled':
                                                                            $statusClass = 'bg-danger text-white';
                                                                            break;
                                                                        default:
                                                                            $statusClass = 'bg-primary text-white';
                                                                    }
                                                                    ?>
                                                                    <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($status); ?></span>
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <!-- View Details Button -->
                                                                        <button type="button"
                                                                                class="btn btn-outline-primary d-flex align-items-center justify-content-center"
                                                                                style="width: 36px; height: 36px; padding: 0;"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#viewOrderModal_<?php echo $order['order_id']; ?>"
                                                                                title="View Details">
                                                                            <i class="bi bi-eye" style="font-size: 1rem;"></i>
                                                                        </button>
                                                                        
                                                                        <!-- Update Status Button -->
                                                                        <button type="button"
                                                                                class="btn btn-outline-success d-flex align-items-center justify-content-center"
                                                                                style="width: 36px; height: 36px; padding: 0;"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#updateOrderModal_<?php echo $order['order_id']; ?>"
                                                                                title="Update Order">
                                                                            <i class="bi bi-pencil" style="font-size: 1rem;"></i>
                                                                        </button>
                                                                    </div>

                                                                    <!-- View Order Details Modal -->
                                                                    <div class="modal fade" 
                                                                        id="viewOrderModal_<?php echo $order['order_id']; ?>" 
                                                                        tabindex="-1" 
                                                                        aria-labelledby="viewOrderModalLabel_<?php echo $order['order_id']; ?>" 
                                                                        aria-hidden="true">
                                                                        <div class="modal-dialog modal-lg">
                                                                            <div class="modal-content">
                                                                                <div class="modal-header">
                                                                                    <h5 class="modal-title" id="viewOrderModalLabel_<?php echo $order['order_id']; ?>">
                                                                                        <i class="bi bi-receipt me-2"></i>
                                                                                        Order Details - #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?>
                                                                                    </h5>
                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                </div>
                                                                                <div class="modal-body">
                                                                                    <div class="row g-4">
                                                                                        <!-- Customer Information -->
                                                                                        <div class="col-md-6">
                                                                                            <div class="card border-0 bg-light">
                                                                                                <div class="card-body">
                                                                                                    <h6 class="card-title text-primary mb-3">
                                                                                                        <i class="bi bi-person-circle me-2"></i>Customer Information
                                                                                                    </h6>
                                                                                                    <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($order['user_firstname'] . ' ' . $order['user_lastname']); ?></p>
                                                                                                    <p class="mb-0"><strong>Email:</strong> <?php echo htmlspecialchars($order['user_email']); ?></p>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        
                                                                                        <!-- Order Information -->
                                                                                        <div class="col-md-6">
                                                                                            <div class="card border-0 bg-light">
                                                                                                <div class="card-body">
                                                                                                    <h6 class="card-title text-primary mb-3">
                                                                                                        <i class="bi bi-bag-check me-2"></i>Order Information
                                                                                                    </h6>
                                                                                                    <p class="mb-2"><strong>Order Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></p>
                                                                                                    <p class="mb-2"><strong>Total Amount:</strong> <span class="fw-bold text-success">$<?php echo number_format($order['order_total'], 2); ?></span></p>
                                                                                                    <p class="mb-0">
                                                                                                        <strong>Status:</strong> 
                                                                                                        <span class="badge <?php
                                                                                                            $status = $order['order_status'];
                                                                                                            switch(strtolower($status)) {
                                                                                                                case 'Pending': echo 'bg-warning text-dark'; break;
                                                                                                                case 'Processing': echo 'bg-info text-white'; break;
                                                                                                                case 'Shipped': echo 'bg-primary text-white'; break;
                                                                                                                case 'Delivered': echo 'bg-success text-white'; break;
                                                                                                                case 'Cancelled': echo 'bg-danger text-white'; break;
                                                                                                                default: echo 'bg-primary text-white';
                                                                                                            }
                                                                                                        ?>"><?php echo ucfirst($status); ?></span>
                                                                                                    </p>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        
                                                                                        <!-- Delivery Address -->
                                                                                        <div class="col-12">
                                                                                            <div class="card border-0 bg-light">
                                                                                                <div class="card-body">
                                                                                                    <h6 class="card-title text-primary mb-3">
                                                                                                        <i class="bi bi-geo-alt me-2"></i>Delivery Address
                                                                                                    </h6>
                                                                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['order_delivery_address'])); ?></p>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="modal-footer">
                                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                                    <button type="button" 
                                                                                            class="btn btn-primary" 
                                                                                            data-bs-dismiss="modal"
                                                                                            data-bs-toggle="modal"
                                                                                            data-bs-target="#updateOrderModal_<?php echo $order['order_id']; ?>">
                                                                                        <i class="bi bi-pencil-square me-2"></i>Edit Order
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Update Order Modal -->
                                                                    <div class="modal fade" 
                                                                        id="updateOrderModal_<?php echo $order['order_id']; ?>" 
                                                                        tabindex="-1" 
                                                                        aria-labelledby="updateOrderModalLabel_<?php echo $order['order_id']; ?>" 
                                                                        aria-hidden="true">
                                                                        <div class="modal-dialog modal-lg">
                                                                            <div class="modal-content">
                                                                                <form method="POST">
                                                                                    <input type="hidden" name="action" value="update_order_status">
                                                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                                    <div class="modal-header">
                                                                                        <h5 class="modal-title" id="updateOrderModalLabel_<?php echo $order['order_id']; ?>">
                                                                                            <i class="bi bi-pencil-square me-2"></i>
                                                                                            Update Order - #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?>
                                                                                        </h5>
                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                    </div>
                                                                                    <div class="modal-body">
                                                                                        <div class="row g-3">
                                                                                            <!-- Customer Info (Read Only) -->
                                                                                            <div class="col-md-6">
                                                                                                <label class="form-label fw-bold">Customer</label>
                                                                                                <input type="text" 
                                                                                                    class="form-control-plaintext" 
                                                                                                    value="<?php echo htmlspecialchars($order['user_firstname'] . ' ' . $order['user_lastname']); ?>" 
                                                                                                    readonly>
                                                                                                <small class="text-muted"><?php echo htmlspecialchars($order['user_email']); ?></small>
                                                                                            </div>
                                                                                            
                                                                                            <!-- Order Total (Read Only) -->
                                                                                            <div class="col-md-6">
                                                                                                <label class="form-label fw-bold">Order Total</label>
                                                                                                <input type="text" 
                                                                                                    class="form-control-plaintext" 
                                                                                                    value="$<?php echo number_format($order['order_total'], 2); ?>" 
                                                                                                    readonly>
                                                                                                <small class="text-muted">Date: <?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></small>
                                                                                            </div>
                                                                                            
                                                                                            <!-- Order Status -->
                                                                                            <div class="col-md-6">
                                                                                                <label for="order_status_<?php echo $order['order_id']; ?>" class="form-label fw-bold">Order Status</label>
                                                                                                <select class="form-select" name="order_status" id="order_status_<?php echo $order['order_id']; ?>" required>
                                                                                                    <option value="">Select Status</option>
                                                                                                    <option value="Pending" <?php echo ($order['order_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                                                                    <option value="Shipped" <?php echo ($order['order_status'] == 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
                                                                                                    <option value="Delivered" <?php echo ($order['order_status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                                                                                                    <option value="Cancelled" <?php echo ($order['order_status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                                                                </select>
                                                                                            </div>
                                                                                            
                                                                                            <!-- Delivery Address -->
                                                                                            <div class="col-12">
                                                                                                <label for="delivery_address_<?php echo $order['order_id']; ?>" class="form-label fw-bold">Delivery Address</label>
                                                                                                <textarea class="form-control" 
                                                                                                        name="order_delivery_address" 
                                                                                                        id="delivery_address_<?php echo $order['order_id']; ?>" 
                                                                                                        rows="3" 
                                                                                                        required><?php echo htmlspecialchars($order['order_delivery_address']); ?></textarea>
                                                                                                <div class="form-text">You can update the delivery address if needed.</div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="modal-footer">
                                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                                        <button type="submit" class="btn btn-success">
                                                                                            <i class="bi bi-check-circle me-2"></i>Update Order
                                                                                        </button>
                                                                                    </div>
                                                                                </form>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="6" class="text-center py-5 text-muted">
                                                                <svg width="48" height="48" class="mb-3 opacity-50">
                                                                    <use xlink:href="#orders"></use>
                                                                </svg>
                                                                <div>No orders found</div>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
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

    <script>
        function showSection(section) {
        // Hide all sections
        const sections = document.querySelectorAll('.content-section');
        sections.forEach(sec => sec.style.display = 'none');

        // Show the selected section
        const target = document.getElementById(section + '-section');
        if (target) {
            target.style.display = 'block';
        }

        // Update active button styling
        const buttons = document.querySelectorAll('.nav-btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        const activeBtn = document.querySelector(`button[data-section="${section}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }

    function previewImageAdd(event) {
        const reader = new FileReader();
        reader.onload = function () {
            const output = document.getElementById('preview_add');
            const container = document.getElementById('preview_container_add');
            output.src = reader.result;
            container.style.display = 'block';
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    function previewImageEdit(event, productId) {
        const reader = new FileReader();
        reader.onload = function () {
            const output = document.getElementById('preview_edit_' + productId);
            const container = document.getElementById('preview_container_edit_' + productId);
            output.src = reader.result;
            container.style.display = 'block';
        };
        reader.readAsDataURL(event.target.files[0]);
    }
    </script>

    <script src="js/jquery-1.11.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="js/plugins.js"></script>
    <script src="js/script.js"></script>

</body>

</html>