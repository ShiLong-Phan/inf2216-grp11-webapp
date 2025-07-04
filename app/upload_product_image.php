<?php
session_start();
require_once 'utils/dbconnect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 1) {
    header("Location: login.php");
    exit();
}

function sanitizeInput($data) {
    return htmlspecialchars(trim(stripslashes($data)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    $prod_image = '';
    
    // Handle image upload if file is provided
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        
        // Validate file upload
        if ($_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error_message'] = "File upload error";
            header("Location: admin_dashboard.php");
            exit();
        }
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['product_image']['type'], $allowed_types)) {
            $_SESSION['error_message'] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
            header("Location: admin_dashboard.php");
            exit();
        }
        
        // Validate file size (5MB max)
        if ($_FILES['product_image']['size'] > 5 * 1024 * 1024) {
            $_SESSION['error_message'] = "File too large. Maximum size is 5MB.";
            header("Location: admin_dashboard.php");
            exit();
        }
        
        // Create upload directory
        $upload_dir = '/var/www/html/images/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $new_filename = 'product_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
            chmod($upload_path, 0644);
            $prod_image = $new_filename;
        } else {
            $_SESSION['error_message'] = "Failed to upload file.";
            header("Location: admin_dashboard.php");
            exit();
        }
    }
    
    if ($action === 'add') {
        // Add new product
        $prod_name = sanitizeInput($_POST['prod_name']);
        $prod_description = sanitizeInput($_POST['prod_description']);
        $prod_price = floatval($_POST['prod_price']);
        $prod_category = sanitizeInput($_POST['prod_category']);
        $prod_stock = intval($_POST['prod_stock']);
        
        $sql = "INSERT INTO products (prod_name, prod_description, prod_price, prod_category, prod_image, prod_stock, prod_date_added) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdssi", $prod_name, $prod_description, $prod_price, $prod_category, $prod_image, $prod_stock);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Product added successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to add product.";
        }
        
    } elseif ($action === 'update' && $product_id > 0) {
        // Update existing product
        $prod_name = sanitizeInput($_POST['prod_name']);
        $prod_description = sanitizeInput($_POST['prod_description']);
        $prod_price = floatval($_POST['prod_price']);
        $prod_category = sanitizeInput($_POST['prod_category']);
        $prod_stock = intval($_POST['prod_stock']);
        
        // Get current image
        $check_sql = "SELECT prod_image FROM products WHERE prod_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $product_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $current_product = $result->fetch_assoc();
        
        // If no new image uploaded, keep current image
        if (empty($prod_image)) {
            $prod_image = $current_product['prod_image'];
        } else {
            // Delete old image if new one uploaded
            if (!empty($current_product['prod_image']) && file_exists('/var/www/html/images/products/' . $current_product['prod_image'])) {
                unlink('/var/www/html/images/products/' . $current_product['prod_image']);
            }
        }
        
        $sql = "UPDATE products SET prod_name = ?, prod_description = ?, prod_price = ?, prod_category = ?, prod_image = ?, prod_stock = ? WHERE prod_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdssii", $prod_name, $prod_description, $prod_price, $prod_category, $prod_image, $prod_stock, $product_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Product updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update product.";
        }
    }
}

header("Location: admin_dashboard.php");
exit();
?>