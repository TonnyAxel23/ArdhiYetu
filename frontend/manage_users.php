<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=Unauthorized access");
    exit();
}

include '../backend/db_connect.php';

// Fetch all users from the database
$sql = "SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    $delete_sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        header("Location: manage_users.php?success=User deleted successfully");
    } else {
        echo "<script>alert('Error deleting user.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - ArdhiYetu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Manage Users</h2>
        <a href="admin_dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"> <?= htmlspecialchars($_GET['success']); ?> </div>
        <?php endif; ?>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['full_name']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td>
                            <form action="update_user_role.php" method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $row['id']; ?>">
                                <select name="role" class="form-select" onchange="this.form.submit()">
                                    <option value="user" <?= $row['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="surveyor" <?= $row['role'] === 'surveyor' ? 'selected' : ''; ?>>Surveyor</option>
                                    <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </form>
                        </td>
                        <td><?= date("d M Y", strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="edit_user.php?id=<?= $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="manage_users.php?delete=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                        </td>

                        
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>