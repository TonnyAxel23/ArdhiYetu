<?php
session_start();
include '../backend/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$sql = "SELECT * FROM transfer_requests WHERE status = 'Pending'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Transfer Requests</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <h2 class="text-center">Pending Transfer Requests</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Land ID</th>
                <th>Current Owner</th>
                <th>New Owner Email</th>
                <th>Reason</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['land_id']) ?></td>
                    <td><?= htmlspecialchars($row['user_id']) ?></td>
                    <td><?= htmlspecialchars($row['new_owner_email']) ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td>
                        <a href="../backend/process_approval.php?id=<?= $row['id'] ?>&action=approve" class="btn btn-success">Approve</a>
                        <a href="../backend/process_approval.php?id=<?= $row['id'] ?>&action=reject" class="btn btn-danger">Reject</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
<?php $conn->close(); ?>