<?php
session_start();

// Check if database connection is missing
if (!isset($conn) || !$conn) {
    // Include database connection
    require_once 'utils/dbconnect.php';
    // Include PHPMailer for resend functionality
    require_once 'PHPMailer/src/Exception.php';
    require_once 'PHPMailer/src/PHPMailer.php';
    require_once 'PHPMailer/src/SMTP.php';
}

// Check if we have an active OTP verification session
if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
} else if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_otp_id'])) {
    // No active verification session, redirect to login
    header("Location: login.php");
    exit();
}

// Function to sanitize input data
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $data);
    $data = strip_tags($data);  // Remove HTML tags instead of encoding ;
    return $data;
}

// Function to generate 6-digit OTP
function generateOTP()
{
    return sprintf("%06d", mt_rand(100000, 999999));
}

// Function to send OTP email and save to database
function sendOTPAndSaveToDatabase($user_id, $email, $firstName)
{
    global $conn;

    // Generate OTP
    $otp = generateOTP();

    // Set expiration time (10 minutes from now)
    $expires_at = date('Y-m-d H:i:s', time() + 600);

    // Get client info for logging
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';

    // Delete any unused OTPs for this user to prevent accumulation
    $deleteSql = "DELETE FROM user_otps WHERE user_id = ? AND is_used = 0";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $user_id);
    $deleteStmt->execute();

    // Insert new OTP record
    $insertSql = "INSERT INTO user_otps (user_id, otp_code, expires_at, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("issss", $user_id, $otp, $expires_at, $ip_address, $user_agent);

    if (!$insertStmt->execute()) {
        error_log("Failed to insert OTP into database: " . $insertStmt->error);
        return false;
    }

    // Get the OTP ID for session reference
    $otp_id = $conn->insert_id;

    // Send email with OTP
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
        $mail->Subject = 'Your Crumbly Login Verification Code';
        $mail->Body    = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <div style="background-color: #4CAF50; padding: 20px; text-align: center; color: white;">
                    <h1>Security Verification</h1>
                </div>
                <div style="padding: 20px; border: 1px solid #ddd; border-top: none;">
                    <p>Dear ' . htmlspecialchars($firstName) . ',</p>
                    <p>Your verification code for Crumbly login is:</p>
                    <div style="background-color: #f2f2f2; padding: 15px; font-size: 24px; text-align: center; letter-spacing: 5px; font-weight: bold; margin: 20px 0;">
                        ' . $otp . '
                    </div>
                    <p>This code will expire in 10 minutes.</p>
                    <p>If you did not attempt to log in to your Crumbly account, please contact our support team immediately.</p>
                    <p>Best regards,<br>The Crumbly Security Team</p>
                </div>
            </div>
        ';
        $mail->AltBody = 'Your verification code for Crumbly login is: ' . $otp . '. This code will expire in 10 minutes.';

        $mail->send();

        // Update session with new OTP ID
        $_SESSION['temp_otp_id'] = $otp_id;

        return true;
    } catch (Exception $e) {
        error_log("OTP Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

$error = '';
$email = $_SESSION['temp_user_email']; // For display purposes

// Handle resend OTP
if (isset($_GET['resend']) && $_GET['resend'] == 'true') {
    // Send new OTP and save to database
    $resendSuccess = sendOTPAndSaveToDatabase(
        $_SESSION['temp_user_id'],
        $_SESSION['temp_user_email'],
        $_SESSION['temp_user_firstname']
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Verify Login - Crumbly</title>
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
                <path fill="currentColor" d="M12 21.85a2 2 0 0 1-1-.25l-.3-.17A15.17 15.17 0 0 1 3 8.23v-.14a2 2 0 0 1 1-1.75l7-3.94a2 2 0 0 1 2 0l7 3.94a2 2 0 0 1 1 1.75v.14a15.17 15.17 0 0 1-7.72 13.2l-.3.17a2 2 0 0 1-.98.25zm0-17.7L5 8.09v.14a13.15 13.15 0 0 0 6.7 11.45l.3.17.3-.17A13.15 13.15 0 0 0 19 8.23v-.14l-7-3.94zm-1.5 12.1a1 1 0 0 1-.7-.29l-2-2a1 1 0 0 1 1.4-1.42L10.5 13l3.3-3.29a1 1 0 1 1 1.4 1.42l-4 4a1 1 0 0 1-.7.29z" />
            </symbol>
        </defs>
    </svg>

    <div class="preloader-wrapper">
        <div class="preloader"></div>
    </div>

    <main>
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-7 col-lg-6">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important; margin: 6rem auto;">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <svg width="48" height="48" class="text-primary mb-3" style="filter: drop-shadow(0 2px 3px rgba(0,0,0,0.1));">
                                    <use xlink:href="#shield-check"></use>
                                </svg>
                                <h2 class="section-title">Two-Factor Authentication</h2>
                                <p class="mb-3">We've sent a verification code to <strong><?php echo htmlspecialchars($email); ?></strong></p>
                                <p class="text-muted">Please enter the 6-digit code to complete your login</p>

                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger mt-3 shadow-sm"><?php echo $error; ?></div>
                                <?php endif; ?>

                                <?php if (isset($resendSuccess)): ?>
                                    <?php if ($resendSuccess): ?>
                                        <div class="alert alert-success mt-3 shadow-sm">A new verification code has been sent to your email.</div>
                                    <?php else: ?>
                                        <div class="alert alert-danger mt-3 shadow-sm">Failed to send a new verification code. Please try again.</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <form action="process-otp.php" method="post">
                                <div class="mb-4">
                                    <label for="otp" class="form-label">Verification Code</label>
                                    <input type="text" class="form-control form-control-lg text-center shadow-sm"
                                        id="otp" name="otp"
                                        style="letter-spacing: 0.5em; font-size: 1.5rem;"
                                        maxlength="6" placeholder="******"
                                        pattern="[0-9]{6}" inputmode="numeric"
                                        required autofocus>
                                    <div class="form-text">Enter the 6-digit code sent to your email</div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg shadow">Verify & Login</button>
                                </div>

                                <div class="mt-4 text-center">
                                    <p>Didn't receive the code? <a href="verify-otp.php?resend=true" class="text-decoration-none">Resend code</a></p>
                                    <p><a href="login.php" class="text-decoration-none text-muted">Cancel and return to login</a></p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'utils/footer.php'; ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Format OTP input to only accept numbers
            const otpInput = document.getElementById('otp');
            if (otpInput) {
                otpInput.addEventListener('input', function(e) {
                    // Remove any non-digit characters
                    this.value = this.value.replace(/[^0-9]/g, '');

                    // Limit to 6 digits
                    if (this.value.length > 6) {
                        this.value = this.value.slice(0, 6);
                    }
                });

                // Auto-submit when 6 digits are entered
                otpInput.addEventListener('keyup', function(e) {
                    if (this.value.length === 6) {
                        // Submit the form after a short delay to give user feedback
                        setTimeout(() => {
                            this.form.submit();
                        }, 300);
                    }
                });
            }
        });
    </script>

    <script src="js/jquery-1.11.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="js/plugins.js"></script>
    <script src="js/script.js"></script>
</body>

</html>