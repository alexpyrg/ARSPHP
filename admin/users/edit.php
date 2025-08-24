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
    header("Location: ../dashboard/admin.php");
    exit();
}

try {
    // Get user details
    $stmt = $db->prepare("
        SELECT id, username, email, role, is_active, created_at, updated_at, last_login
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: ../dashboard/admin.php?error=user_not_found");
        exit();
    }

    // Get user statistics
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accidents WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_accidents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accidents WHERE user_id = ? AND status = 'complete'");
    $stmt->execute([$user_id]);
    $completed_accidents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accidents WHERE user_id = ? AND status = 'draft'");
    $stmt->execute([$user_id]);
    $draft_accidents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accident_reviews WHERE expert_id = ?");
    $stmt->execute([$user_id]);
    $expert_reviews = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get user's recent accidents
    $stmt = $db->prepare("
        SELECT id, caseNumber, accidentDate, location, status, created_at 
        FROM accidents 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $user_accidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get user's activity logs
    $stmt = $db->prepare("
        SELECT action, details, created_at 
        FROM activity_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $user_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get expert reviews if user is expert
    $expert_reviews_list = [];
    if ($user['role'] === 'expert') {
        $stmt = $db->prepare("
            SELECT ar.*, a.caseNumber, a.accidentDate 
            FROM accident_reviews ar
            JOIN accidents a ON ar.accident_id = a.id
            WHERE ar.expert_id = ?
            ORDER BY ar.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$user_id]);
        $expert_reviews_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch(PDOException $e) {
    $error_message = "Σφάλμα κατά την ανάκτηση δεδομένων: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        $db->beginTransaction();

        if ($action === 'update_user') {
            $username = sanitizeInput($_POST['username']);
            $email = sanitizeInput($_POST['email']);
            $role = $_POST['role'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Validate inputs
            if (empty($username) || empty($email)) {
                throw new Exception('Το όνομα χρήστη και το email είναι υποχρεωτικά!');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Το email δεν είναι έγκυρο!');
            }

            // Check if username/email already exists for other users
            $stmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $user_id]);
            if ($stmt->rowCount() > 0) {
                throw new Exception('Το όνομα χρήστη ή το email υπάρχει ήδη!');
            }

            // Update user
            $stmt = $db->prepare("
                UPDATE users 
                SET username = ?, email = ?, role = ?, is_active = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$username, $email, $role, $is_active, $user_id]);

            // Update password if provided
            if (!empty($_POST['new_password'])) {
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$new_password, $user_id]);
            }

            logActivity($db, $current_user['id'], 'user_updated', "Updated user: $username (ID: $user_id)");

            $db->commit();
            $success_message = "Ο χρήστης ενημερώθηκε επιτυχώς!";

            // Refresh user data
            $stmt = $db->prepare("SELECT id, username, email, role, is_active, created_at, updated_at, last_login FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

        } elseif ($action === 'send_warning') {
            $reason = sanitizeInput($_POST['warning_reason']);
            if (empty($reason)) {
                throw new Exception('Ο λόγος της προειδοποίησης είναι υποχρεωτικός!');
            }

            // In a real implementation, you would send an email notification
            // For now, we'll just log it
            logActivity($db, $current_user['id'], 'user_warning_sent', "Warning sent to user ID: $user_id - Reason: $reason");

            $db->commit();
            $success_message = "Η προειδοποίηση στάλθηκε επιτυχώς!";

        } elseif ($action === 'toggle_suspension') {
            $new_status = $user['is_active'] ? 0 : 1;
            $status_text = $new_status ? 'ενεργοποιήθηκε' : 'τέθηκε σε αναστολή';

            $stmt = $db->prepare("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);

            logActivity($db, $current_user['id'], 'user_status_changed', "User ID: $user_id $status_text");

            $db->commit();
            $success_message = "Ο χρήστης $status_text επιτυχώς!";

            // Refresh user data
            $user['is_active'] = $new_status;
        }

    } catch (Exception $e) {
        $db->rollBack();
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση Χρήστη: <?php echo htmlspecialchars($user['username']); ?> - Σύστημα Καταγραφής Ατυχημάτων</title>
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
        }

        .navbar {
            background-color: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ecf0f1;
        }

        .navbar-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .navbar-nav a {
            color: #bdc3c7;
            text-decoration: none;
            padding: 0.5rem 1rem;
            transition: color 0.3s ease;
        }

        .navbar-nav a:hover {
            color: #ecf0f1;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid #3498db;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 2rem;
            font-weight: 700;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .badge {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-danger { background-color: #e74c3c; color: white; }
        .badge-warning { background-color: #f39c12; color: white; }
        .badge-success { background-color: #27ae60; color: white; }
        .badge-primary { background-color: #3498db; color: white; }
        .badge-secondary { background-color: #95a5a6; color: white; }
        .badge-info { background-color: #17a2b8; color: white; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-left: 4px solid transparent;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .stat-card-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-info p {
            color: #7f8c8d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.2;
        }

        .stat-accidents { border-left-color: #3498db; }
        .stat-accidents h3 { color: #3498db; }
        .stat-completed { border-left-color: #27ae60; }
        .stat-completed h3 { color: #27ae60; }
        .stat-drafts { border-left-color: #95a5a6; }
        .stat-drafts h3 { color: #95a5a6; }
        .stat-reviews { border-left-color: #f39c12; }
        .stat-reviews h3 { color: #f39c12; }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
            align-items: start;
        }

        .content-panel {
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .sidebar-panel {
            background: white;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            overflow-x: auto;
        }

        .tab-button {
            padding: 1rem 2rem;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 600;
            color: #7f8c8d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .tab-button:hover {
            color: #2c3e50;
            background-color: #ecf0f1;
        }

        .tab-button.active {
            color: #2c3e50;
            border-bottom-color: #3498db;
            background-color: white;
        }

        .tab-content {
            min-height: 500px;
        }

        .tab-pane {
            display: none;
            padding: 2rem;
        }

        .tab-pane.active {
            display: block;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            font-size: 0.9rem;
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
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .btn-warning {
            background-color: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background-color: #e67e22;
        }

        .btn-success {
            background-color: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background-color: #229954;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid currentColor;
        }

        .btn-outline:hover {
            background-color: currentColor;
            color: white;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.875rem;
            border: 1px solid #bdc3c7;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-group input {
            width: auto;
        }

        .table-container {
            overflow-x: auto;
            border: 1px solid #e9ecef;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: background-color 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .alert {
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            border-left: 4px solid transparent;
        }

        .alert-success {
            background-color: #d5f4e6;
            color: #27ae60;
            border-left-color: #27ae60;
        }

        .alert-danger {
            background-color: #fadbd8;
            color: #e74c3c;
            border-left-color: #e74c3c;
        }

        .user-profile {
            text-align: center;
            margin-bottom: 2rem;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            background-color: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 auto 1rem;
        }

        .user-info h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .user-info p {
            color: #7f8c8d;
            margin-bottom: 0.25rem;
        }

        .action-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e9ecef;
        }

        .action-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .action-section h4 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .empty-state .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .sidebar-panel {
                order: -1;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .header-actions {
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .tabs {
                flex-direction: column;
            }

            .tab-button {
                padding: 1rem;
                text-align: center;
            }

            .tab-pane {
                padding: 1rem;
            }

            .action-buttons .btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand">Σύστημα Καταγραφής Ατυχημάτων</div>
    <div class="navbar-nav">
        <a href="../../dashboard/admin.php">Πίνακας Ελέγχου</a>
        <a href="../../auth/logout.php">Αποσύνδεση</a>
    </div>
</nav>

<div class="container">
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="header">
        <h1>Διαχείριση Χρήστη: <?php echo htmlspecialchars($user['username']); ?></h1>
        <div class="header-actions">
            <span class="badge <?php echo getRoleBadgeClass($user['role']); ?>"><?php echo ucfirst($user['role']); ?></span>
            <span class="badge <?php echo $user['is_active'] ? 'badge-success' : 'badge-secondary'; ?>">
                    <?php echo $user['is_active'] ? 'Ενεργός' : 'Αναστολή'; ?>
                </span>
        </div>
    </div>

    <?php if ($user['role'] === 'registrar' || $user['role'] === 'expert'): ?>
        <div class="stats-grid">
            <div class="stat-card stat-accidents">
                <div class="stat-card-content">
                    <div class="stat-info">
                        <h3><?php echo $total_accidents; ?></h3>
                        <p><?php echo $user['role'] === 'registrar' ? 'Σύνολο Εγγραφών' : 'Εξετασμένες Εγγραφές'; ?></p>
                    </div>
                    <div class="stat-icon"><?php echo $user['role'] === 'registrar' ? '📝' : '👁️'; ?></div>
                </div>
            </div>

            <?php if ($user['role'] === 'registrar'): ?>
                <div class="stat-card stat-completed">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <h3><?php echo $completed_accidents; ?></h3>
                            <p>Ολοκληρωμένες</p>
                        </div>
                        <div class="stat-icon">✅</div>
                    </div>
                </div>

                <div class="stat-card stat-drafts">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <h3><?php echo $draft_accidents; ?></h3>
                            <p>Πρόχειρες</p>
                        </div>
                        <div class="stat-icon">📋</div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($user['role'] === 'expert'): ?>
                <div class="stat-card stat-reviews">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <h3><?php echo $expert_reviews; ?></h3>
                            <p>Αξιολογήσεις</p>
                        </div>
                        <div class="stat-icon">🔍</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="main-content">
        <div class="content-panel">
            <div class="tabs">
                <button class="tab-button active" onclick="switchTab('profile')">Προφίλ Χρήστη</button>
                <button class="tab-button" onclick="switchTab('accidents')">Εγγραφές Ατυχημάτων</button>
                <?php if ($user['role'] === 'expert'): ?>
                    <button class="tab-button" onclick="switchTab('reviews')">Αξιολογήσεις</button>
                <?php endif; ?>
                <button class="tab-button" onclick="switchTab('activity')">Δραστηριότητα</button>
            </div>

            <div class="tab-content">
                <div id="profile" class="tab-pane active">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_user">

                        <div class="form-group">
                            <label for="username">Όνομα Χρήστη</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="role">Ρόλος</label>
                            <select id="role" name="role" required>
                                <option value="registrar" <?php echo $user['role'] === 'registrar' ? 'selected' : ''; ?>>Καταχωρητής</option>
                                <option value="expert" <?php echo $user['role'] === 'expert' ? 'selected' : ''; ?>>Εμπειρογνώμονας</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Διαχειριστής</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="new_password">Νέος Κωδικός Πρόσβασης (Προαιρετικό)</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Αφήστε κενό για να μην αλλάξει">
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                                <label for="is_active">Ενεργός Χρήστης</label>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">Αποθήκευση Αλλαγών</button>
                            <a href="../../dashboard/admin.php" class="btn btn-secondary btn-outline">Επιστροφή</a>
                        </div>
                    </form>
                </div>

                <div id="accidents" class="tab-pane">
                    <?php if (!empty($user_accidents)): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Αριθμός Υπόθεσης</th>
                                    <th>Ημερομηνία</th>
                                    <th>Τοποθεσία</th>
                                    <th>Κατάσταση</th>
                                    <th>Ενέργειες</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($user_accidents as $accident): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($accident['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($accident['caseNumber'] ?? 'Δεν καθορίστηκε'); ?></td>
                                        <td><?php echo formatDate($accident['accidentDate'], 'd/m/Y H:i'); ?></td>
                                        <td><?php echo htmlspecialchars($accident['location'] ?? 'Δεν καθορίστηκε'); ?></td>
                                        <td><span class="badge <?php echo getStatusBadgeClass($accident['status']); ?>"><?php echo ucfirst($accident['status']); ?></span></td>
                                        <td>
                                            <a href="../../accidents/view.php?id=<?php echo $accident['id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.8rem;">Προβολή</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="icon">📋</div>
                            <h3>Δεν υπάρχουν εγγραφές</h3>
                            <p>Ο χρήστης δεν έχει δημιουργήσει εγγραφές ατυχημάτων</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($user['role'] === 'expert'): ?>
                    <div id="reviews" class="tab-pane">
                        <?php if (!empty($expert_reviews_list)): ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>Ημερομηνία</th>
                                        <th>Ατύχημα</th>
                                        <th>Είδος</th>
                                        <th>Κατάσταση</th>
                                        <th>Ενέργειες</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($expert_reviews_list as $review): ?>
                                        <tr>
                                            <td><?php echo formatDate($review['created_at'], 'd/m/Y H:i'); ?></td>
                                            <td>
                                                <div>
                                                    <strong>#<?php echo str_pad($review['accident_id'], 3, '0', STR_PAD_LEFT); ?></strong><br>
                                                    <small><?php echo formatDate($review['accidentDate'], 'd/m/Y'); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                            <span class="badge <?php echo $review['type'] === 'flag' ? 'badge-danger' : ($review['type'] === 'question' ? 'badge-warning' : 'badge-info'); ?>">
                                                <?php echo $review['type'] === 'flag' ? 'Σημείωση' : ($review['type'] === 'question' ? 'Ερώτηση' : 'Σημείωμα'); ?>
                                            </span>
                                            </td>
                                            <td>
                                            <span class="badge <?php echo $review['status'] === 'resolved' ? 'badge-success' : ($review['status'] === 'answered' ? 'badge-info' : 'badge-warning'); ?>">
                                                <?php echo $review['status'] === 'resolved' ? 'Ολοκληρώθηκε' : ($review['status'] === 'answered' ? 'Απαντήθηκε' : 'Εκκρεμεί'); ?>
                                            </span>
                                            </td>
                                            <td>
                                                <a href="../../accidents/view.php?id=<?php echo $review['accident_id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.8rem;">Προβολή</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="icon">🔍</div>
                                <h3>Δεν υπάρχουν αξιολογήσεις</h3>
                                <p>Ο εμπειρογνώμονας δεν έχει κάνει αξιολογήσεις</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div id="activity" class="tab-pane">
                    <?php if (!empty($user_activities)): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Χρόνος</th>
                                    <th>Ενέργεια</th>
                                    <th>Λεπτομέρειες</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($user_activities as $activity): ?>
                                    <tr>
                                        <td><?php echo timeAgo($activity['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="icon">📈</div>
                            <h3>Δεν υπάρχει καταγεγραμμένη δραστηριότητα</h3>
                            <p>Δεν υπάρχουν εγγραφές δραστηριότητας για αυτόν τον χρήστη</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="sidebar-panel">
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Εγγραφή:</strong> <?php echo formatDate($user['created_at'], 'd/m/Y'); ?></p>
                    <p><strong>Τελευταία Σύνδεση:</strong> <?php echo $user['last_login'] ? timeAgo($user['last_login']) : 'Ποτέ'; ?></p>
                </div>
            </div>

            <?php if ($user['id'] != $current_user['id']): ?>
                <div class="action-section">
                    <h4>Ενέργειες Χρήστη</h4>
                    <div class="action-buttons">
                        <form method="POST" style="margin-bottom: 1rem;">
                            <input type="hidden" name="action" value="toggle_suspension">
                            <button type="submit" class="btn <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?>"
                                    onclick="return confirm('Είστε σίγουροι για αυτή την ενέργεια;')">
                                <?php echo $user['is_active'] ? '⏸️ Αναστολή' : '▶️ Ενεργοποίηση'; ?>
                            </button>
                        </form>

                        <button type="button" class="btn btn-warning btn-outline" onclick="showWarningForm()">
                            ⚠️ Αποστολή Προειδοποίησης
                        </button>

                        <a href="../../admin/users/delete.php?id=<?php echo $user['id']; ?>"
                           class="btn btn-danger btn-outline"
                           onclick="return confirm('ΠΡΟΣΟΧΗ: Αυτή η ενέργεια θα διαγράψει τον χρήστη και όλα τα δεδομένα του μόνιμα. Είστε απόλυτα σίγουροι;')">
                            🗑️ Διαγραφή Χρήστη
                        </a>
                    </div>
                </div>

                <div id="warning-form" style="display: none;">
                    <form method="POST">
                        <input type="hidden" name="action" value="send_warning">
                        <div class="form-group">
                            <label for="warning_reason">Λόγος Προειδοποίησης</label>
                            <textarea id="warning_reason" name="warning_reason" placeholder="Εισάγετε τον λόγο της προειδοποίησης..." required></textarea>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-warning">Αποστολή</button>
                            <button type="button" class="btn btn-secondary btn-outline" onclick="hideWarningForm()">Ακύρωση</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="action-section">
                <h4>Πληροφορίες Συστήματος</h4>
                <p style="font-size: 0.9rem; color: #7f8c8d; line-height: 1.4;">
                    <strong>ID Χρήστη:</strong> <?php echo $user['id']; ?><br>
                    <strong>Τελευταία Ενημέρωση:</strong> <?php echo $user['updated_at'] ? timeAgo($user['updated_at']) : 'Ποτέ'; ?><br>
                    <strong>Κατάσταση:</strong> <?php echo $user['is_active'] ? 'Ενεργός' : 'Σε Αναστολή'; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    function switchTab(tabName) {
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });

        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });

        document.getElementById(tabName).classList.add('active');
        event.target.classList.add('active');
    }

    function showWarningForm() {
        document.getElementById('warning-form').style.display = 'block';
    }

    function hideWarningForm() {
        document.getElementById('warning-form').style.display = 'none';
        document.getElementById('warning_reason').value = '';
    }

    // Form validation
    document.querySelector('form[action="update_user"]')?.addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();

        if (!username || username.length < 3) {
            alert('Το όνομα χρήστη πρέπει να είναι τουλάχιστον 3 χαρακτήρες!');
            e.preventDefault();
            return;
        }

        if (!email || !email.includes('@')) {
            alert('Παρακαλώ εισάγετε έγκυρο email!');
            e.preventDefault();
            return;
        }

        const newPassword = document.getElementById('new_password').value;
        if (newPassword && newPassword.length < 8) {
            alert('Ο νέος κωδικός πρέπει να είναι τουλάχιστον 8 χαρακτήρες!');
            e.preventDefault();
            return;
        }
    });

    // Auto-hide alerts after 5 seconds
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // Confirmation for critical actions
    document.querySelectorAll('form button[type="submit"]').forEach(button => {
        if (button.textContent.includes('Διαγραφή') || button.textContent.includes('Αναστολή')) {
            button.addEventListener('click', function(e) {
                const action = this.textContent.trim();
                if (!confirm(`Είστε σίγουροι ότι θέλετε να κάνετε την ενέργεια: "${action}";`)) {
                    e.preventDefault();
                }
            });
        }
    });
</script>
</body>
</html>