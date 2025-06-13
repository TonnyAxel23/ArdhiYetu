<?php
include 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["boundaries"])) {
    echo json_encode(["message" => "No boundaries received"]);
    exit();
}

foreach ($data["boundaries"] as $boundary) {
    $geojson = json_encode($boundary["geometry"]);
    $id = $boundary["id"];

    $stmt = $conn->prepare("UPDATE land_records SET boundary = ST_GeomFromGeoJSON(?) WHERE id = ?");
    $stmt->bind_param("si", $geojson, $id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
echo json_encode(["message" => "Boundaries updated successfully"]);
?>
