<?php
include 'db_connect.php';

function addNotification($user_id, $message) {
    global $conn;
    $sql = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
    $stmt->close();
}
?>
