<?php
session_start();
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: login.html");
    exit();
}

// Generate new OTP
$otp = rand(100000, 999999);
$_SESSION['otp'] = $otp;
$_SESSION['otp_expiry'] = time() + 300; // Expire in 5 minutes

$email = $_SESSION['user_email'];
$subject = "Your New OTP Code";
$message = "Your new OTP code is: $otp. It will expire in 5 minutes.";
$headers = "From: no-reply@ardhiyetu.com";

if (mail($email, $subject, $message, $headers)) {
    echo "<script>alert('New OTP sent!'); window.location.href='verify_otp.php';</script>";
} else {
    echo "<script>alert('Failed to resend OTP.'); window.location.href='verify_otp.php';</script>";
}
?>
