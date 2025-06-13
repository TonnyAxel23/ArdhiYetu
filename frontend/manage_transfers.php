<?php
session_start();
include '../backend/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $transfer_id = $_POST['transfer_id'];
    $action = $_POST['action'];

    if ($action == 'approve') {
        $stmt = $conn->prepare("UPDATE land_records lr
            JOIN ownership_transfers ot ON lr.id = ot.land_id
            SET lr.user_id = ot.new_owner_id
            WHERE ot.id = ?");
        $stmt->bind_param("i", $transfer_id);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE ownership_transfers SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $transfer_id);
        $stmt->execute();
    } elseif ($action == 'reject') {
        $stmt = $conn->prepare("UPDATE ownership_transfers SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $transfer_id);
        $stmt->execute();
    }

    header("Location: manage_transfers.php");
    exit();
}

$transfers = $conn->query("SELECT ot.id, lr.title_number, u1.full_name AS current_owner, u2.full_name AS new_owner, ot.status 
FROM ownership_transfers ot
JOIN land_records lr ON ot.land_id = lr.id
JOIN users u1 ON ot.current_owner_id = u1.id
JOIN users u2 ON ot.new_owner_id = u2.id
WHERE ot.status = 'pending'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Transfers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Manage Ownership Transfers</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Land Title</th>
                    <th>Current Owner</th>
                    <th>New Owner</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $transfers->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['land_title'] ?></td>
                        <td><?= $row['current_owner'] ?></td>
                        <td><?= $row['new_owner'] ?></td>
                        <td><?= $row['status'] ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="transfer_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
