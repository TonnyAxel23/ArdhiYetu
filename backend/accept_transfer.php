<?php
session_start();
header('Content-Type: application/json');
include '../backend/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

$conn->begin_transaction();
try {
    // Verify request belongs to user
    $stmt = $conn->prepare("SELECT land_id FROM land_transfers WHERE id=? AND new_owner_id=? AND status='pending'");
    $stmt->bind_param("ii", $_POST['request_id'], $_SESSION['user_id']);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    if (!$request) throw new Exception("Invalid transfer request");

    // Update land ownership
    $stmt = $conn->prepare("UPDATE land_records SET owner_id=? WHERE id=?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $request['land_id']);
    $stmt->execute();

    // Update transfer status
    $stmt = $conn->prepare("UPDATE land_transfers SET status='approved', processed_at=NOW() WHERE id=?");
    $stmt->bind_param("i", $_POST['request_id']);
    $stmt->execute();

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Transfer completed"]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

?>
