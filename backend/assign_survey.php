<?php
session_start();
include '../backend/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $land_id = $_POST['land_id'];
    $surveyor_id = $_POST['surveyor_id'];

    // Validate inputs
    if (empty($land_id) || empty($surveyor_id)) {
        echo json_encode(["status" => "error", "message" => "Invalid input data."]);
        exit();
    }

    // Check if land exists
    $checkLand = $conn->prepare("SELECT id FROM land_records WHERE id = ?");
    $checkLand->bind_param("i", $land_id);
    $checkLand->execute();
    $result = $checkLand->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Land record not found."]);
        exit();
    }

    // Assign the surveyor
    $assignSurveyor = $conn->prepare("INSERT INTO survey_tasks (land_id, surveyor_id, status, assigned_at) VALUES (?, ?, 'pending', NOW())");
    $assignSurveyor->bind_param("ii", $land_id, $surveyor_id);

    if ($assignSurveyor->execute()) {
        // Notify the surveyor (store notification)
        $notification_message = "You have been assigned a new land survey task.";
        $notifySurveyor = $conn->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
        $notifySurveyor->bind_param("is", $surveyor_id, $notification_message);
        $notifySurveyor->execute();

        echo json_encode(["status" => "success", "message" => "Surveyor assigned successfully and notified."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to assign surveyor."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
}
?>
