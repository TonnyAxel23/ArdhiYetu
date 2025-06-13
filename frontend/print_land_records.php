<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access denied");
}

require_once '../backend/db_connect.php';

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$query = "SELECT title_number, approximate_area, registry_map_sheet, location 
          FROM land_records 
          WHERE deleted_at IS NULL";

if (!empty($search)) {
    $query .= " AND (title_number LIKE '%$search%' OR 
                    registry_map_sheet LIKE '%$search%' OR 
                    location LIKE '%$search%')";
}

$result = $conn->query($query);

if (!$result) {
    die("Error retrieving records: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Print Land Records</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        h2 {
            text-align: center;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 25px;
        }
        table, th, td {
            border: 1px solid #333;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        .print-btn {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<h2>Land Records</h2>

<button onclick="window.print()" class="print-btn">Print</button>

<table>
    <thead>
        <tr>
            <th>Title Number</th>
            <th>Approximate Area</th>
            <th>Registry Map Sheet</th>
            <th>Location</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['title_number']) ?></td>
                <td><?= htmlspecialchars($row['approximate_area']) ?></td>
                <td><?= htmlspecialchars($row['registry_map_sheet']) ?></td>
                <td><?= htmlspecialchars($row['location']) ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>

<?php
$conn->close();
?>
