<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../backend/db_connect.php';

// Get user data (only existing columns)
$user_id = $_SESSION['user_id'];
$sql = "SELECT full_name, email, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle success messages
$update_success = isset($_GET['update_success']);
$password_success = isset($_GET['password_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile - ArdhiYetu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --danger-color: #e74c3c;
            --light-gray: #f8f9fa;
        }
        
        body {
            background-color: var(--light-gray);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .profile-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            color: var(--secondary-color);
        }
        
        .profile-pic-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
        }
        
        .profile-pic {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .profile-pic-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .profile-pic-upload:hover {
            background: #2980b9;
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
        }
        
        .nav-pills .nav-link {
            color: var(--secondary-color);
        }
        
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .password-strength {
            height: 5px;
            margin-top: 5px;
            background: #eee;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .dark-mode {
            background-color: #121212;
            color: #f8f9fa;
        }
        
        .dark-mode .profile-container,
        .dark-mode .form-section {
            background-color: #1e1e1e;
            color: #f8f9fa;
        }
        
        .dark-mode .form-control,
        .dark-mode .form-select {
            background-color: #2d2d2d;
            color: #f8f9fa;
            border-color: #444;
        }
        
        .dark-mode .nav-pills .nav-link {
            color: #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                padding: 20px;
                margin: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container profile-container">
        <!-- Success Alerts -->
        <?php if ($update_success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Profile updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($password_success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Password changed successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="profile-header">
            <h2><i class="fas fa-user-cog me-2"></i>Manage Profile</h2>
            <p class="text-muted">Update your personal information and security settings</p>
        </div>

        <ul class="nav nav-pills mb-4 justify-content-center" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="personal-tab" data-bs-toggle="pill" data-bs-target="#personal" type="button" role="tab">Personal Info</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="password-tab" data-bs-toggle="pill" data-bs-target="#password" type="button" role="tab">Password</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab">Security</button>
            </li>
        </ul>

        <div class="tab-content" id="profileTabsContent">
            <!-- Personal Information Tab -->
            <div class="tab-pane fade show active" id="personal" role="tabpanel">
                <div class="form-section">
                    <form action="../backend/update_profile.php" method="POST" enctype="multipart/form-data">
                        <div class="profile-pic-container">
                            <img src="<?= !empty($user['profile_picture']) ? '../uploads/' . htmlspecialchars($user['profile_picture']) : '../assets/default-profile.png'; ?>" 
                                 class="profile-pic" id="profile-pic-preview">
                            <label for="profile_picture" class="profile-pic-upload" title="Change photo">
                                <i class="fas fa-camera"></i>
                                <input type="file" id="profile_picture" name="profile_picture" class="d-none" accept="image/*">
                            </label>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" 
                                   value="<?= htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Password Tab -->
            <div class="tab-pane fade" id="password" role="tabpanel">
                <div class="form-section">
                    <form action="../backend/change_password.php" method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" id="new_password" name="new_password" class="form-control" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength mt-2">
                                <div class="password-strength-bar" id="password-strength-bar"></div>
                            </div>
                            <small class="text-muted">Password strength: <span id="password-strength-text">Weak</span></small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="password-match-feedback">
                                Passwords do not match
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-danger w-100 py-2" id="change-password-btn">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Security Tab -->
            <div class="tab-pane fade" id="security" role="tabpanel">
                <div class="form-section">
                    <h5><i class="fas fa-shield-alt me-2"></i>Security Settings</h5>
                    
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="darkModeSwitch">
                            <label class="form-check-label" for="darkModeSwitch">Dark Mode</label>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Two-Factor Authentication</h6>
                        <p class="text-muted">Add an extra layer of security to your account</p>
                        <button class="btn btn-outline-primary" disabled>
                            <i class="fas fa-mobile-alt me-2"></i>Enable 2FA (Coming Soon)
                        </button>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Login Activity</h6>
                        <p class="text-muted">Last login: <?= date('F j, Y \a\t g:i a') ?></p>
                        <a href="#" class="btn btn-outline-secondary btn-sm" disabled>
                            <i class="fas fa-history me-1"></i>View Login History (Coming Soon)
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="user_dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Profile picture preview
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profile-pic-preview').src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });

        // Password strength checker
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('password-strength-text');
            
            // Reset
            strengthBar.style.width = '0%';
            strengthBar.style.backgroundColor = '';
            
            if (password.length === 0) {
                strengthText.textContent = '';
                return;
            }
            
            // Calculate strength
            let strength = 0;
            
            // Length
            if (password.length > 7) strength += 1;
            if (password.length > 11) strength += 1;
            
            // Contains numbers
            if (/\d/.test(password)) strength += 1;
            
            // Contains special chars
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 1;
            
            // Contains both lower and upper case
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 1;
            
            // Update UI
            let width = 0;
            let color = '';
            let text = '';
            
            if (strength <= 2) {
                width = 30;
                color = '#e74c3c';
                text = 'Weak';
            } else if (strength <= 4) {
                width = 60;
                color = '#f39c12';
                text = 'Medium';
            } else {
                width = 100;
                color = '#2ecc71';
                text = 'Strong';
            }
            
            strengthBar.style.width = width + '%';
            strengthBar.style.backgroundColor = color;
            strengthText.textContent = text;
            strengthText.style.color = color;
        });

        // Check password match
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            const feedback = document.getElementById('password-match-feedback');
            const submitBtn = document.getElementById('change-password-btn');
            
            if (confirmPassword.length > 0 && newPassword !== confirmPassword) {
                this.classList.add('is-invalid');
                feedback.style.display = 'block';
                submitBtn.disabled = true;
            } else {
                this.classList.remove('is-invalid');
                feedback.style.display = 'none';
                submitBtn.disabled = false;
            }
        });

        // Dark mode toggle
        const darkModeSwitch = document.getElementById('darkModeSwitch');
        
        // Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
            darkModeSwitch.checked = true;
        }
        
        darkModeSwitch.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    </script>
</body>
</html>