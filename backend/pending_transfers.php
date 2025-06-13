<?php
// pending_transfers.php

include '../config/database.php';

// Assume the new owner is logged in and their user ID is stored in session
session_start();
$new_owner_id = $_SESSION['user_id']; // This assumes you save user_id after login

$query = "SELECT ltr.id AS request_id, land_records.title, land_records.location, u.name AS current_owner
          FROM land_transfer_requests ltr
          JOIN land_records ON ltr.land_id = land_records.id
          JOIN users u ON ltr.current_owner_id = u.id
          WHERE ltr.new_owner_id = ? AND ltr.status = 'pending'";


$stmt = $conn->prepare($query);
$stmt->bind_param("i", $new_owner_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Transfers - ArdhiYetu</title>
</head>
<body>

<h2>Pending Land Transfers</h2>

<?php if ($result->num_rows > 0): ?>
<table border="1" cellpadding="8">
    <thead>
        <tr>
            <th>Request ID</th>
            <th>Land Title</th>
            <th>Location</th>
            <th>Current Owner</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['request_id'] ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['location']) ?></td>
            <td><?= htmlspecialchars($row['current_owner']) ?></td>
            <td>
                <a href="accept_transfer.html?request_id=<?= $row['request_id'] ?>">Accept</a> | 
                <a href="decline_transfer.html?request_id=<?= $row['request_id'] ?>">Decline</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
    <p>No pending transfer requests.</p>
<?php endif; ?>

</body>
</html>
