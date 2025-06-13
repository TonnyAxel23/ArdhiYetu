<?php
include 'db_connect.php';

$location = $_POST['location'] ?? '';
$owner = $_POST['owner'] ?? '';
$size = $_POST['size'] ?? '';

$query = "SELECT owner_name, size, location, registration_number FROM land_records WHERE 1=1";

$params = [];
$types = "";

// Apply filters dynamically
if (!empty($location)) {
    $query .= " AND location = ?";
    $params[] = $location;
    $types .= "s";
}
if (!empty($owner)) {
    $query .= " AND owner_name = ?";
    $params[] = $owner;
    $types .= "s";
}
if (!empty($size)) {
    $query .= " AND size >= ?";
    $params[] = $size;
    $types .= "d";
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$output = "";
while ($row = $result->fetch_assoc()) {
    $output .= "<tr>
                    <td>{$row['owner_name']}</td>
                    <td>{$row['size']} acres</td>
                    <td>{$row['location']}</td>
                    <td>{$row['registration_number']}</td>
                </tr>";
}

echo $output ?: "<tr><td colspan='4' class='text-center'>No records found</td></tr>";

$stmt->close();
$conn->close();
?>
