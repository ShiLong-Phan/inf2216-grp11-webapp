<?php
session_start();

// Check if database connection is missing
if (!isset($conn) || !$conn) {
    // Include database connection
    require_once 'utils/dbconnect.php';
}

$message = '';
$status = 'error';

// Check if token is provided
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify the token against the database
    $sql = "SELECT user_id, expires_at FROM auth_tokens WHERE token = ? AND type = '2fa_activation' AND used = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
        $expires_at = strtotime($row['expires_at']);
        
        // Check if token is expired
        if (time() <= $expires_at) {
            // Update user's 2FA status
            $update_sql = "UPDATE users SET user_verified = 1 WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $user_id);
            
            if ($update_stmt->execute()) {
                // Mark token as used
                $mark_used_sql = "UPDATE auth_tokens SET used = 1, used_at = NOW() WHERE token = ?";
                $mark_used_stmt = $conn->prepare($mark_used_sql);
                $mark_used_stmt->bind_param("s", $token);
                $mark_used_stmt->execute();
                
                $message = "Two-factor authentication has been successfully enabled for your account. You'll now receive a verification code by email when signing in.";
                $status = 'success';
                
                // Update session if user is logged in with this account
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
                    // No need to update anything in the session for 2FA status
                }
            } else {
                $message = "Failed to enable two-factor authentication. Please try again.";
            }
        } else {
            $message = "This verification link has expired. Please request a new one from your profile page.";
        }
    } else {
        $message = "Invalid or already used verification link. Please request a new one from your profile page.";
    }
} else {
    $message = "No verification token provided. Please check your email for the complete link.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Verify Two-Factor Authentication - Crumbly</title>
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
    <link rel="stylesheet" type="text/css" href="css/vendor.css">
    <link rel="stylesheet" type="text/css" href="style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'utils/header.php'; ?>
    <?php include 'utils/navbar.php'; ?>

    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
        <defs>
            <symbol xmlns="http://www.w3.org/2000/svg" id="shield-check" viewBox="0 0 24 24">
                <path fill="currentColor" d="M12 21.85a2 2 0 0 1-1-.25l-.3-.17A15.17 15.17 0 0 1 3 8.23v-.14a2 2 0 0 1 1-1.75l7-3.94a2 2 0 0 1 2 0l7 3.94a2 2 0 0 1 1 1.75v.14a15.17 15.17 0 0 1-7.72 13.2l-.3.17a2 2 0 0 1-.98.25zm0-17.7L5 8.09v.14a13.15 13.15 0 0 0 6.7 11.45l.3.17.3-.17A13.15 13.15 0 0 0 19 8.23v-.14l-7-3.94zm-1.5 12.1a1 1 0 0 1-.7-.29l-2-2a1 1 0 0 1 1.4-1.42L10.5 13l3.3-3.29a1 1 0 1 1 1.4 1.42l-4 4a1 1 0 0 1-.7.29z"/>
            </symbol>
            <symbol xmlns="http://www.w3.org/2000/svg" id="shield-x" viewBox="0 0 24 24">
                <path fill="currentColor" d="M12 21.85a2 2 0 0 1-1-.25l-.3-.17A15.17 15.17 0 0 1 3 8.23v-.14a2 2 0 0 1 1-1.75l7-3.94a2 2 0 0 1 2 0l7 3.94a2 2 0 0 1 1 1.75v.14a15.17 15.17 0 0 1-7.72 13.2l-.3.17a2 2 0 0 1-.98.25zm0-17.7L5 8.09v.14a13.15 13.15 0 0 0 6.7 11.45l.3.17.3-.17A13.15 13.15 0 0 0 19 8.23v-.14zM14.5 9.5a1 1 0 0 0-1.42 0L12 10.59l-1.08-1.09a1 1 0 0 0-1.42 1.42l1.09 1.08-1.09 1.08a1 1 0 0 0 1.42 1.42l1.08-1.09 1.08 1.09a1 1 0 0 0 1.42-1.42L13.41 12l1.09-1.08a1 1 0 0 0 0-1.42z"/>
            </symbol>
        </defs>
    </svg>

    <div class="preloader-wrapper">
        <div class="preloader"></div>
    </div>

    <main>
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-7 col-lg-5">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden my-5" style="box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;">
                        <div class="card-body p-4 p-md-5 text-center">
                            <?php if ($status === 'success'): ?>
                                <svg width="64" height="64" class="text-success mb-4">
                                    <use xlink:href="#shield-check"></use>
                                </svg>
                                <h2 class="section-title mb-3">Two-Factor Authentication Enabled</h2>
                            <?php else: ?>
                                <svg width="64" height="64" class="text-danger mb-4">
                                    <use xlink:href="#shield-x"></use>
                                </svg>
                                <h2 class="section-title mb-3">Verification Failed</h2>
                            <?php endif; ?>
                            
                            <p class="mb-4"><?php echo htmlspecialchars($message); ?></p>
                            
                            <div class="d-grid gap-2 mt-4">
                                <a href="profile.php" class="btn btn-primary btn-lg">Return to Profile</a>
                                <?php if ($status !== 'success'): ?>
                                    <a href="index.php" class="btn btn-outline-primary">Go to Homepage</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'utils/footer.php'; ?>
    </main>

    <script src="js/jquery-1.11.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="js/plugins.js"></script>
    <script src="js/script.js"></script>
</body>
</html>