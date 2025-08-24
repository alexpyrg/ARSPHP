<?php
// dashboard/admin.php - UPDATED VERSION
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
            border-bottom: 3px solid #e74c3c;
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

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .quick-action-card {
            background: white;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-top: 4px solid #e74c3c;
        }

        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .quick-action-card .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #e74c3c;
        }

        .quick-action-card h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .quick-action-card p {
            color: #7f8c8d;
            margin-bottom: 1.5rem;
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
            border-bottom-color: #e74c3c;
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

        .btn-lg {
            padding: 1.25rem 2.5rem;
            font-size: 1.1rem;
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

        .chart-container {
            background: white;
            padding: 2rem;
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
            border: 2px dashed #bdc3c7;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .stats-grid,
            .quick-actions {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

            .stats-grid,
            .quick-actions {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stat-card,
            .quick-action-card {
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
        <span class="badge badge-danger">Διαχειριστής</span>
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

    <!-- ADDED: Quick Actions for Admin including Accident Creation -->
    <div class="quick-actions">
        <div class="quick-action-card">
            <div class="icon">📝</div>
            <h3>Νέο Ατύχημα</h3>
            <p>Δημιουργήστε μια νέα εγγραφή ατυχήματος ως διαχειριστής</p>
            <a href="../accidents/create.php" class="btn btn-primary btn-lg">Έναρξη Καταχώρησης</a>
        </div>

        <div class="quick-action-card">
            <div class="icon">👥</div>
            <h3>Διαχείριση Χρηστών</h3>
            <p>Δημιουργία, επεξεργασία και διαχείριση χρηστών συστήματος</p>
            <a href="../admin/users/list.php" class="btn btn-primary btn-lg">Διαχείριση Χρηστών</a>
        </div>

        <div class="quick-action-card">
            <div class="icon">📊</div>
            <h3>Αναφορές & Στατιστικά</h3>
            <p>Δημιουργία αναφορών και προβολή στατιστικών συστήματος</p>
            <a href="../admin/reports/analytics.php" class="btn btn-primary btn-lg">Προβολή Αναφορών</a>
        </div>

        <div class="quick-action-card">
            <div class="icon">🔧</div>
            <h3>Ρυθμίσεις Συστήματος</h3>
            <p>Διαμόρφωση και συντήρηση συστήματος</p>
            <a href="../admin/system/settings.php" class="btn btn-primary btn-lg">Ρυθμίσεις</a>
        </div>
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
                    <a href="../admin/users/list.php" class="btn btn-primary">
                        👥 Πλήρης Διαχείριση Χρηστών
                    </a>
                    <button class="btn btn-success btn-outline">Εξαγωγή Λίστας Χρηστών</button>
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
                        </th>
                        </tr>
                        </thead>
                        <tbody>

            <div id="accidents" class="tab-pane">
                <div class="action-buttons">
                    <a href="../accidents/create.php" class="btn btn-success">📝 Νέο Ατύχημα</a>
                    <a href="../accidents/list.php" class="btn btn-primary">📋 Όλες οι Εγγραφές</a>
                    <button class="btn btn-warning">Μαζικές Ενέργειες</button>
                    <button class="btn btn-primary btn-outline">Εξαγωγή Δεδομένων</button>
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
                            <th>Ενέργειες
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
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Λειτουργία Συντήρησης Συστήματος</label>
                            <select name="maintenance_mode" style="width: 100%; padding: 0.875rem; border: 1px solid #bdc3c7; font-size: 1rem;">
                                <option value="0">Απενεργοποιημένη</option>
                                <option value="1">Ενεργοποιημένη</option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Προεπιλεγμένος Ρόλος Χρήστη</label>
                            <select name="default_role" style="width: 100%; padding: 0.875rem; border: 1px solid #bdc3c7; font-size: 1rem;">
                                <option value="registrar">Καταχωρητής</option>
                                <option value="expert">Εμπειρογνώμονας</option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Μέγιστο Μέγεθος Αρχείου (MB)</label>
                            <input type="number" name="max_file_size" value="10" min="1" max="100" style="width: 100%; padding: 0.875rem; border: 1px solid #bdc3c7; font-size: 1rem;">
                        </div>
                    </div>

                    <div class="action-buttons" style="margin-top: 2rem;">
                        <button type="submit" class="btn btn-success">Αποθήκευση Ρυθμίσεων</button>
                        <button type="button" class="btn btn-warning" onclick="backupDatabase()">Αντίγραφο Ασφαλείας</button>
                        <button type="button" class="btn btn-danger" onclick="clearCache()">Εκκαθάριση Cache</button>
                    </div>
                </form>
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

    function confirmDelete(type, id) {
        if (confirm(`Είστε σίγουροι ότι θέλετε να διαγράψετε αυτό το ${type}? Αυτή η ενέργεια δεν μπορεί να αναιρεθεί.`)) {
            window.location.href = `../admin/${type}/delete.php?id=${id}`;
        }
    }

    function backupDatabase() {
        if (confirm('Θέλετε να δημιουργήσετε αντίγραφο ασφαλείας της βάσης δεδομένων;')) {
            window.location.href = '../admin/system/backup.php';
        }
    }

    function clearCache() {
        if (confirm('Θέλετε να εκκαθαρίσετε την cache του συστήματος;')) {
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

    // Form submission for system settings
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

    // Auto-refresh stats every 60 seconds
    setInterval(function() {
        fetch('../dashboard/get_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update stats if needed
                }
            })
            .catch(error => console.error('Error fetching stats:', error));
    }, 60000);
</script>
</body>
</html>