<?php
// backend/functions.php

// Error reporting configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

/**
 * Safely execute database queries with error handling
 */
function safeQuery($conn, $sql, $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_double($param)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    } catch (mysqli_sql_exception $e) {
        error_log("Database error in query: $sql - " . $e->getMessage());
        return false;
    }
}

/**
 * Get user by ID
 */
function getUserById($user_id, $conn) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $result = safeQuery($conn, $sql, [$user_id]);
    return $result ? $result->fetch_assoc() : null;
}

/**
 * Get dashboard statistics
 */
function getDashboardStatistics($conn) {
    $stats = [];
    
    // Land count
    $result = safeQuery($conn, "SELECT COUNT(*) as total FROM land_records");
    $stats['land_count'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // User count
    $result = safeQuery($conn, "SELECT COUNT(*) as total FROM users");
    $stats['user_count'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Pending transfers
    $result = safeQuery($conn, "SELECT COUNT(*) as total FROM ownership_transfers WHERE status = 'pending'");
    $stats['pending_transfers'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Pending surveys
    $result = safeQuery($conn, "SELECT COUNT(*) as total FROM land_records WHERE status = 'pending_survey'");
    $stats['pending_surveys'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // User roles distribution
    $stats['role_labels'] = [];
    $stats['role_counts'] = [];
    $result = safeQuery($conn, "SELECT role, COUNT(*) as count FROM users GROUP BY role");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['role_labels'][] = $row['role'];
            $stats['role_counts'][] = $row['count'];
        }
    }
    
    return $stats;
}

/**
 * Get recent users
 */
function getRecentUsers($conn, $limit = 5) {
    $users = [];
    $sql = "SELECT id, full_name, email, role FROM users ORDER BY created_at DESC LIMIT ?";
    $result = safeQuery($conn, $sql, [$limit]);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    return $users;
}

/**
 * Get land distribution by location
 */
function getLandDistribution($conn) {
    $data = ['locations' => [], 'counts' => []];
    $result = safeQuery($conn, "SELECT location, COUNT(*) as count FROM land_records GROUP BY location");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data['locations'][] = $row['location'];
            $data['counts'][] = $row['count'];
        }
    }
    return $data;
}

/**
 * Get pending ownership transfers
 */
function getPendingTransfers($conn) {
    $transfers = [];
    $sql = "SELECT ot.id, lr.title_number, u1.full_name AS current_owner, u2.full_name AS new_owner
            FROM ownership_transfers ot
            JOIN land_records lr ON ot.land_id = lr.id
            JOIN users u1 ON ot.current_owner = u1.id
            JOIN users u2 ON ot.new_owner = u2.id
            WHERE ot.status = 'pending'";
    $result = safeQuery($conn, $sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $transfers[] = $row;
        }
    }
    return $transfers;
}

/**
 * Get lands needing survey
 */
function getLandsForSurvey($conn) {
    $lands = [];
    
    // First verify the table structure
    $columns = [];
    $result = safeQuery($conn, "SHOW COLUMNS FROM land_records");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    
    // Determine available title field
    $titleField = 'title_number'; // default
    if (in_array('land_title', $columns)) {
        $titleField = 'land_title';
    } elseif (in_array('title', $columns)) {
        $titleField = 'title';
    }
    
    $sql = "SELECT id, $titleField as display_title, title_number 
            FROM land_records 
            WHERE status = 'pending_survey'";
    
    $result = safeQuery($conn, $sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $lands[] = $row;
        }
    }
    
    return $lands;
}

/**
 * Get available surveyors
 */
function getSurveyors($conn) {
    $surveyors = [];
    $sql = "SELECT id, full_name, email FROM users WHERE role = 'surveyor'";
    $result = safeQuery($conn, $sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $surveyors[] = $row;
        }
    }
    return $surveyors;
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($user_id, $conn) {
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE admin_id = ? AND is_read = 0";
    $result = safeQuery($conn, $sql, [$user_id]);
    return $result ? $result->fetch_assoc()['count'] : 0;
}

/**
 * Get CSS class for role badges
 */
function getRoleBadgeClass($role) {
    switch (strtolower($role)) {
        case 'admin': return 'primary';
        case 'surveyor': return 'success';
        case 'user': return 'info';
        default: return 'secondary';
    }
}