<?php
include 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["id"])) {
    echo json_encode(["message" => "No boundary ID received"]);
    exit();
}

$id = $data["id"];

$stmt = $conn->prepare("UPDATE land_records SET boundary = NULL WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(["message" => "Boundary deleted successfully"]);
?>
