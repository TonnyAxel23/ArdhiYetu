<?php
session_start();
include '../backend/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $land_id = $_POST['land_id'];
    $land_title = trim($_POST['land_title']);
    $location = trim($_POST['location']);
    $size = $_POST['size'];

    if (empty($land_title) || empty($location) || empty($size)) {
        die("All fields are required.");
    }

    $sql = "UPDATE land_records SET land_title = ?, location = ?, size = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdi", $land_title, $location, $size, $land_id);

    if ($stmt->execute()) {
        header("Location: view_land.php?success=Land record updated successfully");
        exit();
    } else {
        die("Error updating land record: " . $stmt->error);
    }

    $stmt->close();
}

$conn->close();
?>
