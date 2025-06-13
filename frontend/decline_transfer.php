<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = "Decline Transfer";
include '../frontend/header.php';
?>

<h2 class="container mt-3">Decline Land Transfer</h2>

<div class="container">
    <form id="declineForm">
        <div class="mb-3">
            <label class="form-label">Request ID:</label>
            <input type="text" id="request_id" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Your User ID (New Owner):</label>
            <input type="text" id="new_owner_id" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-danger">Decline Transfer</button>
    </form>
</div>

<script>
document.getElementById('declineForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new URLSearchParams();
    formData.append('request_id', document.getElementById('request_id').value);
    formData.append('new_owner_id', document.getElementById('new_owner_id').value);

    fetch('../backend/decline_transfer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            window.location.href = 'user_dashboard.php';
        }
    })
    .catch(error => console.error('Error:', error));
});

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const requestId = urlParams.get('request_id');
    if (requestId) {
        document.getElementById('request_id').value = requestId;
    }
});
</script>

<?php include '../includes/footer.php'; ?>