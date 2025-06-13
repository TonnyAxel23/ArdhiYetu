<?php
session_start();
include '../backend/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

$transfer_id = $_POST['transfer_id'];
$action = $_POST['action']; // 'approve' or 'reject'

// Get transfer details
$query = "SELECT land_id, new_owner_id FROM ownership_transfers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $transfer_id);
$stmt->execute();
$result = $stmt->get_result();
$transfer = $result->fetch_assoc();

if (!$transfer) {
    echo json_encode(["status" => "error", "message" => "Transfer request not found."]);
    exit();
}

if ($action == 'approve') {
    // Update land_records table
    $update_query = "UPDATE land_records SET owner_id = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $transfer['new_owner_id'], $transfer['land_id']);
    $stmt->execute();

    // Update transfer request status
    $status_update = "UPDATE ownership_transfers SET status = 'approved', approved_at = NOW() WHERE id = ?";
} else {
    // Reject transfer request
    $status_update = "UPDATE ownership_transfers SET status = 'rejected' WHERE id = ?";
}

$stmt = $conn->prepare($status_update);
$stmt->bind_param("i", $transfer_id);
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Transfer request $action."]);
} else {
    echo json_encode(["status" => "error", "message" => "Error processing request."]);
}
?>
