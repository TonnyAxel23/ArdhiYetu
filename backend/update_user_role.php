<?php
session_start();
include '../backend/db_connect.php';

// Ensure only admin can access this
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(["status" => "error", "message" => "Unauthorized access."]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];

    // Validate role
    $allowed_roles = ['user', 'surveyor', 'admin'];
    if (!in_array($new_role, $allowed_roles)) {
        die(json_encode(["status" => "error", "message" => "Invalid role."]));
    }

    // Update role in the database
    $sql = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_role, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "User role updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update role."]);
    }
}
?>
