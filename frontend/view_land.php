<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../backend/db_connect.php';

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchCondition = '';
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $searchCondition = "WHERE (title_number LIKE '%$search%' OR 
                          registry_map_sheet LIKE '%$search%' OR 
                          location LIKE '%$search%') AND 
                          deleted_at IS NULL";
} else {
    $searchCondition = "WHERE deleted_at IS NULL";
}

// Get total records for pagination
$totalQuery = "SELECT COUNT(id) AS total FROM land_records $searchCondition";
$totalResult = $conn->query($totalQuery);
$totalRecords = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Main query with pagination
$sql = "SELECT id, title_number, approximate_area, registry_map_sheet, location 
        FROM land_records 
        $searchCondition
        ORDER BY title_number ASC
        LIMIT $offset, $recordsPerPage";

$result = $conn->query($sql);

// Check for errors
if (!$result) {
    die("Database error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Land Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .back-btn {
            margin-bottom: 15px;
        }
        .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        #search-box {
            flex-grow: 1;
            max-width: 500px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .action-btns {
            white-space: nowrap;
        }
        .pagination-info {
            margin-top: 15px;
            text-align: center;
        }
        .highlight {
            background-color: #fffde7;
        }
        .view-btn {
            background-color: #4caf50;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-map"></i> Land Records</h2>
            <a href="user_dashboard.php" class="btn btn-secondary back-btn">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Search Bar with Form -->
        <form id="search-form" method="GET" class="search-container">
            <input type="text" id="search-box" name="search" class="form-control" 
                   placeholder="Search by Title Number, Map Sheet, or Location" 
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
            <button type="button" id="reset-btn" class="btn btn-outline-secondary">Reset</button>
        </form>

        <!-- Record Count and Export Button -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <?php if (!empty($search)): ?>
                    <span class="badge bg-info"><?= $totalRecords ?> records found</span>
                <?php endif; ?>
            </div>
            <div>
                <button class="btn btn-sm btn-success" id="export-btn">
                    <i class="bi bi-download"></i> Export to Excel
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Title Number</th>
                        <th>Approx. Area (Acres)</th>
                        <th>Registry Map Sheet</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="land-table">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['title_number'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['approximate_area'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['registry_map_sheet'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['location'] ?? 'N/A') ?></td>
                                <td class="action-btns">
                                    <a href="view_land_details.php?id=<?= $row['id'] ?>" class="btn btn-sm view-btn" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit_land.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $row['id'] ?>)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No land records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="pagination-info">
            Showing <?= ($offset + 1) ?> to <?= min($offset + $recordsPerPage, $totalRecords) ?> of <?= $totalRecords ?> records
        </div>
        <?php endif; ?>

        <!-- Add New Record Button -->
        <div class="text-end mt-3">
            <a href="add_land.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Land Record
            </a>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this land record? This action cannot be undone!
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
    <script>
        // Current record to delete
        let currentRecordId = null;
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        
        // Reset search
        $('#reset-btn').click(function() {
            window.location.href = window.location.pathname;
        });

        // Confirm delete function with modal
        function confirmDelete(landId) {
            currentRecordId = landId;
            confirmModal.show();
        }

        // Handle delete confirmation
        $('#confirmDeleteBtn').click(function() {
            if (!currentRecordId) return;
            
            $.ajax({
                url: "delete_land.php",
                method: "POST",
                data: { land_id: currentRecordId },
                dataType: "json"
            })
            .done(function(data) {
                confirmModal.hide();
                if (data.status === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .fail(function() {
                confirmModal.hide();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to delete record. Please try again.'
                });
            });
        });

        // Export to Excel
        $('#export-btn').click(function() {
            // Show loading indicator
            Swal.fire({
                title: 'Preparing export',
                html: 'Please wait while we prepare your data...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Get all data (not just current page)
            $.ajax({
                url: 'export_land_records.php',
                data: {search: '<?= addslashes($search) ?>'},
                method: 'GET',
                dataType: 'json'
            })
            .done(function(data) {
                Swal.close();
                try {
                    // Format data for Excel
                    const formattedData = data.map(item => ({
                        'Title Number': item.title_number || 'N/A',
                        'Approximate Area': item.approximate_area || 'N/A',
                        'Registry Map Sheet': item.registry_map_sheet || 'N/A',
                        'Location': item.location || 'N/A',
                        'Export Date': new Date().toLocaleString()
                    }));
                    
                    const ws = XLSX.utils.json_to_sheet(formattedData);
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "LandRecords");
                    
                    // Generate filename with current date
                    const dateStr = new Date().toISOString().slice(0, 10);
                    XLSX.writeFile(wb, `land_records_${dateStr}.xlsx`);
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Export Failed',
                        text: 'Error processing export data: ' + e.message
                    });
                }
            })
            .fail(function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Export Failed',
                    text: 'Could not retrieve data for export: ' + error
                });
            });
        });

        // Highlight search terms in table
        $(document).ready(function() {
            const searchTerm = '<?= addslashes($search) ?>';
            if (searchTerm) {
                $('td').each(function() {
                    const text = $(this).text();
                    const highlighted = text.replace(new RegExp(searchTerm, 'gi'), match => 
                        `<span class="highlight">${match}</span>`);
                    $(this).html(highlighted);
                });
            }
        });
    </script>
</body>
</html>