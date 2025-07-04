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

// Function to log login attempts
function logLoginAttempt($email, $ip_address)
{
    global $conn;

    // Current timestamp
    $timestamp = date('Y-m-d H:i:s');

    // Insert log entry
    $sql = "INSERT INTO logs (logs_user_email, logs_timestamp, logs_ip_address) 
            VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $email, $timestamp, $ip_address);

    // Execute query and handle errors
    if (!$stmt->execute()) {
        error_log("Failed to log login attempt: " . $stmt->error);
    }
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

        // Store OTP ID in session for verification
        $_SESSION['temp_otp_id'] = $otp_id;

        return true;
    } catch (Exception $e) {
        error_log("OTP Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

$error = '';
$email = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $email = strtolower(sanitizeInput($_POST['email'])); // Convert to lowercase
    $password = $_POST['password']; // Passwords shouldn't be sanitized as it may alter their value

    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = "Both email and password are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // First check if the user exists and if their account is locked
        $sql = "SELECT user_id, user_email, user_password, user_firstname, user_role, user_verified, user_disableddate, user_login_attempt 
               FROM users WHERE user_email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Get IP address and log the login attempt
            $ip_address = $_SERVER['REMOTE_ADDR'];
            logLoginAttempt($email, $ip_address);

            // Check if account is locked
            if ($user['user_disableddate'] !== NULL) {
                $current_time = new DateTime();
                $lockout_time = new DateTime($user['user_disableddate']);

                if ($current_time < $lockout_time) {
                    // Account is still locked
                    $time_diff = $current_time->diff($lockout_time);
                    $minutes = $time_diff->i;
                    $seconds = $time_diff->s;
                    $error = "Your account is temporarily locked. Please try again in {$minutes}m {$seconds}s.";
                    // Stop further login processing
                } else {
                    // Lock period expired, clear the lockout and proceed with login
                    $sql = "UPDATE users SET user_disableddate = NULL WHERE user_email = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $email);
                    $stmt->execute();

                    // Proceed to password verification
                    verifyPasswordAndLogin($user, $password, $conn);
                }
            } else {
                // Account is not locked, proceed with login
                verifyPasswordAndLogin($user, $password, $conn);
            }
        } else {
            // User not found
            $error = "Invalid email or password";
        }
    }
}

// Function to verify password and handle login
function verifyPasswordAndLogin($user, $password, $conn)
{
    global $error;

    // Verify password
    if (password_verify($password, $user['user_password'])) {
        // Password is correct

        // Reset login attempts and clear any lockout
        $sql = "UPDATE users SET user_login_attempt = 0, user_disableddate = NULL WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();

        // Check if 2FA should be applied (user_verified = 1)
        if ($user['user_verified'] == 1) {
            // Store temporary user info in session
            $_SESSION['temp_user_id'] = $user['user_id'];
            $_SESSION['temp_user_email'] = $user['user_email'];
            $_SESSION['temp_user_firstname'] = $user['user_firstname'];
            $_SESSION['temp_user_role'] = $user['user_role'];

            // Send OTP and save to database
            if (sendOTPAndSaveToDatabase($user['user_id'], $user['user_email'], $user['user_firstname'])) {
                // Redirect to OTP verification page
                header("Location: verify-otp.php");
                exit();
            } else {
                $error = "Could not send verification code. Please try again.";
            }
        } else {
            // Standard login for non-verified users
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_email'] = $user['user_email'];
            $_SESSION['user_firstname'] = $user['user_firstname'];
            $_SESSION['user_role'] = $user['user_role'];

            // Regenerate session ID for security
            session_regenerate_id(true);

            // Redirect based on user role
            if ($user['user_role'] == 1) {
                // Admin user - redirect to admin dashboard
                header("Location: admin_dashboard.php");
            } else {
                // Regular user - redirect to home page
                header("Location: index.php");
            }
            exit();
        }
    } else {
        // Password is incorrect - increment login attempts
        $sql = "UPDATE users SET user_login_attempt = user_login_attempt + 1 WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();

        // Check if attempts have reached 5
        $sql = "SELECT user_login_attempt FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();

        if ($user_data['user_login_attempt'] >= 5) {
            // Set account lockout time (5 minutes from now)
            $lockout_time = date('Y-m-d H:i:s', time() + 300); // 300 seconds = 5 minutes

            $sql = "UPDATE users SET user_disableddate = ?, user_login_attempt = 0 WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $lockout_time, $user['user_id']);
            $stmt->execute();

            $error = "Too many failed attempts. Your account has been locked for 5 minutes.";
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">


<body>
    <?php include 'utils/header.php'; ?>
    <?php include 'utils/navbar.php'; ?>

    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
        <defs>
            <symbol xmlns="http://www.w3.org/2000/svg" id="user" viewBox="0 0 24 24">
                <path fill="currentColor" d="M15.71 12.71a6 6 0 1 0-7.42 0a10 10 0 0 0-6.22 8.18a1 1 0 0 0 2 .22a8 8 0 0 1 15.9 0a1 1 0 0 0 1 .89h.11a1 1 0 0 0 .88-1.1a10 10 0 0 0-6.25-8.19ZM12 12a4 4 0 1 1 4-4a4 4 0 0 1-4 4Z" />
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
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <svg width="48" height="48" class="text-primary mb-3" style="filter: drop-shadow(0 2px 3px rgba(0,0,0,0.1));">
                                    <use xlink:href="#user"></use>
                                </svg>
                                <h2 class="section-title">Login to Your Account</h2>
                                <?php if (!empty($error)): ?>
                                    <div class=" alert-danger mt-3 shadow-sm" style="color:red"><?php echo $error; ?></div>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['registration_success'])): ?>
                                    <div class=" alert-success mt-3 shadow-sm" style="color:lightgreen">Registration successful! Please login with your credentials.</div>
                                    <?php unset($_SESSION['registration_success']); ?>
                                <?php endif; ?>
                            </div>

                            <form action="login.php" method="post">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control form-control-lg shadow-sm" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>


                                <div class="mb-4">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group shadow-sm">
                                        <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z" />
                                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg shadow">Login</button>
                                </div>

                                <div class="mt-4 text-center">
                                    <p>Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a></p>
                                    <p><a href="forgot-password.php" class="text-decoration-none">Forgot your password?</a></p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div></div>

        <?php include 'utils/footer.php'; ?>
    </main>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');

            togglePassword.addEventListener('click', function() {
                // Toggle type attribute
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);

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
        });
    </script>

    <script src="js/jquery-1.11.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="js/plugins.js"></script>
    <script src="js/script.js"></script>
</body>

</html>