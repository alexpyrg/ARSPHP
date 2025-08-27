<?php
// Εδώ κάνουμε όλη τη διαδικασία επεξεργασίας της φόρμας δημιουργίας και επεξεργασίας εγγραφής.
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

session_start();

// Check authentication
$database = new Database();
$auth = new Auth($database);
$auth->requireAuth();

$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        $action = $_POST['action'] ?? 'save';
        $accident_id = $_POST['accident_id'] ?? null;

        if ($action === 'save' || $action === 'submit') {
            // Process Accident Data
            $accident_data = [
                'user_id' => $user_id,
                'caseNumber' => sanitizeInput($_POST['caseNumber'] ?? ''),
                'accidentSeverity_id' => $_POST['accidentSeverity_id'] ?? null,
                'accidentAbandonedVictim_id' => $_POST['accidentAbandonedVictim_id'] ?? null,
                'accidentAlcohol_id' => $_POST['accidentAlcohol_id'] ?? null,
                'accidentDate' => $_POST['accidentDate'] ?? null,
                'accidentDay' => $_POST['accidentDay'] ?? null,
                'accidentExpertArrivalDate' => $_POST['accidentExpertArrivalDate'] ?? null,
                'accidentLongitude' => sanitizeInput($_POST['accidentLongitude'] ?? ''),
                'accidentLatitude' => sanitizeInput($_POST['accidentLatitude'] ?? ''),
                'accidentCase' => sanitizeInput($_POST['accidentCase'] ?? ''),
                'accidentNarcotics_id' => $_POST['accidentNarcotics_id'] ?? null,
                'accidentAnimalCollision_id' => $_POST['accidentAnimalCollision_id'] ?? null,
                'accidentEventsNumber' => $_POST['accidentEventsNumber'] ?? null,
                'accidentSynopsis' => sanitizeInput($_POST['accidentSynopsis'] ?? ''),
                'accidentEventSequence' => sanitizeInput($_POST['accidentEventSequence'] ?? ''),
                'accidentFirstCollisionEvent_id' => $_POST['accidentFirstCollisionEvent_id'] ?? null,
                'accidentMostHarmfulEvent_id' => $_POST['accidentMostHarmfulEvent_id'] ?? null,
                'accidentRelatedFactors' => sanitizeInput($_POST['accidentRelatedFactors'] ?? ''),
                'accidentTotalVehicles' => $_POST['accidentTotalVehicles'] ?? null,
                'accidentVehicleSedan' => $_POST['accidentVehicleSedan'] ?? 0,
                'accidentVehicleVan' => $_POST['accidentVehicleVan'] ?? 0,
                'accidentVehicleHatchback' => $_POST['accidentVehicleHatchback'] ?? 0,
                'accidentVehicleTruck' => $_POST['accidentVehicleTruck'] ?? 0,
                'accidentVehicleCaravan' => $_POST['accidentVehicleCaravan'] ?? 0,
                'accidentVehicleTrailer' => $_POST['accidentVehicleTrailer'] ?? 0,
                'accidentVehicleSport' => $_POST['accidentVehicleSport'] ?? 0,
                'accidentVehicleBus' => $_POST['accidentVehicleBus'] ?? 0,
                'accidentVehicleComercial' => $_POST['accidentVehicleComercial'] ?? 0,
                'accidentVehiclePickupTruck' => $_POST['accidentVehiclePickupTruck'] ?? 0,
                'accidentVehicleOffroad' => $_POST['accidentVehicleOffroad'] ?? 0,
                'accidentVehicleBike' => $_POST['accidentVehicleBike'] ?? 0,
                'accidentVehicleSuv' => $_POST['accidentVehicleSuv'] ?? 0,
                'accidentVehicleBicycle' => $_POST['accidentVehicleBicycle'] ?? 0,
                'accidentVehicleElectric' => $_POST['accidentVehicleElectric'] ?? 0,
                'accidentVehicleOtherTwoWheeler' => $_POST['accidentVehicleOtherTwoWheeler'] ?? 0,
                'accidentVehicleAutonomous' => $_POST['accidentVehicleAutonomous'] ?? 0,
                'accidentVehicleTricycle' => $_POST['accidentVehicleTricycle'] ?? 0,
                'accidentVehiclePedestrian' => $_POST['accidentVehiclePedestrian'] ?? 0,
                'accidentVehicleOther' => $_POST['accidentVehicleOther'] ?? 0,
                'accidentVehicleUnknown' => $_POST['accidentVehicleUnknown'] ?? 0,
                'status' => $action === 'submit' ? 'complete' : 'draft',
                'step_completed' => $_POST['current_step'] ?? 1,
                'location' => sanitizeInput($_POST['location'] ?? ''),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Validate required fields
            $validation_errors = validateAccidentData($accident_data);
            if (!empty($validation_errors)) {
                throw new Exception('Σφάλματα επικύρωσης: ' . implode(', ', $validation_errors));
            }

            if ($accident_id) {
                // Update existing accident
                $accident_id = updateAccident($db, $accident_id, $accident_data, $user_id);
            } else {
                // Create new accident
                $accident_id = createAccident($db, $accident_data);
            }

            // Process Vehicle Data if present
            if (isset($_POST['vehicles']) && is_array($_POST['vehicles'])) {
                processVehicles($db, $accident_id, $_POST['vehicles'], $user_id);
            }

            // Process Road Data if present
            if (isset($_POST['roadType_id']) || isset($_POST['roadSurface_id'])) {
                processRoadData($db, $accident_id, $_POST, $user_id);
            }

            // Handle file uploads
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $uploaded_images = handleImageUploads($_FILES['images'], $accident_id);
                if ($uploaded_images) {
                    updateRoadImages($db, $accident_id, $uploaded_images);
                }
            }

            $db->commit();

            // Log activity
            $action_text = $action === 'submit' ? 'submitted' : 'saved draft for';
            logActivity($db, $user_id, "Accident record {$action_text}", "Accident ID: {$accident_id}");

            // Determine redirect URL based on user role
            $redirect_url = '../dashboard/' . $_SESSION['role'] . '.php';
            if ($action === 'submit') {
                $redirect_url .= '?success=accident_submitted';
            } else {
                $redirect_url .= '?success=draft_saved';
            }

            echo json_encode([
                'success' => true,
                'message' => $action === 'submit' ? 'Η εγγραφή ατυχήματος υποβλήθηκε επιτυχώς!' : 'Το πρόχειρο αποθηκεύτηκε επιτυχώς!',
                'accident_id' => $accident_id,
                'redirect' => $redirect_url
            ]);

        } else {
            echo json_encode(['success' => false, 'message' => 'Μη έγκυρη ενέργεια']);
        }

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Accident form processing error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Παρουσιάστηκε σφάλμα κατά την επεξεργασία της φόρμας: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Μη έγκυρη μέθοδος αιτήματος']);
}

function createAccident($db, $data) {
    $fields = array_keys($data);
    $placeholders = ':' . implode(', :', $fields);

    $sql = "INSERT INTO accidents (" . implode(', ', $fields) . ") VALUES (" . $placeholders . ")";
    $stmt = $db->prepare($sql);

    foreach ($data as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }

    $stmt->execute();
    return $db->lastInsertId();
}

function updateAccident($db, $accident_id, $data, $user_id) {
    // Verify user owns this accident or is admin/expert
    if (!canUserEditAccident($user_id, $_SESSION['role'], getAccidentUserId($db, $accident_id))) {
        throw new Exception('Δεν έχετε δικαίωματα για να αλλάξετε αυτή την εγγραφή!');
    }

    unset($data['user_id']); // Don't update user_id
    unset($data['created_at']); // Don't update created_at
    $data['updated_at'] = date('Y-m-d H:i:s');

    $set_clauses = [];
    foreach (array_keys($data) as $key) {
        $set_clauses[] = "{$key} = :{$key}";
    }

    $sql = "UPDATE accidents SET " . implode(', ', $set_clauses) . " WHERE id = :accident_id";
    $stmt = $db->prepare($sql);

    foreach ($data as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }
    $stmt->bindValue(':accident_id', $accident_id);

    $stmt->execute();
    return $accident_id;
}

function processVehicles($db, $accident_id, $vehicles_data, $user_id) {
    // First, delete existing vehicles for this accident
    $stmt = $db->prepare("DELETE FROM vehicles WHERE accident_id = ?");
    $stmt->execute([$accident_id]);

    foreach ($vehicles_data as $vehicle_data) {
        if (empty($vehicle_data['vehicleType_id'])) continue; // Skip empty vehicles

        $vehicle_data['accident_id'] = $accident_id;
        $vehicle_data['user_id'] = $user_id;
        $vehicle_data['created_at'] = date('Y-m-d H:i:s');
        $vehicle_data['updated_at'] = date('Y-m-d H:i:s');

        // Clean and validate vehicle data
        $clean_vehicle_data = [];
        $vehicle_fields = [
            'accident_id', 'user_id', 'vehicleLicensePlate', 'vehicleColor_id', 'vehicleType_id',
            'vehicleManufacturer_id', 'vehicleModel_id', 'vehicleWheelDrive_id', 'vehicleDrivePosition_id',
            'vehicleLength', 'vehicleWidth', 'vehicleEnginePower', 'vehicleManufactureDate', 'vehicleTare', 'vehicleAxles',
            'vehicleGeneralComments', 'vehicleOccupantsNumber', 'vehicleDamagePossibleFactor_id',
            'vehicleDPFComments', 'vehicleInspected_id', 'vehicleSwerved_id', 'vehicleDangerousCargo_id',
            'vehicleScatteredDangerousCargo_id', 'vehicleCollisions', 'vehicleOnFire_id',
            'vehicleFirefightingEquipmentUsed_id', 'ABS_id', 'ESP_id', 'TCS_id',
            'vehicleElectronicsComments', 'created_at', 'updated_at'
        ];

        foreach ($vehicle_fields as $field) {
            $clean_vehicle_data[$field] = isset($vehicle_data[$field]) ?
                (is_string($vehicle_data[$field]) ? sanitizeInput($vehicle_data[$field]) : $vehicle_data[$field]) : null;
        }

        // Validate vehicle data
        $vehicle_errors = validateVehicleData($clean_vehicle_data);
        if (!empty($vehicle_errors)) {
            throw new Exception('Σφάλματα οχήματος: ' . implode(', ', $vehicle_errors));
        }

        $fields = array_keys($clean_vehicle_data);
        $placeholders = ':' . implode(', :', $fields);

        $sql = "INSERT INTO vehicles (" . implode(', ', $fields) . ") VALUES (" . $placeholders . ")";
        $stmt = $db->prepare($sql);

        foreach ($clean_vehicle_data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->execute();
    }
}

function processRoadData($db, $accident_id, $post_data, $user_id) {
    // First, delete existing road data for this accident
    $stmt = $db->prepare("DELETE FROM roads WHERE accident_id = ?");
    $stmt->execute([$accident_id]);

    $road_data = [
        'accident_id' => $accident_id,
        'user_id' => $user_id,
        'roadTrafficwayFlow_id' => $post_data['roadTrafficwayFlow_id'] ?? null,
        'roadLaneNumber' => $post_data['roadLaneNumber'] ?? null,
        'roadType_id' => $post_data['roadType_id'] ?? null,
        'roadSpeedLimit' => $post_data['roadSpeedLimit'] ?? null,
        'roadSpeedLimitType_id' => $post_data['roadSpeedLimitType_id'] ?? null,
        'roadJunction_id' => $post_data['roadJunction_id'] ?? null,
        'roadLocalArea_id' => $post_data['roadLocalArea_id'] ?? null,
        'roadAlignment_id' => $post_data['roadAlignment_id'] ?? null,
        'roadConstructionZone_id' => $post_data['roadConstructionZone_id'] ?? null,
        'roadTrafficSigns_id' => $post_data['roadTrafficSigns_id'] ?? null,
        'roadTrafficSignalDeviceFunctioning_id' => $post_data['roadTrafficSignalDeviceFunctioning_id'] ?? null,
        'roadSurface_id' => $post_data['roadSurface_id'] ?? null,
        'roadPedestrianFacility_id' => $post_data['roadPedestrianFacility_id'] ?? null,
        'roadCycleFacility_id' => $post_data['roadCycleFacility_id'] ?? null,
        'roadLightConditions_id' => $post_data['roadLightConditions_id'] ?? null,
        'roadWeatherConditions_id' => $post_data['roadWeatherConditions_id'] ?? null,
        'roadStrongWinds_id' => $post_data['roadStrongWinds_id'] ?? null,
        'roadFog_id' => $post_data['roadFog_id'] ?? null,
        'roadConditionComments' => sanitizeInput($post_data['roadConditionComments'] ?? ''),
        'roadPollutants_id' => $post_data['roadPollutants_id'] ?? null,
        'roadTransientConstraints_id' => $post_data['roadTransientConstraints_id'] ?? null,
        'roadAccidentRelatedSignaling_id' => $post_data['roadAccidentRelatedSignaling_id'] ?? null,
        'roadSignalingFactors_id' => $post_data['roadSignalingFactors_id'] ?? null,
        'roadSpeedLimitingFacility_id' => $post_data['roadSpeedLimitingFacility_id'] ?? null,
        'roadSLIContributedToCollision_id' => $post_data['roadSLIContributedToCollision_id'] ?? null,
        'roadPossibleFactorsComments' => sanitizeInput($post_data['roadPossibleFactorsComments'] ?? ''),
        'roadOtherComments' => sanitizeInput($post_data['roadOtherComments'] ?? ''),
        'roadInformationSource_id' => $post_data['roadInformationSource_id'] ?? null,
        'roadISTrustLevel_id' => $post_data['roadISTrustLevel_id'] ?? null,
        'roadISTLDescription' => sanitizeInput($post_data['roadISTLDescription'] ?? ''),
        'roadInvestigationMethod_id' => $post_data['roadInvestigationMethod_id'] ?? null,
        'roadIMTrustLevel_id' => $post_data['roadIMTrustLevel_id'] ?? null,
        'roadIMTLDescription' => sanitizeInput($post_data['roadIMTLDescription'] ?? ''),
        'images' => null, // Will be updated separately
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Validate road data
    $road_errors = validateRoadData($road_data);
    if (!empty($road_errors)) {
        throw new Exception('Σφάλματα οδοστρώματος: ' . implode(', ', $road_errors));
    }

    $fields = array_keys($road_data);
    $placeholders = ':' . implode(', :', $fields);

    $sql = "INSERT INTO roads (" . implode(', ', $fields) . ") VALUES (" . $placeholders . ")";
    $stmt = $db->prepare($sql);

    foreach ($road_data as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }

    $stmt->execute();
}

function handleImageUploads($files, $accident_id) {
    $upload_dir = "../uploads/accidents/{$accident_id}/";

    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Αδυναμία δημιουργίας φακέλου για αρχεία');
        }
    }

    $uploaded_files = [];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $file_type = $files['type'][$i];
            $file_size = $files['size'][$i];
            $original_name = $files['name'][$i];

            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("Το αρχείο '{$original_name}' δεν είναι υποστηριζόμενος τύπος εικόνας");
            }

            if ($file_size > $max_size) {
                throw new Exception("Το αρχείο '{$original_name}' είναι πολύ μεγάλο (μέγιστο: 5MB)");
            }

            $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
            $new_filename = uniqid('img_') . '.' . strtolower($file_extension);
            $file_path = $upload_dir . $new_filename;

            if (move_uploaded_file($files['tmp_name'][$i], $file_path)) {
                $uploaded_files[] = "accidents/{$accident_id}/{$new_filename}";
            } else {
                throw new Exception("Αποτυχία μεταφοράς αρχείου: {$original_name}");
            }
        } elseif ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
            throw new Exception("Σφάλμα μεταφόρτωσης αρχείου: " . $files['error'][$i]);
        }
    }

    return $uploaded_files;
}

function updateRoadImages($db, $accident_id, $image_paths) {
    $images_json = json_encode($image_paths);
    $stmt = $db->prepare("UPDATE roads SET images = ? WHERE accident_id = ?");
    $stmt->execute([$images_json, $accident_id]);
}

function getAccidentUserId($db, $accident_id) {
    $stmt = $db->prepare("SELECT user_id FROM accidents WHERE id = ?");
    $stmt->execute([$accident_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['user_id'] : null;
}

// Validation functions
function validateAccidentData($data) {
    $errors = [];

    if (empty($data['caseNumber'])) {
        $errors[] = 'Ο αριθμός υπόθεσης είναι υποχρεωτικός';
    }

    if (empty($data['accidentDate'])) {
        $errors[] = 'Η ημερομηνία ατυχήματος είναι υποχρεωτική';
    }

    if (empty($data['accidentDay'])) {
        $errors[] = 'Η ημέρα εβδομάδας είναι υποχρεωτική';
    }

    if (empty($data['location'])) {
        $errors[] = 'Η τοποθεσία είναι υποχρεωτική';
    } elseif (strlen($data['location']) < 10) {
        $errors[] = 'Η περιγραφή τοποθεσίας πρέπει να είναι τουλάχιστον 10 χαρακτήρες';
    }

    if (empty($data['accidentSeverity_id'])) {
        $errors[] = 'Η σοβαρότητα ατυχήματος είναι υποχρεωτική';
    }

    if (!empty($data['accidentTotalVehicles']) && $data['accidentTotalVehicles'] < 1) {
        $errors[] = 'Ο συνολικός αριθμός οχημάτων πρέπει να είναι τουλάχιστον 1';
    }

    return $errors;
}

function validateVehicleData($vehicle_data) {
    $errors = [];

    if (empty($vehicle_data['vehicleType_id'])) {
        $errors[] = 'Ο τύπος οχήματος είναι υποχρεωτικός';
    }

    if (!empty($vehicle_data['vehicleOccupantsNumber']) && $vehicle_data['vehicleOccupantsNumber'] < 0) {
        $errors[] = 'Ο αριθμός επιβαινόντων δεν μπορεί να είναι αρνητικός';
    }

    if (!empty($vehicle_data['vehicleManufactureDate'])) {
        $year = intval($vehicle_data['vehicleManufactureDate']);
        if ($year < 1950 || $year > date('Y')) {
            $errors[] = 'Το έτος κατασκευής δεν είναι έγκυρο';
        }
    }

    return $errors;
}

function validateRoadData($data) {
    $errors = [];

    if (empty($data['roadType_id'])) {
        $errors[] = 'Ο τύπος οδοστρώματος είναι υποχρεωτικός';
    }

    if (empty($data['roadSurface_id'])) {
        $errors[] = 'Η επιφάνεια οδοστρώματος είναι υποχρεωτική';
    }

    if (empty($data['roadLightConditions_id'])) {
        $errors[] = 'Οι συνθήκες φωτισμού είναι υποχρεωτικές';
    }

    if (empty($data['roadWeatherConditions_id'])) {
        $errors[] = 'Οι καιρικές συνθήκες είναι υποχρεωτικές';
    }

    if (!empty($data['roadSpeedLimit'])) {
        $speed = intval($data['roadSpeedLimit']);
        if ($speed < 10 || $speed > 200) {
            $errors[] = 'Το όριο ταχύτητας πρέπει να είναι μεταξύ 10 και 200 χλμ/ω';
        }
    }

    if (!empty($data['roadLaneNumber'])) {
        $lanes = intval($data['roadLaneNumber']);
        if ($lanes < 1 || $lanes > 8) {
            $errors[] = 'Ο αριθμός λωρίδων πρέπει να είναι μεταξύ 1 και 8';
        }
    }

    return $errors;
}
?>