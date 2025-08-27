<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($database);

// Require admin role
$auth->requireRole('admin');
$current_user = $auth->getCurrentUser();

try {
    $selected_ids = $_GET['ids'] ?? '';
    $where_clause = '';
    $params = [];

    if (!empty($selected_ids)) {
        $ids_array = explode(',', $selected_ids);
        $placeholders = str_repeat('?,', count($ids_array) - 1) . '?';
        $where_clause = "WHERE a.id IN ($placeholders)";
        $params = $ids_array;
    }

    // Get accidents data
    $stmt = $db->prepare("
        SELECT a.*, u.username as registrar,
               (SELECT COUNT(*) FROM vehicles WHERE accident_id = a.id) as vehicle_count,
               (SELECT COUNT(*) FROM accident_reviews WHERE accident_id = a.id) as review_count
        FROM accidents a 
        JOIN users u ON a.user_id = u.id 
        $where_clause
        ORDER BY a.created_at DESC
    ");

    $stmt->execute($params);
    $accidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($accidents)) {
        header("Location: ../dashboard/admin.php?error=no_data_to_export");
        exit;
    }

    // Set headers for CSV download
    $filename = 'accidents_export_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    // Create output stream
    $output = fopen('php://output', 'w');

    // Add BOM for UTF-8 (for proper Greek characters in Excel)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // CSV Headers
    $headers = [
        'ID',
        'Αριθμός Υπόθεσης',
        'Ημερομηνία Ατυχήματος',
        'Ημέρα',
        'Τοποθεσία',
        'Γεωγραφικό Πλάτος',
        'Γεωγραφικό Μήκος',
        'Σοβαρότητα',
        'Συνολικά Οχήματα',
        'Περίληψη',
        'Καταχωρητής',
        'Κατάσταση',
        'Αριθμός Οχημάτων',
        'Αριθμός Αξιολογήσεων',
        'Ημερομηνία Δημιουργίας',
        'Τελευταία Ενημέρωση'
    ];

    fputcsv($output, $headers);

    // Data rows
    foreach ($accidents as $accident) {
        $row = [
            $accident['id'],
            $accident['caseNumber'] ?? '',
            $accident['accidentDate'] ? formatDate($accident['accidentDate'], 'd/m/Y H:i') : '',
            $accident['accidentDay'] ?? '',
            $accident['location'] ?? '',
            $accident['accidentLatitude'] ?? '',
            $accident['accidentLongitude'] ?? '',
            $accident['accidentSeverity_id'] ?? '',
            $accident['accidentTotalVehicles'] ?? '',
            $accident['accidentSynopsis'] ?? '',
            $accident['registrar'],
            $accident['status'],
            $accident['vehicle_count'],
            $accident['review_count'],
            formatDate($accident['created_at'], 'd/m/Y H:i'),
            $accident['updated_at'] ? formatDate($accident['updated_at'], 'd/m/Y H:i') : ''
        ];

        fputcsv($output, $row);
    }

    fclose($output);

    // Log the export
    $export_count = count($accidents);
    logActivity($db, $current_user['id'], 'accidents_exported', "Exported $export_count accidents to CSV");

} catch(Exception $e) {
    header("Location: ../dashboard/admin.php?error=export_failed");
    exit;
}
?>