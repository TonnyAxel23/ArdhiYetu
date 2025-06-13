<?php
session_start();
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['otp'])) {
    header("Location: login.html");
    exit();
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST['otp'];

    if ($entered_otp == $_SESSION['otp'] && time() < $_SESSION['otp_expiry']) {
        $_SESSION['user_id'] = $_SESSION['temp_user_id']; // Finalize login
        unset($_SESSION['temp_user_id'], $_SESSION['otp'], $_SESSION['otp_expiry']);

        header("Location: user_dashboard.php"); // Redirect after successful verification
        exit();
    } else {
        $message = "<div class='alert alert-danger'>Invalid or expired OTP.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - ArdhiYetu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Verify OTP</h2>
        <?= $message; ?>
        <form method="POST">
            <label>Enter OTP:</label>
            <input type="text" name="otp" class="form-control mb-3" required>
            <button type="submit" class="btn btn-primary w-100">Verify</button>
        </form>

        <form method="POST" action="resend_otp.php">
    <button type="submit" class="btn btn-link">Resend OTP</button>
</form>

    </div>
</body>
</html>
