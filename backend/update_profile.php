<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    // Handle file upload
    if (!empty($_FILES['profile_picture']['name'])) {
        $target_dir = "../uploads/";
        $file_name = basename($_FILES["profile_picture"]["name"]);
        $target_file = $target_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png"];

        if (!in_array($file_type, $allowed_types)) {
            die("Only JPG, JPEG, and PNG files are allowed.");
        }

        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            // Update profile picture in the database
            $sql = "UPDATE users SET full_name = ?, email = ?, profile_picture = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $full_name, $email, $file_name, $user_id);
        } else {
            die("Error uploading file.");
        }
    } else {
        // Update only name and email if no file is uploaded
        $sql = "UPDATE users SET full_name = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $full_name, $email, $user_id);
    }

    if ($stmt->execute()) {
        header("Location: ../frontend/user_profile.php?update=success");
        exit();
    } else {
        die("Update failed: " . $stmt->error);
    }

    $stmt->close();
}
$conn->close();
?>
