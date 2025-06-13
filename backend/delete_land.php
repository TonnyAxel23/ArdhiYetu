<?php
session_start();
include 'db_connect.php';

// Ensure only admin can perform this action
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(["status" => "error", "message" => "Unauthorized access."]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $land_id = $_POST['land_id'];

    $sql = "DELETE FROM land_records WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $land_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Land record deleted successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete land record."]);
    }
}
?>
