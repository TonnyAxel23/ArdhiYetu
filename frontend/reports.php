<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if PDO is available
if (!extension_loaded('pdo')) {
    die('<div style="padding:20px;background:#ffebee;border:2px solid #f44336;border-radius:5px;margin:20px;">
        <h2 style="color:#f44336">PDO Extension Required</h2>
        <p>PDO extension is not loaded. Please enable it in php.ini:</p>
        <ol>
            <li>Open your php.ini file</li>
            <li>Find and uncomment these lines:
                <pre>extension=pdo
extension=pdo_mysql</pre>
            </li>
            <li>Restart your web server</li>
        </ol>
    </div>');
}

// Start session
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Database configuration
$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'ardhiyetu',
    'username' => 'root',
    'password' => ''
];

try {
    // Connect to MySQL server without specifying database first
    $conn = new PDO(
        "mysql:host={$dbConfig['host']}", 
        $dbConfig['username'], 
        $dbConfig['password']
    );
    
    // Set error mode (with compatibility check)
    $conn->setAttribute(constant('PDO::ATTR_ERRMODE') ?? 3, constant('PDO::ERRMODE_EXCEPTION') ?? 2);

    // Check if database exists, create if not
    $stmt = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$dbConfig['dbname']}'");
    if (!$stmt->fetch()) {
        $conn->exec("CREATE DATABASE `{$dbConfig['dbname']}`");
        $conn->exec("USE `{$dbConfig['dbname']}`");
        
        // Create tables
        $conn->exec("CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL
        )");
        
        $conn->exec("CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            created_at DATETIME NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            category_id INT
        )");
        
        // Insert sample data
        $conn->exec("INSERT IGNORE INTO categories (name) VALUES 
                    ('Electronics'), ('Clothing'), ('Home Goods'), ('Books'), ('Other')");
        
        $conn->exec("INSERT IGNORE INTO orders (created_at, amount, category_id) VALUES
                    (NOW() - INTERVAL 1 DAY, 125.50, 1),
                    (NOW() - INTERVAL 2 DAY, 75.25, 2),
                    (NOW() - INTERVAL 3 DAY, 200.00, 1),
                    (NOW() - INTERVAL 4 DAY, 50.75, 3),
                    (NOW() - INTERVAL 5 DAY, 300.00, 4)");
    } else {
        $conn->exec("USE `{$dbConfig['dbname']}`");
    }

    // Set default date range (last 30 days)
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-30 days'));

    // Process filters if submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $start_date = $_POST['start_date'] ?? $start_date;
        $end_date = $_POST['end_date'] ?? $end_date;
        $categories = $_POST['categories'] ?? [];
    }

    // Build base query
    $query = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_orders,
                SUM(amount) as total_revenue,
                AVG(amount) as avg_order_value
              FROM orders
              WHERE created_at BETWEEN :start_date AND :end_date";
    
    // Add category filter if selected
    if (!empty($categories)) {
        $category_ids = implode(',', array_map('intval', $categories));
        $query .= " AND category_id IN ($category_ids)";
    }
    
    $query .= " GROUP BY DATE(created_at) ORDER BY date ASC";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch categories for filter
    $categories_stmt = $conn->query("SELECT id, name FROM categories");
    $all_categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die('<div style="padding:20px;background:#ffebee;border:2px solid #f44336;border-radius:5px;margin:20px;">
        <h2 style="color:#f44336">Database Error</h2>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <p>Please verify your database configuration and ensure MySQL is running.</p>
    </div>');
}

// Calculate summary metrics
$total_revenue = array_sum(array_column($report_data, 'total_revenue'));
$total_orders = array_sum(array_column($report_data, 'total_orders'));
$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Report | ArdhiYetu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/report.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        .error-alert {
            padding: 15px;
            background: #ffebee;
            border-left: 4px solid #f44336;
            margin-bottom: 20px;
            color: #f44336;
        }
        .metric-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            flex: 1;
            min-width: 200px;
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        .positive { color: #28a745; }
        .negative { color: #dc3545; }
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="report-container">
                <header class="report-header">
                    <h1>Analytics Dashboard</h1>
                    <div class="report-controls">
                        <button class="time-filter" id="quick-range">Last 30 Days ▼</button>
                        <div class="dropdown-range" id="range-options" style="display:none;">
                            <button type="button" data-range="7">Last 7 Days</button>
                            <button type="button" data-range="30">Last 30 Days</button>
                            <button type="button" data-range="90">Last Quarter</button>
                            <button type="button" data-range="365">Last Year</button>
                            <button type="button" data-range="custom">Custom Range</button>
                        </div>
                        <button class="export-btn" id="export-report">Export</button>
                        <div class="dropdown-export" id="export-options" style="display:none;">
                            <button type="button" data-format="pdf">PDF</button>
                            <button type="button" data-format="excel">Excel</button>
                            <button type="button" data-format="csv">CSV</button>
                            <button type="button" data-format="png">PNG Image</button>
                        </div>
                        <button class="refresh-btn" id="refresh-data">⟳ Refresh</button>
                        <label class="auto-refresh">
                            <input type="checkbox" id="auto-refresh"> Auto-refresh
                        </label>
                    </div>
                </header>
                
                <form method="post" class="report-filters">
                    <div class="filter-group">
                        <label for="start_date">Date Range</label>
                        <input type="date" id="start_date" name="start_date" 
                               value="<?php echo htmlspecialchars($start_date); ?>" required>
                        <input type="date" id="end_date" name="end_date" 
                               value="<?php echo htmlspecialchars($end_date); ?>" required>
                    </div>
                    
                    <div class="filter-group">
                        <label for="categories">Categories</label>
                        <select multiple class="multi-select" name="categories[]" id="categories">
                            <?php foreach ($all_categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>"
                                    <?php echo (isset($categories) && in_array($category['id'], $categories)) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="apply-filters">Apply Filters</button>
                    <button type="button" class="reset-filters">Reset</button>
                </form>
                
                <div class="report-metrics">
                    <div class="metric-card">
                        <h3>Total Revenue</h3>
                        <p class="metric-value">$<?php echo number_format($total_revenue, 2); ?></p>
                        <p class="metric-change positive">↑ 12% from last period</p>
                    </div>
                    
                    <div class="metric-card">
                        <h3>Total Orders</h3>
                        <p class="metric-value"><?php echo number_format($total_orders); ?></p>
                        <p class="metric-change positive">↑ 8% from last period</p>
                    </div>
                    
                    <div class="metric-card">
                        <h3>Avg. Order Value</h3>
                        <p class="metric-value">$<?php echo number_format($avg_order_value, 2); ?></p>
                        <p class="metric-change negative">↓ 3% from last period</p>
                    </div>
                </div>
                
                <div class="report-visualizations">
                    <div class="chart-container">
                        <canvas id="trend-chart"></canvas>
                    </div>
                    
                    <div class="chart-row">
                        <div class="chart-container half-width">
                            <canvas id="category-chart"></canvas>
                        </div>
                        <div class="chart-container half-width">
                            <canvas id="daily-orders-chart"></canvas>
                        </div>
                    </div>
                    
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                    <th>Avg. Order</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                                    <td><?php echo number_format($row['total_orders']); ?></td>
                                    <td>$<?php echo number_format($row['total_revenue'], 2); ?></td>
                                    <td>$<?php echo number_format($row['avg_order_value'], 2); ?></td>
                                    <td><button class="view-details" data-date="<?php echo htmlspecialchars($row['date']); ?>">View</button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    
    <script>
    // Prepare data for charts
    const reportData = {
        dates: <?php echo json_encode(array_column($report_data, 'date')); ?>,
        orders: <?php echo json_encode(array_column($report_data, 'total_orders')); ?>,
        revenue: <?php echo json_encode(array_column($report_data, 'total_revenue')); ?>,
        avgOrder: <?php echo json_encode(array_column($report_data, 'avg_order_value')); ?>
    };
    
    // Trend Chart (Revenue)
    const trendCtx = document.getElementById('trend-chart').getContext('2d');
    const trendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: reportData.dates,
            datasets: [{
                label: 'Revenue ($)',
                data: reportData.revenue,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: { 
                    mode: 'index', 
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + context.raw.toLocaleString();
                        }
                    }
                },
                title: { display: true, text: 'Revenue Trend' }
            },
            scales: { 
                y: { 
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                } 
            }
        }
    });
    
    // Category Chart (Pie)
    const categoryCtx = document.getElementById('category-chart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($all_categories, 'name')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_fill(0, count($all_categories), 100/count($all_categories))); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ].slice(0, <?php echo count($all_categories); ?>)
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: { display: true, text: 'Revenue by Category' },
                legend: { position: 'right' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw + '%';
                        }
                    }
                }
            }
        }
    });
    
    // Daily Orders Chart (Bar)
    const dailyCtx = document.getElementById('daily-orders-chart').getContext('2d');
    const dailyChart = new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: reportData.dates,
            datasets: [{
                label: 'Daily Orders',
                data: reportData.orders,
                backgroundColor: 'rgba(54, 162, 235, 0.7)'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: { display: true, text: 'Daily Orders' },
                legend: { display: false }
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
    
    // Quick range selector
    document.getElementById('quick-range').addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('range-options').style.display = 
            document.getElementById('range-options').style.display === 'block' ? 'none' : 'block';
    });
    
    // Export options
    document.getElementById('export-report').addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('export-options').style.display = 
            document.getElementById('export-options').style.display === 'block' ? 'none' : 'block';
    });
    
    // Close dropdowns when clicking outside
    window.addEventListener('click', function() {
        document.getElementById('range-options').style.display = 'none';
        document.getElementById('export-options').style.display = 'none';
    });
    
    // Quick range selection
    document.querySelectorAll('#range-options button').forEach(button => {
        button.addEventListener('click', function() {
            const range = this.getAttribute('data-range');
            const endDate = new Date();
            let startDate = new Date();
            
            if (range === 'custom') {
                document.getElementById('quick-range').textContent = 'Custom Range ▼';
                return;
            }
            
            startDate.setDate(startDate.getDate() - parseInt(range));
            
            document.getElementById('start_date').valueAsDate = startDate;
            document.getElementById('end_date').valueAsDate = endDate;
            document.getElementById('quick-range').textContent = this.textContent + ' ▼';
            document.querySelector('form').submit();
        });
    });
    
    // Export functionality
    document.querySelectorAll('#export-options button').forEach(button => {
        button.addEventListener('click', function() {
            const format = this.getAttribute('data-format');
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            alert('Exporting as ' + format + ' from ' + startDate + ' to ' + endDate);
            // In production: window.location.href = `export.php?format=${format}&start=${startDate}&end=${endDate}`;
        });
    });
    
    // Auto-refresh
    const autoRefresh = document.getElementById('auto-refresh');
    let refreshInterval;
    
    autoRefresh.addEventListener('change', function() {
        if (this.checked) {
            refreshInterval = setInterval(() => {
                document.getElementById('refresh-data').click();
            }, 300000); // 5 minutes
        } else {
            clearInterval(refreshInterval);
        }
    });
    
    // Manual refresh
    document.getElementById('refresh-data').addEventListener('click', function() {
        document.querySelector('form').submit();
    });
    
    // Reset filters
    document.querySelector('.reset-filters').addEventListener('click', function() {
        const endDate = new Date();
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - 30);
        
        document.getElementById('start_date').valueAsDate = startDate;
        document.getElementById('end_date').valueAsDate = endDate;
        document.querySelectorAll('.multi-select option').forEach(option => {
            option.selected = false;
        });
        document.querySelector('form').submit();
    });
    
    // View details
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function() {
            const date = this.getAttribute('data-date');
            alert('Showing details for ' + date);
            // In production: window.location.href = `order_details.php?date=${date}`;
        });
    });
    </script>
</body>
</html>