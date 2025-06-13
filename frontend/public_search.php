<?php
include '../backend/db_connect.php';

$search_query = "";
$results = [];
$error = "";

// Check where the user came from
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
$back_link = "index.php"; // Default home page
$back_text = "← Back to Home";

// If coming from user dashboard, change back link
if (strpos($referrer, "user_dashboard.php") !== false) {
    $back_link = "user_dashboard.php";
    $back_text = "← Back to Dashboard";
}

// Handle search
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
    
    if (strlen($search_query) < 2) {
        $error = "Please enter at least 2 characters for search";
    } else {
        try {
            // Enhanced search query with more fields and better relevance
            $sql = "SELECT 
                        lr.id,
                        lr.title_number, 
                        lr.location, 
                        lr.approximate_area as size,
                        lr.registry_map_sheet,
                        u.full_name as owner_name,
                        CASE 
                            WHEN lr.title_number LIKE ? THEN 3 
                            WHEN lr.location LIKE ? THEN 2
                            WHEN lr.registry_map_sheet LIKE ? THEN 1
                            ELSE 0
                        END as relevance
                    FROM land_records lr
                    LEFT JOIN users u ON lr.user_id = u.id
                    WHERE lr.status='public' 
                    AND (lr.title_number LIKE ? OR lr.location LIKE ? OR lr.registry_map_sheet LIKE ?)
                    ORDER BY relevance DESC, lr.created_at DESC
                    LIMIT 20";
            
            $stmt = $conn->prepare($sql);
            $like_query = "%" . $search_query . "%";
            $stmt->bind_param("ssssss", $like_query, $like_query, $like_query, $like_query, $like_query, $like_query);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
            
            if (empty($results)) {
                $error = "No results found for \"".htmlspecialchars($search_query)."\"";
            }
        } catch (Exception $e) {
            $error = "An error occurred during search. Please try again.";
            error_log("Search error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Land Search - ArdhiYetu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .search-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .search-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .search-box {
            position: relative;
        }
        .search-box .form-control {
            padding-left: 40px;
            border-radius: 50px;
        }
        .search-box .search-icon {
            position: absolute;
            left: 15px;
            top: 10px;
            color: #6c757d;
        }
        .result-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .result-card:hover {
            transform: translateY(-5px);
        }
        .highlight {
            background-color: #fffde7;
            font-weight: bold;
            padding: 0 2px;
        }
        .no-results {
            text-align: center;
            padding: 50px 0;
        }
        .no-results i {
            font-size: 50px;
            color: #e0e0e0;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="search-container">
        <!-- Header Section -->
        <div class="search-header">
            <a href="<?= $back_link; ?>" class="btn btn-outline-secondary mb-3">
                <i class="fas fa-arrow-left"></i> <?= $back_text; ?>
            </a>
            <h2 class="text-center mb-4">
                <i class="fas fa-search text-primary"></i> Public Land Record Search
            </h2>
            
            <!-- Search Form -->
            <form method="GET" action="public_search.php">
                <div class="search-box mb-4">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" class="form-control form-control-lg" 
                           placeholder="Search by title number, location, or map sheet..." 
                           value="<?= htmlspecialchars($search_query) ?>" required>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-search me-2"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Results Section -->
        <?php if (!empty($results)): ?>
            <h4 class="mb-4">
                <i class="fas fa-list text-muted me-2"></i> 
                <?= count($results) ?> Result<?= count($results) > 1 ? 's' : '' ?> Found
            </h4>
            
            <?php foreach ($results as $row): 
                // Highlight search terms in results
                $highlighted_title = preg_replace(
                    "/(".$search_query.")/i", 
                    '<span class="highlight">$1</span>', 
                    htmlspecialchars($row['title_number'])
                );
                $highlighted_location = preg_replace(
                    "/(".$search_query.")/i", 
                    '<span class="highlight">$1</span>', 
                    htmlspecialchars($row['location'])
                );
                $highlighted_sheet = preg_replace(
                    "/(".$search_query.")/i", 
                    '<span class="highlight">$1</span>', 
                    htmlspecialchars($row['registry_map_sheet'])
                );
            ?>
                <div class="card result-card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="card-title">
                                    <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                    <?= $highlighted_title ?>
                                </h5>
                                <p class="card-text mb-1">
                                    <i class="fas fa-location-dot text-primary me-2"></i>
                                    <?= $highlighted_location ?>
                                </p>
                                <p class="card-text text-muted">
                                    <i class="fas fa-layer-group me-2"></i>
                                    Map Sheet: <?= $highlighted_sheet ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="d-flex flex-column">
                                    <span class="badge bg-success mb-2 align-self-md-end">
                                        <i class="fas fa-expand me-1"></i>
                                        <?= htmlspecialchars($row['size']) ?> acres
                                    </span>
                                    <?php if (!empty($row['owner_name'])): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            Owner: <?= htmlspecialchars($row['owner_name']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <a href="land_details.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary mt-2">
                                    View Details <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
        <?php elseif ($error): ?>
            <div class="no-results">
                <i class="fas fa-map-marked-alt"></i>
                <h4 class="text-muted"><?= $error ?></h4>
                <p class="text-muted">Try different search terms or check your spelling</p>
            </div>
        <?php elseif ($search_query === ""): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h4 class="text-muted">Search Public Land Records</h4>
                <p class="text-muted">Enter a title number, location, or map sheet reference to begin</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="text-center text-muted mt-4 mb-5">
        <small>ArdhiYetu Public Land Records &copy; <?= date('Y') ?></small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Focus on search input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="search"]').focus();
        });
        
        // Add confirmation before viewing details if not logged in
        document.querySelectorAll('a[href^="land_details.php"]').forEach(link => {
            link.addEventListener('click', function(e) {
                <?php if (!isset($_SESSION['user_id'])): ?>
                    if (!confirm('You need to login to view full details. Continue to login page?')) {
                        e.preventDefault();
                    }
                <?php endif; ?>
            });
        });
    </script>
</body>
</html>