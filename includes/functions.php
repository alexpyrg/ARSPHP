<?php
// Εδώ έχουμε όλες τις μεθόδους που χρησιμοποιούνται σε πολλα αρχεία.

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);

    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';

    return floor($time/31536000) . ' years ago';
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function getRoleBadgeClass($role) {
    switch($role) {
        case 'admin': return 'badge-danger';
        case 'expert': return 'badge-warning';
        case 'registrar': return 'badge-primary';
        default: return 'badge-secondary';
    }
}

function getStatusBadgeClass($status) {
    switch($status) {
        case 'complete': return 'badge-success';
        case 'under_review': return 'badge-warning';
        case 'flagged': return 'badge-danger';
        case 'draft': return 'badge-secondary';
        default: return 'badge-light';
    }
}

function canUserAccessAccident($user_id, $user_role, $accident_user_id) {
    if ($user_role === 'admin' || $user_role === 'expert') {
        return true;
    }

    if ($user_role === 'registrar' && $user_id == $accident_user_id) {
        return true;
    }

    return false;
}

function canUserEditAccident($user_id, $user_role, $accident_user_id) {
    if ($user_role === 'admin') {
        return true;
    }

    if ($user_role === 'registrar' && $user_id == $accident_user_id) {
        return true;
    }

    return false;
}

function logActivity($db, $user_id, $action, $details = '') {
    try {
        $query = "INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (:user_id, :action, :details, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':details', $details);
        $stmt->execute();
    } catch(PDOException $e) {
        // Log error but don't break the application
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

function sendNotification($db, $user_id, $title, $message, $type = 'info') {
    try {
        $query = "INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (:user_id, :title, :message, :type, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':type', $type);
        $stmt->execute();
    } catch(PDOException $e) {
        error_log("Notification failed: " . $e->getMessage());
    }
}

function validateAccidentStep($step, $data) {
    $errors = [];

    switch($step) {
        case 1: // Accident basic info
            if (empty($data['date_time'])) $errors[] = 'Η Ημ/νια και ή ώρα πρέπει να συμπληρωθούν!';
            if (empty($data['location'])) $errors[] = 'Η Τοποθεσία πρέπει να συμπληρωθεί!';
            break;

        case 2: // Vehicle info
            if (empty($data['vehicle_count'])) $errors[] = 'Ο Αριθμός των οχημάτων πρέπει να συμπληρωθεί!';
            break;

        case 3: // Road info
            if (empty($data['road_type'])) $errors[] = 'Το Είδος Οδοστρώματος πρέπει να συμπληρωθεί!';
            if (empty($data['weather_conditions'])) $errors[] = 'Οι Καιρικές συνθήκες πρέπει να συμπληρωθούν!';
            break;
    }

    return $errors;
}
?>