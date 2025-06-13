<?php
session_start();
include '../backend/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $doc_id = intval($_GET['id']); // Convert to integer for security

    // ✅ Step 1: Fetch the file path before deleting
    $fetch_query = "SELECT file_path FROM land_documents WHERE id = ?";
    $stmt = $conn->prepare($fetch_query);

    if (!$stmt) {
        die("Error preparing query: " . $conn->error);
    }

    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        die("Error: Document not found.");
    }

    $stmt->bind_result($file_path);
    $stmt->fetch();
    $stmt->close();

    // ✅ Step 2: Check if file exists before attempting to delete
    if (!empty($file_path) && file_exists($file_path)) {
        if (!unlink($file_path)) {
            die("Error: Unable to delete file.");
        }
    } else {
        die("Error: File not found on server.");
    }

    // ✅ Step 3: Remove the record from the database
    $delete_query = "DELETE FROM land_documents WHERE id = ?";
    $stmt = $conn->prepare($delete_query);

    if (!$stmt) {
        die("Error preparing delete query: " . $conn->error);
    }

    $stmt->bind_param("i", $doc_id);
    
    if ($stmt->execute()) {
        header("Location: view_documents.php?success=Document deleted successfully");
        exit();
    } else {
        die("Error deleting document: " . $stmt->error);
    }
} else {
    die("Error: Invalid document ID.");
}
?>
