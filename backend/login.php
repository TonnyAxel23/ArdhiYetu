<?php
session_start();
ob_start(); // Start output buffering to ensure proper rendering

include '../backend/db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    // Check if email and password are provided
    if (empty($email) || empty($password)) {
        header("Location: ../frontend/login.html?error=Please fill in all fields");
        exit();
    }

    // Prepare the query
    $sql = "SELECT id, full_name, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            // Regenerate session ID to prevent fixation attacks
            session_regenerate_id(true);

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['full_name'];
            $_SESSION['role'] = $row['role'];

            // Determine redirection URL based on role
            $redirect_url = ($row['role'] == 'admin') ? '../frontend/admin_dashboard.php' : 
                            (($row['role'] == 'surveyor') ? '../frontend/surveyor_dashboard.php' : 
                            '../frontend/user_dashboard.php');

            // Display loading screen before redirection
            echo '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Logging In...</title>
                <style>
                    body {
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        background-color: #fff;
                        margin: 0;
                        text-align: center;
                    }
                    .loading-container {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                    }
                    .loading-logo {
                        width: 500px;
                        height: 500px;
                        animation: fadeIn 1.5s ease-in-out;
                    }
                    .loading-text {
                        font-size: 50px;
                        font-weight: bold;
                        color: #333;
                        margin-top: 10px;
                    }
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                </style>
            </head>
            <body>
                <div class="loading-container">
                    <img src="../frontend/images/ArdhiYetu-removebg-preview.png" alt="Loading..." class="loading-logo">
                    <p class="loading-text">Logging in, please wait...</p>
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = "' . $redirect_url . '";
                    }, 2000);
                </script>
            </body>
            </html>';

            ob_end_flush(); // Ensure all output is sent before redirecting
            exit();
        } else {
            header("Location: ../frontend/login.html?error=Invalid email or password");
            exit();
        }
    } else {
        header("Location: ../frontend/login.html?error=Invalid email or password");
        exit();
    }
} else {
    // Prevent direct access
    header("Location: ../frontend/login.html");
    exit();
}
?>
