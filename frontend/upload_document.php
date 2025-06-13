<?php
session_start();
require_once '../backend/db_connect.php';
require_once '../backend/file_upload_validation.php'; // New validation file

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized access']));
}

$message = "";
$allowed_types = ['application/pdf'];
$max_file_size = 5 * 1024 * 1024; // 5MB

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid CSRF token");
        }

        $land_id = intval($_POST['land_id']);
        $user_id = $_SESSION['user_id'];

        // Validate land ownership
        $stmt = $conn->prepare("SELECT id FROM land_records WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $land_id, $user_id);
        $stmt->execute();
        if (!$stmt->get_result()->num_rows) {
            throw new Exception("You don't have permission to upload documents for this land");
        }

        // File upload validation
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Please select a valid file to upload");
        }

        $document = $_FILES['document'];
        $document_name = basename($document['name']);
        $document_tmp = $document['tmp_name'];
        $document_size = $document['size'];
        
        // Validate file type and size
        if ($document_size > $max_file_size) {
            throw new Exception("File size exceeds 5MB limit");
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $document_type = $finfo->file($document_tmp);

        if (!in_array($document_type, $allowed_types)) {
            throw new Exception("Only PDF files are allowed");
        }

        // Validate PDF content (basic check)
        if (!validatePdfContent($document_tmp)) {
            throw new Exception("Invalid PDF file content");
        }

        // Prepare upload directory
        $upload_dir = "../uploads/land_documents/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate safe filename
        $file_extension = pathinfo($document_name, PATHINFO_EXTENSION);
        $safe_filename = "land_{$land_id}_" . bin2hex(random_bytes(8)) . ".{$file_extension}";
        $document_path = $upload_dir . $safe_filename;

        // Move uploaded file
        if (!move_uploaded_file($document_tmp, $document_path)) {
            throw new Exception("Failed to save uploaded file");
        }

        // Store in database
        $sql = "INSERT INTO land_documents (land_id, user_id, file_name, original_name, file_path, file_size, uploaded_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssi", $land_id, $user_id, $safe_filename, $document_name, $document_path, $document_size);

        if (!$stmt->execute()) {
            unlink($document_path); // Clean up if DB fails
            throw new Exception("Database error: " . $conn->error);
        }

        // Success
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'Document uploaded successfully!'
        ];
        header("Location: {$_SERVER['PHP_SELF']}");
        exit();

    } catch (Exception $e) {
        $message = [
            'type' => 'danger',
            'message' => $e->getMessage()
        ];
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check for flash messages
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// Get user's land records
$land_query = "SELECT id, title_number, location FROM land_records WHERE user_id = ? ORDER BY title_number";
$stmt = $conn->prepare($land_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$land_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Land Document</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .container {
            max-width: 800px;
            margin-top: 2rem;
        }
        .file-upload-wrapper {
            position: relative;
            margin-bottom: 1rem;
        }
        .file-upload-input {
            opacity: 0;
            position: absolute;
            top: 0;
            right: 0;
            margin: 0;
            padding: 0;
            font-size: 20px;
            cursor: pointer;
            height: 100%;
            width: 100%;
        }
        .file-upload-label {
            display: block;
            padding: 1rem;
            border: 2px dashed #dee2e6;
            border-radius: 5px;
            text-align: center;
            transition: all 0.3s;
        }
        .file-upload-label:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .file-upload-label.dragover {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }
        .file-info {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .progress {
            height: 10px;
            margin-top: 10px;
            display: none;
        }
        .document-preview {
            max-width: 100%;
            height: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            display: none;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h2 class="card-title mb-0"><i class="bi bi-upload"></i> Upload Land Document</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show" role="alert">
                        <?= $message['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form id="uploadForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="mb-3">
                        <label for="land_id" class="form-label">Select Land Record</label>
                        <select name="land_id" id="land_id" class="form-select" required>
                            <option value="">-- Select Land --</option>
                            <?php foreach ($land_records as $land): ?>
                                <option value="<?= $land['id'] ?>">
                                    <?= htmlspecialchars("Title #{$land['title_number']} - {$land['location']}") ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Upload Document (PDF only, max 5MB)</label>
                        <div class="file-upload-wrapper">
                            <input type="file" name="document" id="document" class="file-upload-input" accept=".pdf" required>
                            <label for="document" class="file-upload-label" id="fileUploadLabel">
                                <i class="bi bi-cloud-arrow-up fs-1"></i>
                                <p class="mb-1">Drag & drop your PDF file here or click to browse</p>
                                <small class="text-muted">Accepted format: PDF (Max 5MB)</small>
                            </label>
                            <div id="fileInfo" class="file-info"></div>
                        </div>
                        <div class="progress" id="uploadProgress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"></div>
                        </div>
                        <div id="documentPreview" class="document-preview"></div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="bi bi-upload"></i> Upload Document
                        </button>
                        <a href="user_dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.min.js"></script>
    <script>
        // Set PDF.js worker path
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.worker.min.js';

        $(document).ready(function() {
            // Drag and drop functionality
            const fileUploadLabel = $('#fileUploadLabel');
            const fileInput = $('#document');
            const fileInfo = $('#fileInfo');
            const preview = $('#documentPreview');
            const progress = $('#uploadProgress');
            const progressBar = $('.progress-bar');
            const submitBtn = $('#submitBtn');

            // Handle drag over
            fileUploadLabel.on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });

            // Handle drag leave
            fileUploadLabel.on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });

            // Handle drop
            fileUploadLabel.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                if (e.originalEvent.dataTransfer.files.length) {
                    fileInput[0].files = e.originalEvent.dataTransfer.files;
                    updateFileInfo();
                }
            });

            // Handle file selection
            fileInput.on('change', function() {
                updateFileInfo();
            });

            function updateFileInfo() {
                const file = fileInput[0].files[0];
                if (file) {
                    fileInfo.html(`<i class="bi bi-file-earmark-pdf"></i> ${file.name} (${formatFileSize(file.size)})`);
                    
                    // Preview PDF (first page only)
                    preview.show();
                    preview.html('<p class="text-center my-3">Loading preview...</p>');
                    
                    const fileReader = new FileReader();
                    fileReader.onload = function() {
                        const typedarray = new Uint8Array(this.result);
                        
                        pdfjsLib.getDocument(typedarray).promise.then(function(pdf) {
                            pdf.getPage(1).then(function(page) {
                                const viewport = page.getViewport({ scale: 0.5 });
                                const canvas = document.createElement('canvas');
                                const context = canvas.getContext('2d');
                                canvas.height = viewport.height;
                                canvas.width = viewport.width;
                                
                                preview.html(canvas);
                                page.render({
                                    canvasContext: context,
                                    viewport: viewport
                                });
                            });
                        }).catch(function() {
                            preview.html('<p class="text-center text-danger my-3">Could not generate preview</p>');
                        });
                    };
                    fileReader.readAsArrayBuffer(file);
                } else {
                    fileInfo.empty();
                    preview.hide();
                }
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            // AJAX form submission with progress
            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const file = fileInput[0].files[0];
                
                if (!file) {
                    alert('Please select a file to upload');
                    return;
                }
                
                submitBtn.prop('disabled', true);
                progress.show();
                
                $.ajax({
                    xhr: function() {
                        const xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                const percent = Math.round((e.loaded / e.total) * 100);
                                progressBar.css('width', percent + '%').attr('aria-valuenow', percent);
                            }
                        }, false);
                        return xhr;
                    },
                    url: $(this).attr('action') || window.location.href,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        window.location.reload();
                    },
                    error: function(xhr) {
                        let errorMsg = 'Upload failed';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message) errorMsg = response.message;
                        } catch (e) {}
                        
                        alert(errorMsg);
                        submitBtn.prop('disabled', false);
                        progress.hide();
                        progressBar.css('width', '0%');
                    }
                });
            });
        });
    </script>
</body>
</html>