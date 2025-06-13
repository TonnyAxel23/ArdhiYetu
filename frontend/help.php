<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../backend/db_connect.php';

// Get user info and recent tickets
$user_id = $_SESSION['user_id'];
$user_query = "SELECT full_name, email, phone FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get recent support tickets if table exists
$tickets_table = $conn->query("SHOW TABLES LIKE 'support_tickets'")->num_rows > 0;
$recent_tickets = [];
if ($tickets_table) {
    $tickets_query = "SELECT id, subject, status, created_at 
                     FROM support_tickets 
                     WHERE user_id = ? 
                     ORDER BY created_at DESC 
                     LIMIT 3";
    $stmt = $conn->prepare($tickets_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $recent_tickets = $stmt->get_result();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - ArdhiYetu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .help-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .help-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .help-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .help-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        
        .help-card h3 {
            color: var(--secondary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .help-card h3 i {
            margin-right: 15px;
            color: var(--primary-color);
        }
        
        .faq-item {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .faq-question {
            font-weight: 600;
            color: var(--secondary-color);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }
        
        .faq-question:hover {
            color: var(--primary-color);
        }
        
        .faq-answer {
            padding: 10px 0;
            color: #555;
            display: none;
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .contact-form .form-group {
            margin-bottom: 20px;
        }
        
        .resource-card {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
            margin-bottom: 15px;
            transition: all 0.3s;
            text-decoration: none;
            color: var(--secondary-color);
        }
        
        .resource-card:hover {
            background: #e9ecef;
            color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .resource-card i {
            font-size: 1.5rem;
            margin-right: 15px;
            color: var(--primary-color);
        }
        
        .emergency-contact {
            background: #fff8f8;
            border-left: 4px solid var(--danger-color);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
        }
        
        .ticket-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: var(--warning-color);
            color: var(--dark);
        }
        
        .status-resolved {
            background-color: var(--success-color);
            color: white;
        }
        
        .status-in-progress {
            background-color: var(--info-color);
            color: white;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: #6c757d;
        }
        
        .search-box input {
            padding-left: 40px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .help-container {
                padding: 15px;
            }
            
            .help-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .help-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include '../frontend/header.php'; ?>
    
    <div class="help-container">
        <!-- Back to Dashboard Button -->
        <a href="user_dashboard.php" class="btn btn-outline-secondary back-btn">
            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
        </a>
        
        <div class="help-header">
            <div>
                <h1><i class="fas fa-hands-helping"></i> Help Center</h1>
                <p class="lead mb-0">Find answers or contact our support team</p>
            </div>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control" placeholder="Search help articles..." id="helpSearch">
            </div>
        </div>
        
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- FAQ Section with Improved Search -->
                <div class="help-card">
                    <h3><i class="fas fa-question-circle"></i> Frequently Asked Questions</h3>
                    
                    <div class="faq-item" data-search="register land new parcel">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>How do I register a new land parcel?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>To register a new land parcel:</p>
                            <ol>
                                <li>Go to the "Register Land" page from your dashboard</li>
                                <li>Fill in all required details including title number, location, and approximate area</li>
                                <li>Upload any supporting documents (title deeds, survey maps)</li>
                                <li>Submit the form for processing</li>
                            </ol>
                            <p class="mb-0">You'll receive a confirmation email once your land is registered. Processing typically takes 3-5 business days.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item" data-search="transfer ownership land">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>How can I transfer land ownership?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Land transfer process:</p>
                            <ol>
                                <li>Navigate to "Transfer Requests" in your dashboard</li>
                                <li>Select the land parcel you wish to transfer</li>
                                <li>Enter the new owner's information (ID or email)</li>
                                <li>Provide a reason for the transfer</li>
                                <li>Upload required documents (signed transfer forms)</li>
                                <li>Submit the request</li>
                            </ol>
                            <p class="mb-0">Both parties will need to verify the transfer before completion. Transfers typically take 7-10 business days.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item" data-search="upload documents">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>What documents can I upload to the system?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>You can upload various land-related documents including:</p>
                            <ul>
                                <li>Title deeds (PDF, JPG, PNG)</li>
                                <li>Survey maps (PDF, JPG)</li>
                                <li>Lease agreements (PDF)</li>
                                <li>Payment receipts (PDF, JPG, PNG)</li>
                                <li>Approval letters (PDF, JPG)</li>
                                <li>Identity documents (for verification)</li>
                            </ul>
                            <p class="mb-0"><strong>Maximum file size:</strong> 10MB per file. For multiple documents, please combine them into a single PDF or contact support.</p>
                        </div>
                    </div>
                    
                    <!-- Additional FAQ Items -->
                    <div class="faq-item" data-search="update profile">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>How do I update my profile information?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>To update your profile:</p>
                            <ol>
                                <li>Go to "Profile Settings" from the main menu</li>
                                <li>Edit your personal information (name, email, phone)</li>
                                <li>Upload a new profile picture (optional)</li>
                                <li>Click "Save Changes" to update</li>
                            </ol>
                            <p class="mb-0"><strong>Note:</strong> Changing your email will require verification.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item" data-search="incorrect information error">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>What should I do if I find incorrect information about my land?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>If you find incorrect information:</p>
                            <ol>
                                <li>Go to the land record in question</li>
                                <li>Click the "Report Error" button</li>
                                <li>Describe the incorrect information in detail</li>
                                <li>Upload any supporting documents proving the correct information</li>
                                <li>Submit the report</li>
                            </ol>
                            <p class="mb-0">Our verification team will review your request within 3 business days and contact you for any additional information needed.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item" data-search="payment fees">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>What are the fees for land registration and transfers?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Current fee structure:</p>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Fee</th>
                                        <th>Processing Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>New Land Registration</td>
                                        <td>TZS 50,000</td>
                                        <td>3-5 business days</td>
                                    </tr>
                                    <tr>
                                        <td>Ownership Transfer</td>
                                        <td>TZS 75,000</td>
                                        <td>7-10 business days</td>
                                    </tr>
                                    <tr>
                                        <td>Document Upload (per parcel)</td>
                                        <td>Free</td>
                                        <td>Immediate</td>
                                    </tr>
                                    <tr>
                                        <td>Data Correction Request</td>
                                        <td>TZS 25,000</td>
                                        <td>5-7 business days</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="mb-0">Payments can be made via mobile money or bank transfer. Receipts should be uploaded to your account.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Enhanced Contact Support Section -->
                <div class="help-card">
                    <h3><i class="fas fa-envelope"></i> Contact Support</h3>
                    
                    <?php if ($tickets_table && $recent_tickets->num_rows > 0): ?>
                        <div class="alert alert-info mb-4">
                            <h5><i class="fas fa-ticket-alt me-2"></i>Your Recent Support Tickets</h5>
                            <ul class="mb-0">
                                <?php while ($ticket = $recent_tickets->fetch_assoc()): ?>
                                    <li>
                                        <strong><?= htmlspecialchars($ticket['subject']) ?></strong>
                                        <span class="ticket-status status-<?= str_replace(' ', '-', strtolower($ticket['status'])) ?>">
                                            <?= $ticket['status'] ?>
                                        </span>
                                        <small class="text-muted">- <?= date('M j, Y', strtotime($ticket['created_at'])) ?></small>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form class="contact-form" id="supportForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Your Name</label>
                                    <input type="text" class="form-control" id="name" value="<?= htmlspecialchars($user['full_name']) ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <select class="form-select" id="subject" required>
                                <option value="">Select a topic</option>
                                <option value="Land Registration">Land Registration</option>
                                <option value="Ownership Transfer">Ownership Transfer</option>
                                <option value="Document Upload">Document Upload</option>
                                <option value="Account Issues">Account Issues</option>
                                <option value="Data Correction">Data Correction</option>
                                <option value="Payment Issues">Payment Issues</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea class="form-control" id="message" rows="5" required placeholder="Describe your issue in detail..."></textarea>
                            <small class="text-muted">Please include any relevant land title numbers or reference numbers</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="attachments">Attachments (Optional)</label>
                            <input type="file" class="form-control" id="attachments" multiple accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Max 3 files (10MB each). Supported formats: PDF, JPG, PNG</small>
                        </div>
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="urgent" value="1">
                            <label class="form-check-label" for="urgent">
                                Mark as urgent (for critical issues only)
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-paper-plane me-2"></i> Send Message
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="saveDraft()">
                            <i class="fas fa-save me-2"></i> Save Draft
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Enhanced Quick Resources -->
                <div class="help-card">
                    <h3><i class="fas fa-book-open"></i> Quick Resources</h3>
                    
                    <a href="../docs/User_Guide.pdf" class="resource-card" target="_blank" download>
                        <i class="fas fa-file-pdf"></i>
                        <div>
                            <h5>User Guide</h5>
                            <p class="mb-0">Complete system documentation (PDF, 2.4MB)</p>
                        </div>
                    </a>
                    
                    <a href="../docs/Land_Registration_Process.pdf" class="resource-card" target="_blank" download>
                        <i class="fas fa-file-alt"></i>
                        <div>
                            <h5>Land Registration Process</h5>
                            <p class="mb-0">Step-by-step guide (PDF, 1.1MB)</p>
                        </div>
                    </a>
                    
                    <a href="../docs/Transfer_Requirements.pdf" class="resource-card" target="_blank" download>
                        <i class="fas fa-exchange-alt"></i>
                        <div>
                            <h5>Transfer Requirements</h5>
                            <p class="mb-0">Document checklist (PDF, 0.8MB)</p>
                        </div>
                    </a>
                    
                    <a href="video_tutorials.php" class="resource-card">
                        <i class="fas fa-video"></i>
                        <div>
                            <h5>Video Tutorials</h5>
                            <p class="mb-0">Watch how-to guides</p>
                        </div>
                    </a>
                    
                    <a href="faq_full.php" class="resource-card">
                        <i class="fas fa-question-circle"></i>
                        <div>
                            <h5>Full FAQ Database</h5>
                            <p class="mb-0">Browse all questions</p>
                        </div>
                    </a>
                </div>
                
                <!-- Enhanced Emergency Contacts -->
                <div class="help-card">
                    <h3><i class="fas fa-phone-alt"></i> Emergency Contacts</h3>
                    
                    <div class="emergency-contact">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Land Disputes</h5>
                        <p class="mb-1">Ministry of Lands: <strong>0800 123 456</strong></p>
                        <p class="mb-1">Email: <strong>lands@ardhiyetu.go.tz</strong></p>
                        <p class="mb-0">Available 24/7</p>
                    </div>
                    
                    <div class="emergency-contact">
                        <h5><i class="fas fa-user-shield me-2"></i>Fraud Reporting</h5>
                        <p class="mb-1">Anti-Corruption Bureau: <strong>0800 789 012</strong></p>
                        <p class="mb-1">Email: <strong>report@acb.go.tz</strong></p>
                        <p class="mb-0">Mon-Fri, 8am-5pm</p>
                    </div>
                    
                    <div class="emergency-contact">
                        <h5><i class="fas fa-balance-scale me-2"></i>Legal Assistance</h5>
                        <p class="mb-1">Legal Aid: <strong>0800 345 678</strong></p>
                        <p class="mb-1">Email: <strong>info@legalaid.go.tz</strong></p>
                        <p class="mb-0">Free consultation available</p>
                    </div>
                    
                    <div class="emergency-contact">
                        <h5><i class="fas fa-map-marker-alt me-2"></i>Physical Offices</h5>
                        <p class="mb-1"><strong>ArdhiYetu Headquarters</strong></p>
                        <p class="mb-1">123 Land Street, Dar es Salaam</p>
                        <p class="mb-0">Open Mon-Fri, 8:30am-4:30pm</p>
                    </div>
                </div>
                
                <!-- Live Chat Option -->
                <div class="help-card">
                    <h3><i class="fas fa-comments"></i> Live Chat Support</h3>
                    <p>Chat with a support agent in real-time during business hours:</p>
                    <button id="liveChatBtn" class="btn btn-success w-100 mb-3" onclick="startLiveChat()">
                        <i class="fas fa-comment-dots me-2"></i> Start Live Chat
                    </button>
                    <small class="text-muted">Available Monday-Friday, 8:00 AM - 5:00 PM</small>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../frontend/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle FAQ answers with animation
        function toggleAnswer(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('i');
            
            if (answer.style.display === 'block') {
                answer.style.animation = 'fadeOut 0.3s ease-out';
                setTimeout(() => {
                    answer.style.display = 'none';
                }, 250);
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                answer.style.display = 'block';
                answer.style.animation = 'fadeIn 0.3s ease-out';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
                
                // Close other open answers
                document.querySelectorAll('.faq-answer').forEach(item => {
                    if (item !== answer && item.style.display === 'block') {
                        item.style.display = 'none';
                        const otherIcon = item.previousElementSibling.querySelector('i');
                        otherIcon.classList.remove('fa-chevron-up');
                        otherIcon.classList.add('fa-chevron-down');
                    }
                });
            }
        }
        
        // FAQ Search Functionality
        document.getElementById('helpSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            document.querySelectorAll('.faq-item').forEach(item => {
                const question = item.querySelector('.faq-question span').textContent.toLowerCase();
                const searchData = item.getAttribute('data-search').toLowerCase();
                
                if (question.includes(searchTerm) || searchData.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Handle contact form submission
        document.getElementById('supportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;
            
            if (!subject || !message) {
                alert('Please fill in all required fields');
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sending...';
            submitBtn.disabled = true;
            
            // Simulate form submission (in real app, use AJAX)
            setTimeout(() => {
                alert('Thank you for your message! Our support team will contact you soon.');
                this.reset();
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // In a real app, you would:
                // 1. Collect form data
                // 2. Send via AJAX to your backend
                // 3. Handle response
                // 4. Show success/error message
            }, 1500);
        });
        
        // Save draft functionality
        function saveDraft() {
            const formData = {
                subject: document.getElementById('subject').value,
                message: document.getElementById('message').value,
                phone: document.getElementById('phone').value,
                urgent: document.getElementById('urgent').checked
            };
            
            localStorage.setItem('supportDraft', JSON.stringify(formData));
            alert('Draft saved! You can continue later.');
        }
        
        // Load draft if exists
        window.addEventListener('DOMContentLoaded', () => {
            const draft = localStorage.getItem('supportDraft');
            if (draft) {
                const formData = JSON.parse(draft);
                document.getElementById('subject').value = formData.subject;
                document.getElementById('message').value = formData.message;
                document.getElementById('phone').value = formData.phone;
                document.getElementById('urgent').checked = formData.urgent;
                
                if (confirm('You have a saved draft. Would you like to continue?')) {
                    document.getElementById('supportForm').scrollIntoView();
                }
            }
        });
        
        // Live chat simulation
        function startLiveChat() {
            const now = new Date();
            const hours = now.getHours();
            const day = now.getDay(); // 0 = Sunday, 1 = Monday, etc.
            
            // Check if within business hours (Mon-Fri, 8am-5pm)
            if (day >= 1 && day <= 5 && hours >= 8 && hours < 17) {
                alert('Connecting you to a live agent... Please wait.');
                // In a real app, this would open a chat widget
            } else {
                alert('Our live chat is currently unavailable. Please try again during business hours (Monday-Friday, 8:00 AM - 5:00 PM) or submit a support ticket.');
            }
        }
        
        // Open all FAQ answers when printing
        window.onbeforeprint = function() {
            document.querySelectorAll('.faq-answer').forEach(answer => {
                answer.style.display = 'block';
            });
        };
    </script>
</body>
</html>