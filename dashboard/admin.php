<?php
// dashboard/admin.php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($database);

// Require admin role
$auth->requireRole('admin');
$current_user = $auth->getCurrentUser();

// Get statistics
try {
    // Total users
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
    $stmt->execute();
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total accidents
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accidents");
    $stmt->execute();
    $total_accidents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Flagged records
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accidents WHERE status = 'flagged'");
    $stmt->execute();
    $flagged_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pending actions (reviews with no response)
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accident_reviews WHERE status = 'pending'");
    $stmt->execute();
    $pending_actions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Recent activity
    $stmt = $db->prepare("
        SELECT al.*, u.username 
        FROM activity_logs al 
        JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent users
    $stmt = $db->prepare("
        SELECT id, username, email, role, is_active, created_at, last_login 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent accidents
    $stmt = $db->prepare("
        SELECT a.id, a.accidentDate, a.location, a.status, u.username as registrar 
        FROM accidents a 
        JOIN users u ON a.user_id = u.id 
        ORDER BY a.created_at DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $recent_accidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error_message = "Σφάλμα κατά την ανάκτηση δεδομένων: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Πίνακας Ελέγχου Διαχειριστή - Σύστημα Καταγραφής Ατυχημάτων</title>
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

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #bdc3c7;
        }

        .navbar-user strong {
            color: #ecf0f1;
        }

        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .dashboard-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid #3498db;
        }

        .dashboard-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .dashboard-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-left: 4px solid transparent;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .stat-card-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-info h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-info p {
            color: #7f8c8d;
            font-size: 1rem;
            font-weight: 500;
        }

        .stat-icon {
            font-size: 3rem;
            opacity: 0.2;
        }

        .stat-users { border-left-color: #3498db; }
        .stat-users h3 { color: #3498db; }
        .stat-accidents { border-left-color: #27ae60; }
        .stat-accidents h3 { color: #27ae60; }
        .stat-flagged { border-left-color: #e74c3c; }
        .stat-flagged h3 { color: #e74c3c; }
        .stat-activity { border-left-color: #f39c12; }
        .stat-activity h3 { color: #f39c12; }

        .main-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            overflow-x: auto;
        }

        .tab-button {
            padding: 1.25rem 2rem;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 600;
            color: #7f8c8d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            white-space: nowrap;
            position: relative;
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
            min-height: 600px;
        }

        .tab-pane {
            display: none;
            padding: 2rem;
        }

        .tab-pane.active {
            display: block;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.875rem 1.75rem;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
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

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
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

        .action-links {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .action-links a,
        .action-links button {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .action-view {
            background-color: #3498db;
            color: white;
        }

        .action-edit {
            background-color: #f39c12;
            color: white;
        }

        .action-delete {
            background-color: #e74c3c;
            color: white;
        }

        .action-warn {
            background-color: #e67e22;
            color: white;
        }

        .action-suspend {
            background-color: #95a5a6;
            color: white;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .filter-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #bdc3c7;
            border-radius: 6px;
            font-size: 0.9rem;
            background-color: white;
            transition: border-color 0.3s ease;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(44, 62, 80, 0.8);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            margin: 5% auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 2rem 2rem 1rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #7f8c8d;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .close-modal:hover {
            background-color: #e74c3c;
            color: white;
        }

        .modal-body {
            padding: 2rem;
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

        .form-group select,
        .form-group textarea,
        .form-group input {
            width: 100%;
            padding: 0.875rem;
            border: 1px solid #bdc3c7;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group select:focus,
        .form-group textarea:focus,
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .modal-footer {
            padding: 1rem 2rem 2rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .alert {
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
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

        .chart-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2c3e50;
        }

        .chart-placeholder {
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 2px dashed #bdc3c7;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .filters {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .navbar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            .navbar-user {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }

            .dashboard-header h1 {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
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

            .action-buttons {
                flex-direction: column;
            }

            .filters {
                grid-template-columns: 1fr;
                padding: 1rem;
            }

            .modal-content {
                width: 95%;
                margin: 2% auto;
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 1rem;
            }

            .table-container {
                font-size: 0.85rem;
            }

            .table th,
            .table td {
                padding: 0.5rem;
            }

            .action-links {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .dashboard-header h1 {
                font-size: 1.75rem;
            }

            .stat-info h3 {
                font-size: 2rem;
            }

            .btn {
                padding: 0.75rem 1.25rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand">Σύστημα Καταγραφής Ατυχημάτων</div>
    <div class="navbar-user">
        <span>Καλώς ήρθατε, <strong><?php echo htmlspecialchars($current_user['username']); ?></strong></span>
        <span class="badge <?php echo getRoleBadgeClass($current_user['role']); ?>"><?php echo ucfirst($current_user['role']); ?></span>
        <a href="../auth/logout.php" class="btn btn-danger">Αποσύνδεση</a>
    </div>
</nav>

<div class="container">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="dashboard-header">
        <h1>Πίνακας Ελέγχου Διαχειριστή</h1>
        <p>Διαχείριση συστήματος, χρηστών και εποπτεία</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card stat-users">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $total_users ?? 0; ?></h3>
                    <p>Σύνολο Χρηστών</p>
                </div>
                <div class="stat-icon">👥</div>
            </div>
        </div>
        <div class="stat-card stat-accidents">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $total_accidents ?? 0; ?></h3>
                    <p>Σύνολο Ατυχημάτων</p>
                </div>
                <div class="stat-icon">🚗</div>
            </div>
        </div>
        <div class="stat-card stat-flagged">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $flagged_records ?? 0; ?></h3>
                    <p>Σημειωμένες Εγγραφές</p>
                </div>
                <div class="stat-icon">🚩</div>
            </div>
        </div>
        <div class="stat-card stat-activity">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $pending_actions ?? 0; ?></h3>
                    <p>Εκκρεμείς Ενέργειες</p>
                </div>
                <div class="stat-icon">⏳</div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="tabs">
            <button class="tab-button active" onclick="switchTab('overview')">Επισκόπηση</button>
            <button class="tab-button" onclick="switchTab('users')">Διαχείριση Χρηστών</button>
            <button class="tab-button" onclick="switchTab('accidents')">Εγγραφές Ατυχημάτων</button>
            <button class="tab-button" onclick="switchTab('reports')">Αναφορές & Στατιστικά</button>
            <button class="tab-button" onclick="switchTab('system')">Ρυθμίσεις Συστήματος</button>
        </div>

        <div class="tab-content">
            <div id="overview" class="tab-pane active">
                <div class="chart-container">
                    <div class="chart-title">Πρόσφατη Δραστηριότητα</div>
                    <div class="chart-placeholder">
                        Γράφημα καθημερινών υποβολών, αξιολογήσεων, σημειώσεων
                    </div>
                </div>

                <h3 style="margin-bottom: 1.5rem; color: #2c3e50;">Πρόσφατες Ενέργειες</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Χρόνος</th>
                            <th>Χρήστης</th>
                            <th>Ενέργεια</th>
                            <th>Λεπτομέρειες</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($recent_activities)): ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <tr>
                                    <td><?php echo timeAgo($activity['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['username']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #7f8c8d;">Δεν υπάρχουν πρόσφατες ενέργειες</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="users" class="tab-pane">
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="openModal('add-user-modal')">
                        + Προσθήκη Νέου Χρήστη
                    </button>
                    <button class="btn btn-success btn-outline">Εξαγωγή Λίστας Χρηστών</button>
                </div>

                <div class="filters">
                    <div class="filter-group">
                        <label>Ρόλος</label>
                        <select id="user-role-filter">
                            <option value="">Όλοι οι Ρόλοι</option>
                            <option value="registrar">Καταχωρητής</option>
                            <option value="expert">Εμπειρογνώμονας</option>
                            <option value="admin">Διαχειριστής</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Κατάσταση</label>
                        <select id="user-status-filter">
                            <option value="">Όλες οι Καταστάσεις</option>
                            <option value="1">Ενεργός</option>
                            <option value="0">Αναστολή</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Αναζήτηση</label>
                        <input type="text" id="user-search" placeholder="Αναζήτηση χρηστών...">
                    </div>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Όνομα Χρήστη</th>
                            <th>Email</th>
                            <th>Ρόλος</th>
                            <th>Κατάσταση</th>
                            <th>Εγγραφή</th>
                            <th>Τελευταία Σύνδεση</th>
                            <th>Ενέργειες</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($recent_users)): ?>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td>#<?php echo str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="badge <?php echo getRoleBadgeClass($user['role']); ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                    <td><span class="badge <?php echo $user['is_active'] ? 'badge-success' : 'badge-secondary'; ?>"><?php echo $user['is_active'] ? 'Ενεργός' : 'Αναστολή'; ?></span></td>
                                    <td><?php echo formatDate($user['created_at'], 'd/m/Y'); ?></td>
                                    <td><?php echo $user['last_login'] ? timeAgo($user['last_login']) : 'Ποτέ'; ?></td>
                                    <td>
                                        <div class="action-links">
                                            <a href="../admin/users/edit.php?id=<?php echo $user['id']; ?>" class="action-view">Προβολή</a>
                                            <a href="../admin/users/edit.php?id=<?php echo $user['id']; ?>" class="action-edit">Επεξεργασία</a>
                                            <?php if ($user['id'] != $current_user['id']): ?>
                                                <button class="action-warn" onclick="openActionModal('warn', '<?php echo htmlspecialchars($user['username']); ?>', <?php echo $user['id']; ?>)">Προειδοποίηση</button>
                                                <button class="action-suspend" onclick="openActionModal('suspend', '<?php echo htmlspecialchars($user['username']); ?>', <?php echo $user['id']; ?>)">Αναστολή</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #7f8c8d;">Δεν βρέθηκαν χρήστες</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="accidents" class="tab-pane">
                <div class="action-buttons">
                    <button class="btn btn-danger" onclick="deleteSelected()">Διαγραφή Επιλεγμένων</button>
                    <button class="btn btn-warning">Μαζικές Ενέργειες</button>
                    <button class="btn btn-primary">Εξαγωγή Δεδομένων</button>
                </div>

                <div class="filters">
                    <div class="filter-group">
                        <label>Κατάσταση</label>
                        <select id="accident-status-filter">
                            <option value="">Όλες οι Καταστάσεις</option>
                            <option value="draft">Πρόχειρο</option>
                            <option value="complete">Ολοκληρώθηκε</option>
                            <option value="flagged">Σημειωμένο</option>
                            <option value="under_review">Υπό Αξιολόγηση</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Ημερομηνία Από</label>
                        <input type="date" id="accident-date-from">
                    </div>
                    <div class="filter-group">
                        <label>Ημερομηνία Έως</label>
                        <input type="date" id="accident-date-to">
                    </div>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>ID</th>
                            <th>Ημερομηνία/Ώρα</th>
                            <th>Τοποθεσία</th>
                            <th>Καταχωρητής</th>
                            <th>Κατάσταση</th>
                            <th>Ενέργειες</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($recent_accidents)): ?>
                            <?php foreach ($recent_accidents as $accident): ?>
                                <tr>
                                    <td><input type="checkbox" name="accident-select" value="<?php echo $accident['id']; ?>"></td>
                                    <td>#<?php echo str_pad($accident['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo formatDate($accident['accidentDate'], 'd/m/Y H:i'); ?></td>
                                    <td><?php echo htmlspecialchars($accident['location'] ?? 'Δεν καθορίστηκε'); ?></td>
                                    <td><?php echo htmlspecialchars($accident['registrar']); ?></td>
                                    <td><span class="badge <?php echo getStatusBadgeClass($accident['status']); ?>"><?php echo ucfirst($accident['status']); ?></span></td>
                                    <td>
                                        <div class="action-links">
                                            <a href="../accidents/view.php?id=<?php echo $accident['id']; ?>" class="action-view">Προβολή</a>
                                            <a href="../accidents/edit.php?id=<?php echo $accident['id']; ?>" class="action-edit">Επεξεργασία</a>
                                            <button class="action-delete" onclick="confirmDelete('accident', <?php echo $accident['id']; ?>)">Διαγραφή</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #7f8c8d;">Δεν βρέθηκαν εγγραφές ατυχημάτων</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="reports" class="tab-pane">
                <div class="action-buttons">
                    <button class="btn btn-primary">Δημιουργία Αναφοράς</button>
                    <button class="btn btn-success">Προγραμματισμός Αναφοράς</button>
                </div>

                <div class="chart-container">
                    <div class="chart-title">Τάσεις Ατυχημάτων (Μηνιαίες)</div>
                    <div class="chart-placeholder">
                        Γραμμικό Γράφημα - Υποβολές ατυχημάτων ανά μήνα
                    </div>
                </div>

                <div class="chart-container">
                    <div class="chart-title">Δραστηριότητα Χρηστών</div>
                    <div class="chart-placeholder">
                        Στηλόγραμμα - Εγγραφές χρηστών και δραστηριότητα
                    </div>
                </div>
            </div>

            <div id="system" class="tab-pane">
                <h3 style="margin-bottom: 2rem; color: #2c3e50;">Διαμόρφωση Συστήματος</h3>

                <form id="system-settings-form">
                    <div class="form-group">
                        <label>Λειτουργία Συντήρησης Συστήματος</label>
                        <select name="maintenance_mode">
                            <option value="0">Απενεργοποιημένη</option>
                            <option value="1">Ενεργοποιημένη</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Προεπιλεγμένος Ρόλος Χρήστη</label>
                        <select name="default_role">
                            <option value="registrar">Καταχωρητής</option>
                            <option value="expert">Εμπειρογνώμονας</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Μέγιστο Μέγεθος Αρχείου (MB)</label>
                        <input type="number" name="max_file_size" value="10" min="1" max="100">
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn btn-success">Αποθήκευση Ρυθμίσεων</button>
                        <button type="button" class="btn btn-warning" onclick="backupDatabase()">Αντίγραφο Ασφαλείας</button>
                        <button type="button" class="btn btn-danger" onclick="clearCache()">Εκκαθάριση Cache</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="add-user-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Προσθήκη Νέου Χρήστη</h3>
            <button class="close-modal" onclick="closeModal('add-user-modal')">&times;</button>
        </div>

        <div class="modal-body">
            <form id="add-user-form">
                <div class="form-group">
                    <label for="new-username">Όνομα Χρήστη</label>
                    <input type="text" id="new-username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="new-email">Email</label>
                    <input type="email" id="new-email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="new-role">Ρόλος</label>
                    <select id="new-role" name="role" required>
                        <option value="registrar">Καταχωρητής</option>
                        <option value="expert">Εμπειρογνώμονας</option>
                        <option value="admin">Διαχειριστής</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="new-password">Προσωρινός Κωδικός</label>
                    <input type="password" id="new-password" name="password" required>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('add-user-modal')">Ακύρωση</button>
            <button type="submit" form="add-user-form" class="btn btn-primary">Προσθήκη Χρήστη</button>
        </div>
    </div>
</div>

<!-- Action Modal (Warn/Suspend) -->
<div id="action-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="action-modal-title">Ενέργεια Χρήστη</h3>
            <button class="close-modal" onclick="closeModal('action-modal')">&times;</button>
        </div>

        <div class="modal-body">
            <form id="action-form">
                <input type="hidden" id="action-type" name="action_type">
                <input type="hidden" id="target-user-id" name="target_user_id">
                <input type="hidden" id="target-user" name="target_user">

                <div class="form-group">
                    <label for="action-reason">Λόγος</label>
                    <textarea id="action-reason" name="reason" placeholder="Παρακαλώ παρέχετε λόγο για αυτή την ενέργεια..." required></textarea>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('action-modal')">Ακύρωση</button>
            <button type="submit" form="action-form" class="btn btn-danger" id="action-submit">Υποβολή</button>
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

    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        if (modalId === 'add-user-modal') {
            document.getElementById('add-user-form').reset();
        }
        if (modalId === 'action-modal') {
            document.getElementById('action-form').reset();
        }
    }

    function openActionModal(actionType, username, userId) {
        const modal = document.getElementById('action-modal');
        const title = document.getElementById('action-modal-title');
        const actionTypeField = document.getElementById('action-type');
        const targetUserField = document.getElementById('target-user');
        const targetUserIdField = document.getElementById('target-user-id');
        const submitBtn = document.getElementById('action-submit');

        actionTypeField.value = actionType;
        targetUserField.value = username;
        targetUserIdField.value = userId;

        if (actionType === 'warn') {
            title.textContent = 'Προειδοποίηση Χρήστη: ' + username;
            submitBtn.textContent = 'Αποστολή Προειδοποίησης';
            submitBtn.className = 'btn btn-warning';
        } else if (actionType === 'suspend') {
            title.textContent = 'Αναστολή Χρήστη: ' + username;
            submitBtn.textContent = 'Αναστολή Χρήστη';
            submitBtn.className = 'btn btn-danger';
        }

        openModal('action-modal');
    }

    function confirmDelete(type, id) {
        if (confirm(`Είστε σίγουροι ότι θέλετε να διαγράψετε αυτό το ${type}? Αυτή η ενέργεια δεν μπορεί να αναιρεθεί.`)) {
            window.location.href = `../admin/${type}/delete.php?id=${id}`;
        }
    }

    function deleteSelected() {
        const checkboxes = document.querySelectorAll('input[name="accident-select"]:checked');
        if (checkboxes.length === 0) {
            alert('Παρακαλώ επιλέξτε τουλάχιστον μία εγγραφή για διαγραφή.');
            return;
        }

        if (confirm(`Είστε σίγουροι ότι θέλετε να διαγράψετε ${checkboxes.length} εγγραφές? Αυτή η ενέργεια δεν μπορεί να αναιρεθεί.`)) {
            const ids = Array.from(checkboxes).map(cb => cb.value);
            // Implement bulk delete functionality
            console.log('Deleting accidents:', ids);
        }
    }

    function backupDatabase() {
        if (confirm('Θέλετε να δημιουργήσετε αντίγραφο ασφαλείας της βάσης δεδομένων;')) {
            window.location.href = '../admin/system/backup.php';
        }
    }

    function clearCache() {
        if (confirm('Θέλετε να εκκαθαρίσετε την cache του συστήματος;')) {
            // Implement cache clearing functionality
            alert('Η cache εκκαθαρίστηκε επιτυχώς!');
        }
    }

    // Select all checkbox functionality
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="accident-select"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Form submissions
    document.getElementById('add-user-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../admin/users/create.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Ο χρήστης προστέθηκε επιτυχώς!');
                    closeModal('add-user-modal');
                    location.reload();
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            })
            .catch(error => {
                alert('Παρουσιάστηκε σφάλμα κατά την προσθήκη του χρήστη.');
                console.error('Error:', error);
            });
    });

    document.getElementById('action-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const actionType = formData.get('action_type');

        const url = actionType === 'warn' ? '../admin/users/send_warning.php' : '../admin/users/suspend.php';

        fetch(url, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Η ενέργεια "${actionType}" ολοκληρώθηκε επιτυχώς!`);
                    closeModal('action-modal');
                    location.reload();
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            })
            .catch(error => {
                alert('Παρουσιάστηκε σφάλμα κατά την εκτέλεση της ενέργειας.');
                console.error('Error:', error);
            });
    });

    document.getElementById('system-settings-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../admin/system/settings.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Οι ρυθμίσεις αποθηκεύτηκαν επιτυχώς!');
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            })
            .catch(error => {
                alert('Παρουσιάστηκε σφάλμα κατά την αποθήκευση των ρυθμίσεων.');
                console.error('Error:', error);
            });
    });

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    // Auto-refresh stats every 30 seconds
    setInterval(function() {
        fetch('get_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('#total-users').textContent = data.total_users;
                    document.querySelector('#total-accidents').textContent = data.total_accidents;
                    document.querySelector('#flagged-records').textContent = data.flagged_records;
                    document.querySelector('#pending-actions').textContent = data.pending_actions;
                }
            })
            .catch(error => console.error('Error fetching stats:', error));
    }, 30000);
</script>
</body>
</html>