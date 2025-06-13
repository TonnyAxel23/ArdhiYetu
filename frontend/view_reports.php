<?php
session_start();
include '../backend/db_connect.php';

// Ensure only admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch total land records
$land_count_query = "SELECT COUNT(*) as total FROM land_records";
$land_count_result = $conn->query($land_count_query);
$land_count = $land_count_result->fetch_assoc()['total'] ?? 0;

// Fetch total registered users
$user_count_query = "SELECT COUNT(*) as total FROM users";
$user_count_result = $conn->query($user_count_query);
$user_count = $user_count_result->fetch_assoc()['total'] ?? 0;

// Fetch pending ownership transfers
$pending_transfers_query = "SELECT COUNT(*) as total FROM ownership_transfers WHERE status = 'pending'";
$pending_transfers_result = $conn->query($pending_transfers_query);
$pending_transfers = $pending_transfers_result->fetch_assoc()['total'] ?? 0;

// Fetch land distribution by location
$land_distribution_query = "SELECT location, COUNT(*) as count FROM land_records GROUP BY location";
$land_distribution_result = $conn->query($land_distribution_query);

$locations = [];
$counts = [];
while ($row = $land_distribution_result->fetch_assoc()) {
    $locations[] = $row['location'];
    $counts[] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reports - ArdhiYetu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center">Land Reports</h2>

        <!-- Back to Dashboard -->
        <a href="admin_dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

        <div class="alert alert-info text-center">
            <h4>Total Lands: <strong><?= $land_count; ?></strong> | Total Users: <strong><?= $user_count; ?></strong> | Pending Transfers: <strong><?= $pending_transfers; ?></strong></h4>
        </div>

        <h4>Land Distribution by Location</h4>
        <canvas id="barChart"></canvas>

        <h4 class="mt-4">Pending Ownership Transfers</h4>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Land Title</th>
                    <th>Current Owner</th>
                    <th>New Owner</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
               $transfers_query = "SELECT ot.id, lr.title_number, u1.full_name AS current_owner, u2.full_name AS new_owner, ot.status
                    FROM ownership_transfers ot
                    JOIN land_records lr ON ot.land_id = lr.id
                    JOIN users u1 ON ot.current_owner = u1.id
                    JOIN users u2 ON ot.new_owner = u2.id";

                $transfers_result = $conn->query($transfers_query);

                while ($row = $transfers_result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['title_number']); ?></td>
                    <td><?= htmlspecialchars($row['current_owner']); ?></td>
                    <td><?= htmlspecialchars($row['new_owner']); ?></td>
                    <td><?= htmlspecialchars($row['status']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Bar Chart Data
        const locations = <?= json_encode($locations); ?>;
        const counts = <?= json_encode($counts); ?>;

        new Chart(document.getElementById('barChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: locations,
                datasets: [{
                    label: 'Land Count',
                    data: counts,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
</body>
</html>
