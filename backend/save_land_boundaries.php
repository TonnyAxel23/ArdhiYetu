<?php
include 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["boundaries"])) {
    echo json_encode(["message" => "No boundaries received"]);
    exit();
}

foreach ($data["boundaries"] as $boundary) {
    $geojson = json_encode($boundary);
    $stmt = $conn->prepare("INSERT INTO land_records (land_title, boundary) VALUES (?, ST_GeomFromGeoJSON(?))");
    $land_title = "Custom Land Area";
    $stmt->bind_param("ss", $land_title, $geojson);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
echo json_encode(["message" => "Boundaries saved successfully"]);
?>
