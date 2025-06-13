<?php
session_start();
include '../backend/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["message" => "Unauthorized"]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];
    $sql = "UPDATE notifications SET status = 'read' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
    echo json_encode(["message" => "Notification marked as read"]);
}
?>
