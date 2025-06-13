<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit(json_encode(['status' => 'error', 'message' => 'Unauthorized access']));
}

require_once '../backend/db_connect.php';

header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_POST['land_id']) || !is_numeric($_POST['land_id'])) {
        throw new Exception("Invalid land ID");
    }

    $landId = (int)$_POST['land_id'];
    $currentDateTime = date('Y-m-d H:i:s');

    // Soft delete (mark as deleted)
    $stmt = $conn->prepare("UPDATE land_records SET deleted_at = ? WHERE id = ?");
    $stmt->bind_param("si", $currentDateTime, $landId);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete record: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("Record not found or already deleted");
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Land record deleted successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}