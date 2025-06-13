<?php
session_start();
include '../backend/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? password_hash(trim($_POST['password']), PASSWORD_BCRYPT) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    // Set default role
    $role = 'user';

    // Validate required fields
    if (empty($full_name) || empty($email) || empty($password)) {
        header("Location: ../frontend/register.html?error=Please fill all required fields.");
        exit();
    }

    // Check if email already exists
    $check_email_sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_email_sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        header("Location: ../frontend/register.html?error=Email already exists. Try another.");
        exit();
    }
    $stmt->close();

    // Insert new user
    $insert_sql = "INSERT INTO users (full_name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);

    // Check if phone is empty
    if (empty($phone)) {
        $phone = NULL; // set to NULL for database
    }

    $stmt->bind_param("sssss", $full_name, $email, $password, $role, $phone);

    if ($stmt->execute()) {
        $stmt->close();
        // Show success message and auto-redirect
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta http-equiv='refresh' content='4;url=../frontend/login.html'>
            <title>Registration Successful</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    text-align: center;
                    margin-top: 100px;
                    background-color: #f0f8ff;
                }
                .message {
                    font-size: 24px;
                    color: green;
                }
            </style>
        </head>
        <body>
            <div class='message'>
                Registration Successful!<br>
                Redirecting you to login page in 4 seconds...
            </div>
        </body>
        </html>";
        exit();
    } else {
        $stmt->close();
        header("Location: ../frontend/register.html?error=Registration failed. Try again.");
        exit();
    }
}
$conn->close();
?>
