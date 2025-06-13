<?php
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include your database connection
include 'db_connect.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate email
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    // Check if email exists in the database
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        die("Email not found.");
    }

    $user = $result->fetch_assoc();
    $user_id = $user['id'];

    // Generate a secure reset token
    $token = bin2hex(random_bytes(32));
    $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

    // Insert or update the reset token in the database
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expiry) 
                            VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE token = ?, expiry = ?");
    $stmt->bind_param("issss", $user_id, $token, $expiry, $token, $expiry);
    $stmt->execute();

    // Prepare to send the email using PHPMailer
    $mail = new PHPMailer(true);
    try {
        // SMTP settings for SendGrid
        $mail->isSMTP();
        $mail->Host       = 'smtp.sendgrid.net';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'apikey'; // Always 'apikey'
        $mail->Password   = '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'; // Your real SendGrid API key
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('tonnyodhiambo49@gmail.com', 'ArdhiYetu Support');
        $mail->addAddress($email);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body    = "Click the link below to reset your password:<br><br>
                          <a href='http://localhost/ArdhiYetu/frontend/reset_password.html?token=$token'>Reset Password</a>";

        $mail->send();
        echo "Password reset link sent!";
    } catch (Exception $e) {
        echo "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
