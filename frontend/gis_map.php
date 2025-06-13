<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../backend/db_connect.php';

// Fetch existing land boundaries
$sql = "SELECT id, title_number, ST_AsGeoJSON(boundary) as geojson FROM land_records WHERE boundary IS NOT NULL";
$result = $conn->query($sql);
$land_boundaries = [];

while ($row = $result->fetch_assoc()) {
    $land_boundaries[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time GIS Map - ArdhiYetu</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- Leaflet EasyButton -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2.4.0/src/easy-button.css">

    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .map-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 15px;
        }
        
        #map { 
            height: 600px; 
            width: 100%;
            border-radius: 6px;
            margin-top: 15px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .back-btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .back-btn:hover {
            background-color: #0056b3;
        }
        
        .page-title {
            margin: 0;
            color: #333;
        }
        
        @media (max-width: 768px) {
            #map {
                height: 400px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>

<div class="map-container">
    <div class="header">
        <h1 class="page-title">Land Parcel Map</h1>
        <a href="user_dashboard.php" class="back-btn">
            <i class="fa fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <div id="map"></div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2.4.0/src/easy-button.js"></script>

<script>
    // Initialize the Map with proper bounds
    var map = L.map('map').setView([-1.2921, 36.8219], 10);

    // Base Layers
    var streetLayer = L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoidG9ubnlheGVsIiwiYSI6ImNtYTFleDR1ejBvemUyanNmOG51cWoyaW4ifQ.Al4ZiGjKNj9XmdFvbqUj2g', {
        attribution: '© <a href="https://www.mapbox.com/">Mapbox</a> © OpenStreetMap contributors',
        id: 'mapbox/streets-v12',
        tileSize: 512,
        zoomOffset: -1
    }).addTo(map);

    var satelliteLayer = L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoidG9ubnlheGVsIiwiYSI6ImNtYTFleDR1ejBvemUyanNmOG51cWoyaW4ifQ.Al4ZiGjKNj9XmdFvbqUj2g', {
        attribution: '© <a href="https://www.mapbox.com/">Mapbox</a> © OpenStreetMap contributors',
        id: 'mapbox/satellite-streets-v12',
        tileSize: 512,
        zoomOffset: -1
    });

    // Layer Control
    var baseLayers = {
        "Street View": streetLayer,
        "Satellite View": satelliteLayer
    };
    L.control.layers(baseLayers).addTo(map);

    // Add Search Control
    var geocoder = L.Control.geocoder({
        position: 'topright',
        defaultMarkGeocode: true,
        collapsed: false,
        placeholder: 'Search address or location...',
        errorMessage: 'Location not found.',
        suggestTimeout: 300
    }).addTo(map);

    geocoder.on('markgeocode', function(e) {
        map.fitBounds(e.geocode.bbox, {padding: [50, 50], maxZoom: 16});
    });

    // Add Locate Control
    L.easyButton('fa-crosshairs', function(btn, map) {
        map.locate({setView: true, maxZoom: 14});
    }, 'Locate me', 'topright').addTo(map);

    // Location found handler
    var userLocationMarker;
    map.on('locationfound', function(e) {
        if (userLocationMarker) {
            map.removeLayer(userLocationMarker);
        }
        
        userLocationMarker = L.marker(e.latlng, {
            icon: L.divIcon({
                className: 'location-pin',
                html: '<i class="fa fa-map-marker" style="color:red; font-size:24px;"></i>',
                iconSize: [24, 24],
                iconAnchor: [12, 24]
            })
        }).addTo(map)
        .bindPopup("Your current location")
        .openPopup();
    });

    map.on('locationerror', function(e) {
        alert("Location access denied. Error: " + e.message);
    });

    // Load land boundaries
    var landBoundaries = <?= json_encode($land_boundaries); ?>;
    landBoundaries.forEach(function(land) {
        if (land.geojson) {
            try {
                var geoJson = JSON.parse(land.geojson);
                var layer = L.geoJSON(geoJson, {
                    style: {
                        color: '#3388ff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.2
                    }
                }).addTo(map);
                
                layer.bindPopup(`<b>Title Number:</b> ${land.title_number}`);
            } catch (e) {
                console.error("Error parsing GeoJSON for land", land.id, e);
            }
        }
    });

    // Load additional land data
    fetch("fetch_land_data.php")
        .then(response => response.json())
        .then(data => {
            data.forEach(land => {
                if (land.geojson) {
                    try {
                        var layer = L.geoJSON(land.geojson, {
                            style: {
                                color: '#ff7800',
                                weight: 2,
                                opacity: 1,
                                fillOpacity: 0.2
                            }
                        }).addTo(map);
                        
                        layer.bindPopup(`<b>${land.title}</b><br>Owner: ${land.owner}`);
                    } catch (e) {
                        console.error("Error parsing GeoJSON for land", land.id, e);
                    }
                }
            });
        })
        .catch(error => console.error("Error loading land parcels:", error));
</script>

</body>
</html>