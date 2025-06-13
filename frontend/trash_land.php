<?php
session_start();
include '../backend/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

// Fetch soft-deleted records
$sql = "SELECT * FROM land_records WHERE deleted_at IS NOT NULL";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trash - Deleted Land Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1000px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .btn-restore {
            color: #28a745;
            border: 1px solid #28a745;
        }
        .btn-restore:hover {
            background-color: #28a745;
            color: white;
        }
        .btn-delete {
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        .btn-delete:hover {
            background-color: #dc3545;
            color: white;
        }
        .table th {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Deleted Land Records (Trash)</h2>
        <a href="view_land.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Land Records</a>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Location</th>
                    <th>Size (Acres)</th>
                    <th>Date Deleted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($row['land_title']); ?></td>
                        <td><?= htmlspecialchars($row['location']); ?></td>
                        <td><?= htmlspecialchars($row['size']); ?> acres</td>
                        <td><?= date("d M Y, H:i", strtotime($row['deleted_at'])); ?></td>
                        <td>
                            <button class="btn btn-sm btn-restore" onclick="restoreLand(<?= $row['id'] ?>)">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                            <button class="btn btn-sm btn-delete" onclick="permanentlyDelete(<?= $row['id'] ?>)">
                                <i class="fas fa-trash"></i> Delete Permanently
                            </button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script>
        function restoreLand(landId) {
            if (confirm("Are you sure you want to restore this land record?")) {
                fetch("restore_land.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "land_id=" + landId
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === "success") {
                        location.reload();
                    }
                })
                .catch(error => console.error("Error restoring land record:", error));
            }
        }

        function permanentlyDelete(landId) {
            if (confirm("This will permanently delete the land record. Proceed?")) {
                fetch("delete_permanently.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "land_id=" + landId
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === "success") {
                        location.reload();
                    }
                })
                .catch(error => console.error("Error deleting land record:", error));
            }
        }
    </script>
</body>
</html>
