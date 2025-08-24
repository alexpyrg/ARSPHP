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

        // Prevent admin from warning themselves
        if ($target_user_id == $current_user['id']) {
            echo json_encode(['success' => false, 'message' => 'Δεν μπορείτε να στείλετε προειδοποίηση στον εαυτό σας!']);
            exit;
        }

        // Verify target user exists
        $stmt = $db->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute([$target_user_id]);
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$target_user) {
            echo json_encode(['success' => false, 'message' => 'Ο χρήστης δεν βρέθηκε!']);
            exit;
        }

        $db->beginTransaction();

        logActivity($db, $current_user['id'], 'user_warning_sent',
            "Warning sent to user: {$target_user['username']} (ID: $target_user_id) - Reason: $reason");

        try {
            $notification_title = "Προειδοποίηση από Διαχειριστή";
            $notification_message = "Λάβατε προειδοποίηση από διαχειριστή. Λόγος: " . $reason;

            // This assumes you have a notifications table
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, title, message, type, created_at) 
                VALUES (?, ?, ?, 'warning', NOW())
            ");
            $stmt->execute([$target_user_id, $notification_title, $notification_message]);
        } catch(PDOException $e) {
            // If notifications table doesn't exist, just continue with logging
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Η προειδοποίηση στάλθηκε επιτυχώς στον χρήστη {$target_user['username']}!"
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