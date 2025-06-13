<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'surveyor') {
    header("Location: login.php?error=unauthorized");
    exit();
}

require_once '../backend/db_connect.php';
require_once '../backend/functions.php';

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get surveyor data
$user_id = $_SESSION['user_id'];
$surveyor = getSurveyorById($user_id, $conn);
if (!$surveyor) {
    header("Location: logout.php");
    exit();
}

$profile_pic = $surveyor['profile_picture'] ? '../uploads/' . $surveyor['profile_picture'] : '../assets/default-profile.png';

// Get dashboard statistics
$stats = getSurveyorStatistics($user_id, $conn);
$pending_surveys = getPendingSurveys($user_id, $conn);
$recent_updates = getRecentSurveyUpdates($user_id, $conn);
$unread_notifications = getUnreadNotificationCount($user_id, $conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surveyor Dashboard | ArdhiYetu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #343a40;
            --secondary-color: #2c3136;
            --accent-color: #007bff;
            --text-color: #adb5bd;
            --text-active: #fff;
            --transition-speed: 0.3s;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }
        
        /* Sidebar styling */
        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }
        
        #sidebar {
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            background: var(--primary-color);
            color: var(--text-color);
            transition: all var(--transition-speed);
            height: 100vh;
            position: fixed;
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        #sidebar.active {
            margin-left: calc(-1 * var(--sidebar-width));
        }
        
        #sidebar .sidebar-header {
            padding: 20px;
            background: var(--secondary-color);
            text-align: center;
            border-bottom: 1px solid #4b545c;
        }
        
        #sidebar ul.components {
            padding: 20px 0;
        }
        
        #sidebar ul li a {
            padding: 12px 20px;
            font-size: 1em;
            display: block;
            color: var(--text-color);
            text-decoration: none;
            transition: all var(--transition-speed);
            border-left: 3px solid transparent;
        }
        
        #sidebar ul li a:hover {
            color: var(--text-active);
            background: #495057;
            border-left: 3px solid var(--accent-color);
        }
        
        #sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        #sidebar ul li.active > a {
            color: var(--text-active);
            background: #495057;
            border-left: 3px solid var(--accent-color);
        }
        
        #sidebar ul li a[aria-expanded="true"] {
            color: var(--text-active);
            background: #495057;
        }
        
        #sidebar ul ul a {
            font-size: 0.9em;
            padding-left: 50px;
            background: var(--secondary-color);
        }
        
        /* Content area styling */
        #content {
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            transition: all var(--transition-speed);
            position: absolute;
            right: 0;
        }
        
        #content.active {
            width: 100%;
        }
        
        /* Responsive styling */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
            }
            #sidebar.active {
                margin-left: 0;
            }
            #content {
                width: 100%;
            }
            #content.active {
                width: calc(100% - var(--sidebar-width));
            }
        }
        
        /* Navbar styling */
        .navbar {
            padding: 15px 10px;
            background: #fff;
            border: none;
            border-radius: 0;
            margin-bottom: 0;
            box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        /* Notification badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.6em;
        }
        
        /* Card styling */
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .card-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            border-radius: 8px 8px 0 0 !important;
        }
        
        /* Status badges */
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        
        .badge-completed {
            background-color: #198754;
        }
        
        .badge-approved {
            background-color: #0d6efd;
        }
        
        .badge-rejected {
            background-color: #dc3545;
        }
        
        /* Due date warnings */
        .due-soon {
            background-color: #fff3cd !important;
        }
        
        .overdue {
            background-color: #f8d7da !important;
        }
        
        /* Chart container */
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        /* Table styling */
        .table-responsive {
            overflow-x: auto;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="active">
            <div class="sidebar-header">
                <img src="<?= htmlspecialchars($profile_pic) ?>" class="rounded-circle mb-3" width="80" height="80" style="object-fit: cover;">
                <h5><?= htmlspecialchars($_SESSION['user_name']) ?></h5>
                <p class="text-muted small mb-0">Surveyor</p>
            </div>

            <ul class="list-unstyled components">
                <li class="active">
                    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li>
                    <a href="surveys.php"><i class="bi bi-map"></i> My Surveys</a>
                </li>
                <li>
                    <a href="completed_surveys.php"><i class="bi bi-check-circle"></i> Completed Surveys</a>
                </li>
                <li>
                    <a href="reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
                </li>
                <li>
                    <a href="surveyor_profile.php"><i class="bi bi-person"></i> My Profile</a>
                </li>
                <li class="mt-4">
                    <a href="../backend/logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content" class="active">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-outline-secondary">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <div class="d-flex align-items-center ms-auto">
                        <div class="dropdown me-3">
                            <a href="#" class="dropdown-toggle position-relative" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bell fs-5"></i>
                                <?php if ($unread_notifications > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                                        <?= $unread_notifications ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <div id="notification-list">
                                    <!-- Notifications loaded via AJAX -->
                                    <li><a class="dropdown-item" href="#">No new notifications</a></li>
                                </div>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="notifications.php">View All</a></li>
                            </ul>
                        </div>
                        
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?= htmlspecialchars($profile_pic) ?>" width="32" height="32" class="rounded-circle me-2">
                                <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="surveyor_profile.php"><i class="bi bi-person me-2"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="../backend/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid py-4">
                <!-- Dashboard Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-start-primary border-3 h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-uppercase text-primary fw-bold small">Total Surveys</div>
                                        <div class="h3 mb-0 fw-bold"><?= number_format($stats['total_surveys']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-map fs-1 text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-start-success border-3 h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-uppercase text-success fw-bold small">Completed</div>
                                        <div class="h3 mb-0 fw-bold"><?= number_format($stats['completed_surveys']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-check-circle fs-1 text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-start-warning border-3 h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-uppercase text-warning fw-bold small">Pending</div>
                                        <div class="h3 mb-0 fw-bold"><?= number_format($stats['pending_surveys']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-hourglass-split fs-1 text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-start-info border-3 h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-uppercase text-info fw-bold small">Approval Rate</div>
                                        <div class="h3 mb-0 fw-bold">
                                            <?= $stats['total_surveys'] > 0 ? 
                                                round(($stats['approved_surveys'] / $stats['total_surveys']) * 100) : 0 ?>%
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-graph-up fs-1 text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Surveys Section -->
                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Pending Surveys</h5>
                                <a href="surveys.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="pendingSurveysTable">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Location</th>
                                                <th>Due Date</th>
                                                <th>Days Left</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_surveys as $survey): 
                                                $due_date = new DateTime($survey['due_date']);
                                                $today = new DateTime();
                                                $interval = $today->diff($due_date);
                                                $days_left = $interval->format('%r%a');
                                                $row_class = '';
                                                
                                                if ($days_left < 0) {
                                                    $row_class = 'overdue';
                                                    $status_text = 'Overdue by ' . abs($days_left) . ' days';
                                                } elseif ($days_left <= 3) {
                                                    $row_class = 'due-soon';
                                                    $status_text = 'Due in ' . $days_left . ' days';
                                                } else {
                                                    $status_text = 'Due in ' . $days_left . ' days';
                                                }
                                            ?>
                                            <tr class="<?= $row_class ?>">
                                                <td><?= htmlspecialchars($survey['land_title']) ?></td>
                                                <td><?= htmlspecialchars($survey['location']) ?></td>
                                                <td><?= date('M j, Y', strtotime($survey['due_date'])) ?></td>
                                                <td><?= $status_text ?></td>
                                                <td>
                                                    <a href="survey_details.php?id=<?= $survey['id'] ?>" class="btn btn-sm btn-primary" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="submit_survey.php?id=<?= $survey['id'] ?>" class="btn btn-sm btn-success" title="Submit">
                                                        <i class="bi bi-send-check"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Updates</h5>
                                <a href="completed_surveys.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php foreach ($recent_updates as $update): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($update['land_title']) ?></h6>
                                            <small class="text-muted"><?= date('M j', strtotime($update['updated_at'])) ?></small>
                                        </div>
                                        <p class="mb-1"><?= htmlspecialchars($update['location']) ?></p>
                                        <small>
                                            Status: 
                                            <span class="badge bg-<?= $update['status'] == 'approved' ? 'approved' : 'rejected' ?>">
                                                <?= ucfirst($update['status']) ?>
                                            </span>
                                        </small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Survey Progress Chart -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Survey Progress</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="surveyProgressChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="bg-light py-3 mt-4">
                <div class="container text-center">
                    <span class="text-muted">© <?= date('Y') ?> ArdhiYetu. All rights reserved.</span>
                </div>
            </footer>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {
        // Toggle sidebar
        $('#sidebarCollapse').on('click', function() {
            $('#sidebar, #content').toggleClass('active');
        });

        // Initialize DataTables
        $('#pendingSurveysTable').DataTable({
            responsive: true,
            pageLength: 5,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            }
        });

        // Load notifications
        loadNotifications();

        // Initialize chart
        initChart();
    });

    function initChart() {
        const ctx = document.getElementById('surveyProgressChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Pending', 'Approved', 'Rejected'],
                datasets: [{
                    data: [
                        <?= $stats['completed_surveys'] ?>,
                        <?= $stats['pending_surveys'] ?>,
                        <?= $stats['approved_surveys'] ?>,
                        <?= $stats['rejected_surveys'] ?>
                    ],
                    backgroundColor: [
                        'rgba(25, 135, 84, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(13, 110, 253, 0.7)',
                        'rgba(220, 53, 69, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function loadNotifications() {
        $.ajax({
            url: '../backend/fetch_notifications.php',
            method: 'GET',
            data: { 
                user_id: <?= $user_id ?>,
                unread_only: true, 
                limit: 5 
            },
            success: function(response) {
                $('#notification-list').html(response);
            },
            error: function(xhr, status, error) {
                console.error("Error loading notifications:", error);
            }
        });
    }

    function getSurveyorById($user_id, $conn) {
    $sql = "SELECT * FROM users WHERE id = ? AND role = 'surveyor'";
    $result = safeQuery($conn, $sql, [$user_id]);
    return $result ? $result->fetch_assoc() : null;
}

function getSurveyorStatistics($user_id, $conn) {
    $stats = [];
    
    // Total surveys
    $result = safeQuery($conn, "SELECT COUNT(*) as total FROM survey_tasks WHERE surveyor_id = ?", [$user_id]);
    $stats['total_surveys'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Completed surveys
    $result = safeQuery($conn, "SELECT COUNT(*) as total FROM survey_tasks WHERE surveyor_id = ? AND status = 'completed'", [$user_id]);
    $stats['completed_surveys'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Pending surveys
    $result = safeQuery($conn, "SELECT COUNT(*) as total FROM survey_tasks WHERE surveyor_id = ? AND status = 'pending'", [$user_id]);
    $stats['pending_surveys'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Approved surveys
    $result = safeQuery($conn, "SELECT COUNT(*) as total FROM survey_tasks WHERE surveyor_id = ? AND status = 'approved'", [$user_id]);
    $stats['approved_surveys'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Rejected surveys
    $result = safeQuery($conn, "SELECT COUNT(*) as total FROM survey_tasks WHERE surveyor_id = ? AND status = 'rejected'", [$user_id]);
    $stats['rejected_surveys'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    return $stats;
}

function getPendingSurveys($user_id, $conn) {
    $surveys = [];
    $sql = "SELECT id, land_title, location, due_date FROM survey_tasks 
            WHERE surveyor_id = ? AND status = 'pending' 
            ORDER BY due_date ASC";
    $result = safeQuery($conn, $sql, [$user_id]);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $surveys[] = $row;
        }
    }
    return $surveys;
}

function getRecentSurveyUpdates($user_id, $conn, $limit = 5) {
    $updates = [];
    $sql = "SELECT land_title, location, status, updated_at 
            FROM survey_tasks 
            WHERE surveyor_id = ? AND status IN ('approved', 'rejected') 
            ORDER BY updated_at DESC 
            LIMIT ?";
    $result = safeQuery($conn, $sql, [$user_id, $limit]);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $updates[] = $row;
        }
    }
    return $updates;
}

    // Refresh notifications every 30 seconds
    setInterval(loadNotifications, 30000);
    </script>
</body>
</html>