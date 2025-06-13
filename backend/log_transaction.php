<?php
include 'db_connect.php';

function logTransaction($land_id, $action, $performed_by) {
    global $conn;
    $sql = "INSERT INTO land_transactions (land_id, action, performed_by) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $land_id, $action, $performed_by);
    $stmt->execute();
    $stmt->close();
}
?>
