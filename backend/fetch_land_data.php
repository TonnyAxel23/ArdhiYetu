<?php
include '../backend/db_connect.php';

header('Content-Type: application/json');

$sql = "SELECT id, land_title, owner_name, ST_AsGeoJSON(boundary) AS geojson FROM land_records WHERE boundary IS NOT NULL";
$result = $conn->query($sql);

$land_parcels = [];

while ($row = $result->fetch_assoc()) {
    $land_parcels[] = [
        'id' => $row['id'],
        'title' => $row['land_title'],
        'owner' => $row['owner_name'],
        'geojson' => json_decode($row['geojson'])
    ];
}

echo json_encode($land_parcels);
?>
