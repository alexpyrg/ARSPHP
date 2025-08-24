<?php
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($database);

// Require admin role
$auth->requireRole('admin');
$current_user = $auth->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $target_user_id = $_POST['target_user_id'] ?? null;
        $target_username = $_POST['target_user'] ?? '';
        $reason = sanitizeInput($_POST['reason'] ?? '');

        // Validate input
        if (empty($target_user_id) || empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'Απαιτούνται όλα τα πεδία!']);
            exit;
        }

        // Prevent admin from suspending themselves
        if ($target_user_id == $current_user['id']) {
            echo json_encode(['success' => false, 'message' => 'Δεν μπορείτε να θέσετε σε αναστολή τον εαυτό σας!']);
            exit;
        }

        // Get current user status
        $stmt = $db->prepare("SELECT username, email, is_active FROM users WHERE id = ?");
        $stmt->execute([$target_user_id]);
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$target_user) {
            echo json_encode(['success' => false, 'message' => 'Ο χρήστης δεν βρέθηκε!']);
            exit;
        }

        $db->beginTransaction();

        // Toggle suspension status
        $new_status = $target_user['is_active'] ? 0 : 1;
        $action_text = $new_status ? 'ενεργοποιήθηκε' : 'τέθηκε σε αναστολή';
        $action_type = $new_status ? 'user_reactivated' : 'user_suspended';

        // Update user status
        $stmt = $db->prepare("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $target_user_id]);

        // Log the action
        logActivity($db, $current_user['id'], $action_type,
            "User {$target_user['username']} (ID: $target_user_id) $action_text - Reason: $reason");

        // Store suspension/reactivation notification
        try {
            $notification_title = $new_status ? "Λογαριασμός Ενεργοποιήθηκε" : "Λογαριασμός σε Αναστολή";
            $notification_message = $new_status
                ? "Ο λογαριασμός σας ενεργοποιήθηκε από διαχειριστή. Λόγος: " . $reason
                : "Ο λογαριασμός σας τέθηκε σε αναστολή από διαχειριστή. Λόγος: " . $reason;

            // This assumes you have a notifications table
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, title, message, type, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $notification_type = $new_status ? 'success' : 'warning';
            $stmt->execute([$target_user_id, $notification_title, $notification_message, $notification_type]);
        } catch(PDOException $e) {
            // If notifications table doesn't exist, just continue with logging
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Ο χρήστης {$target_user['username']} $action_text επιτυχώς!"
        ]);

    } catch(PDOException $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Σφάλμα βάσης δεδομένων: ' . $e->getMessage()]);
    } catch(Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Σφάλμα: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>