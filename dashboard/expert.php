<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($database);

// Require expert role
$auth->requireRole('expert');
$current_user = $auth->getCurrentUser();

// Get statistics
try {
    // Total accidents to review
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accidents WHERE status IN ('complete', 'under_review')");
    $stmt->execute();
    $total_for_review = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Flagged by me
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accidents WHERE status = 'flagged' AND flagged_by = ?");
    $stmt->bindParam(1, $current_user['id']);
    $stmt->execute();
    $flagged_by_me = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // My pending reviews
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accident_reviews WHERE expert_id = ? AND status = 'pending'");
    $stmt->bindParam(1, $current_user['id']);
    $stmt->execute();
    $pending_reviews = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Resolved this month
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM accident_reviews 
        WHERE expert_id = ? 
        AND status = 'resolved' 
        AND DATE_FORMAT(updated_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
    ");
    $stmt->bindParam(1, $current_user['id']);
    $stmt->execute();
    $resolved_this_month = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Recent accidents for review
    $stmt = $db->prepare("
        SELECT a.*, u.username as registrar 
        FROM accidents a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.status IN ('complete', 'under_review', 'flagged')
        ORDER BY a.created_at DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $recent_accidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // My recent reviews
    $stmt = $db->prepare("
        SELECT ar.*, a.caseNumber, a.accidentDate, u.username as registrar
        FROM accident_reviews ar
        JOIN accidents a ON ar.accident_id = a.id
        JOIN users u ON a.user_id = u.id
        WHERE ar.expert_id = ?
        ORDER BY ar.created_at DESC
        LIMIT 20
    ");
    $stmt->bindParam(1, $current_user['id']);
    $stmt->execute();
    $my_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error_message = "Σφάλμα κατά την ανάκτηση δεδομένων: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Πίνακας Ελέγχου Εμπειρογνώμονα - Σύστημα Καταγραφής Ατυχημάτων</title>
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
            background-color: #f39c12;
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
            color: #fff;
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #fff;
        }

        .navbar-user strong {
            color: #fff;
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
            border-bottom: 3px solid #f39c12;
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

        .stat-review { border-left-color: #3498db; }
        .stat-review h3 { color: #3498db; }
        .stat-flagged { border-left-color: #e74c3c; }
        .stat-flagged h3 { color: #e74c3c; }
        .stat-pending { border-left-color: #f39c12; }
        .stat-pending h3 { color: #f39c12; }
        .stat-resolved { border-left-color: #27ae60; }
        .stat-resolved h3 { color: #27ae60; }

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
            border-bottom-color: #f39c12;
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

        .action-flag {
            background-color: #e74c3c;
            color: white;
        }

        .action-question {
            background-color: #f39c12;
            color: white;
        }

        .action-note {
            background-color: #27ae60;
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
            border-color: #f39c12;
            box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.1);
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
            max-width: 600px;
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
            border-color: #f39c12;
            box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.1);
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

        .priority-high {
            background-color: #fff5f5;
            border-left: 4px solid #e74c3c;
        }

        .priority-medium {
            background-color: #fffbf0;
            border-left: 4px solid #f39c12;
        }

        .priority-low {
            background-color: #f0f9ff;
            border-left: 4px solid #3498db;
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
        <span class="badge badge-warning">Εμπειρογνώμονας</span>
        <a href="../auth/logout.php" class="btn btn-danger">Αποσύνδεση</a>
    </div>
</nav>

<div class="container">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="dashboard-header">
        <h1>Πίνακας Ελέγχου Εμπειρογνώμονα</h1>
        <p>Αξιολόγηση και επισκόπηση εγγραφών ατυχημάτων</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card stat-review">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $total_for_review ?? 0; ?></h3>
                    <p>Προς Αξιολόγηση</p>
                </div>
                <div class="stat-icon">📋</div>
            </div>
        </div>
        <div class="stat-card stat-flagged">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $flagged_by_me ?? 0; ?></h3>
                    <p>Σημειωμένες από Εμένα</p>
                </div>
                <div class="stat-icon">🚩</div>
            </div>
        </div>
        <div class="stat-card stat-pending">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $pending_reviews ?? 0; ?></h3>
                    <p>Εκκρεμείς Αξιολογήσεις</p>
                </div>
                <div class="stat-icon">⏳</div>
            </div>
        </div>
        <div class="stat-card stat-resolved">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $resolved_this_month ?? 0; ?></h3>
                    <p>Ολοκληρωμένες (Μήνας)</p>
                </div>
                <div class="stat-icon">✅</div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="tabs">
            <button class="tab-button active" onclick="switchTab('review-queue')">Ουρά Αξιολόγησης</button>
            <button class="tab-button" onclick="switchTab('my-reviews')">Οι Αξιολογήσεις Μου</button>
            <button class="tab-button" onclick="switchTab('flagged')">Σημειωμένες Εγγραφές</button>
            <button class="tab-button" onclick="switchTab('analytics')">Στατιστικά</button>
        </div>

        <div class="tab-content">
            <div id="review-queue" class="tab-pane active">
                <div class="action-buttons">
                    <button class="btn btn-warning" onclick="openModal('bulk-action-modal')">
                        Μαζικές Ενέργειες
                    </button>
                    <button class="btn btn-primary btn-outline">Εξαγωγή Λίστας</button>
                </div>

                <div class="filters">
                    <div class="filter-group">
                        <label>Κατάσταση</label>
                        <select id="status-filter">
                            <option value="">Όλες οι Καταστάσεις</option>
                            <option value="complete">Ολοκληρωμένες</option>
                            <option value="under_review">Υπό Αξιολόγηση</option>
                            <option value="flagged">Σημειωμένες</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Ημερομηνία Από</label>
                        <input type="date" id="date-from-filter">
                    </div>
                    <div class="filter-group">
                        <label>Ημερομηνία Έως</label>
                        <input type="date" id="date-to-filter">
                    </div>
                    <div class="filter-group">
                        <label>Προτεραιότητα</label>
                        <select id="priority-filter">
                            <option value="">Όλες</option>
                            <option value="high">Υψηλή</option>
                            <option value="medium">Μέση</option>
                            <option value="low">Χαμηλή</option>
                        </select>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>ID</th>
                            <th>Ημερομηνία Ατυχήματος</th>
                            <th>Τοποθεσία</th>
                            <th>Καταχωρητής</th>
                            <th>Κατάσταση</th>
                            <th>Προτεραιότητα</th>
                            <th>Ενέργειες</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($recent_accidents)): ?>
                            <?php foreach ($recent_accidents as $accident): ?>
                                <?php
                                // Determine priority based on accident characteristics
                                $priority = 'low';
                                $priority_class = 'priority-low';
                                if ($accident['accidentSeverity_id'] >= 3) {
                                    $priority = 'high';
                                    $priority_class = 'priority-high';
                                } elseif ($accident['accidentTotalVehicles'] > 2) {
                                    $priority = 'medium';
                                    $priority_class = 'priority-medium';
                                }
                                ?>
                                <tr class="<?php echo $priority_class; ?>">
                                    <td><input type="checkbox" name="accident-select" value="<?php echo $accident['id']; ?>"></td>
                                    <td>#<?php echo str_pad($accident['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo formatDate($accident['accidentDate'], 'd/m/Y H:i'); ?></td>
                                    <td><?php echo htmlspecialchars($accident['location'] ?? 'Δεν καθορίστηκε'); ?></td>
                                    <td><?php echo htmlspecialchars($accident['registrar']); ?></td>
                                    <td><span class="badge <?php echo getStatusBadgeClass($accident['status']); ?>"><?php echo ucfirst($accident['status']); ?></span></td>
                                    <td>
                                                <span class="badge <?php echo $priority === 'high' ? 'badge-danger' : ($priority === 'medium' ? 'badge-warning' : 'badge-info'); ?>">
                                                    <?php echo $priority === 'high' ? 'Υψηλή' : ($priority === 'medium' ? 'Μέση' : 'Χαμηλή'); ?>
                                                </span>
                                    </td>
                                    <td>
                                        <div class="action-links">
                                            <a href="../accidents/view.php?id=<?php echo $accident['id']; ?>" class="action-view">Προβολή</a>
                                            <button class="action-flag" onclick="flagAccident(<?php echo $accident['id']; ?>)">Σημείωση</button>
                                            <button class="action-question" onclick="askQuestion(<?php echo $accident['id']; ?>)">Ερώτηση</button>
                                            <button class="action-note" onclick="addNote(<?php echo $accident['id']; ?>)">Σημείωμα</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #7f8c8d;">Δεν βρέθηκαν εγγραφές προς αξιολόγηση</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="my-reviews" class="tab-pane">
                <div class="action-buttons">
                    <button class="btn btn-success">Εξαγωγή Αξιολογήσεων</button>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Ημερομηνία Αξιολόγησης</th>
                            <th>Ατύχημα</th>
                            <th>Είδος Αξιολόγησης</th>
                            <th>Κατάσταση</th>
                            <th>Απάντηση</th>
                            <th>Ενέργειες</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($my_reviews)): ?>
                            <?php foreach ($my_reviews as $review): ?>
                                <tr>
                                    <td><?php echo formatDate($review['created_at'], 'd/m/Y H:i'); ?></td>
                                    <td>
                                        <div>
                                            <strong>#<?php echo str_pad($review['accident_id'], 3, '0', STR_PAD_LEFT); ?></strong><br>
                                            <small><?php echo formatDate($review['accidentDate'], 'd/m/Y'); ?> - <?php echo htmlspecialchars($review['registrar']); ?></small>
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
                                    <td><?php echo $review['response'] ? 'Ναι' : 'Όχι'; ?></td>
                                    <td>
                                        <div class="action-links">
                                            <a href="../reviews/respond_to_review.php?id=<?php echo $review['id']; ?>" class="action-view">Προβολή</a>
                                            <?php if ($review['status'] === 'pending'): ?>
                                                <button class="action-note" onclick="markResolved(<?php echo $review['id']; ?>)">Ολοκλήρωση</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #7f8c8d;">Δεν έχετε κάνει αξιολογήσεις ακόμα</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="flagged" class="tab-pane">
                <p style="color: #7f8c8d; text-align: center; padding: 2rem;">Σημειωμένες εγγραφές από όλους τους εμπειρογνώμονες</p>
            </div>

            <div id="analytics" class="tab-pane">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <div class="chart-container">
                        <div class="chart-title">Αξιολογήσεις ανά Μήνα</div>
                        <div style="height: 200px; display: flex; align-items: center; justify-content: center; color: #7f8c8d; background-color: #f8f9fa; border-radius: 6px; border: 2px dashed #bdc3c7;">
                            Γράφημα στατιστικών
                        </div>
                    </div>

                    <div class="chart-container">
                        <div class="chart-title">Είδη Αξιολογήσεων</div>
                        <div style="height: 200px; display: flex; align-items: center; justify-content: center; color: #7f8c8d; background-color: #f8f9fa; border-radius: 6px; border: 2px dashed #bdc3c7;">
                            Διάγραμμα πίτας
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Modals -->
<div id="flag-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Σημείωση Εγγραφής</h3>
            <button class="close-modal" onclick="closeModal('flag-modal')">&times;</button>
        </div>

        <div class="modal-body">
            <form id="flag-form">
                <input type="hidden" id="flag-accident-id" name="accident_id">

                <div class="form-group">
                    <label for="flag-reason">Λόγος Σημείωσης</label>
                    <select id="flag-reason" name="reason" required>
                        <option value="">Επιλέξτε λόγο...</option>
                        <option value="incomplete_data">Ελλιπή Δεδομένα</option>
                        <option value="inconsistent_info">Ασύνεπες Πληροφορίες</option>
                        <option value="requires_investigation">Απαιτεί Περαιτέρω Διερεύνηση</option>
                        <option value="technical_issues">Τεχνικά Προβλήματα</option>
                        <option value="other">Άλλο</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="flag-comments">Σχόλια</label>
                    <textarea id="flag-comments" name="comments" placeholder="Περιγράψτε το πρόβλημα..." required></textarea>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('flag-modal')">Ακύρωση</button>
            <button type="submit" form="flag-form" class="btn btn-danger">Σημείωση</button>
        </div>
    </div>
</div>

<div id="question-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Υποβολή Ερώτησης</h3>
            <button class="close-modal" onclick="closeModal('question-modal')">&times;</button>
        </div>

        <div class="modal-body">
            <form id="question-form">
                <input type="hidden" id="question-accident-id" name="accident_id">

                <div class="form-group">
                    <label for="question-category">Κατηγορία Ερώτησης</label>
                    <select id="question-category" name="category" required>
                        <option value="">Επιλέξτε κατηγορία...</option>
                        <option value="vehicle_details">Λεπτομέρειες Οχήματος</option>
                        <option value="road_conditions">Συνθήκες Οδοστρώματος</option>
                        <option value="weather_factors">Καιρικοί Παράγοντες</option>
                        <option value="human_factors">Ανθρώπινοι Παράγοντες</option>
                        <option value="timeline">Χρονοδιάγραμμα Συμβάντων</option>
                        <option value="other">Άλλο</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="question-text">Ερώτηση</label>
                    <textarea id="question-text" name="question" placeholder="Τι θέλετε να ρωτήσετε σχετικά με αυτή την εγγραφή;" required></textarea>
                </div>

                <div class="form-group">
                    <label for="question-priority">Προτεραιότητα</label>
                    <select id="question-priority" name="priority" required>
                        <option value="low">Χαμηλή</option>
                        <option value="medium" selected>Μέση</option>
                        <option value="high">Υψηλή</option>
                    </select>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('question-modal')">Ακύρωση</button>
            <button type="submit" form="question-form" class="btn btn-warning">Υποβολή Ερώτησης</button>
        </div>
    </div>
</div>

<div id="note-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Προσθήκη Σημειώματος</h3>
            <button class="close-modal" onclick="closeModal('note-modal')">&times;</button>
        </div>

        <div class="modal-body">
            <form id="note-form">
                <input type="hidden" id="note-accident-id" name="accident_id">

                <div class="form-group">
                    <label for="note-type">Είδος Σημειώματος</label>
                    <select id="note-type" name="type" required>
                        <option value="">Επιλέξτε είδος...</option>
                        <option value="observation">Παρατήρηση</option>
                        <option value="recommendation">Σύσταση</option>
                        <option value="clarification">Διευκρίνιση</option>
                        <option value="analysis">Ανάλυση</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="note-content">Περιεχόμενο Σημειώματος</label>
                    <textarea id="note-content" name="content" placeholder="Γράψτε το σημείωμά σας..." required></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="note-confidential" name="confidential" value="1">
                        Εμπιστευτικό (μόνο για εμπειρογνώμονες)
                    </label>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('note-modal')">Ακύρωση</button>
            <button type="submit" form="note-form" class="btn btn-success">Αποθήκευση Σημειώματος</button>
        </div>
    </div>
</div>

<div id="bulk-action-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Μαζικές Ενέργειες</h3>
            <button class="close-modal" onclick="closeModal('bulk-action-modal')">&times;</button>
        </div>

        <div class="modal-body">
            <form id="bulk-action-form">
                <div class="form-group">
                    <label for="bulk-action">Ενέργεια</label>
                    <select id="bulk-action" name="action" required>
                        <option value="">Επιλέξτε ενέργεια...</option>
                        <option value="mark_reviewed">Σήμανση ως Αξιολογημένα</option>
                        <option value="assign_priority">Αλλαγή Προτεραιότητας</option>
                        <option value="add_tag">Προσθήκη Ετικέτας</option>
                    </select>
                </div>

                <div class="form-group" id="priority-group" style="display: none;">
                    <label for="bulk-priority">Νέα Προτεραιότητα</label>
                    <select id="bulk-priority" name="priority">
                        <option value="low">Χαμηλή</option>
                        <option value="medium">Μέση</option>
                        <option value="high">Υψηλή</option>
                    </select>
                </div>

                <div class="form-group" id="tag-group" style="display: none;">
                    <label for="bulk-tag">Ετικέτα</label>
                    <input type="text" id="bulk-tag" name="tag" placeholder="Εισάγετε ετικέτα...">
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('bulk-action-modal')">Ακύρωση</button>
            <button type="submit" form="bulk-action-form" class="btn btn-primary">Εφαρμογή</button>
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
        // Reset forms
        const form = document.querySelector(`#${modalId} form`);
        if (form) form.reset();
    }

    function flagAccident(accidentId) {
        document.getElementById('flag-accident-id').value = accidentId;
        openModal('flag-modal');
    }

    function askQuestion(accidentId) {
        document.getElementById('question-accident-id').value = accidentId;
        openModal('question-modal');
    }

    function addNote(accidentId) {
        document.getElementById('note-accident-id').value = accidentId;
        openModal('note-modal');
    }

    function markResolved(reviewId) {
        if (confirm('Είστε σίγουροι ότι θέλετε να σημειώσετε αυτή την αξιολόγηση ως ολοκληρωμένη;')) {
            fetch('../reviews/ajax/mark_resolved.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ review_id: reviewId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Η αξιολόγηση σημειώθηκε ως ολοκληρωμένη!');
                        location.reload();
                    } else {
                        alert('Σφάλμα: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Παρουσιάστηκε σφάλμα.');
                    console.error('Error:', error);
                });
        }
    }

    // Select all checkbox functionality
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="accident-select"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Bulk action form dynamic fields
    document.getElementById('bulk-action').addEventListener('change', function() {
        const priorityGroup = document.getElementById('priority-group');
        const tagGroup = document.getElementById('tag-group');

        priorityGroup.style.display = 'none';
        tagGroup.style.display = 'none';

        if (this.value === 'assign_priority') {
            priorityGroup.style.display = 'block';
        } else if (this.value === 'add_tag') {
            tagGroup.style.display = 'block';
        }
    });

    // Form submissions
    document.getElementById('flag-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../reviews/add_review.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Η εγγραφή σημειώθηκε επιτυχώς!');
                    closeModal('flag-modal');
                    location.reload();
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            })
            .catch(error => {
                alert('Παρουσιάστηκε σφάλμα κατά τη σημείωση.');
                console.error('Error:', error);
            });
    });

    document.getElementById('question-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('type', 'question');

        fetch('../reviews/add_review.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Η ερώτηση υποβλήθηκε επιτυχώς!');
                    closeModal('question-modal');
                    location.reload();
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            })
            .catch(error => {
                alert('Παρουσιάστηκε σφάλμα κατά την υποβολή της ερώτησης.');
                console.error('Error:', error);
            });
    });

    document.getElementById('note-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('type', 'note');

        fetch('../reviews/add_review.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Το σημείωμα αποθηκεύτηκε επιτυχώς!');
                    closeModal('note-modal');
                    location.reload();
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            })
            .catch(error => {
                alert('Παρουσιάστηκε σφάλμα κατά την αποθήκευση του σημειώματος.');
                console.error('Error:', error);
            });
    });

    document.getElementById('bulk-action-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const selectedCheckboxes = document.querySelectorAll('input[name="accident-select"]:checked');

        if (selectedCheckboxes.length === 0) {
            alert('Παρακαλώ επιλέξτε τουλάχιστον μία εγγραφή.');
            return;
        }

        const formData = new FormData(this);
        const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
        formData.append('accident_ids', JSON.stringify(selectedIds));

        fetch('../admin/bulk_actions.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Η μαζική ενέργεια ολοκληρώθηκε επιτυχώς!');
                    closeModal('bulk-action-modal');
                    location.reload();
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            })
            .catch(error => {
                alert('Παρουσιάστηκε σφάλμα κατά την εκτέλεση της μαζικής ενέργειας.');
                console.error('Error:', error);
            });
    });

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    // Auto-refresh stats every 60 seconds
    setInterval(function() {
        fetch('get_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update stats if needed
                }
            })
            .catch(error => console.error('Error fetching stats:', error));
    }, 60000);

    // Filter functionality
    document.querySelectorAll('#status-filter, #date-from-filter, #date-to-filter, #priority-filter').forEach(filter => {
        filter.addEventListener('change', applyFilters);
    });

    function applyFilters() {
        const status = document.getElementById('status-filter').value;
        const dateFrom = document.getElementById('date-from-filter').value;
        const dateTo = document.getElementById('date-to-filter').value;
        const priority = document.getElementById('priority-filter').value;

        const rows = document.querySelectorAll('.table tbody tr');

        rows.forEach(row => {
            let showRow = true;

            // Apply status filter
            if (status && !row.textContent.toLowerCase().includes(status.toLowerCase())) {
                showRow = false;
            }

            // Apply priority filter
            if (priority) {
                const priorityText = priority === 'high' ? 'Υψηλή' : (priority === 'medium' ? 'Μέση' : 'Χαμηλή');
                if (!row.textContent.includes(priorityText)) {
                    showRow = false;
                }
            }

            row.style.display = showRow ? '' : 'none';
        });
    }
</script>
</body>
</html>