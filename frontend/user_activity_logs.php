<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

include '../backend/db_connect.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_user = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$query = "SELECT logs.id, users.full_name, logs.action, logs.timestamp FROM logs JOIN users ON logs.user_id = users.id WHERE 1=1";

if ($search) {
    $query .= " AND (logs.action LIKE ? OR users.full_name LIKE ?)";
}
if ($filter_user) {
    $query .= " AND users.id = ?";
}
$query .= " ORDER BY logs.timestamp DESC";

$stmt = $conn->prepare($query);
if ($search && $filter_user) {
    $search_param = "%$search%";
    $stmt->bind_param("ssi", $search_param, $search_param, $filter_user);
} elseif ($search) {
    $search_param = "%$search%";
    $stmt->bind_param("ss", $search_param, $search_param);
} elseif ($filter_user) {
    $stmt->bind_param("i", $filter_user);
}
$stmt->execute();
$result = $stmt->get_result();

$users = $conn->query("SELECT id, full_name FROM users ORDER BY full_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Logs - ArdhiYetu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link btn btn-light text-dark" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="text-center">User Activity Logs</h2>
        <form method="GET" class="mb-3">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search actions or users" value="<?= htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="user_id" class="form-select">
                        <option value="">Filter by User</option>
                        <?php while ($user = $users->fetch_assoc()) { ?>
                            <option value="<?= $user['id']; ?>" <?= $filter_user == $user['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($user['full_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="user_activity_logs.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <table id="logsTable" class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']); ?></td>
                        <td><?= htmlspecialchars($row['full_name']); ?></td>
                        <td><?= htmlspecialchars($row['action']); ?></td>
                        <td><?= htmlspecialchars($row['timestamp']); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script>
        $(document).ready(function () {
            $('#logsTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'csv', className: 'btn btn-success' },
                    { extend: 'pdf', className: 'btn btn-danger' }
                ]
            });
        });
    </script>
</body>
</html>
