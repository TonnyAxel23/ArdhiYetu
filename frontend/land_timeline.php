<?php
session_start();
include '../backend/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$land_id = $_GET['id'] ?? null;
if (!$land_id) {
    die("Invalid land record.");
}

// Fetch land details
$land_query = "SELECT land_title, location, size FROM land_records WHERE id = ?";
$stmt = $conn->prepare($land_query);
$stmt->bind_param("i", $land_id);
$stmt->execute();
$land = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch transaction history
$history_query = "SELECT action, performed_by, timestamp FROM land_transactions WHERE land_id = ? ORDER BY timestamp DESC";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $land_id);
$stmt->execute();
$history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Land Transaction Timeline</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .timeline {
            position: relative;
            list-style: none;
            padding: 0;
        }
        .timeline::before {
            content: "";
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #007bff;
        }
        .timeline-item {
            position: relative;
            padding-left: 50px;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: "🔹";
            position: absolute;
            left: 10px;
            top: 0;
            font-size: 18px;
            color: #007bff;
        }
        .timeline-item .timestamp {
            font-size: 14px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Land Transaction Timeline</h2>
        <h4><?= htmlspecialchars($land['land_title']) ?></h4>
        <p><strong>Location:</strong> <?= htmlspecialchars($land['location']) ?></p>
        <p><strong>Size:</strong> <?= htmlspecialchars($land['size']) ?> acres</p>

        <ul class="timeline">
            <?php while ($row = $history->fetch_assoc()): ?>
                <li class="timeline-item">
                    <strong><?= htmlspecialchars($row['action']) ?></strong><br>
                    <span class="timestamp"><?= date("d M Y, H:i", strtotime($row['timestamp'])) ?></span><br>
                    <em>Performed by: <?= htmlspecialchars($row['performed_by']) ?></em>
                </li>
            <?php endwhile; ?>
        </ul>

        <a href="view_land.php" class="btn btn-secondary">Back to Land Records</a>
    </div>
</body>
</html>
