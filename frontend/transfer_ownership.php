<?php
session_start();
include '../backend/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $land_id = $_POST['land_id'];
    $new_owner_email = $_POST['new_owner_email'];

    // Get new owner ID from email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $new_owner_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $new_owner = $result->fetch_assoc();
    
    if (!$new_owner) {
        $message = "<div class='alert alert-danger'>User with this email not found.</div>";
    } else {
        $new_owner_id = $new_owner['id'];
        $current_owner_id = $_SESSION['user_id'];

        // Insert transfer request
        $stmt = $conn->prepare("INSERT INTO ownership_transfers (land_id, current_owner_id, new_owner_id) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $land_id, $current_owner_id, $new_owner_id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Ownership transfer request sent successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Failed to send request.</div>";
        }
    }
}

$land_query = "SELECT id, title_number FROM land_records WHERE user_id = ?";
$stmt = $conn->prepare($land_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$land_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Land Ownership</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Transfer Land Ownership</h2>
        <?= $message; ?>
        <form method="POST">
            <label>Select Land:</label>
            <select name="land_id" class="form-control mb-2" required>
                <?php while ($row = $land_result->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>"><?= $row['land_title'] ?></option>
                <?php endwhile; ?>
            </select>

            <label>New Owner Email:</label>
            <input type="email" name="new_owner_email" class="form-control mb-2" required>

            <button type="submit" class="btn btn-primary">Request Transfer</button>
        </form>
    </div>
</body>
</html>
