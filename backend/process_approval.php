<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $request_id = $_GET['id'];
    $action = $_GET['action'];

    if ($action === "approve") {
        $status = "Approved";

        // Fetch request details
        $query = "SELECT land_id, new_owner_email FROM transfer_requests WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        $stmt->close();

        // Find the new owner's ID from their email
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $request['new_owner_email']);
        $stmt->execute();
        $result = $stmt->get_result();
        $new_owner = $result->fetch_assoc();
        $stmt->close();

        if ($new_owner) {
            // Update land ownership
            $updateQuery = "UPDATE land_records SET user_id = ? WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("ii", $new_owner['id'], $request['land_id']);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $status = "Rejected";
    }

    // Update transfer request status
    $sql = "UPDATE transfer_requests SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $request_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Request $status successfully!'); window.location.href='../frontend/approve_transfer.php';</script>";
}

$conn->close();
?>
