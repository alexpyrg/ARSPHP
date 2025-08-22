<?php
// Εδώ κάνουμε τη διαδικασία εγγραφής χρήστη.
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $auth = new Auth($database);

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'registrar';

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
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
        echo json_encode(['success' => false, 'message' => 'Ο κωδικός πρόσβασης πρέπει να ειναι τουλάχιστον 8 χαρακτήρες']);
        exit;
    }

    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Παρακαλώ επιβαιβεώστε ότι οι κωδικοί πρόσβασης ταιριάζουν!']);
        exit;
    }

    if (!in_array($role, ['registrar', 'expert', 'admin'])) {
        echo json_encode(['success' => false, 'message' => 'Ο ρόλος που επιλέξατε δεν είναι σωστός!']);
        exit;
    }

    $result = $auth->register($username, $email, $password, $role);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>