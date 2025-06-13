<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Check if the email exists in the users table
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        header("Location: ../frontend/forgot_password.html?error=Email not found");
        exit();
    }

    // Generate a unique token
    $token = bin2hex(random_bytes(32));

    // Insert reset request into the database
    $sql = "INSERT INTO password_resets (email, token) VALUES (?, ?) ON DUPLICATE KEY UPDATE token=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $email, $token, $token);
    $stmt->execute();
    
    // Send the reset email
    $reset_link = "http://localhost/ArdhiYetu/frontend/reset_password.php?token=" . $token;
    $subject = "Reset Your ArdhiYetu Password";
    $message = "Click the following link to reset your password: " . $reset_link;
    $headers = "From: no-reply@ardhiyetu.com\r\nContent-Type: text/plain; charset=UTF-8";

    if (mail($email, $subject, $message, $headers)) {
        header("Location: ../frontend/forgot_password.html?success=Check your email for reset link");
        exit();
    } else {
        header("Location: ../frontend/forgot_password.html?error=Failed to send email");
        exit();
    }
}

$conn->close();
?>
