<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../backend/db_connect.php';

if (!isset($_GET['id'])) {
    die("Land record not found.");
}

$land_id = $_GET['id'];

// Fetch land details
$sql = "SELECT title_number, location, approximate_area, ST_AsGeoJSON(boundary) as geojson FROM land_records WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $land_id);
$stmt->execute();
$result = $stmt->get_result();
$land = $result->fetch_assoc();
$stmt->close();

if (!$land) {
    die("Invalid land record.");
}

$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $land_title = trim($_POST['title_number']);
    $location = trim($_POST['location']);
    $size = $_POST['approximate_area'];
    $geojson = $_POST['geojson'];

    $update_sql = "UPDATE land_records SET title_number=?, location=?, approximate_area=?, boundary=ST_GeomFromGeoJSON(?) WHERE id=?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssdsi", $title_number, $location, $approximate_area, $geojson, $land_id);

    if ($update_stmt->execute()) {
        $message = "<div class='alert alert-success'>Land record updated successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error: " . $update_stmt->error . "</div>";
    }
    $update_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Land Record</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <style>
        .container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .back-btn {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Edit Land Record</h2>

        <!-- Back to Dashboard Button -->
        <a href="user_dashboard.php" class="btn btn-secondary back-btn">← Back to Dashboard</a>

        <?= $message; ?>

        <form method="POST" action="add_land.php" onsubmit="return confirmSubmission()">
            <label>Land Title:</label>
            <input type="text" name="land_title" required class="form-control mb-2">

            <label>Owner Name:</label> <!-- ✅ Added Owner Name Field -->
            <input type="text" name="owner_name" required class="form-control mb-2">

            <label>Registration Number:</label> <!-- ✅ Added Registration Number Field -->
            <input type="text" name="registration_number" required class="form-control mb-2">

            <label>Location:</label>
            <input type="text" name="location" required class="form-control mb-2">

            <label>Size (Acres):</label>
            <input type="number" step="0.01" name="size" required class="form-control mb-2">

            <label>Select Land Location on Map:</label>
            <div id="map" style="height: 400px; margin-bottom: 10px;"></div>

            <input type="hidden" id="latitude" name="latitude">
            <input type="hidden" id="longitude" name="longitude">

            <button type="submit" class="btn btn-primary w-100">Add Land Record</button>
        </form>
    </div>

    <script>
        var map = L.map('map').setView([-1.2921, 36.8219], 10); // Default center: Nairobi
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);

        var geojson = <?= json_encode($land["geojson"]); ?>;
        if (geojson) {
            var layer = L.geoJSON(JSON.parse(geojson)).addTo(drawnItems);
        }

        var drawControl = new L.Control.Draw({
            edit: { featureGroup: drawnItems, remove: true },
            draw: { polygon: true, rectangle: true }
        });
        map.addControl(drawControl);

        map.on('draw:created', function (event) {
            drawnItems.clearLayers();
            var layer = event.layer;
            drawnItems.addLayer(layer);
            document.getElementById('geojson').value = JSON.stringify(layer.toGeoJSON());
        });
    </script>
</body>
</html>
