<?php
include 'db_connect.php';

// Fetch all land boundaries
$sql = "SELECT land_title, ST_AsGeoJSON(boundary) as geojson FROM land_records WHERE boundary IS NOT NULL";
$result = $conn->query($sql);

$kml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$kml .= "<kml xmlns=\"http://www.opengis.net/kml/2.2\">\n";
$kml .= "<Document>\n";
$kml .= "<name>Land Boundaries</name>\n";

while ($row = $result->fetch_assoc()) {
    $geojson = json_decode($row["geojson"], true);
    $coordinates = [];

    if ($geojson && isset($geojson["geometry"]["coordinates"])) {
        foreach ($geojson["geometry"]["coordinates"][0] as $coord) {
            $coordinates[] = "{$coord[0]},{$coord[1]},0";
        }
    }

    if (!empty($coordinates)) {
        $kml .= "<Placemark>\n";
        $kml .= "<name>{$row['land_title']}</name>\n";
        $kml .= "<Polygon>\n";
        $kml .= "<outerBoundaryIs>\n";
        $kml .= "<LinearRing>\n";
        $kml .= "<coordinates>\n";
        $kml .= implode(" ", $coordinates) . "\n";
        $kml .= "</coordinates>\n";
        $kml .= "</LinearRing>\n";
        $kml .= "</outerBoundaryIs>\n";
        $kml .= "</Polygon>\n";
        $kml .= "</Placemark>\n";
    }
}

$kml .= "</Document>\n";
$kml .= "</kml>\n";

header("Content-Type: application/vnd.google-earth.kml+xml");
header("Content-Disposition: attachment; filename=land_boundaries.kml");

echo $kml;
?>
