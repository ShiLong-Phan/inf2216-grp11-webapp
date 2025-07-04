<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 1) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'utils/dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['product_image'])) {
    $action = $_POST['action'] ?? '';
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    // Validate file upload
    if ($_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error_message'] = "File upload error: " . $_FILES['product_image']['error'];
        header("Location: admin_dashboard.php");
        exit();
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = $_FILES['product_image']['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['error_message'] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        header("Location: admin_dashboard.php");
        exit();
    }
    
    // Validate file size (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($_FILES['product_image']['size'] > $max_size) {
        $_SESSION['error_message'] = "File too large. Maximum size is 5MB.";
        header("Location: admin_dashboard.php");
        exit();
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = 'images/products/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $_SESSION['error_message'] = "Failed to create upload directory.";
            header("Location: admin_dashboard.php");
            exit();
        }
    }
    
    // Generate unique filename
    $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
    $new_filename = 'product_' . uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
        // Set proper file permissions
        chmod($upload_path, 0644);
        
        if ($action === 'add') {
            // For new product - store filename in session temporarily
            $_SESSION['temp_product_image'] = $new_filename;
            $_SESSION['success_message'] = "Image uploaded successfully! Now complete the product details.";
        } elseif ($action === 'update' && $product_id > 0) {
            // For existing product - update database directly
            
            // Get current image to delete old one
            $check_sql = "SELECT prod_image FROM products WHERE prod_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $product_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $current_product = $result->fetch_assoc();
            
            // Update database with new image
            $update_sql = "UPDATE products SET prod_image = ? WHERE prod_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $new_filename, $product_id);
            
            if ($update_stmt->execute()) {
                // Delete old image if it exists
                if (!empty($current_product['prod_image']) && file_exists('images/products/' . $current_product['prod_image'])) {
                    unlink('images/products/' . $current_product['prod_image']);
                }
                $_SESSION['success_message'] = "Product image updated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to update product image in database.";
            }
        }
    } else {
        $_SESSION['error_message'] = "Failed to upload file.";
    }
} else {
    $_SESSION['error_message'] = "No file uploaded.";
}

header("Location: admin_dashboard.php");
exit();
?>