<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access denied");
}

require_once '../backend/db_connect.php';

// Set headers to return JSON
header('Content-Type: application/json');

try {
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

    $query = "SELECT title_number, approximate_area, registry_map_sheet, location 
              FROM land_records 
              WHERE deleted_at IS NULL";

    if (!empty($search)) {
        $query .= " AND (title_number LIKE '%$search%' OR 
                        registry_map_sheet LIKE '%$search%' OR 
                        location LIKE '%$search%')";
    }

    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}