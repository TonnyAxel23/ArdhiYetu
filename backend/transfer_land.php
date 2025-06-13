<?php
session_start();
header('Content-Type: application/json');
include '../backend/db_connect.php';

// Validate session and CSRF
if (!isset($_SESSION['user_id']) || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit();
}

// Input validation
$required = ['land_id', 'new_owner_phone', 'reason'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(["status" => "error", "message" => "$field is required"]);
        exit();
    }
}

$conn->begin_transaction();
try {
    // Validate land ownership
    $stmt = $conn->prepare("SELECT id, owner_id FROM land_records WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $_POST['land_id'], $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the land exists and is owned by the logged-in user
    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "You do not own this land parcel"]);
        exit();
    }

    // Get new owner ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $_POST['new_owner_phone']);
    $stmt->execute();
    $new_owner = $stmt->get_result()->fetch_assoc();
    if (!$new_owner) {
        throw new Exception("New owner not found");
    }

    // Create transfer request
    $stmt = $conn->prepare("INSERT INTO land_transfers (land_id, current_owner_id, new_owner_id, reason) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $_POST['land_id'], $_SESSION['user_id'], $new_owner['id'], $_POST['reason']);
    $stmt->execute();
    $request_id = $conn->insert_id;

    // Simulate SMS send by writing to a log
    $accept_link = "https://yourdomain.com/accept_transfer.php?request_id=$request_id";
    $message = "Land transfer request #$request_id\nAccept: $accept_link";
    file_put_contents('sms.log', "To: {$_POST['new_owner_phone']}\n$message\n\n", FILE_APPEND);

    $conn->commit();
    echo json_encode([ 
        "status" => "success", 
        "message" => "Land transfer request submitted successfully.", 
        "request_id" => $request_id 
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
