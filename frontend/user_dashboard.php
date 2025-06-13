<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php?error=Unauthorized access");
    exit();
}

include '../backend/db_connect.php';

$user_id = $_SESSION['user_id'];

// 1. Check for user profile columns
$user_columns = $conn->query("SHOW COLUMNS FROM users");
$user_fields = [];
while ($column = $user_columns->fetch_assoc()) {
    $user_fields[] = $column['Field'];
}

$select_fields = ['full_name', 'email'];
if (in_array('profile_picture', $user_fields)) $select_fields[] = 'profile_picture';
if (in_array('last_login', $user_fields)) $select_fields[] = 'last_login';

// Get user profile data
$user_query = "SELECT " . implode(', ', $select_fields) . " FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
if (!$stmt) die("Database error: " . $conn->error);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile_pic = (!empty($user['profile_picture'])) 
    ? '../uploads/' . $user['profile_picture'] 
    : '../assets/default-profile.png';

// 2. Get land statistics
$stats = [
    'land_count' => 0,
    'doc_count' => 0,
    'pending_transfers' => 0
];

// Land count
$land_count_query = "SELECT COUNT(*) as count FROM land_records WHERE user_id = ?";
$stmt = $conn->prepare($land_count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['land_count'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
$stmt->close();

// Document count
$doc_count_query = "SELECT COUNT(*) as count FROM land_documents 
                   WHERE land_id IN (SELECT id FROM land_records WHERE user_id = ?)";
$stmt = $conn->prepare($doc_count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['doc_count'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
$stmt->close();

// 3. Handle transfer requests if table exists
$transfer_requests_table = $conn->query("SHOW TABLES LIKE 'transfer_requests'")->num_rows > 0;
if ($transfer_requests_table) {
    $transfer_columns = $conn->query("SHOW COLUMNS FROM transfer_requests");
    $transfer_fields = [];
    while ($column = $transfer_columns->fetch_assoc()) {
        $transfer_fields[] = $column['Field'];
    }
    
    // Check for different possible user ID column names
    $user_id_columns = ['requestor_id', 'from_user_id', 'user_id'];
    $user_id_column = null;
    
    foreach ($user_id_columns as $col) {
        if (in_array($col, $transfer_fields)) {
            $user_id_column = $col;
            break;
        }
    }
    
    if ($user_id_column) {
        $transfer_query = "SELECT COUNT(*) as count FROM transfer_requests 
                          WHERE $user_id_column = ? AND status = 'pending'";
        $stmt = $conn->prepare($transfer_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['pending_transfers'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
        $stmt->close();
    }
}

// 4. Get recent activities if table exists
$activities = [];
$activities_table = $conn->query("SHOW TABLES LIKE 'user_activities'")->num_rows > 0;
if ($activities_table) {
    $activities_query = "SELECT activity_type, description, created_at 
                        FROM user_activities 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 5";
    $stmt = $conn->prepare($activities_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $activities = $stmt->get_result();
    $stmt->close();
}

// 5. Get recent land records
$recent_lands_query = "SELECT id, title_number, location, approximate_area, status 
                      FROM land_records 
                      WHERE user_id = ? 
                      ORDER BY created_at DESC 
                      LIMIT 5";
$stmt = $conn->prepare($recent_lands_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_lands = $stmt->get_result();
$stmt->close();

// 6. Get land status distribution
$status_distribution_query = "SELECT status, COUNT(*) as count 
                            FROM land_records 
                            WHERE user_id = ? 
                            GROUP BY status";
$stmt = $conn->prepare($status_distribution_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$status_distribution = $stmt->get_result();
$stmt->close();

// 7. Get upcoming deadlines if table exists
$deadlines = [];
$deadlines_table = $conn->query("SHOW TABLES LIKE 'user_deadlines'")->num_rows > 0;
if ($deadlines_table) {
    $deadlines_query = "SELECT title, deadline_date, DATEDIFF(deadline_date, CURDATE()) as days_remaining
                       FROM user_deadlines
                       WHERE user_id = ? AND deadline_date >= CURDATE()
                       ORDER BY deadline_date ASC
                       LIMIT 3";
    $stmt = $conn->prepare($deadlines_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $deadlines = $stmt->get_result();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - ArdhiYetu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        .sidebar {
            background: var(--secondary-color);
            color: white;
            padding: 20px 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 15px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--primary-color);
        }
        
        .sidebar-menu li a i {
            width: 24px;
            text-align: center;
            margin-right: 12px;
            font-size: 1.1rem;
        }
        
        .main-content {
            padding: 25px;
            overflow-y: auto;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-card .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .badge-pending {
            background-color: var(--warning-color);
            color: var(--dark-color);
        }
        
        .badge-approved {
            background-color: var(--success-color);
            color: white;
        }
        
        .badge-rejected {
            background-color: var(--danger-color);
            color: white;
        }
        
        .badge-active {
            background-color: var(--success-color);
            color: white;
        }
        
        .badge-inactive {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .deadline-urgent {
            color: var(--danger-color);
            font-weight: bold;
        }
        
        .deadline-warning {
            color: var(--warning-color);
            font-weight: bold;
        }
        
        @media (max-width: 992px) {
            .dashboard-container {
                grid-template-columns: 80px 1fr;
            }
            
            .sidebar-menu li span {
                display: none;
            }
            
            .sidebar-menu li a {
                justify-content: center;
                padding: 15px 0;
            }
            
            .sidebar-menu li a i {
                margin-right: 0;
                font-size: 1.3rem;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                height: auto;
                position: static;
                display: flex;
                overflow-x: auto;
                padding: 10px;
            }
            
            .sidebar-header {
                display: none;
            }
            
            .sidebar-menu {
                display: flex;
                width: 100%;
            }
            
            .sidebar-menu li {
                flex: 1;
                text-align: center;
            }
            
            .sidebar-menu li a {
                flex-direction: column;
                padding: 10px 5px;
                font-size: 0.7rem;
            }
            
            .sidebar-menu li a i {
                margin-right: 0;
                margin-bottom: 5px;
                font-size: 1.2rem;
            }
            
            .sidebar-menu li span {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="text-center mb-3">
                    <img src="<?= $profile_pic ?>" class="rounded-circle" width="80" height="80" style="object-fit: cover; border: 3px solid var(--primary-color);">
                    <h5 class="mt-2 mb-0"><?= htmlspecialchars($_SESSION['user_name']) ?></h5>
                    <small class="text-muted">User</small>
                </div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="add_land.php"><i class="fas fa-plus-circle"></i> <span>Register Land</span></a></li>
                <li><a href="view_land.php"><i class="fas fa-folder-open"></i> <span>Land Records</span></a></li>
                <li><a href="javascript:void(0)" onclick="printLandRecords()"><i class="fas fa-print"></i> <span>Print Land Record</span></a></li>
                <li><a href="gis_map.php"><i class="fas fa-map-marked-alt"></i> <span>Interactive Map</span></a></li>
                <li><a href="view_documents.php"><i class="fas fa-file-alt"></i> <span>Documents</span></a></li>
                <li><a href="land_transfer_request.php"><i class="fas fa-exchange-alt"></i> <span>Transfer Requests</span></a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
                <li><a href="user_profile.php"><i class="fas fa-user-cog"></i> <span>Profile Settings</span></a></li>
                <li><a href="help.php"><i class="fas fa-question-circle"></i> <span>Help Center</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Dashboard Overview</h2>
                <div class="text-muted">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <?= date('l, F j, Y') ?>
                </div>
            </div>
            
            <!-- Welcome Banner -->
            <div class="dashboard-card bg-primary text-white mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3>Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h3>
                        <?php if (in_array('last_login', $user_fields) && !empty($user['last_login'])): ?>
                            <p class="mb-0">Last login: <?= date('M j, Y \a\t g:i a', strtotime($user['last_login'])) ?></p>
                        <?php else: ?>
                            <p class="mb-0">Welcome to your dashboard!</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-center">
                        <img src="../assets/dashboard-illustration.svg" alt="Dashboard Illustration" style="max-height: 120px;">
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-md-4">
                    <div class="dashboard-card stat-card">
                        <div class="stat-value"><?= $stats['land_count'] ?></div>
                        <div class="stat-label">Registered Lands</div>
                        <div class="mt-2">
                            <a href="view_land.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card stat-card" style="border-left-color: var(--success-color);">
                        <div class="stat-value" style="color: var(--success-color);"><?= $stats['doc_count'] ?></div>
                        <div class="stat-label">Uploaded Documents</div>
                        <div class="mt-2">
                            <a href="view_documents.php" class="btn btn-sm btn-outline-success">Manage</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card stat-card" style="border-left-color: var(--warning-color);">
                        <div class="stat-value" style="color: var(--warning-color);"><?= $stats['pending_transfers'] ?></div>
                        <div class="stat-label">Pending Transfers</div>
                        <div class="mt-2">
                            <?php if ($transfer_requests_table): ?>
                                <a href="land_transfer_request.php" class="btn btn-sm btn-outline-warning">Review</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-secondary" disabled>Feature Not Available</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="row mt-4">
                <div class="col-md-8">
                    <div class="dashboard-card">
                        <h5>Land Status Distribution</h5>
                        <div id="statusChart" style="min-height: 300px;">
                            <?php if ($status_distribution->num_rows > 0): ?>
                                <!-- Chart will be rendered by JavaScript -->
                            <?php else: ?>
                                <div class="alert alert-info">No status distribution data available</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h5>Upcoming Deadlines</h5>
                        <?php if ($deadlines_table && $deadlines->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while ($deadline = $deadlines->fetch_assoc()): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <strong><?= htmlspecialchars($deadline['title']) ?></strong>
                                            <span class="<?= $deadline['days_remaining'] <= 3 ? 'deadline-urgent' : ($deadline['days_remaining'] <= 7 ? 'deadline-warning' : '') ?>">
                                                <?= $deadline['days_remaining'] ?> days
                                            </span>
                                        </div>
                                        <small class="text-muted">Due: <?= date('M j, Y', strtotime($deadline['deadline_date'])) ?></small>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No upcoming deadlines</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity and Land Records -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Recent Activity</h5>
                            <?php if ($activities_table): ?>
                                <a href="activity_log.php" class="btn btn-sm btn-outline-secondary">View All</a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($activities_table && $activities->num_rows > 0): ?>
                            <div class="activity-feed">
                                <?php while ($activity = $activities->fetch_assoc()): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <?php 
                                                $icon = 'fa-bell';
                                                if (strpos($activity['activity_type'], 'transfer') !== false) $icon = 'fa-exchange-alt';
                                                if (strpos($activity['activity_type'], 'document') !== false) $icon = 'fa-file-alt';
                                                if (strpos($activity['activity_type'], 'land') !== false) $icon = 'fa-map-marked';
                                            ?>
                                            <i class="fas <?= $icon ?> text-primary"></i>
                                        </div>
                                        <div>
                                            <p class="mb-1"><?= htmlspecialchars($activity['description']) ?></p>
                                            <small class="text-muted"><?= date('M j, g:i a', strtotime($activity['created_at'])) ?></small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No recent activities found</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Recent Land Records</h5>
                            <a href="view_land.php" class="btn btn-sm btn-outline-secondary">View All</a>
                        </div>
                        
                        <?php if ($recent_lands->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($land = $recent_lands->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <a href="land_details.php?id=<?= $land['id'] ?>">
                                                        <?= htmlspecialchars($land['title_number']) ?>
                                                    </a>
                                                </td>
                                                <td><?= htmlspecialchars($land['location']) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= strtolower($land['status']) ?>">
                                                        <?= $land['status'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No land records found</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="dashboard-card mt-4">
                <h5 class="mb-3">Quick Actions</h5>
                <div class="row">
                    <div class="col-md-3 col-6 mb-3">
                        <a href="add_land.php" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-plus-circle me-2"></i> Register Land
                        </a>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <a href="upload_document.php" class="btn btn-success w-100 py-2">
                            <i class="fas fa-upload me-2"></i> Upload Document
                        </a>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <a href="land_transfer_request.php" class="btn btn-warning w-100 py-2">
                            <i class="fas fa-exchange-alt me-2"></i> Transfer Request
                        </a>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <a href="help.php" class="btn btn-info w-100 py-2">
                            <i class="fas fa-question-circle me-2"></i> Get Help
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    
    <script>
        // Initialize Status Distribution Chart
        <?php if ($status_distribution->num_rows > 0): ?>
        <?php
        $status_labels = [];
        $status_counts = [];
        $status_colors = [];
        
        $status_distribution->data_seek(0);
        while ($row = $status_distribution->fetch_assoc()) {
            $status_labels[] = $row['status'];
            $status_counts[] = $row['count'];
            
            switch(strtolower($row['status'])) {
                case 'active': $status_colors[] = '#28a745'; break;
                case 'pending': $status_colors[] = '#ffc107'; break;
                case 'inactive': $status_colors[] = '#6c757d'; break;
                case 'disputed': $status_colors[] = '#dc3545'; break;
                default: $status_colors[] = '#3498db';
            }
        }
        ?>
        
        var statusChartOptions = {
            series: [{
                name: 'Lands',
                data: <?= json_encode($status_counts) ?>
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            colors: <?= json_encode($status_colors) ?>,
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: true,
                    distributed: true
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: <?= json_encode($status_labels) ?>,
                title: {
                    text: 'Number of Lands'
                }
            },
            yaxis: {
                title: {
                    text: 'Status'
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + " lands";
                    }
                }
            }
        };

        var statusChart = new ApexCharts(document.querySelector("#statusChart"), statusChartOptions);
        statusChart.render();
        <?php endif; ?>

        // Print functionality
        function printLandRecords() {
            window.open('print_land_records.php', '_blank');
        }
    </script>
</body>
</html>