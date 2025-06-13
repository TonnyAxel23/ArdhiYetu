<?php
session_start();
include '../backend/db_connect.php';

header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    echo json_encode(["message" => "Unauthorized access"]);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $land_id = $_POST['land_id'];
    $new_owner_id = $_POST['new_owner_id'];

    // Verify if the land belongs to the logged-in user
    $check_query = "SELECT id FROM land_records WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $land_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo json_encode(["message" => "Unauthorized: You do not own this land."]);
        exit();
    }

    // Insert the transfer request
    $insert_query = "INSERT INTO ownership_transfers (land_id, current_owner, new_owner, status) VALUES (?, ?, ?, 'pending')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iii", $land_id, $user_id, $new_owner_id);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Transfer request submitted successfully"]);
    } else {
        echo json_encode(["message" => "Failed to submit transfer request"]);
    }
}
?>
