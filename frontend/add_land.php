<?php
session_start();
include '../backend/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$form_data = [
    'title_number' => '',
    'approximate_area' => '',
    'registry_map_sheet' => '',
    'location' => '',
    'latitude' => '',
    'longitude' => ''
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $form_data = [
        'title_number' => trim($_POST['title_number']),
        'approximate_area' => $_POST['approximate_area'],
        'registry_map_sheet' => trim($_POST['registry_map_sheet']),
        'location' => trim($_POST['location']),
        'latitude' => $_POST['latitude'],
        'longitude' => $_POST['longitude']
    ];

    // Validate inputs
    $errors = [];
    foreach ($form_data as $key => $value) {
        if (empty($value)) {
            $errors[] = ucfirst(str_replace('_', ' ', $key)) . " is required";
        }
    }

    if (!is_numeric($form_data['approximate_area']) || $form_data['approximate_area'] <= 0) {
        $errors[] = "Approximate area must be a positive number";
    }

    if (!empty($errors)) {
        $message = '<div class="alert alert-danger"><ul>';
        foreach ($errors as $error) {
            $message .= "<li>$error</li>";
        }
        $message .= '</ul></div>';
    } else {
        // Check for duplicate title number
        $check_sql = "SELECT id FROM land_records WHERE title_number = ? AND user_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("si", $form_data['title_number'], $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = '<div class="alert alert-danger">A land record with this title number already exists for your account.</div>';
        } else {
            // Insert data into database
            $sql = "INSERT INTO land_records (user_id, title_number, approximate_area, registry_map_sheet, latitude, longitude, location) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isdsdds", $user_id, $form_data['title_number'], $form_data['approximate_area'], 
                $form_data['registry_map_sheet'], $form_data['latitude'], $form_data['longitude'], $form_data['location']);

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Land record added successfully! <a href="view_land.php?id='.$stmt->insert_id.'" class="alert-link">View record</a></div>';
                // Reset form data except location
                $form_data = array_merge(array_fill_keys(array_keys($form_data), ''), ['location' => $form_data['location']]);
            } else {
                $message = '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Land Record - ArdhiYetu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-container {
            max-width: 1200px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .form-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid var(--secondary-color);
        }
        
        #map {
            height: 500px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #ddd;
        }
        
        .search-container {
            position: relative;
            margin-bottom: 15px;
        }
        
        .search-container i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: #6c757d;
        }
        
        .search-container input {
            padding-left: 40px;
            border-radius: 50px;
        }
        
        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
                margin: 15px;
            }
            
            #map {
                height: 350px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <a href="user_dashboard.php" class="btn btn-outline-secondary mb-3">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <h2 class="text-center">
                <i class="fas fa-plus-circle text-primary"></i> Add New Land Record
            </h2>
        </div>

        <?= $message ?>

        <form method="POST" action="add_land.php" id="landForm">
            <div class="form-section">
                <h4><i class="fas fa-info-circle"></i> Land Details</h4>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Title Number *</label>
                        <input type="text" name="title_number" class="form-control" 
                               value="<?= htmlspecialchars($form_data['title_number']) ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Approximate Area (Acres) *</label>
                        <input type="number" step="0.01" name="approximate_area" class="form-control" 
                               value="<?= htmlspecialchars($form_data['approximate_area']) ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Registry Map Sheet Number *</label>
                        <input type="text" name="registry_map_sheet" class="form-control" 
                               value="<?= htmlspecialchars($form_data['registry_map_sheet']) ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Location *</label>
                        <input type="text" name="location" class="form-control" 
                               value="<?= htmlspecialchars($form_data['location']) ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4><i class="fas fa-map-marked-alt"></i> Map Location</h4>
                
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="search-box" class="form-control" placeholder="Search location...">
                </div>
                
                <div id="map"></div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Latitude</label>
                        <input type="text" id="latitude" name="latitude" class="form-control" 
                               value="<?= htmlspecialchars($form_data['latitude']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Longitude</label>
                        <input type="text" id="longitude" name="longitude" class="form-control" 
                               value="<?= htmlspecialchars($form_data['longitude']) ?>" readonly>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="fas fa-save me-2"></i> Save Land Record
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script>
        // Initialize map
        var map = L.map('map').setView([-1.2921, 36.8219], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var marker;
        var geocoder = L.Control.geocoder({
            defaultMarkGeocode: true,
            collapsed: false,
            placeholder: 'Search location...'
        }).on('markgeocode', function(e) {
            var latlng = e.geocode.center;
            updateLocation(latlng, e.geocode.name);
        }).addTo(map);

        function updateLocation(latlng, name) {
            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker(latlng).addTo(map);
            map.setView(latlng, 16);
            
            document.getElementById('latitude').value = latlng.lat.toFixed(6);
            document.getElementById('longitude').value = latlng.lng.toFixed(6);
            
            if (name) {
                document.querySelector('input[name="location"]').value = name;
            }
        }

        map.on('click', function(e) {
            updateLocation(e.latlng);
            
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}`)
                .then(response => response.json())
                .then(data => {
                    if (data.address) {
                        let location = [
                            data.address.road,
                            data.address.village,
                            data.address.town,
                            data.address.city,
                            data.address.county
                        ].filter(Boolean).join(', ');
                        
                        if (location) {
                            document.querySelector('input[name="location"]').value = location;
                        }
                    }
                });
        });

        document.getElementById('search-box').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                geocoder.options.geocoder.geocode(this.value, function(results) {
                    if (results.length > 0) {
                        updateLocation(results[0].center, results[0].name);
                    }
                });
            }
        });

        document.getElementById('landForm').addEventListener('submit', function(e) {
            if (!document.getElementById('latitude').value) {
                e.preventDefault();
                alert('Please select a location on the map');
            }
        });
    </script>
</body>
</html>