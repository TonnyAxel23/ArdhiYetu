<?php
// decline_transfer.php

include '../backend/db_connect.php';

// Get POST data
$request_id = $_POST['request_id'];
$new_owner_id = $_POST['new_owner_id'];

// Check if request exists
$query = "SELECT * FROM land_transfer_requests WHERE id = ? AND new_owner_id = ? AND status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $request_id, $new_owner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "No pending request found."]);
    exit;
}

// Update request status
$update_request = "UPDATE land_transfer_requests SET status = 'declined' WHERE id = ?";
$stmt2 = $conn->prepare($update_request);
$stmt2->bind_param("i", $request_id);
$stmt2->execute();

echo json_encode(["success" => true, "message" => "Land transfer declined."]);
?>
