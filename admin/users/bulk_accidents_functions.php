<?php
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($database);

// Require admin role
$auth->requireRole('admin');
$current_user = $auth->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action_type = $_POST['action_type'] ?? '';
        $accident_ids = json_decode($_POST['accident_ids'] ?? '[]', true);
        $reason = sanitizeInput($_POST['reason'] ?? '');

        if (empty($accident_ids)) {
            echo json_encode(['success' => false, 'message' => 'Δεν επιλέχθηκαν εγγραφές!']);
            exit;
        }

        $db->beginTransaction();
        $updated_count = 0;

        switch ($action_type) {
            case 'change_status':
                $new_status = $_POST['new_status'] ?? '';
                if (empty($new_status)) {
                    throw new Exception('Δεν επιλέχθηκε νέα κατάσταση!');
                }

                $placeholders = str_repeat('?,', count($accident_ids) - 1) . '?';
                $stmt = $db->prepare("UPDATE accidents SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)");
                $params = array_merge([$new_status], $accident_ids);
                $stmt->execute($params);
                $updated_count = $stmt->rowCount();

                logActivity($db, $current_user['id'], 'bulk_status_change', "Changed status to $new_status for " . count($accident_ids) . " accidents. Reason: $reason");
                break;

            case 'assign_expert':
                $expert_id = $_POST['expert_id'] ?? '';
                if (empty($expert_id)) {
                    throw new Exception('Δεν επιλέχθηκε εμπειρογνώμονας!');
                }

                foreach ($accident_ids as $accident_id) {
                    $stmt = $db->prepare("INSERT INTO accident_reviews (accident_id, expert_id, type, content, status, created_at) VALUES (?, ?, 'assignment', ?, 'pending', NOW())");
                    $content = "Ανατέθηκε από διαχειριστή. " . ($reason ? "Σχόλια: $reason" : "");
                    $stmt->execute([$accident_id, $expert_id, $content]);
                    $updated_count++;
                }

                logActivity($db, $current_user['id'], 'bulk_expert_assignment', "Assigned expert $expert_id to " . count($accident_ids) . " accidents");
                break;
            case 'export_selected':
                // For export, we'll redirect to the export script
                $db->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Η εξαγωγή θα ξεκινήσει σε λίγο...',
                    'redirect' => '../admin/export_accidents.php?ids=' . implode(',', $accident_ids)
                ]);
                exit;
                break;

            default:
                throw new Exception('Μη έγκυρη ενέργεια!');
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Η ενέργεια ολοκληρώθηκε επιτυχώς! Επηρεάστηκαν $updated_count εγγραφές.",
            'updated_count' => $updated_count
        ]);

    } catch(Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Σφάλμα: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>