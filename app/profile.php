<?php
include "utils/session.php";

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
    $data = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $data);
    $data = strip_tags($data);  // Remove HTML tags instead of encoding them
    return $data;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$errors = [];
$first_name = '';
$last_name = '';
$email = '';
$address = '';

//2FA
// Function to generate a secure token
function generateSecureToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

// Function to send 2FA verification email
function send2FAVerificationEmail($email, $firstName, $token) {
    require_once 'PHPMailer/src/Exception.php';
    require_once 'PHPMailer/src/PHPMailer.php';
    require_once 'PHPMailer/src/SMTP.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username = getenv('MAIL_USERNAME');
        $mail->Password = getenv('MAIL_PASSWORD');
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('crumblymoo@gmail.com', 'Crumbly Security');
        $mail->addAddress($email, $firstName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Enable Two-Factor Authentication for Your Crumbly Account';
        
        // Create the verification URL
        $verificationUrl = "https://" . $_SERVER['HTTP_HOST'] . "/verify-2fa.php?token=" . urlencode($token);
        
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <div style="background-color: #4CAF50; padding: 20px; text-align: center; color: white;">
                    <h1>Two-Factor Authentication</h1>
                </div>
                <div style="padding: 20px; border: 1px solid #ddd; border-top: none;">
                    <p>Dear ' . htmlspecialchars($firstName) . ',</p>
                    <p>You recently requested to enable two-factor authentication for your Crumbly account.</p>
                    <p>To complete the setup, please click the button below:</p>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . $verificationUrl . '" style="background-color: #4CAF50; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold;">Enable 2FA</a>
                    </div>
                    
                    <p>This link will expire in 24 hours for security reasons.</p>
                    <p>If you did not request to enable two-factor authentication, please ignore this email or contact our support team immediately.</p>
                    <p>Best regards,<br>The Crumbly Security Team</p>
                </div>
            </div>
        ';
        
        $mail->AltBody = 'You requested to enable 2FA for your Crumbly account. Please visit the following link to complete the setup: ' . $verificationUrl;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("2FA Verification Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Add this after the existing POST handler that updates profile info

// Process 2FA enable request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enable_2fa'])) {
    // Generate a secure token
    $token = generateSecureToken();
    $user_id = $_SESSION['user_id'];
    $expiry = date('Y-m-d H:i:s', time() + 86400); // 24 hours from now
    
    // First check if we already have the auth_tokens table
    $check_table_sql = "SHOW TABLES LIKE 'auth_tokens'";
    $check_table_result = $conn->query($check_table_sql);
    
    // Create the table if it doesn't exist
    if ($check_table_result->num_rows == 0) {
        $create_table_sql = "CREATE TABLE `auth_tokens` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `token` varchar(128) NOT NULL,
            `type` varchar(32) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `expires_at` datetime NOT NULL,
            `used` tinyint(1) NOT NULL DEFAULT 0,
            `used_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `token` (`token`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if (!$conn->query($create_table_sql)) {
            $errors[] = "Failed to create auth_tokens table: " . $conn->error;
        }
    }
    
    // Store token in database
    $token_sql = "INSERT INTO auth_tokens (user_id, token, type, expires_at) VALUES (?, ?, '2fa_activation', ?)";
    $token_stmt = $conn->prepare($token_sql);
    $token_stmt->bind_param("iss", $user_id, $token, $expiry);
    
    if ($token_stmt->execute()) {
        // Send verification email
        if (send2FAVerificationEmail($_SESSION['user_email'], $_SESSION['user_firstname'], $token)) {
            $success_message = "A verification email has been sent to enable two-factor authentication. Please check your inbox.";
        } else {
            $errors[] = "Could not send verification email. Please try again.";
        }
    } else {
        $errors[] = "Failed to process your request. Please try again.";
    }
}

// Handle 2FA disable request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disable_2fa'])) {
    $user_id = $_SESSION['user_id'];
    
    // Update user's 2FA status
    $update_sql = "UPDATE users SET user_verified = 0 WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $user_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Two-factor authentication has been disabled for your account.";
    } else {
        $errors[] = "Failed to disable two-factor authentication. Please try again.";
    }
}

// Get user information from database
$sql = "SELECT user_firstname, user_lastname, user_email, user_address FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $first_name = $user['user_firstname'];
    $last_name = $user['user_lastname'];
    $email = $user['user_email'];
    $address = $user['user_address'] ?? '';
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['enable_2fa']) && !isset($_POST['disable_2fa'])) {
    // Sanitize form data
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $address = sanitizeInput($_POST['address']);
    $password = $_POST['password'] ?? ''; // Don't sanitize passwords
    $new_password = $_POST['new_password'] ?? ''; // Don't sanitize passwords
    $confirm_password = $_POST['confirm_password'] ?? ''; // Don't sanitize passwords

    // Validate form data
    if (empty($first_name)) {
        $errors[] = "First name is required";
    } elseif (!preg_match("/^[a-zA-Z ]*$/", $first_name)) {
        $errors[] = "First name should only contain letters and spaces";
    }

    if (empty($last_name)) {
        $errors[] = "Last name is required";
    } elseif (!preg_match("/^[a-zA-Z ]*$/", $last_name)) {
        $errors[] = "Last name should only contain letters and spaces";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email already exists but belongs to a different user
        $sql = "SELECT user_id FROM users WHERE user_email = ? AND user_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = "Email already registered to another account";
        }
    }

    // If changing password, validate it
    if (!empty($new_password)) {
        if (empty($password)) {
            $errors[] = "Current password is required to set a new password";
        } else {
            // Verify current password
            $sql = "SELECT user_password FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (!password_verify($password, $user['user_password'])) {
                    $errors[] = "Current password is incorrect";
                }
            }
        }

        if (strlen($new_password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        } elseif (!preg_match("/[A-Z]/", $new_password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        } elseif (!preg_match("/[a-z]/", $new_password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        } elseif (!preg_match("/[0-9]/", $new_password)) {
            $errors[] = "Password must contain at least one number";
        }

        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }

    // If no errors, update user profile
    if (empty($errors)) {
        if (!empty($new_password)) {
            // Update with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET user_firstname = ?, user_lastname = ?, user_email = ?, user_address = ?, user_password = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $first_name, $last_name, $email, $address, $hashed_password, $_SESSION['user_id']);
        } else {
            // Update without changing password
            $sql = "UPDATE users SET user_firstname = ?, user_lastname = ?, user_email = ?, user_address = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $first_name, $last_name, $email, $address, $_SESSION['user_id']);
        }

        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            // Update session variables
            $_SESSION['user_firstname'] = $first_name;
            $_SESSION['user_email'] = $email;
        } else {
            $errors[] = "Failed to update profile: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<?php include 'utils/header.php'; ?>

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
                        <!-- Left side: Profile header with navigation buttons -->
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
                                            <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($_SESSION['user_firstname']); ?></h5>
                                            <p class="text-secondary-50 small mb-0"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                                        </div>
                                    </div>

                                    <!-- Navigation buttons in horizontal layout -->
                                    <div class="d-grid gap-2 mt-2">
                                        <a href="profile.php" class="btn btn-light py-2 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-gear me-2" viewBox="0 0 16 16">
                                                <path d="M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0M8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m.256 7a4.5 4.5 0 0 1-.229-1.004H3c.001-.246.154-.986.832-1.664C4.484 10.68 5.711 10 8 10q.39 0 .74.025c.226-.341.496-.65.804-.918Q8.844 9.002 8 9c-5 0-6 3-6 4s1 1 1 1z" />
                                                <path d="M11.886 9.46c.18-.613 1.048-.613 1.229 0l.043.148a.64.64 0 0 0 .921.382l.136-.074c.561-.306 1.175.308.87.869l-.075.136a.64.64 0 0 0 .382.92l.149.045c.612.18.612 1.048 0 1.229l-.15.043a.64.64 0 0 0-.38.921l.074.136c.305.561-.309 1.175-.87.87l-.136-.075a.64.64 0 0 0-.92.382l-.045.149c-.18.612-1.048.612-1.229 0l-.043-.15a.64.64 0 0 0-.921-.38l-.136.074c-.561.305-1.175-.309-.87-.87l.075-.136a.64.64 0 0 0-.382-.92l-.148-.045c-.613-.18-.613-1.048 0-1.229l.148-.043a.64.64 0 0 0 .382-.921l-.074-.136c-.306-.561.308-1.175.869-.87l.136.075a.64.64 0 0 0 .92-.382zM14 12.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0z" />
                                            </svg>
                                            Edit Profile
                                        </a>
                                        <a href="viewcustomerorders.php" class="btn btn-outline-light py-2 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-seam me-2" viewBox="0 0 16 16">
                                                <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2zm3.564 1.426L5.596 5 8 5.961 14.154 3.5zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464z" />
                                            </svg>
                                            My Orders
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right side: Main content -->
                        <div class="col-md-9 col-lg-9">
                            <!-- Profile form card -->
                            <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;">
                                <div class="card-header bg-white p-4 border-0">
                                    <div class="d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-pencil-square text-primary me-2" viewBox="0 0 16 16">
                                            <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                                            <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z" />
                                        </svg>
                                        <h4 class="mb-0">Edit Your Profile</h4>
                                    </div>
                                </div>

                                <div class="card-body p-4">
                                    <form action="profile.php" method="post">
                                        <div class="row">
                                            <!-- Left side: Basic profile information -->
                                            <div class="col-lg-6 pe-lg-4 mb-4 mb-lg-0">
                                                <h5 class="mb-3 pb-2 border-bottom">Personal Information</h5>

                                                <div class="row">
                                                    <div class="col-sm-6 mb-3">
                                                        <label for="first_name" class="form-label">First Name</label>
                                                        <input type="text" class="form-control form-control-lg shadow-sm" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                                                    </div>

                                                    <div class="col-sm-6 mb-3">
                                                        <label for="last_name" class="form-label">Last Name</label>
                                                        <input type="text" class="form-control form-control-lg shadow-sm" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="email" class="form-label">Email Address</label>
                                                    <input type="email" class="form-control form-control-lg shadow-sm" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                                </div>

                                                <div class="mb-4">
                                                    <label for="address" class="form-label">Address</label>
                                                    <textarea class="form-control form-control-lg shadow-sm" id="address" name="address" rows="4"><?php echo htmlspecialchars($address); ?></textarea>
                                                </div>

                                                <!-- Replace this section with the fixed wrapping version -->
                                                <div class="mb-4 border-top pt-4">
                                                    <label class="form-label d-flex align-items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-shield-lock-fill text-primary me-2" viewBox="0 0 16 16">
                                                            <path fill-rule="evenodd" d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.465 9.99a11.777 11.777 0 0 0 2.517 2.453c.386.273.744.482 1.048.625.28.132.581.24.829.24s.548-.108.829-.24a7.159 7.159 0 0 0 1.048-.625 11.775 11.775 0 0 0 2.517-2.453c1.678-2.195 3.061-5.513 2.465-9.99a1.541 1.541 0 0 0-1.044-1.263 62.467 62.467 0 0 0-2.887-.87C9.843.266 8.69 0 8 0zm0 5a1.5 1.5 0 0 1 .5 2.915l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99A1.5 1.5 0 0 1 8 5z" />
                                                        </svg>
                                                        Two-Factor Authentication
                                                    </label>
                                                    <div class="mt-2">
                                                        <?php
                                                        // Check if 2FA is already enabled for this user
                                                        $check2fa_sql = "SELECT user_verified FROM users WHERE user_id = ?";
                                                        $check2fa_stmt = $conn->prepare($check2fa_sql);
                                                        $check2fa_stmt->bind_param("i", $_SESSION['user_id']);
                                                        $check2fa_stmt->execute();
                                                        $check2fa_result = $check2fa_stmt->get_result();
                                                        $user2fa = $check2fa_result->fetch_assoc();

                                                        if ($user2fa['user_verified'] == 1):
                                                        ?>
                                                            <div class="alert alert-success d-flex align-items-center" role="alert">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-check-circle-fill me-2" viewBox="0 0 16 16">
                                                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                                                </svg>
                                                                <div>
                                                                    Email 2FA is currently <strong>enabled</strong> for your account.
                                                                </div>
                                                            </div>
                                                            <form action="profile.php" method="post" class="mt-2">
                                                                <input type="hidden" name="disable_2fa" value="1">
                                                                <button type="submit" class="btn btn-outline-danger" name="disable_2fa_btn">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shield-lock-fill me-2" viewBox="0 0 16 16">
                                                                        <path fill-rule="evenodd" d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.465 9.99a11.777 11.777 0 0 0 2.517 2.453c.386.273.744.482 1.048.625.28.132.581.24.829.24s.548-.108.829-.24a7.159 7.159 0 0 0 1.048-.625 11.775 11.775 0 0 0 2.517-2.453c1.678-2.195 3.061-5.513 2.465-9.99a1.541 1.541 0 0 0-1.044-1.263 62.467 62.467 0 0 0-2.887-.87C9.843.266 8.69 0 8 0zm0 5a1.5 1.5 0 0 1 .5 2.915l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99A1.5 1.5 0 0 1 8 5z" />
                                                                    </svg>
                                                                    Disable 2FA
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <!-- Fix text wrapping with max-width and word-break -->
                                                            <p class="text-muted mb-2" style="max-width: 100%; word-wrap: break-word; overflow-wrap: break-word;">
                                                                Enable two-factor authentication to add an extra layer of security to your account.
                                                                When enabled, you'll need to enter a verification code sent to your email each time you sign in.
                                                            </p>
                                                            <form action="profile.php" method="post">
                                                                <input type="hidden" name="enable_2fa" value="1">
                                                                <button type="submit" class="btn btn-primary" name="enable_2fa_btn">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shield-lock-fill me-2" viewBox="0 0 16 16">
                                                                        <path fill-rule="evenodd" d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.465 9.99a11.777 11.777 0 0 0 2.517 2.453c.386.273.744.482 1.048.625.28.132.581.24.829.24s.548-.108.829-.24a7.159 7.159 0 0 0 1.048-.625 11.775 11.775 0 0 0 2.517-2.453c1.678-2.195 3.061-5.513 2.465-9.99a1.541 1.541 0 0 0-1.044-1.263 62.467 62.467 0 0 0-2.887-.87C9.843.266 8.69 0 8 0zm0 5a1.5 1.5 0 0 1 .5 2.915l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99A1.5 1.5 0 0 1 8 5z" />
                                                                    </svg>
                                                                    Enable Email 2FA
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>


                                            </div>

                                            <!-- Vertical divider for larger screens -->
                                            <div class="d-none d-lg-block col-lg-1 position-relative">
                                                <div class="vr h-100 position-absolute start-50"></div>
                                            </div>

                                            <!-- Horizontal divider for smaller screens -->
                                            <div class="d-lg-none col-12 mb-4">
                                                <hr>
                                            </div>

                                            <!-- Right side: Password change -->
                                            <div class="col-lg-5 ps-lg-4">
                                                <h5 class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-shield-lock me-2" viewBox="0 0 16 16">
                                                        <path d="M5.338 1.59a61.44 61.44 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55 0 0 0 .101.025.615.615 0 0 0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.456 62.456 0 0 1 5.072.56z" />
                                                        <path d="M9.5 6.5a1.5 1.5 0 0 1-1 1.415l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99a1.5 1.5 0 1 1 2-1.415z" />
                                                    </svg>
                                                    Change Password
                                                </h5>
                                                <p class="text-muted small mb-3">Leave these fields empty if you don't want to change your password</p>

                                                <!-- Password fields remain unchanged -->
                                                <div class="mb-3">
                                                    <label for="password" class="form-label">Current Password</label>
                                                    <div class="input-group shadow-sm">
                                                        <input type="password" class="form-control" id="password" name="password">
                                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z" />
                                                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="new_password" class="form-label">New Password</label>
                                                    <div class="input-group shadow-sm">
                                                        <input type="password" class="form-control" id="new_password" name="new_password">
                                                        <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z" />
                                                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <div class="mt-2">
                                                        <div class="d-flex flex-wrap gap-2 password-requirements">
                                                            <span class="badge bg-light text-dark p-2" id="req-length">8+ characters</span>
                                                            <span class="badge bg-light text-dark p-2" id="req-uppercase">Uppercase</span>
                                                            <span class="badge bg-light text-dark p-2" id="req-lowercase">Lowercase</span>
                                                            <span class="badge bg-light text-dark p-2" id="req-number">Number</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                                    <div class="input-group shadow-sm">
                                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z" />
                                                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <div id="password-match-feedback" class="form-text"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Bottom submit button spanning full width -->
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-primary btn-lg py-3 shadow">Update Profile</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility functions
            function setupPasswordToggle(buttonId, inputId) {
                const toggleBtn = document.getElementById(buttonId);
                const input = document.getElementById(inputId);

                if (toggleBtn && input) {
                    toggleBtn.addEventListener('click', function() {
                        // Toggle type attribute
                        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                        input.setAttribute('type', type);

                        // Toggle icon
                        if (type === 'text') {
                            this.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash" viewBox="0 0 16 16">
                                <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486z"/>
                                <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>
                                <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z"/>
                            </svg>`;
                        } else {
                            this.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                            </svg>`;
                        }
                    });
                }
            }

            // Setup toggle functionality for all password fields
            setupPasswordToggle('togglePassword', 'password');
            setupPasswordToggle('toggleNewPassword', 'new_password');
            setupPasswordToggle('toggleConfirmPassword', 'confirm_password');

            // Password strength indicator
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const reqLength = document.getElementById('req-length');
            const reqUppercase = document.getElementById('req-uppercase');
            const reqLowercase = document.getElementById('req-lowercase');
            const reqNumber = document.getElementById('req-number');
            const matchFeedback = document.getElementById('password-match-feedback');

            if (newPassword && confirmPassword) {
                // Check password requirements
                newPassword.addEventListener('input', function() {
                    const value = this.value;

                    // Check length
                    if (value.length >= 8) {
                        reqLength.classList.replace('bg-light', 'bg-success');
                        reqLength.classList.replace('text-dark', 'text-white');
                    } else {
                        reqLength.classList.replace('bg-success', 'bg-light');
                        reqLength.classList.replace('text-white', 'text-dark');
                    }

                    // Check uppercase
                    if (/[A-Z]/.test(value)) {
                        reqUppercase.classList.replace('bg-light', 'bg-success');
                        reqUppercase.classList.replace('text-dark', 'text-white');
                    } else {
                        reqUppercase.classList.replace('bg-success', 'bg-light');
                        reqUppercase.classList.replace('text-white', 'text-dark');
                    }

                    // Check lowercase
                    if (/[a-z]/.test(value)) {
                        reqLowercase.classList.replace('bg-light', 'bg-success');
                        reqLowercase.classList.replace('text-dark', 'text-white');
                    } else {
                        reqLowercase.classList.replace('bg-success', 'bg-light');
                        reqLowercase.classList.replace('text-white', 'text-dark');
                    }

                    // Check number
                    if (/[0-9]/.test(value)) {
                        reqNumber.classList.replace('bg-light', 'bg-success');
                        reqNumber.classList.replace('text-dark', 'text-white');
                    } else {
                        reqNumber.classList.replace('bg-success', 'bg-light');
                        reqNumber.classList.replace('text-white', 'text-dark');
                    }

                    // Check match if confirm password has a value
                    if (confirmPassword.value) {
                        checkPasswordMatch();
                    }
                });

                // Check if passwords match
                function checkPasswordMatch() {
                    if (newPassword.value && confirmPassword.value) {
                        if (newPassword.value === confirmPassword.value) {
                            matchFeedback.textContent = "Passwords match!";
                            matchFeedback.className = "form-text text-success mt-2";
                        } else {
                            matchFeedback.textContent = "Passwords do not match";
                            matchFeedback.className = "form-text text-danger mt-2";
                        }
                    } else {
                        matchFeedback.textContent = "";
                    }
                }

                confirmPassword.addEventListener('input', checkPasswordMatch);
            }

            <?php if (!empty($success_message) || !empty($errors)): ?>
                // Auto-dismiss success message after 5 seconds
                <?php if (!empty($success_message)): ?>
                    const successAlert = document.querySelector('.alert-success');
                    if (successAlert) {
                        setTimeout(() => {
                            successAlert.style.opacity = "0";
                            successAlert.style.transition = "opacity 0.5s";
                            setTimeout(() => {
                                successAlert.style.display = "none";
                            }, 500);
                        }, 5000);
                    }
                <?php endif; ?>
            <?php endif; ?>
        });
    </script>

    <script src="js/jquery-1.11.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="js/plugins.js"></script>
    <script src="js/script.js"></script>
</body>

</html>