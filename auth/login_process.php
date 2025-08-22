<?php
// auth/login_process.php
// εδώ κάνουμε τη διαδικασία για την είσοδο του χρήστη (authentication).
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $auth = new Auth($database);

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Παρακαλώ συμπληρώστε όλα τα πεδία!']);
        exit;
    }

    $result = $auth->login($username, $password);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>