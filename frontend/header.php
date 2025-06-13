<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ArdhiYetu' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 60px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container">
        <a class="navbar-brand" href="user_dashboard.php">ArdhiYetu</a>
        <div class="navbar-nav ms-auto">
            <?php if(isset($_SESSION['admin_id'])): ?>
                <a class="nav-link" href="approve_transfer.php">Approve Transfers</a>
            <?php endif; ?>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a class="nav-link" href="land_transfer_request.php">Transfer Land</a>
                <a class="nav-link" href="logout.php">Logout</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<div class="container">