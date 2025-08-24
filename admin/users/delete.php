<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($database);

// Require admin role
$auth->requireRole('admin');
$current_user = $auth->getCurrentUser();

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header("Location: ../../dashboard/admin.php");
    exit();
}

// Prevent admin from deleting themselves
if ($user_id == $current_user['id']) {
    header("Location: ../../dashboard/admin.php?error=cannot_delete_self");
    exit();
}

try {
    // Get user details
    $stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: ../../dashboard/admin.php?error=user_not_found");
        exit();
    }

    // Get user statistics for confirmation
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accidents WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_accidents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accident_reviews WHERE expert_id = ?");
    $stmt->execute([$user_id]);
    $user_reviews = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

} catch(PDOException $e) {
    header("Location: ../../dashboard/admin.php?error=database_error");
    exit();
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        $db->beginTransaction();

        // Delete user's accident reviews
        $stmt = $db->prepare("DELETE FROM accident_reviews WHERE expert_id = ?");
        $stmt->execute([$user_id]);

        // Delete user's vehicles
        $stmt = $db->prepare("DELETE FROM vehicles WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Delete user's roads
        $stmt = $db->prepare("DELETE FROM roads WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Delete user's accidents
        $stmt = $db->prepare("DELETE FROM accidents WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Delete user's activity logs
        $stmt = $db->prepare("DELETE FROM activity_logs WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Finally delete the user
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        // Log the deletion
        logActivity($db, $current_user['id'], 'user_deleted', "Deleted user: {$user['username']} (ID: {$user_id})");

        $db->commit();

        header("Location: ../../dashboard/admin.php?success=user_deleted");
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        $error_message = "Σφάλμα κατά τη διαγραφή του χρήστη: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαγραφή Χρήστη - Σύστημα Καταγραφής Ατυχημάτων</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #2c3e50;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: white;
            max-width: 600px;
            padding: 3rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            margin: 2rem 1rem;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #e74c3c;
        }

        .header .icon {
            font-size: 4rem;
            color: #e74c3c;
            margin-bottom: 1rem;
        }

        .header h1 {
            color: #e74c3c;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .user-info {
            background-color: #f8f9fa;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 4px solid #e74c3c;
        }

        .user-info h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .user-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.875rem;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-size: 1rem;
            color: #2c3e50;
            font-weight: 500;
        }

        .badge {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .badge-danger { background-color: #e74c3c; color: white; }
        .badge-warning { background-color: #f39c12; color: white; }
        .badge-primary { background-color: #3498db; color: white; }

        .warning-section {
            background-color: #fff5f5;
            border: 2px solid #fed7d7;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .warning-section h4 {
            color: #e74c3c;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
        }

        .warning-list {
            list-style: none;
            padding: 0;
        }

        .warning-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #fed7d7;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .warning-list li:last-child {
            border-bottom: none;
        }

        .warning-count {
            font-weight: 700;
            color: #e74c3c;
            font-size: 1.1rem;
        }

        .confirmation-section {
            margin-bottom: 2rem;
        }

        .confirmation-checkbox {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            margin-bottom: 1rem;
            transition: border-color 0.3s ease;
        }

        .confirmation-checkbox:hover {
            border-color: #e74c3c;
        }

        .confirmation-checkbox input {
            width: 20px;
            height: 20px;
        }

        .confirmation-checkbox label {
            color: #2c3e50;
            font-weight: 600;
            cursor: pointer;
            flex: 1;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .btn-danger:disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .alert {
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            border-left: 4px solid transparent;
        }

        .alert-danger {
            background-color: #fadbd8;
            color: #e74c3c;
            border-left-color: #e74c3c;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 2rem 1rem;
                margin: 1rem;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .user-details {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="header">
        <div class="icon">⚠️</div>
        <h1>Διαγραφή Χρήστη</h1>
        <p>Αυτή η ενέργεια δεν μπορεί να αναιρεθεί</p>
    </div>

    <div class="user-info">
        <h3>Στοιχεία Χρήστη προς Διαγραφή</h3>

        <div class="user-details">
            <div class="detail-item">
                <div class="detail-label">Όνομα Χρήστη</div>
                <div class="detail-value"><?php echo htmlspecialchars($user['username']); ?></div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Email</div>
                <div class="detail-value"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Ρόλος</div>
            <div class="detail-value">
                <span class="badge <?php echo getRoleBadgeClass($user['role']); ?>"><?php echo ucfirst($user['role']); ?></span>
            </div>
        </div>
    </div>

    <div class="warning-section">
        <h4>⚠️ ΠΡΟΣΟΧΗ: Τα ακόλουθα δεδομένα θα διαγραφούν ΜΟΝΙΜΑ</h4>

        <ul class="warning-list">
            <li>
                <span>Εγγραφές Ατυχημάτων</span>
                <span class="warning-count"><?php echo $user_accidents; ?></span>
            </li>
            <li>
                <span>Αξιολογήσεις Εμπειρογνώμονα</span>
                <span class="warning-count"><?php echo $user_reviews; ?></span>
            </li>
            <li>
                <span>Αρχεία και Εικόνες</span>
                <span class="warning-count">Όλα</span>
            </li>
            <li>
                <span>Ιστορικό Δραστηριότητας</span>
                <span class="warning-count">Όλα</span>
            </li>
            <li>
                <span>Λογαριασμός Χρήστη</span>
                <span class="warning-count">Μόνιμα</span>
            </li>
        </ul>
    </div>

    <form method="POST">
        <div class="confirmation-section">
            <div class="confirmation-checkbox">
                <input type="checkbox" id="confirm1" required>
                <label for="confirm1">Κατανοώ ότι αυτή η ενέργεια θα διαγράψει ΜΟΝΙΜΑ όλα τα δεδομένα του χρήστη</label>
            </div>

            <div class="confirmation-checkbox">
                <input type="checkbox" id="confirm2" required>
                <label for="confirm2">Κατανοώ ότι αυτή η ενέργεια ΔΕΝ ΜΠΟΡΕΙ να αναιρεθεί</label>
            </div>

            <div class="confirmation-checkbox">
                <input type="checkbox" id="confirm3" required>
                <label for="confirm3">Επιβεβαιώνω ότι θέλω να διαγράψω τον χρήστη <strong><?php echo htmlspecialchars($user['username']); ?></strong></label>
            </div>
        </div>

        <div class="action-buttons">
            <a href="../users/edit.php?id=<?php echo $user_id; ?>" class="btn btn-secondary">
                ← Ακύρωση
            </a>

            <button type="submit" name="confirm_delete" value="1" class="btn btn-danger" id="delete-btn" disabled>
                🗑️ Οριστική Διαγραφή
            </button>
        </div>
    </form>
</div>

<script>
    // Enable delete button only when all checkboxes are checked
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    const deleteBtn = document.getElementById('delete-btn');

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            deleteBtn.disabled = !allChecked;

            if (allChecked) {
                deleteBtn.style.opacity = '1';
            } else {
                deleteBtn.style.opacity = '0.6';
            }
        });
    });

    // Final confirmation before submission
    document.querySelector('form').addEventListener('submit', function(e) {
        const username = '<?php echo addslashes($user['username']); ?>';
        const userInput = prompt(`Για τελική επιβεβαίωση, παρακαλώ πληκτρολογήστε το όνομα χρήστη: ${username}`);

        if (userInput !== username) {
            e.preventDefault();
            alert('Το όνομα χρήστη δεν ταιριάζει. Η διαγραφή ακυρώθηκε.');
            return false;
        }

        // Double confirmation
        const finalConfirm = confirm(`ΤΕΛΙΚΗ ΠΡΟΕΙΔΟΠΟΙΗΣΗ: Θα διαγραφεί ΜΟΝΙΜΑ ο χρήστης "${username}" και ΟΛΑ τα δεδομένα του. Αυτή η ενέργεια ΔΕΝ ΜΠΟΡΕΙ να αναιρεθεί. Συνεχίζετε;`);

        if (!finalConfirm) {
            e.preventDefault();
            return false;
        }
    });

    // Auto-focus first checkbox
    document.getElementById('confirm1').focus();

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // ESC to cancel
        if (e.key === 'Escape') {
            window.location.href = '../users/edit.php?id=<?php echo $user_id; ?>';
        }
    });
</script>
</body>
</html>