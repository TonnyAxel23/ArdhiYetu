<?php
session_start();
include '../backend/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if land_documents table exists
$check_table = $conn->query("SHOW TABLES LIKE 'land_documents'");
if ($check_table->num_rows == 0) {
    die("Error: Table 'land_documents' does not exist. Please create it in your database.");
}

// Pagination setup
$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// Get total number of documents
$count_query = "SELECT COUNT(d.id) as total 
                FROM land_documents d
                JOIN land_records r ON d.land_id = r.id
                WHERE r.user_id = ?";
$stmt_count = $conn->prepare($count_query);
$stmt_count->bind_param("i", $_SESSION['user_id']);
$stmt_count->execute();
$total_result = $stmt_count->get_result()->fetch_assoc();
$total_pages = ceil($total_result['total'] / $results_per_page);

// Fetch documents with pagination
$documents_query = "SELECT d.id, d.file_name, d.file_path, r.title_number, 
                    r.location, d.upload_date, d.file_size
                    FROM land_documents d
                    JOIN land_records r ON d.land_id = r.id
                    WHERE r.user_id = ?
                    ORDER BY d.upload_date DESC
                    LIMIT ?, ?";
$stmt = $conn->prepare($documents_query);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("iii", $_SESSION['user_id'], $offset, $results_per_page);
$stmt->execute();
$documents = $stmt->get_result();

// File type icons mapping
$file_icons = [
    'pdf' => 'file-pdf',
    'doc' => 'file-word',
    'docx' => 'file-word',
    'xls' => 'file-excel',
    'xlsx' => 'file-excel',
    'jpg' => 'file-image',
    'jpeg' => 'file-image',
    'png' => 'file-image',
    'gif' => 'file-image',
    'txt' => 'file-alt',
    'zip' => 'file-archive',
    'rar' => 'file-archive'
];

// Function to get file icon class
function getFileIcon($filename) {
    global $file_icons;
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return isset($file_icons[$ext]) ? $file_icons[$ext] : 'file';
}

// Function to format file size
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Land Documents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .file-icon {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        .pdf-icon { color: #e74c3c; }
        .word-icon { color: #2b579a; }
        .excel-icon { color: #217346; }
        .image-icon { color: #e67e22; }
        .file-icon-default { color: #7f8c8d; }
        .document-card {
            transition: transform 0.2s;
            margin-bottom: 15px;
        }
        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .search-box {
            max-width: 400px;
            margin: 0 auto 20px;
        }
        .upload-area {
            border: 2px dashed #ddd;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #aaa;
        }
        .file-info {
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="fas fa-folder-open me-2"></i>My Land Documents</h2>
            <a href="user_dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <form id="searchForm" class="search-box">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search documents..." name="search">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-upload me-1"></i> Upload Document
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['upload_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Document uploaded successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['delete_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Document deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($documents->num_rows == 0): ?>
            <div class="text-center py-5">
                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                <h4>No documents found</h4>
                <p class="text-muted">You haven't uploaded any documents yet.</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-upload me-1"></i> Upload Your First Document
                </button>
            </div>
        <?php else: ?>
            <div class="row">
                <?php while ($row = $documents->fetch_assoc()): 
                    $file_icon = getFileIcon($row['file_name']);
                    $icon_class = "text-muted";
                    if (strpos($file_icon, 'pdf') !== false) $icon_class = "pdf-icon";
                    elseif (strpos($file_icon, 'word') !== false) $icon_class = "word-icon";
                    elseif (strpos($file_icon, 'excel') !== false) $icon_class = "excel-icon";
                    elseif (strpos($file_icon, 'image') !== false) $icon_class = "image-icon";
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card document-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <i class="fas fa-<?= $file_icon ?> file-icon <?= $icon_class ?>"></i>
                                <div>
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($row['file_name']) ?></h5>
                                    <p class="card-subtitle text-muted small">
                                        Title No: <?= htmlspecialchars($row['title_number']) ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="file-info mb-3">
                                <div><i class="fas fa-calendar-alt me-1"></i> <?= date('M d, Y', strtotime($row['upload_date'])) ?></div>
                                <div><i class="fas fa-file me-1"></i> <?= formatFileSize($row['file_size']) ?></div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="<?= htmlspecialchars($row['file_path']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                                <a href="<?= htmlspecialchars($row['file_path']) ?>" class="btn btn-sm btn-outline-success" download>
                                    <i class="fas fa-download me-1"></i> Download
                                </a>
                                <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?= $row['id'] ?>" data-filename="<?= htmlspecialchars($row['file_name']) ?>">
                                    <i class="fas fa-trash me-1"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="../backend/upload_document.php" method="post" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadModalLabel">Upload New Document</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="land_id" class="form-label">Select Land Title</label>
                            <select class="form-select" id="land_id" name="land_id" required>
                                <option value="">-- Select Land Title --</option>
                                <?php
                                $lands_query = "SELECT id, title_number FROM land_records WHERE user_id = ?";
                                $stmt_lands = $conn->prepare($lands_query);
                                $stmt_lands->bind_param("i", $_SESSION['user_id']);
                                $stmt_lands->execute();
                                $lands = $stmt_lands->get_result();
                                while ($land = $lands->fetch_assoc()): ?>
                                    <option value="<?= $land['id'] ?>"><?= htmlspecialchars($land['title_number']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="document" class="form-label">Document File</label>
                            <input type="file" class="form-control" id="document" name="document" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" required>
                            <div class="form-text">Allowed formats: PDF, DOC, XLS, JPG, PNG (Max 10MB)</div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload Document</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="filename-to-delete"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirm-delete" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Delete confirmation
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const documentId = this.getAttribute('data-id');
                const filename = this.getAttribute('data-filename');
                
                document.getElementById('filename-to-delete').textContent = filename;
                document.getElementById('confirm-delete').href = `../backend/delete_document.php?id=${documentId}`;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });

        // Drag and drop functionality
        const uploadArea = document.querySelector('.upload-area');
        if (uploadArea) {
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#0d6efd';
                uploadArea.style.backgroundColor = 'rgba(13, 110, 253, 0.05)';
            });

            uploadArea.addEventListener('dragleave', () => {
                uploadArea.style.borderColor = '#ddd';
                uploadArea.style.backgroundColor = '';
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#ddd';
                uploadArea.style.backgroundColor = '';
                
                if (e.dataTransfer.files.length) {
                    document.getElementById('document').files = e.dataTransfer.files;
                    // You can add preview functionality here if needed
                }
            });
        }
    </script>
</body>
</html>