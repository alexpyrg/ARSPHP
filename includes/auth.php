<?php
// Αρχείο: includes/auth.php
// Αυτό το αρχείο χρησιμοποιήται για τη σύνδεση & εγγραφή των χρηστών.
session_start();

class Auth {
    private $db;

    public function __construct($database) {
        $this->db = $database->getConnection();
    }

    public function register($username, $email, $password, $role = 'registrar') {
        try {
            // Check if user exists
            $query = "SELECT id FROM users WHERE username = :username OR email = :email";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Το όνομα χρήστη ή το email υπάρχνου ήδη!'];
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $query = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':role', $role);
            $stmt->execute();

            return ['success' => true, 'message' => 'Η εγγραφή χρήστη ολοκληρώθηκε με επιτυχία!'];

        } catch(PDOException $exception) {
            return ['success' => false, 'message' => 'Registration failed: ' . $exception->getMessage()];
        }
    }

    public function login($username, $password) {
        try {
            $query = "SELECT id, username, email, password, role, is_active FROM users WHERE username = :username";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user['is_active']) {
                    return ['success' => false, 'message' => 'Ο λογαριασμός είναι απενεργοποιημένος'];
                }

                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];

                    return ['success' => true, 'message' => 'Επιτυχής είσοδος', 'redirect' => $this->getDashboardUrl($user['role'])];
                } else {
                    return ['success' => false, 'message' => 'Λάθος κωδικός πρόσβασης'];
                }
            } else {
                return ['success' => false, 'message' => 'Ο χρήστης δεν βρέθηκε'];
            }

        } catch(PDOException $exception) {
            return ['success' => false, 'message' => 'Login failed: ' . $exception->getMessage()];
        }
    }

    public function logout() {
        session_destroy();
        header("Location: ../auth/login.php");
        exit();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header("Location: ../auth/login.php");
            exit();
        }
    }

    public function requireRole($required_role) {
        $this->requireAuth();
        $roles_hierarchy = ['registrar' => 1, 'expert' => 2, 'admin' => 3];
        $user_level = $roles_hierarchy[$_SESSION['role']] ?? 0;
        $required_level = $roles_hierarchy[$required_role] ?? 999;

        if ($user_level < $required_level) {
            header("Location: ../dashboard/" . $_SESSION['role'] . ".php");
            exit();
        }
    }

    private function getDashboardUrl($role) {
        return "../dashboard/{$role}.php";
    }

    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role']
            ];
        }
        return null;
    }
}