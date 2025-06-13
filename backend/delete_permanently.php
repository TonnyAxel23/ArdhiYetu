<?php
session_start();
include '../backend/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['land_id'])) {
    $land_id = $_POST['land_id'];

    $sql = "DELETE FROM land_records WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $land_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Land record permanently deleted!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete the land record."]);
    }

    $stmt->close();
    $conn->close();
}
?>
