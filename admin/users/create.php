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
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'registrar';

        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Παρακλώ συμπληρώστε όλα τα πεδία!']);
            exit;
        }

        if (strlen($username) < 3) {
            echo json_encode(['success' => false, 'message' => 'Το όνομα χρήστη πρέπει να είναι τουλάχιστον 3 χαρακτήρες!']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Το email δεν είναι σωστό!']);
            exit;
        }

        if (strlen($password) < 8) {
            echo json_encode(['success' => false, 'message' => 'Ο κωδικός πρόσβασης πρέπει να είναι τουλάχιστον 8 χαρακτήρες!']);
            exit;
        }

        if (!in_array($role, ['registrar', 'expert', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Ο ρόλος που επιλέξατε δεν είναι σωστός!']);
            exit;
        }

        // Check if user already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Το όνομα χρήστη ή το email υπάρχει ήδη!']);
            exit;
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password, role, is_active, created_at) 
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$username, $email, $hashed_password, $role]);

        $new_user_id = $db->lastInsertId();

        // Log activity
        logActivity($db, $current_user['id'], 'user_created', "Created new user: $username (Role: $role, ID: $new_user_id)");

        echo json_encode([
            'success' => true,
            'message' => 'Ο χρήστης δημιουργήθηκε επιτυχώς!',
            'user_id' => $new_user_id
        ]);

    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα βάσης δεδομένων: ' . $e->getMessage()]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>