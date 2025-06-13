<?php
session_start();
include '../backend/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Generate CSRF token if not already created
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch user's land parcels for dropdown
$user_id = $_SESSION['user_id'];
$lands_query = "SELECT id, title_number, location FROM land_records WHERE user_id = ?";
$stmt = $conn->prepare($lands_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$lands = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Land Transfer Request - ArdhiYetu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #28a745;
            --danger-color: #dc3545;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .transfer-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .transfer-header {
            text-align: center;
            margin-bottom: 30px;
            color: var(--secondary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .phone-input {
            position: relative;
        }
        
        .phone-prefix {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .phone-input input {
            padding-left: 40px;
        }
        
        .status-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: none;
        }
        
        @media (max-width: 768px) {
            .transfer-container {
                padding: 20px;
                margin: 15px;
            }
        }
    </style>
</head>
<body>
<?php include '../frontend/header.php'; ?>

<div class="container transfer-container">
    <div class="transfer-header">
        <h2><i class="fas fa-exchange-alt me-2"></i>Land Transfer Request</h2>
        <p class="text-muted">Initiate the transfer of land ownership</p>
    </div>

    <a href="user_dashboard.php" class="btn btn-outline-secondary mb-4">
        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
    </a>

    <!-- Status Alert (hidden by default) -->
    <div class="alert status-alert alert-dismissible fade show" role="alert">
        <span id="alert-message"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <form id="transferForm" class="needs-validation" novalidate>
        <!-- CSRF Token hidden input -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="form-section">
            <h4 class="mb-4"><i class="fas fa-map-marked-alt me-2"></i>Land Details</h4>
            
            <div class="mb-3">
                <label for="land_id" class="form-label">Select Land Parcel:</label>
                <select class="form-select" id="land_id" name="land_id" required>
                    <option value="">-- Select Land Parcel --</option>
                    <?php while ($land = $lands->fetch_assoc()): ?>
                        <option value="<?= $land['id'] ?>">
                            <?= htmlspecialchars($land['title_number']) ?> - <?= htmlspecialchars($land['location']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <div class="invalid-feedback">Please select a land parcel</div>
            </div>
            
            <div class="mb-3">
                <div id="landDetails" class="card bg-light p-3" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Title Number:</strong> <span id="detail-title"></span></p>
                            <p><strong>Location:</strong> <span id="detail-location"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Size:</strong> <span id="detail-size"></span></p>
                            <p><strong>Current Owner:</strong> <span id="detail-owner"></span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h4 class="mb-4"><i class="fas fa-user-plus me-2"></i>New Owner Details</h4>
            
            <div class="mb-3 phone-input">
                <label for="new_owner_phone" class="form-label">Phone Number:</label>
                <span class="phone-prefix">+255</span>
                <input type="tel" class="form-control" id="new_owner_phone" name="new_owner_phone" 
                       pattern="[0-9]{9}" minlength="9" maxlength="9" required
                       placeholder="712345678">
                <div class="invalid-feedback">Please enter a valid 9-digit phone number (without +255)</div>
                <small class="form-text text-muted">We'll verify this number with the new owner</small>
            </div>
            
            <div class="mb-3">
                <label for="new_owner_email" class="form-label">Email (Optional):</label>
                <input type="email" class="form-control" id="new_owner_email" name="new_owner_email">
                <div class="invalid-feedback">Please enter a valid email address</div>
            </div>
        </div>

        <div class="form-section">
            <h4 class="mb-4"><i class="fas fa-file-signature me-2"></i>Transfer Details</h4>
            
            <div class="mb-3">
                <label for="reason" class="form-label">Reason for Transfer:</label>
                <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                <div class="invalid-feedback">Please provide a reason for the transfer</div>
            </div>
            
            <div class="mb-3">
                <label for="supporting_docs" class="form-label">Supporting Documents (Optional):</label>
                <input type="file" class="form-control" id="supporting_docs" name="supporting_docs[]" multiple
                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <div class="form-text">Upload any relevant documents (max 5MB each)</div>
            </div>
            
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="terms_agree" required>
                <label class="form-check-label" for="terms_agree">
                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                </label>
                <div class="invalid-feedback">You must agree to the terms and conditions</div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-paper-plane me-2"></i>Submit Transfer Request
            </button>
        </div>
    </form>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Transfer Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>1. Transfer Process</h6>
                <p>The land transfer process will be initiated upon submission of this request. The new owner will need to verify their identity and accept the transfer.</p>
                
                <h6>2. Fees and Charges</h6>
                <p>A transfer fee of TZS 50,000 will be charged for processing this request. Additional government fees may apply.</p>
                
                <h6>3. Verification</h6>
                <p>Both parties will need to provide valid identification documents and may be required to appear in person for verification.</p>
                
                <h6>4. Processing Time</h6>
                <p>The transfer process typically takes 7-14 business days to complete, depending on verification requirements.</p>
                
                <h6>5. Cancellation Policy</h6>
                <p>Transfer requests can be cancelled within 24 hours of submission without penalty.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<?php include '../frontend/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Form validation
    (function() {
        'use strict';
        
        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        const forms = document.querySelectorAll('.needs-validation');
        
        // Loop over them and prevent submission
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    })();

    // Show/hide land details when selected
    document.getElementById('land_id').addEventListener('change', function() {
        const landId = this.value;
        const landDetails = document.getElementById('landDetails');
        
        if (landId) {
            // In a real app, you would fetch these details from your backend
            // This is just a demonstration
            document.getElementById('detail-title').textContent = this.options[this.selectedIndex].text.split(' - ')[0];
            document.getElementById('detail-location').textContent = this.options[this.selectedIndex].text.split(' - ')[1];
            document.getElementById('detail-size').textContent = '0.5 acres'; // Example data
            document.getElementById('detail-owner').textContent = 'You (Current Owner)';
            
            landDetails.style.display = 'block';
        } else {
            landDetails.style.display = 'none';
        }
    });

    // Form submission with AJAX
    document.getElementById('transferForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            return;
        }

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        
        fetch('../backend/transfer_land.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Show status alert
            const alert = document.querySelector('.status-alert');
            const alertMessage = document.getElementById('alert-message');
            
            alert.classList.remove('alert-success', 'alert-danger');
            alert.classList.add(data.status === 'success' ? 'alert-success' : 'alert-danger');
            alertMessage.textContent = data.message;
            alert.style.display = 'block';
            
            // Scroll to top to show message
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            if (data.status === 'success') {
                // Reset form on success
                this.reset();
                this.classList.remove('was-validated');
                document.getElementById('landDetails').style.display = 'none';
                
                // Hide alert after 5 seconds
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 5000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        })
        .finally(() => {
            // Restore button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    });

    // Phone number validation
    document.getElementById('new_owner_phone').addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
</script>
</body>
</html>