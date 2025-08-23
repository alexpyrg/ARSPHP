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

        if ($action === 'save_draft' || $action === 'submit') {

            // Process Accident Data
            $accident_data = [
                'user_id' => $user_id,
                'caseNumber' => sanitizeInput($_POST['caseNumber'] ?? ''),
                'accidentSeverity_id' => $_POST['accidentSeverity_id'] ?? null,
                'accidentAbandonedVictim_id' => $_POST['accidentAbandonedVictim_id'] ?? null,
                'accidentGADAS_id' => $_POST['accidentGADAS_id'] ?? null,
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
                'accidentGDV_id' => $_POST['accidentGDV_id'] ?? null,
                'accidentSynopsis' => sanitizeInput($_POST['accidentSynopsis'] ?? ''),
                'accidentEventSequence' => sanitizeInput($_POST['accidentEventSequence'] ?? ''),
                'accidentFirstCollisionEvent_id' => $_POST['accidentFirstCollisionEvent_id'] ?? null,
                'accidentMostHarmfulEvent_id' => $_POST['accidentMostHarmfulEvent_id'] ?? null,
                'accidentRelatedFactors' => $_POST['accidentRelatedFactors'] ?? null,
                'accidentInformationSource_id' => $_POST['accidentInformationSource_id'] ?? null,
                'accidentISTrustLevel_id' => $_POST['accidentISTrustLevel_id'] ?? null,
                'accidentISTLDescription' => sanitizeInput($_POST['accidentISTLDescription'] ?? ''),
                'accidentInvestigationMethod_id' => $_POST['accidentInvestigationMethod_id'] ?? null,
                'accidentIMTrustLevel_id' => $_POST['accidentIMTrustLevel_id'] ?? null,
                'accidentIMTLDescription' => sanitizeInput($_POST['accidentIMTLDescription'] ?? ''),
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
                'step_completed' => $_POST['current_step'] ?? 1
            ];

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

            echo json_encode([
                'success' => true,
                'message' => $action === 'submit' ? 'Accident record submitted successfully!' : 'Draft saved successfully!',
                'accident_id' => $accident_id
            ]);

        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Accident form processing error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing the form.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

function createAccident($db, $data) {
    $sql = "INSERT INTO accidents (" . implode(', ', array_keys($data)) . ") VALUES (:" . implode(', :', array_keys($data)) . ")";
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

        // Clean and validate vehicle data
        $clean_vehicle_data = [];
        $vehicle_fields = [
            'accident_id', 'user_id', 'vehicleLicensePlate', 'vehicleColor_id', 'vehicleType_id',
            'vehicleManufacturer_id', 'vehicleModel_id', 'vehicleWheelDrive_id', 'vehicleDrivePosition_id',
            'vehicleLength', 'vehicleWidth', 'vehicleRoadwayAlignment_id', 'vehicleTrailer_id',
            'vehicleEnginePower', 'vehicleManufactureDate', 'vehicleTare', 'vehicleAxles',
            'vehicleGeneralComments', 'vehicleOccupantsNumber', 'vehicleDamagePossibleFactor_id',
            'vehicleDPFComments', 'vehicleInspected_id', 'vehicleSwerved_id', 'vehicleDangerousCargo_id',
            'vehicleScatteredDangerousCargo_id', 'vehicleCollisions', 'CDC3_id', 'CDC4_id',
            'vehicleOnFire_id', 'vehicleFirefightingEquipmentUsed_id', 'vehicleCollisionOffroadObject_id',
            'vehicleCollisionType_id', 'ABS_id', 'ESP_id', 'TCS_id', 'ACS_id', 'LDW_id', 'CSS_id',
            'vehicleElectronicsComments', 'vehicleInformationSource_id', 'vehicleISTrustLevel_id',
            'vehicleISTLDescription', 'vehicleInvestigationMethod_id', 'vehicleIMTrustLevel_id',
            'vehicleIMTLDescription'
        ];

        foreach ($vehicle_fields as $field) {
            $clean_vehicle_data[$field] = isset($vehicle_data[$field]) ?
                (is_string($vehicle_data[$field]) ? sanitizeInput($vehicle_data[$field]) : $vehicle_data[$field]) : null;
        }

        $sql = "INSERT INTO vehicles (" . implode(', ', array_keys($clean_vehicle_data)) . ") VALUES (:" . implode(', :', array_keys($clean_vehicle_data)) . ")";
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
        'images' => null // Will be updated separately
    ];

    $sql = "INSERT INTO roads (" . implode(', ', array_keys($road_data)) . ") VALUES (:" . implode(', :', array_keys($road_data)) . ")";
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
        mkdir($upload_dir, 0755, true);
    }

    $uploaded_files = [];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $file_type = $files['type'][$i];
            $file_size = $files['size'][$i];

            if (!in_array($file_type, $allowed_types)) {
                continue; // Skip non-image files
            }

            if ($file_size > $max_size) {
                continue; // Skip files that are too large
            }

            $file_extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $new_filename = uniqid('img_') . '.' . $file_extension;
            $file_path = $upload_dir . $new_filename;

            if (move_uploaded_file($files['tmp_name'][$i], $file_path)) {
                $uploaded_files[] = "accidents/{$accident_id}/{$new_filename}";
            }
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

// Additional validation functions
function validateAccidentData($data) {
    $errors = [];

    if (empty($data['accidentDate'])) {
        $errors[] = 'Accident date is required';
    }

    if (empty($data['accidentDay'])) {
        $errors[] = 'Day of week is required';
    }

    if (!empty($data['accidentTotalVehicles']) && $data['accidentTotalVehicles'] < 1) {
        $errors[] = 'Total vehicles must be at least 1';
    }

    return $errors;
}

function validateVehicleData($vehicle_data) {
    $errors = [];

    if (empty($vehicle_data['vehicleType_id'])) {
        $errors[] = 'Το πεδίο "Τύπος Οχήματος" είναι υποχρεωτικό!';
    }

    if (!empty($vehicle_data['vehicleOccupantsNumber']) && $vehicle_data['vehicleOccupantsNumber'] < 0) {
        $errors[] = 'Ο αριθμός επιβαινόντνων δε μπορεί να είναι αρνητικός!';
    }

    return $errors;
}

function validateRoadData($data) {
    $errors = [];

    if (empty($data['roadType_id'])) {
        $errors[] = 'Το πεδίο "Τύπος Οδοστρώματος" είναι υποχρεωτικό!';
    }

    if (empty($data['roadLightConditions_id'])) {
        $errors[] = 'Το πεδίο "Φωτισμός" είναι υποχρεωτικό!';
    }

    if (empty($data['roadWeatherConditions_id'])) {
        $errors[] = 'Το πεδίο "Καιρικές Συνθήκες" είναι υποχρεωτικό!';
    }

    if (!empty($data['roadSpeedLimit'])) {
        if ($data['roadSpeedLimit'] < 10 || $data['roadSpeedLimit'] > 200) {
            $errors[] = 'Το όριο ταχύτητας πρέπει να είναι μεταξύ 10 και 200 χλμ/ω';
        }
    }

    return $errors;
}
?>