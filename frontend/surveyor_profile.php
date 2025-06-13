<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'surveyor') {
    header("Location: login.php");
    exit();
}

include '../backend/db_connect.php';

$user_id = $_SESSION['user_id'];
$message = "";

// Fetch surveyor details
$sql = "SELECT full_name, email, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$surveyor = $result->fetch_assoc();

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    // Handle profile picture upload
    $profile_picture = $surveyor['profile_picture'];
    if (!empty($_FILES['profile_picture']['name'])) {
        $upload_dir = "../uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $profile_picture = $upload_dir . basename($_FILES['profile_picture']['name']);
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_picture);
    }

    $update_query = "UPDATE users SET full_name = ?, email = ?, profile_picture = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssi", $name, $email, $profile_picture, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['user_name'] = $name;
        $message = "<div class='alert alert-success'>Profile updated successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Failed to update profile.</div>";
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($stored_password);
    $stmt->fetch();
    $stmt->close();

    if (password_verify($current_password, $stored_password)) {
        $update_password_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($update_password_query);
        $stmt->bind_param("si", $new_password, $user_id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Password changed successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Failed to change password.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Current password is incorrect.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surveyor Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 600px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        .profile-pic { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Surveyor Profile</h2>
        <?= $message; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="text-center">
                <img src="<?= $surveyor['profile_picture'] ?: '../assets/default-profile.png'; ?>" class="profile-pic">
            </div>
            <div class="mb-3">
                <label>Full Name:</label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($surveyor['full_name']); ?>" required>
            </div>
            <div class="mb-3">
                <label>Email:</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($surveyor['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label>Profile Picture:</label>
                <input type="file" name="profile_picture" class="form-control">
            </div>
            <button type="submit" name="update_profile" class="btn btn-primary w-100">Update Profile</button>
        </form>

        <h4 class="mt-4">Change Password</h4>
        <form method="POST">
            <div class="mb-3">
                <label>Current Password:</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>New Password:</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <button type="submit" name="change_password" class="btn btn-danger w-100">Change Password</button>
        </form>

        <a href="surveyor_dashboard.php" class="btn btn-secondary mt-3">← Back to Dashboard</a>
    </div>
</body>
</html>
