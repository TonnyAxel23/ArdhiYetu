<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit();
}

require_once '../backend/db_connect.php';
require_once '../backend/functions.php';

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get dashboard data
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id, $conn);
if (!$user) {
    header("Location: logout.php");
    exit();
}

$profile_pic = $user['profile_picture'] ? '../uploads/' . $user['profile_picture'] : '../assets/default-profile.png';
$stats = getDashboardStatistics($conn);
$recent_users = getRecentUsers($conn, 5);
$land_distribution = getLandDistribution($conn);
$transfers = getPendingTransfers($conn);
$lands_for_survey = getLandsForSurvey($conn);
$surveyors = getSurveyors($conn);
$unread_notifications = getUnreadNotificationCount($user_id, $conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ArdhiYetu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Integrated CSS */
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
        
        /* Dropdown menu styling */
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .dropdown-item {
            padding: 8px 20px;
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
        
        /* Chart container */
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        /* Table styling */
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            font-size: 0.9em;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
        
        /* Custom border colors */
        .border-start-primary {
            border-left: 3px solid #4e73df !important;
        }
        
        .border-start-success {
            border-left: 3px solid #1cc88a !important;
        }
        
        .border-start-info {
            border-left: 3px solid #36b9cc !important;
        }
        
        .border-start-warning {
            border-left: 3px solid #f6c23e !important;
        }
        
        /* Badge colors */
        .badge-primary {
            background-color: #4e73df;
        }
        
        .badge-success {
            background-color: #1cc88a;
        }
        
        .badge-info {
            background-color: #36b9cc;
        }
        
        .badge-warning {
            background-color: #f6c23e;
        }
        
        /* Button hover effects */
        .btn-outline-primary:hover {
            color: #fff;
        }
        
        /* Footer styling */
        footer {
            background-color: #f8f9fa;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        /* Spinner animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .spinner-border {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            vertical-align: text-bottom;
            border: 0.2em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spin 0.75s linear infinite;
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
                <p class="text-muted small mb-0">Administrator</p>
            </div>

            <ul class="list-unstyled components">
                <li class="active">
                    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li>
                    <a href="#userSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="bi bi-people"></i> User Management
                    </a>
                    <ul class="collapse list-unstyled" id="userSubmenu">
                        <li><a href="manage_users.php"><i class="bi bi-list-ul"></i> All Users</a></li>
                        <li><a href="add_user.php"><i class="bi bi-person-plus"></i> Add New User</a></li>
                        <li><a href="user_roles.php"><i class="bi bi-shield-lock"></i> Role Management</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#landSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="bi bi-map"></i> Land Records
                    </a>
                    <ul class="collapse list-unstyled" id="landSubmenu">
                        <li><a href="manage_land.php"><i class="bi bi-list-ul"></i> All Land Records</a></li>
                        <li><a href="add_land.php"><i class="bi bi-plus-square"></i> Add New Record</a></li>
                        <li><a href="land_categories.php"><i class="bi bi-tags"></i> Land Categories</a></li>
                    </ul>
                </li>
                <li>
                    <a href="transfers.php"><i class="bi bi-arrow-left-right"></i> Ownership Transfers</a>
                </li>
                <li>
                    <a href="reports.php"><i class="bi bi-bar-chart"></i> Reports & Analytics</a>
                </li>
                <li>
                    <a href="#systemSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="bi bi-gear"></i> System Settings
                    </a>
                    <ul class="collapse list-unstyled" id="systemSubmenu">
                        <li><a href="settings.php"><i class="bi bi-sliders"></i> Application Settings</a></li>
                        <li><a href="audit_logs.php"><i class="bi bi-journal-text"></i> Audit Logs</a></li>
                        <li><a href="backup.php"><i class="bi bi-database"></i> Database Backup</a></li>
                    </ul>
                </li>
                <li class="mt-4">
                    <a href="../frontend/logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
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
                                <li><a class="dropdown-item text-center" href="notifications.php">View All Notifications</a></li>
                            </ul>
                        </div>
                        
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?= htmlspecialchars($profile_pic) ?>" width="32" height="32" class="rounded-circle me-2">
                                <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i> My Profile</a></li>
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
                                        <div class="text-uppercase text-primary fw-bold small">Total Lands</div>
                                        <div class="h3 mb-0 fw-bold"><?= number_format($stats['land_count']) ?></div>
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
                                        <div class="text-uppercase text-success fw-bold small">Total Users</div>
                                        <div class="h3 mb-0 fw-bold"><?= number_format($stats['user_count']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people fs-1 text-success"></i>
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
                                        <div class="text-uppercase text-info fw-bold small">Pending Transfers</div>
                                        <div class="h3 mb-0 fw-bold"><?= number_format($stats['pending_transfers']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-arrow-left-right fs-1 text-info"></i>
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
                                        <div class="text-uppercase text-warning fw-bold small">Surveys Needed</div>
                                        <div class="h3 mb-0 fw-bold"><?= number_format($stats['pending_surveys']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-compass fs-1 text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Land Distribution by Location</h5>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-filter"></i> Filter
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#">This Month</a></li>
                                        <li><a class="dropdown-item" href="#">Last Month</a></li>
                                        <li><a class="dropdown-item" href="#">This Year</a></li>
                                        <li><a class="dropdown-item" href="#">All Time</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="landDistributionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">User Roles Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="height: 250px;">
                                    <canvas id="userRolesChart"></canvas>
                                </div>
                                <div class="mt-3 text-center" id="role-legend"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Section -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Users</h5>
                                <a href="manage_users.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="recentUsersTable">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_users as $user): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['full_name']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= getRoleBadgeClass($user['role']) ?>">
                                                        <?= htmlspecialchars($user['role']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="view_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                                        <i class="bi bi-pencil"></i>
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
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Pending Ownership Transfers</h5>
                                <a href="transfers.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="pendingTransfersTable">
                                        <thead>
                                            <tr>
                                                <th>Land Title</th>
                                                <th>Current Owner</th>
                                                <th>New Owner</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transfers as $transfer): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($transfer['title_number']) ?></td>
                                                <td><?= htmlspecialchars($transfer['current_owner']) ?></td>
                                                <td><?= htmlspecialchars($transfer['new_owner']) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-success approve-transfer" data-id="<?= $transfer['id'] ?>" title="Approve">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger reject-transfer" data-id="<?= $transfer['id'] ?>" title="Reject">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                    <a href="view_transfer.php?id=<?= $transfer['id'] ?>" class="btn btn-sm btn-info" title="Details">
                                                        <i class="bi bi-info-circle"></i>
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
                </div>
                
                <!-- Assign Surveyor Section -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Assign Surveyor</h5>
                            </div>
                            <div class="card-body">
                                <form id="assignSurveyorForm">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="land_id" class="form-label">Select Land</label>
                                            <select class="form-select" id="land_id" name="land_id" required>
                                                <option value="">Choose land...</option>
                                                <?php foreach ($lands_for_survey as $land): ?>
                                                <option value="<?= $land['id'] ?>">
                                                    <?= htmlspecialchars($land['display_title'] ?? $land['title_number']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="surveyor_id" class="form-label">Select Surveyor</label>
                                            <select class="form-select" id="surveyor_id" name="surveyor_id" required>
                                                <option value="">Choose surveyor...</option>
                                                <?php foreach ($surveyors as $surveyor): ?>
                                                <option value="<?= $surveyor['id'] ?>">
                                                    <?= htmlspecialchars($surveyor['full_name']) ?> (<?= htmlspecialchars($surveyor['email']) ?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="survey_notes" class="form-label">Additional Notes</label>
                                        <textarea class="form-control" id="survey_notes" name="survey_notes" rows="2"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary" id="assignSurveyorBtn">
                                        <span id="assignSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                        Assign Surveyor
                                    </button>
                                </form>
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
    <script>
    $(document).ready(function() {
        // Toggle sidebar
        $('#sidebarCollapse').on('click', function() {
            $('#sidebar, #content').toggleClass('active');
            $('.wrapper').toggleClass('menu-displayed');
        });

        // Initialize DataTables
        $('#recentUsersTable, #pendingTransfersTable').DataTable({
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

        // Process transfer actions
        $('.approve-transfer').on('click', function() {
            processTransfer($(this).data('id'), 'approve');
        });

        $('.reject-transfer').on('click', function() {
            processTransfer($(this).data('id'), 'reject');
        });

        // Assign surveyor form submission
        $('#assignSurveyorForm').on('submit', function(e) {
            e.preventDefault();
            assignSurveyor();
        });

        // Initialize charts
        initCharts();
    });

    function initCharts() {
        // Land Distribution Chart
        const landCtx = document.getElementById('landDistributionChart').getContext('2d');
        const landChart = new Chart(landCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($land_distribution['locations']) ?>,
                datasets: [{
                    label: 'Number of Lands',
                    data: <?= json_encode($land_distribution['counts']) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // User Roles Chart
        const rolesCtx = document.getElementById('userRolesChart').getContext('2d');
        const rolesChart = new Chart(rolesCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($stats['role_labels']) ?>,
                datasets: [{
                    data: <?= json_encode($stats['role_counts']) ?>,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(153, 102, 255, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '70%'
            }
        });

        // Generate legend for roles chart
        const roleLegend = document.getElementById('role-legend');
        <?php foreach ($stats['role_labels'] as $i => $label): ?>
            roleLegend.innerHTML += `
                <span class="badge bg-${rolesChart.data.datasets[0].backgroundColor[<?= $i ?>]} me-2 mb-2 p-2">
                    <?= $label ?>: <?= $stats['role_counts'][$i] ?>
                </span>
            `;
        <?php endforeach; ?>
    }

    function loadNotifications() {
        $.ajax({
            url: '../backend/fetch_notifications.php',
            method: 'GET',
            data: { unread_only: true, limit: 5 },
            success: function(response) {
                $('#notification-list').html(response);
            },
            error: function(xhr, status, error) {
                console.error("Error loading notifications:", error);
            }
        });
    }

    function processTransfer(id, action) {
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to ${action} this transfer request`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: `Yes, ${action} it!`
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../backend/process_transfer.php',
                    method: 'POST',
                    data: { 
                        transfer_id: id, 
                        action: action,
                        csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                    },
                    beforeSend: function() {
                        $(`button[data-id="${id}"]`).prop('disabled', true);
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: data.message,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error!', data.message, 'error');
                            }
                        } catch (e) {
                            Swal.fire('Error!', 'Invalid server response', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire('Error!', 'An error occurred while processing your request.', 'error');
                    },
                    complete: function() {
                        $(`button[data-id="${id}"]`).prop('disabled', false);
                    }
                });
            }
        });
    }

    function assignSurveyor() {
        const form = $('#assignSurveyorForm')[0];
        const formData = new FormData(form);
        
        $('#assignSpinner').removeClass('d-none');
        $('#assignSurveyorBtn').prop('disabled', true);
        
        $.ajax({
            url: '../backend/assign_survey.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: data.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            form.reset();
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                } catch (e) {
                    Swal.fire('Error!', 'Invalid server response', 'error');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('Error!', 'An error occurred while assigning the surveyor.', 'error');
            },
            complete: function() {
                $('#assignSpinner').addClass('d-none');
                $('#assignSurveyorBtn').prop('disabled', false);
            }
        });
    }

    // Refresh notifications every 30 seconds
    setInterval(loadNotifications, 30000);
    </script>
</body>
</html>