<?php
session_start();
include '../backend/db_connect.php';

// Ensure only admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch all land records
$sql = "SELECT id, owner_name, title_number, location, approximate_area, registry_map_sheet FROM land_records";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Land Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center">Manage Land Records</h2>

        <!-- Back to Dashboard -->
        <a href="admin_dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Owner</th>
                    <th>Title</th>
                    <th>Location</th>
                    <th>Size (Acres)</th>
                    <th>Registration No.</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['owner_name']); ?></td>
                    <td><?= htmlspecialchars($row['title_number']); ?></td>
                    <td><?= htmlspecialchars($row['location']); ?></td>
                    <td><?= htmlspecialchars($row['approximate_area']); ?></td>
                    <td><?= htmlspecialchars($row['registry_map_sheet']); ?></td>
                    <td>
                        <a href="edit_land.php?id=<?= $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $row['id']; ?>)">Delete</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
        function confirmDelete(landId) {
            if (confirm("Are you sure you want to delete this land record? This action cannot be undone!")) {
                fetch("../backend/delete_land.php", {
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
