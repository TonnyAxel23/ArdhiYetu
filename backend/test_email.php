<?php
$to = "recipient@example.com";
$subject = "Test Email from XAMPP";
$message = "This is a test email from XAMPP local server.";
$headers = "From: your-email@gmail.com";

if (mail($to, $subject, $message, $headers)) {
    echo "Email sent successfully!";
} else {
    echo "Failed to send email.";
}
?>
