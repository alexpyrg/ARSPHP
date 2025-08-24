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

// Handle filters
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

try {
    // Build query with filters
    $where_conditions = [];
    $params = [];

    if (!empty($role_filter)) {
        $where_conditions[] = "role = ?";
        $params[] = $role_filter;
    }

    if ($status_filter !== '') {
        $where_conditions[] = "is_active = ?";
        $params[] = (int)$status_filter;
    }

    if (!empty($search)) {
        $where_conditions[] = "(username LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Get users with pagination
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = 20;
    $offset = ($page - 1) * $per_page;

    // Count total users
    $count_query = "SELECT COUNT(*) as total FROM users $where_clause";
    $stmt = $db->prepare($count_query);
    $stmt->execute($params);
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_users / $per_page);

    // Get users
    $query = "
        SELECT id, username, email, role, is_active, created_at, last_login,
               (SELECT COUNT(*) FROM accidents WHERE user_id = users.id) as accident_count,
               (SELECT COUNT(*) FROM accident_reviews WHERE expert_id = users.id) as review_count
        FROM users 
        $where_clause 
        ORDER BY created_at DESC 
        LIMIT $per_page OFFSET $offset
    ";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get system statistics
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
    $stmt->execute();
    $total_system_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
    $stmt->execute();
    $active_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $new_users_month = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

} catch(PDOException $e) {
    $error_message = "Σφάλμα κατά την ανάκτηση δεδομένων: " . $e->getMessage();
}

// Handle success/error messages
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'user_deleted':
            $success_message = 'Ο χρήστης διαγράφηκε επιτυχώς!';
            break;
        case 'user_created':
            $success_message = 'Ο χρήστης δημιουργήθηκε επιτυχώς!';
            break;
        case 'user_updated':
            $success_message = 'Ο χρήστης ενημερώθηκε επιτυχώς!';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'user_not_found':
            $error_message = 'Ο χρήστης δεν βρέθηκε!';
            break;
        case 'cannot_delete_self':
            $error_message = 'Δεν μπορείτε να διαγράψετε τον εαυτό σας!';
            break;
        case 'database_error':
            $error_message = 'Σφάλμα βάσης δεδομένων!';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση Χρηστών - Σύστημα Καταγραφής Ατυχημάτων</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-left: 4px solid transparent;
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

        .stat-total { border-left-color: #3498db; }
        .stat-total h3 { color: #3498db; }
        .stat-active { border-left-color: #27ae60; }
        .stat-active h3 { color: #27ae60; }
        .stat-new { border-left-color: #f39c12; }
        .stat-new h3 { color: #f39c12; }

        .filters {
            background: white;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
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
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
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
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-success {
            background-color: #27ae60;
            color: white;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
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

        .table-container {
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
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

        .action-links a {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
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

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            background: white;
            color: #3498db;
            text-decoration: none;
            border: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }

        .pagination a:hover {
            background-color: #3498db;
            color: white;
        }

        .pagination a.active {
            background-color: #3498db;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .filter-actions {
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
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="header">
        <h1>Διαχείριση Χρηστών</h1>
        <button class="btn btn-success" onclick="openModal('add-user-modal')">
            + Προσθήκη Χρήστη
        </button>
    </div>

    <div class="stats-grid">
        <div class="stat-card stat-total">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $total_system_users; ?></h3>
                    <p>Σύνολο Χρηστών</p>
                </div>
                <div class="stat-icon">👥</div>
            </div>
        </div>

        <div class="stat-card stat-active">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $active_users; ?></h3>
                    <p>Ενεργοί Χρήστες</p>
                </div>
                <div class="stat-icon">✅</div>
            </div>
        </div>

        <div class="stat-card stat-new">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $new_users_month; ?></h3>
                    <p>Νέοι (30 ημέρες)</p>
                </div>
                <div class="stat-icon">🆕</div>
            </div>
        </div>
    </div>

    <form method="GET" class="filters">
        <div class="filters-grid">
            <div class="filter-group">
                <label>Ρόλος</label>
                <select name="role">
                    <option value="">Όλοι οι Ρόλοι</option>
                    <option value="registrar" <?php echo $role_filter === 'registrar' ? 'selected' : ''; ?>>Καταχωρητής</option>
                    <option value="expert" <?php echo $role_filter === 'expert' ? 'selected' : ''; ?>>Εμπειρογνώμονας</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Διαχειριστής</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Κατάσταση</label>
                <select name="status">
                    <option value="">Όλες οι Καταστάσεις</option>
                    <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Ενεργός</option>
                    <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Αναστολή</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Αναζήτηση</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Όνομα ή email...">
            </div>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Εφαρμογή Φίλτρων</button>
            <a href="list.php" class="btn btn-secondary">Εκκαθάριση</a>
        </div>
    </form>

    <div class="table-container">
        <?php if (!empty($users)): ?>
            <table class="table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Χρήστης</th>
                    <th>Email</th>
                    <th>Ρόλος</th>
                    <th>Κατάσταση</th>
                    <th>Εγγραφές</th>
                    <th>Αξιολογήσεις</th>
                    <th>Εγγραφή</th>
                    <th>Τελευταία Σύνδεση</th>
                    <th>Ενέργειες</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>#<?php echo str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="badge <?php echo getRoleBadgeClass($user['role']); ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $user['is_active'] ? 'badge-success' : 'badge-secondary'; ?>">
                                <?php echo $user['is_active'] ? 'Ενεργός' : 'Αναστολή'; ?>
                            </span>
                        </td>
                        <td><?php echo $user['accident_count']; ?></td>
                        <td><?php echo $user['review_count']; ?></td>
                        <td><?php echo formatDate($user['created_at'], 'd/m/Y'); ?></td>
                        <td><?php echo $user['last_login'] ? timeAgo($user['last_login']) : 'Ποτέ'; ?></td>
                        <td>
                            <div class="action-links">
                                <a href="edit.php?id=<?php echo $user['id']; ?>" class="action-view">Προβολή</a>
                                <a href="edit.php?id=<?php echo $user['id']; ?>" class="action-edit">Επεξεργασία</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php echo $role_filter ? "&role=$role_filter" : ''; ?><?php echo $status_filter !== '' ? "&status=$status_filter" : ''; ?><?php echo $search ? "&search=" . urlencode($search) : ''; ?>">Πρώτη</a>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $role_filter ? "&role=$role_filter" : ''; ?><?php echo $status_filter !== '' ? "&status=$status_filter" : ''; ?><?php echo $search ? "&search=" . urlencode($search) : ''; ?>">Προηγούμενη</a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <a href="?page=<?php echo $i; ?><?php echo $role_filter ? "&role=$role_filter" : ''; ?><?php echo $status_filter !== '' ? "&status=$status_filter" : ''; ?><?php echo $search ? "&search=" . urlencode($search) : ''; ?>"
                           class="<?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $role_filter ? "&role=$role_filter" : ''; ?><?php echo $status_filter !== '' ? "&status=$status_filter" : ''; ?><?php echo $search ? "&search=" . urlencode($search) : ''; ?>">Επόμενη</a>
                        <a href="?page=<?php echo $total_pages; ?><?php echo $role_filter ? "&role=$role_filter" : ''; ?><?php echo $status_filter !== '' ? "&status=$status_filter" : ''; ?><?php echo $search ? "&search=" . urlencode($search) : ''; ?>">Τελευταία</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state">
                <div class="icon">👤</div>
                <h3>Δεν βρέθηκαν χρήστες</h3>
                <p>Δοκιμάστε να αλλάξετε τα φίλτρα αναζήτησης</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add User Modal -->
<div id="add-user-modal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(44, 62, 80, 0.8); z-index: 1000;">
    <div class="modal-content" style="background: white; width: 90%; max-width: 500px; margin: 5% auto; padding: 2rem; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid #e9ecef;">
            <h3 style="color: #2c3e50; font-size: 1.5rem; font-weight: 700;">Προσθήκη Νέου Χρήστη</h3>
            <button class="close-modal" onclick="closeModal('add-user-modal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #7f8c8d; padding: 0.5rem;">&times;</button>
        </div>

        <form id="add-user-form">
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Όνομα Χρήστη</label>
                <input type="text" name="username" required style="width: 100%; padding: 0.875rem; border: 1px solid #bdc3c7; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Email</label>
                <input type="email" name="email" required style="width: 100%; padding: 0.875rem; border: 1px solid #bdc3c7; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Ρόλος</label>
                <select name="role" required style="width: 100%; padding: 0.875rem; border: 1px solid #bdc3c7; font-size: 1rem;">
                    <option value="registrar">Καταχωρητής</option>
                    <option value="expert">Εμπειρογνώμονας</option>
                    <option value="admin">Διαχειριστής</option>
                </select>
            </div>

            <div style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">Προσωρινός Κωδικός</label>
                <input type="password" name="password" required style="width: 100%; padding: 0.875rem; border: 1px solid #bdc3c7; font-size: 1rem;">
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('add-user-modal')">Ακύρωση</button>
                <button type="submit" class="btn btn-success">Προσθήκη Χρήστη</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.getElementById('add-user-form').reset();
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    // Handle form submission
    document.getElementById('add-user-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('create.php', {
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
</script>
</body>
</html>