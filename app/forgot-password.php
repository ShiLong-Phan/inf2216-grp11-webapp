<?php
include "utils/session.php";

// Check if database connection is missing
if (!isset($conn) || !$conn) {
    // Include database connection
    require_once 'utils/dbconnect.php';
}

// Include PHPMailer
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to sanitize input data
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $data);
    $data = strip_tags($data);  // Remove HTML tags instead of encoding them
    return $data;
}

$message = '';
$messageType = '';
$email = '';


// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $email = sanitizeInput($_POST['email']);

    // Validate email
    if (empty($email)) {
        $message = "Email address is required";
        $messageType = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format";
        $messageType = "danger";
    } else {
        // Check if email exists in database
        $sql = "SELECT user_id, user_firstname, user_lastname FROM users WHERE user_email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $userId = $user['user_id'];
            $firstName = $user['user_firstname'];
            $lastName = $user['user_lastname'];

            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in database
            $sql = "INSERT INTO password_reset_tokens (user_id, token, expires_at) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $userId, $token, $expires);

            if ($stmt->execute()) {
                // Send email with reset link
                $mail = new PHPMailer(true);

                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; // Update with your SMTP server
                    $mail->SMTPAuth   = true;
                    $mail->Username = getenv('MAIL_USERNAME');
                    $mail->Password = getenv('MAIL_PASSWORD');
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;

                    // Recipients
                    $mail->setFrom('Crumblymoo@gmail.com', 'Crumbly');
                    $mail->addAddress($email, $firstName . ' ' . $lastName);

                    // Content
                    $resetLink = 'https://' . $_SERVER['HTTP_HOST'] . '/reset-password.php?token=' . $token;

                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request';
                    $mail->Body = '
                        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                            <div style="background-color: #4CAF50; padding: 20px; text-align: center;">
                                <h1 style="color: white; margin: 0;">Crumbly</h1>
                            </div>
                            <div style="padding: 20px; border: 1px solid #ddd; border-top: none;">
                                <h2>Password Reset Request</h2>
                                <p>Dear ' . $firstName . ',</p>
                                <p>We received a request to reset your password. Click the button below to reset your password:</p>
                                <div style="text-align: center; margin: 30px 0;">
                                    <a href="' . $resetLink . '" style="background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">Reset Your Password</a>
                                </div>
                                <p>If the button did not wrok please use this link: ' . $resetLink . '</p>
                                <p>This link will expire in 1 hour.</p>
                                <p>If you didn\'t request a password reset, you can safely ignore this email.</p>
                                <p>Thank you,<br>The Crumbly Team</p>
                            </div>
                            <div style="background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #777;">
                                <p>&copy; ' . date('Y') . ' Crumbly. All rights reserved.</p>
                            </div>
                        </div>
                    ';

                    $mail->send();
                    $message = "A password reset link has been sent to your email address, it may take up to 5 minutes to arrive.";
                    $messageType = "success";
                    $email = ""; // Clear email field after successful submission
                } catch (Exception $e) {
                    $message = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    $messageType = "danger";
                }
            } else {
                $message = "An error occurred, please try again later";
                $messageType = "danger";
            }
        } else {
            // For security, don't reveal if email exists or not
            $message = "If your email is registered with us, you will receive a password reset link";
            $messageType = "success";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<?php include 'utils/header.php'; ?>

<body>
    <?php include 'utils/navbar.php'; ?>

    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
        <defs>
            <symbol xmlns="http://www.w3.org/2000/svg" id="key" viewBox="0 0 16 16">
                <path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L13 9.207l-.646.647a.5.5 0 0 1-.708 0L11 9.207l-.646.647a.5.5 0 0 1-.708 0L9 9.207l-.646.647A.5.5 0 0 1 8 10h-.535A4 4 0 0 1 0 8zm4-3a3 3 0 1 0 2.712 4.285A.5.5 0 0 1 7.163 9h.63l.853-.854a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.793-.793-1-1h-6.63a.5.5 0 0 1-.451-.285A3 3 0 0 0 4 5z" />
                <path d="M4 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0z" />
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
                                    <use xlink:href="#key"></use>
                                </svg>
                                <h2 class="section-title">Forgot Password</h2>
                                <p class="text-muted mb-4">Enter your email address and we'll send you a link to reset your password.</p>

                                <?php if (!empty($message)): ?>
                                    <div class="alert alert-<?php echo $messageType; ?> shadow-sm mt-3">
                                        <?php if ($messageType === 'danger'): ?>
                                            <div class="d-flex align-items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" viewBox="0 0 16 16">
                                                    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                                                </svg>
                                                <div><?php echo $message; ?></div>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check-circle-fill flex-shrink-0 me-2" viewBox="0 0 16 16">
                                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                                </svg>
                                                <div><?php echo $message; ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <form action="forgot-password.php" method="post">
                                <div class="mb-4">
                                    <label for="email" class="form-label">Email Address</label>
                                    <div class="input-group shadow-sm">
                                        <span class="input-group-text bg-light border-end-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope text-muted" viewBox="0 0 16 16">
                                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z" />
                                            </svg>
                                        </span>
                                        <input type="email" class="form-control form-control-lg border-start-0" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="name@example.com" required>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg shadow">Send Reset Link</button>
                                </div>

                                <div class="mt-4 text-center">
                                    <p>Remember your password? <a href="login.php" class="text-decoration-none fw-medium">Back to login</a></p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'utils/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-dismiss success message after 5 seconds
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.opacity = '0';
                    successAlert.style.transition = 'opacity 0.5s';
                    setTimeout(() => {
                        successAlert.style.display = 'none';
                    }, 500);
                }, 5000);
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