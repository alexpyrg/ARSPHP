<?php
// dashboard/registrar.php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($database);

// Require registrar role (or higher)
$auth->requireAuth();
$current_user = $auth->getCurrentUser();

// Get statistics for current user
try {
    // Total accidents created by this user
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accidents WHERE user_id = ?");
    $stmt->bindParam(1, $current_user['id']);
    $stmt->execute();
    $total_accidents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Draft accidents
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accidents WHERE user_id = ? AND status = 'draft'");
    $stmt->bindParam(1, $current_user['id']);
    $stmt->execute();
    $draft_accidents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Completed accidents
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accidents WHERE user_id = ? AND status = 'complete'");
    $stmt->bindParam(1, $current_user['id']);
    $stmt->execute();
    $completed_accidents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Flagged accidents (expert reviews)
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM accidents WHERE user_id = ? AND status = 'flagged'");
    $stmt->bindParam(1, $current_user['id']);
    $stmt->execute();
    $flagged_accidents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Recent accidents by this user
    $stmt = $db->prepare("
        SELECT id, caseNumber, accidentDate, location, status, created_at, updated_at
        FROM accidents 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->bindParam(1, $current_user['id']);
    $stmt->execute();
    $my_accidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent reviews on my accidents
    $stmt = $db->prepare("
        SELECT ar.*, a.caseNumber, a.accidentDate, u.username as expert_name
        FROM accident_reviews ar
        JOIN accidents a ON ar.accident_id = a.id
        JOIN users u ON ar.expert_id = u.id
        WHERE a.user_id = ?
        ORDER BY ar.created_at DESC
        LIMIT 10
    ");
    $stmt->bindParam(1, $current_user['id']);
    $stmt->execute();
    $recent_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pending questions that need answers
    $stmt = $db->prepare("
        SELECT ar.*, a.caseNumber, a.accidentDate, u.username as expert_name
        FROM accident_reviews ar
        JOIN accidents a ON ar.accident_id = a.id
        JOIN users u ON ar.expert_id = u.id
        WHERE a.user_id = ? AND ar.type = 'question' AND ar.status = 'pending'
        ORDER BY ar.created_at DESC
    ");
    $stmt->bindParam(1, $current_user['id']);
    $stmt->execute();
    $pending_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error_message = "Σφάλμα κατά την ανάκτηση δεδομένων: " . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Πίνακας Ελέγχου Καταχωρητή - Σύστημα Καταγραφής Ατυχημάτων</title>
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
            background-color: #3498db;
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

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .quick-action-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-top: 4px solid #3498db;
        }

        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .quick-action-card .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #3498db;
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

        .stat-total { border-left-color: #3498db; }
        .stat-total h3 { color: #3498db; }
        .stat-draft { border-left-color: #95a5a6; }
        .stat-draft h3 { color: #95a5a6; }
        .stat-complete { border-left-color: #27ae60; }
        .stat-complete h3 { color: #27ae60; }
        .stat-flagged { border-left-color: #e74c3c; }
        .stat-flagged h3 { color: #e74c3c; }

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

.action-continue {
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

.alert-warning {
background-color: #fff3cd;
color: #856404;
border-left-color: #ffc107;
}

.alert-info {
background-color: #d1ecf1;
color: #0c5460;
border-left-color: #17a2b8;
}

.empty-state {
text-align: center;
padding: 3rem;
color: #7f8c8d;
}

.empty-state .icon {
font-size: 4rem;
margin-bottom: 1rem;
opacity: 0.3;
}

.empty-state h3 {
margin-bottom: 1rem;
color: #95a5a6;
}

.progress-container {
background-color: #f8f9fa;
border-radius: 8px;
padding: 1.5rem;
margin-bottom: 2rem;
}

.progress-title {
font-weight: 600;
color: #2c3e50;
margin-bottom: 1rem;
}

.progress-bar {
background-color: #e9ecef;
border-radius: 10px;
height: 10px;
overflow: hidden;
}

.progress-fill {
background-color: #3498db;
height: 100%;
transition: width 0.3s ease;
}

.progress-text {
margin-top: 0.5rem;
font-size: 0.875rem;
color: #7f8c8d;
}

.notification-badge {
background-color: #e74c3c;
color: white;
border-radius: 50%;
padding: 0.25rem 0.5rem;
font-size: 0.75rem;
position: absolute;
top: -8px;
right: -8px;
min-width: 20px;
text-align: center;
}

.question-card {
background-color: #fff3cd;
border-left: 4px solid #ffc107;
padding: 1rem;
margin-bottom: 1rem;
border-radius: 6px;
}

.question-header {
display: flex;
justify-content: space-between;
align-items: flex-start;
margin-bottom: 0.5rem;
}

.question-meta {
font-size: 0.875rem;
color: #856404;
}

.question-content {
color: #856404;
margin-bottom: 1rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
.stats-grid,
.quick-actions {
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
        <span class="badge badge-primary">Καταχωρητής</span>
        <a href="../auth/logout.php" class="btn btn-danger">Αποσύνδεση</a>
    </div>
</nav>

<div class="container">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="dashboard-header">
        <h1>Πίνακας Ελέγχου Καταχωρητή</h1>
        <p>Δημιουργία και διαχείριση εγγραφών ατυχημάτων</p>
    </div>

    <?php if (!empty($pending_questions)): ?>
        <div class="alert alert-warning">
            <strong>Εκκρεμείς Ερωτήσεις:</strong> Έχετε <?php echo count($pending_questions); ?> ερωτήσεις από εμπειρογνώμονες που περιμένουν απάντηση.
            <a href="#" onclick="switchTab('questions')" style="color: #856404; text-decoration: underline;">Προβολή ερωτήσεων</a>
        </div>
    <?php endif; ?>

    <div class="quick-actions">
        <div class="quick-action-card">
            <div class="icon">📝</div>
            <h3>Νέο Ατύχημα</h3>
            <p>Δημιουργήστε μια νέα εγγραφή ατυχήματος με οδηγούμενη διαδικασία 3 βημάτων</p>
            <a href="../accidents/create.php" class="btn btn-primary btn-lg">Έναρξη Καταχώρησης</a>
        </div>

        <div class="quick-action-card">
            <div class="icon">📋</div>
            <h3>Συνέχεια Πρόχειρου</h3>
            <p>Συνεχίστε την επεξεργασία μιας μη ολοκληρωμένης εγγραφής</p>
            <?php if ($draft_accidents > 0): ?>
                <a href="#" onclick="switchTab('my-accidents')" class="btn btn-warning btn-lg">
                    Προβολή Πρόχειρων (<?php echo $draft_accidents; ?>)
                </a>
            <?php else: ?>
                <button class="btn btn-secondary btn-lg" disabled>Δεν Υπάρχουν Πρόχειρα</button>
            <?php endif; ?>
        </div>

        <div class="quick-action-card">
            <div class="icon">🔍</div>
            <h3>Οι Εγγραφές Μου</h3>
            <p>Προβολή και διαχείριση όλων των εγγραφών ατυχημάτων που έχετε δημιουργήσει</p>
            <a href="#" onclick="switchTab('my-accidents')" class="btn btn-primary btn-lg">Προβολή Όλων</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card stat-total">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $total_accidents ?? 0; ?></h3>
                    <p>Συνολικές Εγγραφές</p>
                </div>
                <div class="stat-icon">📊</div>
            </div>
        </div>
        <div class="stat-card stat-draft">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $draft_accidents ?? 0; ?></h3>
                    <p>Πρόχειρες</p>
                </div>
                <div class="stat-icon">📝</div>
            </div>
        </div>
        <div class="stat-card stat-complete">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $completed_accidents ?? 0; ?></h3>
                    <p>Ολοκληρωμένες</p>
                </div>
                <div class="stat-icon">✅</div>
            </div>
        </div>
        <div class="stat-card stat-flagged">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $flagged_accidents ?? 0; ?></h3>
                    <p>Σημειωμένες</p>
                </div>
                <div class="stat-icon">🚩</div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="tabs">
            <button class="tab-button active" onclick="switchTab('my-accidents')">Οι Εγγραφές Μου</button>
            <button class="tab-button" onclick="switchTab('reviews')">Αξιολογήσεις</button>
            <button class="tab-button" onclick="switchTab('questions')" style="position: relative;">
                Ερωτήσεις
                <?php if (!empty($pending_questions)): ?>
                    <span class="notification-badge"><?php echo count($pending_questions); ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-button" onclick="switchTab('analytics')">Στατιστικά</button>
        </div>

        <div class="tab-content">
            <div id="my-accidents" class="tab-pane active">
                <div class="filters">
                    <div class="filter-group">
                        <label>Κατάσταση</label>
                        <select id="status-filter">
                            <option value="">Όλες οι Καταστάσεις</option>
                            <option value="draft">Πρόχειρες</option>
                            <option value="complete">Ολοκληρωμένες</option>
                            <option value="flagged">Σημειωμένες</option>
                            <option value="under_review">Υπό Αξιολόγηση</option>
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
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Αριθμός Υπόθεσης</th>
                            <th>Ημερομηνία Ατυχήματος</th>
                            <th>Τοποθεσία</th>
                            <th>Κατάσταση</th>
                            <th>Τελευταία Ενημέρωση</th>
                            <th>Ενέργειες</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($my_accidents)): ?>
                            <?php foreach ($my_accidents as $accident): ?>
                                <tr>
                                    <td>#<?php echo str_pad($accident['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($accident['caseNumber'] ?? 'Δεν καθορίστηκε'); ?></td>
                                    <td><?php echo formatDate($accident['accidentDate'], 'd/m/Y H:i'); ?></td>
                                    <td><?php echo htmlspecialchars($accident['location'] ?? 'Δεν καθορίστηκε'); ?></td>
                                    <td><span class="badge <?php echo getStatusBadgeClass($accident['status']); ?>"><?php echo ucfirst($accident['status']); ?></span></td>
                                    <td><?php echo timeAgo($accident['updated_at']); ?></td>
                                    <td>
                                        <div class="action-links">
                                            <a href="../accidents/view.php?id=<?php echo $accident['id']; ?>" class="action-view">Προβολή</a>
                                            <?php if ($accident['status'] === 'draft'): ?>
                                                <a href="../accidents/create.php?continue=<?php echo $accident['id']; ?>" class="action-continue">Συνέχεια</a>
                                            <?php endif; ?>
                                            <?php if (canUserEditAccident($current_user['id'], $current_user['role'], $accident['user_id'])): ?>
                                                <a href="../accidents/edit.php?id=<?php echo $accident['id']; ?>" class="action-edit">Επεξεργασία</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="icon">📝</div>
                                        <h3>Δεν έχετε δημιουργήσει εγγραφές ακόμα</h3>
                                        <p>Ξεκινήστε δημιουργώντας την πρώτη σας εγγραφή ατυχήματος</p>
                                        <a href="../accidents/create.php" class="btn btn-primary">Νέα Εγγραφή</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="reviews" class="tab-pane">
                <h3 style="margin-bottom: 1.5rem; color: #2c3e50;">Αξιολογήσεις από Εμπειρογνώμονες</h3>

                <div class="table-container">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Ημερομηνία</th>
                            <th>Εμπειρογνώμονας</th>
                            <th>Ατύχημα</th>
                            <th>Είδος</th>
                            <th>Κατάσταση</th>
                            <th>Ενέργειες</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($recent_reviews)): ?>
                            <?php foreach ($recent_reviews as $review): ?>
                                <tr>
                                    <td><?php echo formatDate($review['created_at'], 'd/m/Y H:i'); ?></td>
                                    <td><?php echo htmlspecialchars($review['expert_name']); ?></td>
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
                                        <div class="action-links">
                                            <a href="../reviews/respond_to_review.php?id=<?php echo $review['id']; ?>" class="action-view">Προβολή</a>
                                            <?php if ($review['type'] === 'question' && $review['status'] === 'pending'): ?>
                                                <button class="action-edit" onclick="respondToQuestion(<?php echo $review['id']; ?>)">Απάντηση</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div class="icon">💬</div>
                                        <h3>Δεν υπάρχουν αξιολογήσεις</h3>
                                        <p>Οι εμπειρογνώμονες δεν έχουν κάνει ακόμα αξιολογήσεις στις εγγραφές σας</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="questions" class="tab-pane">
                <h3 style="margin-bottom: 1.5rem; color: #2c3e50;">Εκκρεμείς Ερωτήσεις</h3>

                <?php if (!empty($pending_questions)): ?>
                    <?php foreach ($pending_questions as $question): ?>
                        <div class="question-card">
                            <div class="question-header">
                                <div>
                                    <strong>Ατύχημα #<?php echo str_pad($question['accident_id'], 3, '0', STR_PAD_LEFT); ?></strong>
                                    <span class="badge badge-warning">Εκκρεμεί</span>
                                </div>
                                <div class="question-meta">
                                    <?php echo htmlspecialchars($question['expert_name']); ?> • <?php echo timeAgo($question['created_at']); ?>
                                </div>
                            </div>
                            <div class="question-content">
                                <?php echo htmlspecialchars($question['content']); ?>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="../accidents/view.php?id=<?php echo $question['accident_id']; ?>" class="btn btn-primary">Προβολή Ατυχήματος</a>
                                <button class="btn btn-success" onclick="respondToQuestion(<?php echo $question['id']; ?>)">Απάντηση</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="icon">❓</div>
                        <h3>Δεν υπάρχουν εκκρεμείς ερωτήσεις</h3>
                        <p>Όλες οι ερωτήσεις από εμπειρογνώμονες έχουν απαντηθεί</p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="analytics" class="tab-pane">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <div style="background: white; padding: 2rem; border-radius: 8px; border: 1px solid #e9ecef;">
                        <h4 style="margin-bottom: 1rem; color: #2c3e50;">Κατάσταση Εγγραφών</h4>
                        <div style="height: 200px; display: flex; align-items: center; justify-content: center; color: #7f8c8d; background-color: #f8f9fa; border-radius: 6px; border: 2px dashed #bdc3c7;">
                            Διάγραμμα πίτας
                        </div>
                    </div>
                </div>

                <div class="progress-container">
                    <div class="progress-title">Στόχος Μηνιαίων Εγγραφών</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min(100, ($completed_accidents ?? 0) * 10); ?>%"></div>
                    </div>
                    <div class="progress-text">
                        <?php echo $completed_accidents ?? 0; ?> από 10 ολοκληρωμένες εγγραφές αυτόν τον μήνα
                    </div>
                </div>
                <div class="progress-container">
                <h4 style="margin-bottom: 1rem; color: #2c3e50;">Πρόοδος Εγγραφών</h4>
                <div style="height: 200px; display: flex; align-items: center; justify-content: center; color: #7f8c8d; background-color: #f8f9fa; border-radius: 6px; border: 2px dashed #bdc3c7;">
                    Γράφημα προόδου
                </div>
                </div>
            </div>

            <div style="background: white;
            </div>
        </div>
    </div>
</div>

<!-- Response Modal -->
<div id="response-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Απάντηση σε Ερώτηση</h3>
            <button class="close-modal" onclick="closeModal('response-modal')">&times;</button>
        </div>

        <div class="modal-body">
            <div id="question-details" style="background-color: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                <strong>Ερώτηση:</strong>
                <div id="question-text"></div>
            </div>

            <form id="response-form">
                <input type="hidden" id="review-id" name="review_id">

                <div class="form-group">
                    <label for="response-text">Η Απάντησή Σας</label>
                    <textarea id="response-text" name="response" placeholder="Γράψτε την απάντησή σας..." required></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="needs-clarification" name="needs_clarification" value="1">
                        Απαιτεί περαιτέρω διευκρινίσεις
                    </label>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('response-modal')">Ακύρωση</button>
            <button type="submit" form="response-form" class="btn btn-primary">Αποστολή Απάντησης</button>
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

    function respondToQuestion(reviewId) {
        // In a real implementation, fetch the question details via AJAX
        document.getElementById('review-id').value = reviewId;
        document.getElementById('question-text').textContent = 'Φόρτωση ερώτησης...';

        // Fetch question details
        fetch(`../reviews/ajax/get_review_details.php?id=${reviewId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('question-text').textContent = data.content;
                } else {
                    document.getElementById('question-text').textContent = 'Σφάλμα φόρτωσης ερώτησης';
                }
            })
            .catch(error => {
                document.getElementById('question-text').textContent = 'Σφάλμα φόρτωσης ερώτησης';
                console.error('Error:', error);
            });

        openModal('response-modal');
    }

    function deleteAccident(accidentId) {
        if (confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την εγγραφή? Αυτή η ενέργεια δεν μπορεί να αναιρεθεί.')) {
            window.location.href = `../accidents/delete.php?id=${accidentId}`;
        }
    }

    // Form submission for response
    document.getElementById('response-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../reviews/respond_to_review.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Η απάντησή σας στάλθηκε επιτυχώς!');
                    closeModal('response-modal');
                    location.reload();
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            })
            .catch(error => {
                alert('Παρουσιάστηκε σφάλμα κατά την αποστολή της απάντησης.');
                console.error('Error:', error);
            });
    });

    // Filter functionality
    document.querySelectorAll('#status-filter, #date-from-filter, #date-to-filter').forEach(filter => {
        filter.addEventListener('change', applyFilters);
    });

    function applyFilters() {
        const status = document.getElementById('status-filter').value;
        const dateFrom = document.getElementById('date-from-filter').value;
        const dateTo = document.getElementById('date-to-filter').value;

        const rows = document.querySelectorAll('#my-accidents .table tbody tr');

        rows.forEach(row => {
            let showRow = true;

            // Skip empty state row
            if (row.cells.length === 1) return;

            // Apply status filter
            if (status && !row.textContent.toLowerCase().includes(status.toLowerCase())) {
                showRow = false;
            }

            // Apply date filters would require more complex parsing
            // This is a simplified version

            row.style.display = showRow ? '' : 'none';
        });
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    // Auto-save reminder for draft accidents
    <?php if ($draft_accidents > 0): ?>
    setInterval(function() {
        const draftNotification = document.createElement('div');
        draftNotification.className = 'alert alert-info';
        draftNotification.innerHTML = `
                <strong>Υπενθύμιση:</strong> Έχετε <?php echo $draft_accidents; ?> πρόχειρες εγγραφές.
                <a href="#" onclick="switchTab('my-accidents')" style="color: #0c5460; text-decoration: underline;">Ολοκληρώστε τες τώρα</a>
            `;

        // Show notification temporarily
        const container = document.querySelector('.container');
        container.insertBefore(draftNotification, container.firstChild);

        setTimeout(() => {
            draftNotification.remove();
        }, 5000);
    }, 300000); // Every 5 minutes
    <?php endif; ?>

    // Check for notifications on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Check if user has been away and returned to remind about drafts
        let lastActivity = localStorage.getItem('lastActivity');
        let now = Date.now();

        if (lastActivity && (now - lastActivity) > 3600000) { // 1 hour
            <?php if ($draft_accidents > 0): ?>
            setTimeout(() => {
                if (confirm('Καλώς επιστρέψατε! Έχετε πρόχειρες εγγραφές που περιμένουν ολοκλήρωση. Θέλετε να τις δείτε;')) {
                    switchTab('my-accidents');
                }
            }, 2000);
            <?php endif; ?>
        }

        localStorage.setItem('lastActivity', now);
    });

    // Update last activity timestamp
    document.addEventListener('click', function() {
        localStorage.setItem('lastActivity', Date.now());
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+N for new accident
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            window.location.href = '../accidents/create.php';
        }

        // Ctrl+M for my accidents
        if (e.ctrlKey && e.key === 'm') {
            e.preventDefault();
            switchTab('my-accidents');
        }
    });

    // Show keyboard shortcuts hint
    setTimeout(() => {
        const hint = document.createElement('div');
        hint.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #2c3e50;
                color: white;
                padding: 10px 15px;
                border-radius: 6px;
                font-size: 0.8rem;
                z-index: 1000;
                opacity: 0.8;
            `;
        hint.innerHTML = 'Συντομεύσεις: Ctrl+N (Νέο), Ctrl+M (Οι Εγγραφές Μου)';
        document.body.appendChild(hint);

        setTimeout(() => hint.remove(), 5000);
    }, 3000);
</script>
</body>
</html>



