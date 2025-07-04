<?php
session_start();

// Check if database connection is missing
if (!isset($conn) || !$conn) {
    // Include database connection
    require_once 'utils/dbconnect.php';
}

// Check if we have an active OTP verification session
if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit();
} else if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_otp_id'])) {
    // No active verification session, redirect to login
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
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

// Redirect to verification page if not a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>window.location.href = 'verify-otp.php';</script>";
    exit();
}

echo "test";
// Process OTP verification
$entered_otp = sanitizeInput($_POST['otp']);

// Get user ID from session
$user_id = $_SESSION['temp_user_id'];
$otp_id = $_SESSION['temp_otp_id'];

// Check if OTP is valid and not expired

$sql = "SELECT id FROM user_otps 
        WHERE id = ? AND user_id = ? AND otp_code = ? AND expires_at >= UTC_TIMESTAMP() AND is_used = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $otp_id, $user_id, $entered_otp);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {

    $otpresult = $result->fetch_assoc();
    echo "OTP ID: " . $otpresult['id'] . "<br>";

    // OTP is valid, mark it as used
    $sql = "UPDATE user_otps SET is_used = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $otp_id);
    $stmt->execute();

    // Set the verified session variables from temporary ones
    $_SESSION['user_id'] = $_SESSION['temp_user_id'];
    $_SESSION['user_email'] = $_SESSION['temp_user_email'];
    $_SESSION['user_firstname'] = $_SESSION['temp_user_firstname'];
    $_SESSION['user_role'] = $_SESSION['temp_user_role'];

    // Remove temporary variables and OTP data
    unset($_SESSION['temp_user_id']);
    unset($_SESSION['temp_user_email']);
    unset($_SESSION['temp_user_firstname']);
    unset($_SESSION['temp_user_role']);
    unset($_SESSION['temp_otp_id']);

    // Save session before redirecting
    session_write_close();

    // Redirect based on user role
    if ($_SESSION['user_role'] == 1) {
        echo "<script>window.location.href = 'admin_dashboard.php';</script>";
    } else {
        echo "<script>window.location.href = 'index.php';</script>";
    }
    exit();
} else {
    // Check if OTP is expired
    $sql = "SELECT id FROM user_otps 
            WHERE id = ? AND user_id = ? AND expires_at <= UTC_TIMESTAMP() AND is_used = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $otp_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Redirect back with error
        echo "<script>window.location.href = 'verify-otp.php?error=expired';</script>";
    } else {
        // Redirect back with error
        echo "<script>window.location.href = 'verify-otp.php?error=invalid';</script>";
    }
    exit();
}
?>