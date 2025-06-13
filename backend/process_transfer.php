<?php
session_start();
include '../backend/db_connect.php';

header('Content-Type: application/json');

// ✅ Debug: Check if session is properly set
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["message" => "Unauthorized access"]);
    exit();
}

// ✅ Decode JSON input safely
$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['transfer_id']) || !isset($data['action'])) {
    echo json_encode(["message" => "Invalid request parameters"]);
    exit();
}

$transfer_id = $data['transfer_id'];
$action = $data['action'];

if ($action === "approve") {
    // ✅ Fetch land and new owner details
    $query = "SELECT land_id, new_owner_id FROM ownership_transfers WHERE id = ? AND status = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $transfer_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($land_id, $new_owner_id);
        $stmt->fetch();
        $stmt->close(); // Close the previous statement

        // ✅ Check if land exists before updating
        $check_land = "SELECT id FROM land_records WHERE id = ?";
        $stmt = $conn->prepare($check_land);
        $stmt->bind_param("i", $land_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            echo json_encode(["message" => "Error: Land record not found"]);
            exit();
        }
        $stmt->close(); // Close the check statement

        // ✅ Update land ownership
        $update_query = "UPDATE land_records SET user_id = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ii", $new_owner_id, $land_id);
        if (!$stmt->execute()) {
            echo json_encode(["message" => "Error updating land ownership"]);
            exit();
        }
        $stmt->close(); // Close update statement

        // ✅ Mark transfer as approved
        $update_status = "UPDATE ownership_transfers SET status = 'approved' WHERE id = ?";
        $stmt = $conn->prepare($update_status);
        $stmt->bind_param("i", $transfer_id);
        $stmt->execute();
        $stmt->close(); // Close status update statement

        echo json_encode(["message" => "Ownership transfer approved successfully"]);
    } else {
        echo json_encode(["message" => "Invalid or non-pending transfer request"]);
    }
} elseif ($action === "reject") {
    // ✅ Reject transfer request
    $update_status = "UPDATE ownership_transfers SET status = 'rejected' WHERE id = ?";
    $stmt = $conn->prepare($update_status);
    $stmt->bind_param("i", $transfer_id);
    if ($stmt->execute()) {
        echo json_encode(["message" => "Ownership transfer rejected"]);
    } else {
        echo json_encode(["message" => "Error rejecting transfer request"]);
    }
    $stmt->close();
} else {
    echo json_encode(["message" => "Invalid action"]);
}

$conn->close();
?>
