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
        $input = json_decode(file_get_contents('php://input'), true);
        $accident_ids = $input['accident_ids'] ?? [];

        if (empty($accident_ids)) {
            echo json_encode(['success' => false, 'message' => 'Δεν επιλέχθηκαν εγγραφές για διαγραφή!']);
            exit;
        }

        $db->beginTransaction();

        $deleted_count = 0;
        foreach ($accident_ids as $accident_id) {
            // Delete related data first
            $stmt = $db->prepare("DELETE FROM accident_reviews WHERE accident_id = ?");
            $stmt->execute([$accident_id]);

            $stmt = $db->prepare("DELETE FROM vehicles WHERE accident_id = ?");
            $stmt->execute([$accident_id]);

            $stmt = $db->prepare("DELETE FROM roads WHERE accident_id = ?");
            $stmt->execute([$accident_id]);

            // Delete the accident
            $stmt = $db->prepare("DELETE FROM accidents WHERE id = ?");
            $stmt->execute([$accident_id]);

            if ($stmt->rowCount() > 0) {
                $deleted_count++;
            }

            // Log the deletion
            logActivity($db, $current_user['id'], 'bulk_accident_delete', "Deleted accident ID: $accident_id");
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Διαγράφηκαν επιτυχώς $deleted_count εγγραφές",
            'deleted_count' => $deleted_count
        ]);

    } catch(Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Σφάλμα: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>