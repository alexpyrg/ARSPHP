<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Καταγραφή Ατυχήματος - Σύστημα Καταγραφής Ατυχημάτων</title>
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
            z-index: 1000;
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

        .badge {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 4px;
        }

        .badge-primary {
            background-color: #3498db;
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .form-header {
            text-align: center;
            margin-bottom: 3rem;
            background: white;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .form-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .form-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .progress-container {
            background-color: #ecf0f1;
            height: 8px;
            margin-bottom: 2rem;
            position: relative;
        }

        .progress-bar {
            background-color: #3498db;
            height: 100%;
            transition: width 0.5s ease;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3rem;
            position: relative;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }

        .step-number {
            width: 50px;
            height: 50px;
            background-color: #bdc3c7;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .step.active .step-number {
            background-color: #3498db;
        }

        .step.completed .step-number {
            background-color: #27ae60;
        }

        .step-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #7f8c8d;
            text-align: center;
        }

        .step.active .step-title {
            color: #2c3e50;
        }

        .step-connector {
            position: absolute;
            top: 25px;
            left: 50px;
            right: -50px;
            height: 2px;
            background-color: #bdc3c7;
            z-index: 1;
        }

        .step:last-child .step-connector {
            display: none;
        }

        .step.completed .step-connector {
            background-color: #27ae60;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .form-header h1 {
                font-size: 1.8rem;
            }

            .progress-steps {
                flex-direction: column;
                gap: 1rem;
            }

            .step-connector {
                display: none;
            }

            .step {
                flex-direction: row;
                justify-content: flex-start;
                align-items: center;
                gap: 1rem;
            }

            .step-number {
                margin-bottom: 0;
            }
        }


        /* Add this CSS to your existing style section */

        .vehicle-forms {
            margin-top: 2rem;
        }

        .vehicle-form {
            background-color: #f8f9fa;
            border: 2px solid #dee2e6;
            margin-bottom: 2rem;
            padding: 2rem;
        }

        .vehicle-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #dee2e6;
        }

        .vehicle-header h4 {
            color: #2c3e50;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .remove-vehicle-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .remove-vehicle-btn:hover {
            background-color: #c0392b;
        }

        @media (max-width: 768px) {
            .vehicle-form {
                padding: 1.5rem 1rem;
            }
        }

        .form-container {
            background: white;
            padding: 3rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        .step-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #ecf0f1;
        }

        .step-header h2 {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .step-header p {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .form-section {
            background-color: #f8f9fa;
            padding: 2rem;
            border: 1px solid #e9ecef;
        }

        .form-section h3 {
            color: #2c3e50;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.half {
            display: inline-block;
            width: calc(50% - 0.5rem);
            margin-right: 1rem;
        }

        .form-group.half:nth-child(even) {
            margin-right: 0;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .form-group label.required::after {
            content: ' *';
            color: #e74c3c;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #e9ecef;
            background-color: #fff;
            font-size: 0.95rem;
            color: #2c3e50;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-control.error {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }

        .form-control.success {
            border-color: #27ae60;
        }

        select.form-control {
            cursor: pointer;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .form-help {
            font-size: 0.8rem;
            color: #7f8c8d;
            margin-top: 0.25rem;
        }

        .form-error {
            font-size: 0.8rem;
            color: #e74c3c;
            margin-top: 0.25rem;
            display: none;
        }

        .validation-summary {
            background-color: #fff5f5;
            border: 2px solid #fed7d7;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: none;
        }

        .validation-summary.show {
            display: block;
        }

        .validation-summary h4 {
            color: #e74c3c;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .validation-list {
            list-style: none;
            padding: 0;
        }

        .validation-list li {
            color: #e74c3c;
            font-size: 0.9rem;
            padding: 0.25rem 0;
            border-bottom: 1px solid #fed7d7;
        }

        .validation-list li:last-child {
            border-bottom: none;
        }

        /* Vehicle Type Breakdown */
        .vehicle-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
            padding: 1.5rem;
            background-color: #ecf0f1;
            border: 1px solid #bdc3c7;
        }

        .breakdown-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .breakdown-item input {
            width: 60px;
            padding: 0.5rem;
            border: 1px solid #bdc3c7;
            text-align: center;
        }

        .breakdown-item label {
            font-size: 0.85rem;
            color: #2c3e50;
            margin: 0;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid #ecf0f1;
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
            text-align: center;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
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

        .btn-success:hover {
            background-color: #229954;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .btn-warning {
            background-color: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background-color: #e67e22;
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid currentColor;
        }

        .btn-outline:hover {
            background-color: currentColor;
            color: white;
        }

        .auto-save-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .auto-save-indicator.saving {
            color: #f39c12;
        }

        .auto-save-indicator.saved {
            color: #27ae60;
        }

        /* Responsive Design for Forms */
        @media (max-width: 768px) {
            .form-container {
                padding: 2rem 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .form-section {
                padding: 1.5rem;
            }

            .form-group.half {
                display: block;
                width: 100%;
                margin-right: 0;
            }

            .form-actions {
                flex-direction: column;
                gap: 1rem;
            }

            .vehicle-breakdown {
                grid-template-columns: 1fr;
            }
        }

        /* Add this CSS to your existing style section */

        .file-upload-area {
            border: 2px dashed #bdc3c7;
            padding: 3rem 2rem;
            text-align: center;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: #3498db;
            background-color: #ecf0f1;
        }

        .file-upload-area.dragover {
            border-color: #27ae60;
            background-color: #d5f4e6;
        }

        .file-upload-icon {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 1rem;
        }

        .file-upload-text {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .file-upload-help {
            color: #95a5a6;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .file-list {
            margin-top: 1rem;
        }

        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #ecf0f1;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            border: 1px solid #bdc3c7;
        }

        .file-item-name {
            font-size: 0.9rem;
            color: #2c3e50;
        }

        .file-item-remove {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 0.25rem 0.5rem;
            font-size: 0.7rem;
            cursor: pointer;
        }

        .file-item-remove:hover {
            background-color: #c0392b;
        }

        @media (max-width: 768px) {
            .file-upload-area {
                padding: 2rem 1rem;
            }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand">Σύστημα Καταγραφής Ατυχημάτων</div>
    <div class="navbar-user">
        <span>Καλώς ήρθατε, <strong id="current-user">Καταχωρητής</strong></span>
        <span class="badge badge-primary">Καταχωρητής</span>
    </div>
</nav>

<div class="container">
    <div class="form-header">
        <h1>Καταγραφή Νέου Ατυχήματος</h1>
        <p>Συμπληρώστε όλα τα απαραίτητα στοιχεία για την ολοκλήρωση της εγγραφής</p>

        <div class="progress-container">
            <div class="progress-bar" id="progress-bar" style="width: 33.33%"></div>
        </div>

        <div class="progress-steps">
            <div class="step active" id="step-1">
                <div class="step-number">1</div>
                <div class="step-title">Στοιχεία Ατυχήματος</div>
                <div class="step-connector"></div>
            </div>
            <div class="step" id="step-2">
                <div class="step-number">2</div>
                <div class="step-title">Οχήματα</div>
                <div class="step-connector"></div>
            </div>
            <div class="step" id="step-3">
                <div class="step-number">3</div>
                <div class="step-title">Οδόστρωμα & Εικόνες</div>
            </div>
        </div>
    </div>

    <div class="validation-summary" id="validation-summary">
        <h4>⚠️ Παρακαλώ διορθώστε τα ακόλουθα σφάλματα:</h4>
        <ul class="validation-list" id="validation-list"></ul>
    </div>

    <!-- Form will be added in next part -->
    <form id="accident-form" class="form-container" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="current_step" value="1">
        <input type="hidden" name="accident_id" id="accident_id">

        <!-- Step 1: Accident Details -->
        <div class="form-step active" id="form-step-1">
            <div class="step-header">
                <h2>Βήμα 1: Στοιχεία Ατυχήματος</h2>
                <p>Εισάγετε τα βασικά στοιχεία του ατυχήματος</p>
            </div>

            <div class="form-grid">
                <div class="form-section">
                    <h3>Βασικά Στοιχεία</h3>

                    <div class="form-group">
                        <label for="caseNumber" class="required">Αριθμός Υπόθεσης</label>
                        <input type="text" id="caseNumber" name="caseNumber" class="form-control" required>
                        <div class="form-help">Μοναδικός αριθμός αναφοράς της υπόθεσης</div>
                        <div class="form-error">Το πεδίο είναι υποχρεωτικό</div>
                    </div>

                    <div class="form-group">
                        <label for="accidentDate" class="required">Ημερομηνία και Ώρα</label>
                        <input type="datetime-local" id="accidentDate" name="accidentDate" class="form-control" required>
                        <div class="form-error">Παρακαλώ εισάγετε έγκυρη ημερομηνία και ώρα</div>
                    </div>

                    <div class="form-group">
                        <label for="accidentDay" class="required">Ημέρα Εβδομάδας</label>
                        <select id="accidentDay" name="accidentDay" class="form-control" required>
                            <option value="">Επιλέξτε ημέρα...</option>
                            <option value="1">Δευτέρα</option>
                            <option value="2">Τρίτη</option>
                            <option value="3">Τετάρτη</option>
                            <option value="4">Πέμπτη</option>
                            <option value="5">Παρασκευή</option>
                            <option value="6">Σάββατο</option>
                            <option value="7">Κυριακή</option>
                        </select>
                        <div class="form-error">Παρακαλώ επιλέξτε ημέρα εβδομάδας</div>
                    </div>

                    <div class="form-group">
                        <label for="accidentExpertArrivalDate">Άφιξη Εμπειρογνώμονα</label>
                        <input type="datetime-local" id="accidentExpertArrivalDate" name="accidentExpertArrivalDate" class="form-control">
                        <div class="form-help">Προαιρετικό πεδίο</div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Τοποθεσία</h3>

                    <div class="form-group">
                        <label for="location" class="required">Περιγραφή Τοποθεσίας</label>
                        <textarea id="location" name="location" class="form-control" rows="3" required placeholder="π.χ. Λεωφόρος Κηφισίας 100, Αθήνα"></textarea>
                        <div class="form-error">Η περιγραφή τοποθεσίας είναι υποχρεωτική</div>
                    </div>

                    <div class="form-group half">
                        <label for="accidentLatitude">Γεωγραφικό Πλάτος</label>
                        <input type="number" id="accidentLatitude" name="accidentLatitude" class="form-control" step="0.000001" placeholder="37.975">
                        <div class="form-help">Προαιρετικό (GPS)</div>
                    </div>

                    <div class="form-group half">
                        <label for="accidentLongitude">Γεωγραφικό Μήκος</label>
                        <input type="number" id="accidentLongitude" name="accidentLongitude" class="form-control" step="0.000001" placeholder="23.735">
                        <div class="form-help">Προαιρετικό (GPS)</div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Σοβαρότητα & Κατηγοριοποίηση</h3>

                    <div class="form-group">
                        <label for="accidentSeverity_id" class="required">Σοβαρότητα Ατυχήματος</label>
                        <select id="accidentSeverity_id" name="accidentSeverity_id" class="form-control" required>
                            <option value="">Επιλέξτε σοβαρότητα...</option>
                            <option value="1">Ελαφρό (Υλικές Ζημίες)</option>
                            <option value="2">Μέτριο (Ελαφροί Τραυματισμοί)</option>
                            <option value="3">Σοβαρό (Σοβαροί Τραυματισμοί)</option>
                            <option value="4">Θανατηφόρο</option>
                        </select>
                        <div class="form-error">Παρακαλώ επιλέξτε σοβαρότητα ατυχήματος</div>
                    </div>

                    <div class="form-group">
                        <label for="accidentAlcohol_id">Εμπλοκή Αλκοόλ</label>
                        <select id="accidentAlcohol_id" name="accidentAlcohol_id" class="form-control">
                            <option value="">Δεν καθορίστηκε</option>
                            <option value="1">Ναι</option>
                            <option value="2">Όχι</option>
                            <option value="3">Υπό Διερεύνηση</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="accidentNarcotics_id">Εμπλοκή Ναρκωτικών</label>
                        <select id="accidentNarcotics_id" name="accidentNarcotics_id" class="form-control">
                            <option value="">Δεν καθορίστηκε</option>
                            <option value="1">Ναι</option>
                            <option value="2">Όχι</option>
                            <option value="3">Υπό Διερεύνηση</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="accidentAnimalCollision_id">Σύγκρουση με Ζώο</label>
                        <select id="accidentAnimalCollision_id" name="accidentAnimalCollision_id" class="form-control">
                            <option value="">Δεν καθορίστηκε</option>
                            <option value="1">Ναι</option>
                            <option value="2">Όχι</option>
                        </select>
                    </div>


                </div>
                <!-- Replace the last form-section in Step 1 with this complete version -->
                <div class="form-section">
                    <h3>Οχήματα & Συμβάντα</h3>

                    <div class="form-group">
                        <label for="accidentTotalVehicles" class="required">Συνολικός Αριθμός Οχημάτων</label>
                        <input type="number" id="accidentTotalVehicles" name="accidentTotalVehicles" class="form-control" min="1" max="50" required>
                        <div class="form-help">Αριθμός οχημάτων που εμπλέκονται στο ατύχημα</div>
                        <div class="form-error">Ο αριθμός οχημάτων πρέπει να είναι τουλάχιστον 1</div>
                    </div>

                    <!-- Vehicle Type Breakdown -->
                    <div id="vehicle-breakdown" style="display: none;">
                        <h4 style="margin-bottom: 1rem; color: #2c3e50;">Κατανομή Τύπων Οχημάτων:</h4>
                        <div class="vehicle-breakdown">
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleSedan" value="0" min="0">
                                <label>Sedan</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleVan" value="0" min="0">
                                <label>Van</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleHatchback" value="0" min="0">
                                <label>Hatchback</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleTruck" value="0" min="0">
                                <label>Φορτηγό</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleCaravan" value="0" min="0">
                                <label>Τροχόσπιτο</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleTrailer" value="0" min="0">
                                <label>Ρυμουλκούμενο</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleSport" value="0" min="0">
                                <label>Αθλητικό</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleBus" value="0" min="0">
                                <label>Λεωφορείο</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleComercial" value="0" min="0">
                                <label>Εμπορικό</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehiclePickupTruck" value="0" min="0">
                                <label>Pickup</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleOffroad" value="0" min="0">
                                <label>Offroad</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleBike" value="0" min="0">
                                <label>Μοτοσικλέτα</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleSuv" value="0" min="0">
                                <label>SUV</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleBicycle" value="0" min="0">
                                <label>Ποδήλατο</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleElectric" value="0" min="0">
                                <label>Ηλεκτρικό</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleOtherTwoWheeler" value="0" min="0">
                                <label>Άλλο Δίτροχο</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleAutonomous" value="0" min="0">
                                <label>Αυτόνομο</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleTricycle" value="0" min="0">
                                <label>Τρίτροχο</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehiclePedestrian" value="0" min="0">
                                <label>Πεζός</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleOther" value="0" min="0">
                                <label>Άλλο</label>
                            </div>
                            <div class="breakdown-item">
                                <input type="number" name="accidentVehicleUnknown" value="0" min="0">
                                <label>Άγνωστο</label>
                            </div>
                        </div>
                        <div class="form-help" style="margin-top: 1rem; text-align: center;">
                            <strong>Σύνολο: <span id="vehicle-total">0</span></strong>
                            (Πρέπει να ισούται με τον συνολικό αριθμό οχημάτων)
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="accidentEventsNumber">Αριθμός Συμβάντων</label>
                        <input type="number" id="accidentEventsNumber" name="accidentEventsNumber" class="form-control" min="1" max="10">
                        <div class="form-help">Πόσα διαφορετικά συμβάντα συνέβησαν</div>
                    </div>

                    <div class="form-group">
                        <label for="accidentFirstCollisionEvent_id">Πρώτο Συμβάν Σύγκρουσης</label>
                        <select id="accidentFirstCollisionEvent_id" name="accidentFirstCollisionEvent_id" class="form-control">
                            <option value="">Δεν καθορίστηκε</option>
                            <option value="1">Μετωπική Σύγκρουση</option>
                            <option value="2">Πλευρική Σύγκρουση</option>
                            <option value="3">Σύγκρουση από Πίσω</option>
                            <option value="4">Ανατροπή</option>
                            <option value="5">Παρασκήνιο</option>
                            <option value="6">Σύγκρουση με Εμπόδιο</option>
                            <option value="7">Άλλο</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="accidentMostHarmfulEvent_id">Πιο Επιβλαβές Συμβάν</label>
                        <select id="accidentMostHarmfulEvent_id" name="accidentMostHarmfulEvent_id" class="form-control">
                            <option value="">Δεν καθορίστηκε</option>
                            <option value="1">Μετωπική Σύγκρουση</option>
                            <option value="2">Πλευρική Σύγκρουση</option>
                            <option value="3">Σύγκρουση από Πίσω</option>
                            <option value="4">Ανατροπή</option>
                            <option value="5">Παρασκήνιο</option>
                            <option value="6">Σύγκρουση με Εμπόδιο</option>
                            <option value="7">Φωτιά</option>
                            <option value="8">Άλλο</option>
                        </select>
                    </div>
                </div>

                <!-- Add one more form-section for descriptions -->
                <div class="form-section">
                    <h3>Περιγραφή & Πληροφορίες</h3>

                    <div class="form-group">
                        <label for="accidentSynopsis">Σύνοψη Ατυχήματος</label>
                        <textarea id="accidentSynopsis" name="accidentSynopsis" class="form-control" rows="4" placeholder="Περιγράψτε συνοπτικά τι συνέβη..."></textarea>
                        <div class="form-help">Σύντομη περιγραφή των γεγονότων</div>
                    </div>

                    <div class="form-group">
                        <label for="accidentEventSequence">Ακολουθία Συμβάντων</label>
                        <textarea id="accidentEventSequence" name="accidentEventSequence" class="form-control" rows="4" placeholder="Χρονολογική σειρά των συμβάντων..."></textarea>
                        <div class="form-help">Λεπτομερής χρονολογική περιγραφή</div>
                    </div>

                    <div class="form-group">
                        <label for="accidentCase">Περιγραφή Υπόθεσης</label>
                        <textarea id="accidentCase" name="accidentCase" class="form-control" rows="3" placeholder="Επιπλέον λεπτομέρειες για την υπόθεση..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="accidentRelatedFactors">Σχετικοί Παράγοντες</label>
                        <textarea id="accidentRelatedFactors" name="accidentRelatedFactors" class="form-control" rows="3" placeholder="Παράγοντες που συνέβαλαν στο ατύχημα..."></textarea>
                        <div class="form-help">π.χ. καιρικές συνθήκες, κατάσταση οδοστρώματος</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2 and 3 will be added in next parts -->
        <div class="form-step" id="form-step-2">
            <div class="step-header">
                <h2>Βήμα 2: Στοιχεία Οχημάτων</h2>
                <p>Εισάγετε λεπτομερή στοιχεία για κάθε όχημα που εμπλέκεται στο ατύχημα</p>
            </div>

            <div class="vehicle-forms" id="vehicle-forms">
                <!-- Εδω δημιουργούνται δυναμικά οι φόρμες για τα οχήματα -->
            </div>

            <button type="button" id="add-vehicle-btn" class="btn btn-primary" style="margin-top: 1rem;">
                + Προσθήκη Οχήματος
            </button>
        </div>

        <!-- Replace the content inside form-step-3 div -->
        <div class="form-step" id="form-step-3">
            <div class="step-header">
                <h2>Βήμα 3: Οδόστρωμα & Εικόνες</h2>
                <p>Περιγράψτε τις συνθήκες του οδοστρώματος και προσθέστε φωτογραφίες</p>
            </div>

            <div class="form-grid">
                <div class="form-section">
                    <h3>Χαρακτηριστικά Οδοστρώματος</h3>

                    <div class="form-group">
                        <label for="roadType_id" class="required">Τύπος Οδοστρώματος</label>
                        <select id="roadType_id" name="roadType_id" class="form-control" required>
                            <option value="">Επιλέξτε τύπο...</option>
                            <option value="1">Αυτοκινητόδρομος</option>
                            <option value="2">Εθνική Οδός</option>
                            <option value="3">Επαρχιακή Οδός</option>
                            <option value="4">Αστική Οδός</option>
                            <option value="5">Τοπική Οδός</option>
                            <option value="6">Ιδιωτική Οδός</option>
                            <option value="7">Άλλο</option>
                        </select>
                        <div class="form-error">Παρακαλώ επιλέξτε τύπο οδοστρώματος</div>
                    </div>

                    <div class="form-group">
                        <label for="roadSurface_id" class="required">Επιφάνεια Οδοστρώματος</label>
                        <select id="roadSurface_id" name="roadSurface_id" class="form-control" required>
                            <option value="">Επιλέξτε επιφάνεια...</option>
                            <option value="1">Άσφαλτος</option>
                            <option value="2">Σκυρόδεμα</option>
                            <option value="3">Χώμα</option>
                            <option value="4">Χαλίκι</option>
                            <option value="5">Πλακόστρωτο</option>
                            <option value="6">Άλλο</option>
                        </select>
                        <div class="form-error">Παρακαλώ επιλέξτε επιφάνεια οδοστρώματος</div>
                    </div>

                    <div class="form-group half">
                        <label for="roadLaneNumber">Αριθμός Λωρίδων</label>
                        <input type="number" id="roadLaneNumber" name="roadLaneNumber" class="form-control" min="1" max="8">
                    </div>

                    <div class="form-group half">
                        <label for="roadSpeedLimit">Όριο Ταχύτητας (km/h)</label>
                        <input type="number" id="roadSpeedLimit" name="roadSpeedLimit" class="form-control" min="10" max="200" step="10">
                    </div>

                    <div class="form-group">
                        <label for="roadAlignment_id">Ευθυγράμμιση Οδοστρώματος</label>
                        <select id="roadAlignment_id" name="roadAlignment_id" class="form-control">
                            <option value="">Δεν καθορίστηκε</option>
                            <option value="1">Ευθεία</option>
                            <option value="2">Αριστερή Στροφή</option>
                            <option value="3">Δεξιά Στροφή</option>
                            <option value="4">Ανηφόρα</option>
                            <option value="5">Κατηφόρα</option>
                            <option value="6">Λόφος</option>
                            <option value="7">Κοιλάδα</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Διασταυρώσεις & Σηματοδότηση</h3>

                    <div class="form-group">
                        <label for="roadJunction_id">Τύπος Διασταύρωσης</label>
                        <select id="roadJunction_id" name="roadJunction_id" class="form-control">
                            <option value="">Καμία/Δεν καθορίστηκε</option>
                            <option value="1">Κανονική Διασταύρωση</option>
                            <option value="2">Κυκλική Διασταύρωση</option>
                            <option value="3">Διασταύρωση Σχήματος Τ</option>
                            <option value="4">Διασταύρωση Σχήματος Υ</option>
                            <option value="5">Σήραγγα</option>
                            <option value="6">Γέφυρα</option>
                            <option value="7">Άλλο</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="roadTrafficSigns_id">Οδική Σηματοδότηση</label>
                        <select id="roadTrafficSigns_id" name="roadTrafficSigns_id" class="form-control">
                            <option value="">Δεν καθορίστηκε</option>
                            <option value="1">Φανάρι (Λειτουργεί)</option>
                            <option value="2">Φανάρι (Εκτός Λειτουργίας)</option>
                            <option value="3">Σήμανση STOP</option>
                            <option value="4">Σήμανση Παραχώρησης</option>
                            <option value="5">Χωρίς Σηματοδότηση</option>
                            <option value="6">Άλλο</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="roadConstructionZone_id">Ζώνη Εργασιών</label>
                        <select id="roadConstructionZone_id" name="roadConstructionZone_id" class="form-control">
                            <option value="">Όχι</option>
                            <option value="1">Ναι - Ενεργή</option>
                            <option value="2">Ναι - Ανενεργή</option>
                            <option value="3">Ναι - Εγκαταλελειμμένη</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="roadSpeedLimitingFacility_id">Εγκαταστάσεις Περιορισμού Ταχύτητας</label>
                        <select id="roadSpeedLimitingFacility_id" name="roadSpeedLimitingFacility_id" class="form-control">
                            <option value="">Δεν καθορίστηκε</option>
                            <option value="1">Εξόγκωμα</option>
                            <option value="2">Στένωμα</option>
                            <option value="3">Ζικ-Ζακ</option>
                            <option value="4">Κάμερα Ταχύτητας</option>
                            <option value="5">Άλλο</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Συνθήκες Περιβάλλοντος</h3>

                    <div class="form-group">
                        <label for="roadLightConditions_id" class="required">Συνθήκες Φωτισμού</label>
                        <select id="roadLightConditions_id" name="roadLightConditions_id" class="form-control" required>
                            <option value="">Επιλέξτε συνθήκες...</option>
                            <option value="1">Ημέρα</option>
                            <option value="2">Σούρουπο</option>
                            <option value="3">Νύχτα με Φωτισμό</option>
                            <option value="4">Νύχτα χωρίς Φωτισμό</option>
                            <option value="5">Αυγή</option>
                        </select>
                        <div class="form-error">Παρακαλώ επιλέξτε συνθήκες φωτισμού</div>
                    </div>

                    <div class="form-group">
                        <label for="roadWeatherConditions_id" class="required">Καιρικές Συνθήκες</label>
                        <select id="roadWeatherConditions_id" name="roadWeatherConditions_id" class="form-control" required>
                            <option value="">Επιλέξτε καιρό...</option>
                            <option value="1">Καθαρός</option>
                            <option value="2">Συννεφιά</option>
                            <option value="3">Βροχή (Ελαφριά)</option>
                            <option value="4">Βροχή (Έντονη)</option>
                            <option value="5">Χιόνι</option>
                            <option value="6">Χαλάζι</option>
                            <option value="7">Ομίχλη</option>
                            <option value="8">Άλλο</option>
                        </select>
                        <div class="form-error">Παρακαλώ επιλέξτε καιρικές συνθήκες</div>
                    </div>

                    <div class="form-group">
                        <label for="roadStrongWinds_id">Ισχυροί Άνεμοι</label>
                        <select id="roadStrongWinds_id" name="roadStrongWinds_id" class="form-control">
                            <option value="">Δεν καθορίστηκε</option>
                            <option value="1">Ναι</option>
                            <option value="2">Όχι</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="roadFog_id">Ομίχλη</label>
                        <select id="roadFog_id" name="roadFog_id" class="form-control">
                            <option value="">Δεν καθορίστηκε</option>
                            <option value="1">Ναι - Ελαφριά</option>
                            <option value="2">Ναι - Έντονη</option>
                            <option value="3">Όχι</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="roadConditionComments">Σχόλια για τις Συνθήκες</label>
                        <textarea id="roadConditionComments" name="roadConditionComments" class="form-control" rows="3" placeholder="Επιπλέον λεπτομέρειες για τις συνθήκες του οδοστρώματος..."></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Επιπλέον Παράγοντες</h3>

                    <div class="form-group">
                        <label for="roadPollutants_id">Ρύπανση Οδοστρώματος</label>
                        <select id="roadPollutants_id" name="roadPollutants_id" class="form-control">
                            <option value="">Δεν καθορίστηκε</option>
                            <option value="1">Πετρέλαιο/Λάδια</option>
                            <option value="2">Νερό</option>
                            <option value="3">Λάσπη</option>
                            <option value="4">Χιόνι/Πάγος</option>
                            <option value="5">Φύλλα</option>
                            <option value="6">Σκουπίδια</option>
                            <option value="7">Άλλο</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="roadTransientConstraints_id">Προσωρινοί Περιορισμοί</label>
                        <select id="roadTransientConstraints_id" name="roadTransientConstraints_id" class="form-control">
                            <option value="">Δεν καθορίστηκε</option>
                            <option value="1">Κυκλοφοριακή Συμφόρηση</option>
                            <option value="2">Εργασίες Συντήρησης</option>
                            <option value="3">Σταθμευμένα Οχήματα</option>
                            <option value="4">Εκδήλωση</option>
                            <option value="5">Ατύχημα</option>
                            <option value="6">Άλλο</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="roadPossibleFactorsComments">Σχόλια για Πιθανούς Παράγοντες</label>
                        <textarea id="roadPossibleFactorsComments" name="roadPossibleFactorsComments" class="form-control" rows="3" placeholder="Περιγράψτε άλλους παράγοντες που μπορεί να συνέβαλαν..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="roadOtherComments">Άλλα Σχόλια</label>
                        <textarea id="roadOtherComments" name="roadOtherComments" class="form-control" rows="3" placeholder="Οποιαδήποτε άλλα σχόλια για το οδόστρωμα..."></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Εικόνες & Έγγραφα</h3>

                    <div class="form-group">
                        <label for="images">Φωτογραφίες Ατυχήματος</label>
                        <div class="file-upload-area" id="file-upload-area">
                            <div class="file-upload-icon">📷</div>
                            <div class="file-upload-text">
                                Κάντε κλικ εδώ ή σύρετε αρχεία για να προσθέσετε φωτογραφίες
                            </div>
                            <div class="file-upload-help">
                                Υποστηριζόμενα: JPG, PNG, GIF, WEBP | Μέγιστο μέγεθος: 5MB ανά αρχείο
                            </div>
                        </div>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" style="display: none;">
                        <div class="file-list" id="file-list"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <div class="auto-save-indicator" id="auto-save-indicator">
                <span>💾</span>
                <span id="save-status">Αυτόματη αποθήκευση ενεργή</span>
            </div>

            <div style="display: flex; gap: 1rem;">
                <button type="button" id="prev-btn" class="btn btn-secondary" style="display: none;">
                    ← Προηγούμενο
                </button>

                <button type="button" id="save-draft-btn" class="btn btn-warning btn-outline">
                    💾 Αποθήκευση Προχείρου
                </button>

                <button type="button" id="next-btn" class="btn btn-primary">
                    Επόμενο →
                </button>

                <button type="submit" id="submit-btn" class="btn btn-success" style="display: none;">
                    ✓ Υποβολή Εγγραφής
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    // Form state management
    let currentStep = 1;
    let totalSteps = 3;
    let vehicleCount = 0;

    // Initialize form when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initializeForm();
        setupEventListeners();
    });

    function initializeForm() {
        updateStepDisplay();

        // Auto-fill day of week when date changes
        document.getElementById('accidentDate').addEventListener('change', function() {
            const date = new Date(this.value);
            if (!isNaN(date.getTime())) {
                const dayOfWeek = date.getDay() === 0 ? 7 : date.getDay(); // Convert Sunday from 0 to 7
                document.getElementById('accidentDay').value = dayOfWeek;
            }
        });

        // Setup vehicle count tracking
        document.getElementById('accidentTotalVehicles').addEventListener('input', function() {
            const count = parseInt(this.value) || 0;
            toggleVehicleBreakdown(count > 0);
            updateVehicleBreakdown();
        });

        // Setup vehicle breakdown calculation
        document.querySelectorAll('.vehicle-breakdown input').forEach(input => {
            input.addEventListener('input', updateVehicleBreakdown);
        });
    }

    function setupEventListeners() {
        // Step navigation
        document.getElementById('next-btn').addEventListener('click', nextStep);
        document.getElementById('prev-btn').addEventListener('click', prevStep);
        document.getElementById('save-draft-btn').addEventListener('click', saveDraft);

        // Form validation on input
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });

            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
            });
        });
    }

    function updateStepDisplay() {
        // Update progress bar
        const progress = (currentStep / totalSteps) * 100;
        document.getElementById('progress-bar').style.width = progress + '%';

        // Update step indicators
        for (let i = 1; i <= totalSteps; i++) {
            const step = document.getElementById(`step-${i}`);
            const stepElement = document.getElementById(`form-step-${i}`);

            step.classList.remove('active', 'completed');
            stepElement.classList.remove('active');

            if (i < currentStep) {
                step.classList.add('completed');
            } else if (i === currentStep) {
                step.classList.add('active');
                stepElement.classList.add('active');
            }
        }

        // Update navigation buttons
        document.getElementById('prev-btn').style.display = currentStep > 1 ? 'block' : 'none';
        document.getElementById('next-btn').style.display = currentStep < totalSteps ? 'block' : 'none';
        document.getElementById('submit-btn').style.display = currentStep === totalSteps ? 'block' : 'none';

        // Update hidden field
        document.querySelector('input[name="current_step"]').value = currentStep;
    }

    function nextStep() {
        if (validateCurrentStep()) {
            if (currentStep < totalSteps) {
                currentStep++;
                updateStepDisplay();
                scrollToTop();
            }
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            currentStep--;
            updateStepDisplay();
            scrollToTop();
        }
    }

    function validateCurrentStep() {
        clearValidationErrors();
        let isValid = true;
        const errors = [];


        if (currentStep === 1) {

            const requiredFields = [
                'caseNumber',
                'accidentDate',
                'accidentDay',
                'location',
                'accidentSeverity_id',
                'accidentTotalVehicles'
            ];

            requiredFields.forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (!field.value.trim()) {
                    markFieldError(field);
                    errors.push(`Το πεδίο "${field.previousElementSibling.textContent.replace(' *', '')}" είναι υποχρεωτικό`);
                    isValid = false;
                }
            });

            const totalVehicles = parseInt(document.getElementById('accidentTotalVehicles').value) || 0;
            if (totalVehicles > 0) {
                const breakdownTotal = calculateVehicleBreakdownTotal();
                if (breakdownTotal !== totalVehicles) {
                    errors.push(`Η κατανομή τύπων οχημάτων (${breakdownTotal}) δεν ταιριάζει με το σύνολο (${totalVehicles})`);
                    isValid = false;
                }
            }

            const location = document.getElementById('location').value.trim();
            if (location.length < 10) {
                markFieldError(document.getElementById('location'));
                errors.push('Η περιγραφή τοποθεσίας πρέπει να είναι τουλάχιστον 10 χαρακτήρες');
                isValid = false;
            }

        } else if (currentStep === 2) {
            // Existing Step 2 validation...
            const vehicleForms = document.querySelectorAll('.vehicle-form');
            vehicleForms.forEach((form, index) => {
                const vehicleType = form.querySelector('[name$="[vehicleType_id]"]');
                if (!vehicleType.value) {
                    markFieldError(vehicleType);
                    errors.push(`Όχημα ${index + 1}: Επιλέξτε τύπο οχήματος`);
                    isValid = false;
                }
            });

        } else if (currentStep === 3) {
            // Validate required fields in step 3
            const requiredFields = [
                'roadType_id',
                'roadSurface_id',
                'roadLightConditions_id',
                'roadWeatherConditions_id'
            ];

            requiredFields.forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (!field.value.trim()) {
                    markFieldError(field);
                    errors.push(`Το πεδίο "${field.previousElementSibling.textContent.replace(' *', '')}" είναι υποχρεωτικό`);
                    isValid = false;
                }
            });
        }

        if (!isValid) {
            showValidationErrors(errors);
        }


        return isValid;
    }

    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;

        // Required field validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
        }

        // Number validation
        if (field.type === 'number' && value) {
            const num = parseFloat(value);
            const min = parseFloat(field.min);
            const max = parseFloat(field.max);

            if (!isNaN(min) && num < min) isValid = false;
            if (!isNaN(max) && num > max) isValid = false;
        }

        // Date validation
        if (field.type === 'datetime-local' && value) {
            const date = new Date(value);
            const now = new Date();
            if (date > now) {
                isValid = false;
            }
        }

        // Update field appearance
        field.classList.remove('error', 'success');
        const errorDiv = field.parentElement.querySelector('.form-error');

        if (isValid) {
            field.classList.add('success');
            if (errorDiv) errorDiv.style.display = 'none';
        } else {
            field.classList.add('error');
            if (errorDiv) errorDiv.style.display = 'block';
        }

        return isValid;
    }

    function toggleVehicleBreakdown(show) {
        const breakdown = document.getElementById('vehicle-breakdown');
        breakdown.style.display = show ? 'block' : 'none';
    }

    function updateVehicleBreakdown() {
        const inputs = document.querySelectorAll('.vehicle-breakdown input');
        let total = 0;

        inputs.forEach(input => {
            total += parseInt(input.value) || 0;
        });

        document.getElementById('vehicle-total').textContent = total;

        // Update styling based on match
        const targetTotal = parseInt(document.getElementById('accidentTotalVehicles').value) || 0;
        const totalDisplay = document.getElementById('vehicle-total').parentElement;

        totalDisplay.style.color = total === targetTotal ? '#27ae60' : '#e74c3c';
    }

    function calculateVehicleBreakdownTotal() {
        const inputs = document.querySelectorAll('.vehicle-breakdown input');
        let total = 0;
        inputs.forEach(input => {
            total += parseInt(input.value) || 0;
        });
        return total;
    }

    function markFieldError(field) {
        field.classList.add('error');
        const errorDiv = field.parentElement.querySelector('.form-error');
        if (errorDiv) errorDiv.style.display = 'block';
    }

    function clearValidationErrors() {
        document.querySelectorAll('.form-control').forEach(field => {
            field.classList.remove('error');
        });

        document.querySelectorAll('.form-error').forEach(error => {
            error.style.display = 'none';
        });

        document.getElementById('validation-summary').classList.remove('show');
    }

    function showValidationErrors(errors) {
        const summary = document.getElementById('validation-summary');
        const list = document.getElementById('validation-list');

        list.innerHTML = '';
        errors.forEach(error => {
            const li = document.createElement('li');
            li.textContent = error;
            list.appendChild(li);
        });

        summary.classList.add('show');
        scrollToTop();
    }

    function saveDraft() {
        const indicator = document.getElementById('auto-save-indicator');
        const status = document.getElementById('save-status');

        indicator.classList.add('saving');
        status.textContent = 'Γίνεται αποθήκευση...';

        // Simulate save (in production, this would be an AJAX call)
        setTimeout(() => {
            indicator.classList.remove('saving');
            indicator.classList.add('saved');
            status.textContent = 'Αποθηκεύτηκε';

            setTimeout(() => {
                indicator.classList.remove('saved');
                status.textContent = 'Αυτόματη αποθήκευση ενεργή';
            }, 2000);
        }, 1000);
    }

    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }


    // Add these functions to your existing script section

    function generateVehicleForms() {
        const totalVehicles = parseInt(document.getElementById('accidentTotalVehicles').value) || 0;
        const container = document.getElementById('vehicle-forms');

        // Clear existing forms
        container.innerHTML = '';

        for (let i = 0; i < totalVehicles; i++) {
            const vehicleForm = createVehicleForm(i);
            container.appendChild(vehicleForm);
        }

        vehicleCount = totalVehicles;
    }

    function createVehicleForm(index) {
        const div = document.createElement('div');
        div.className = 'vehicle-form';
        div.innerHTML = `
        <div class="vehicle-header">
            <h4>Όχημα ${index + 1}</h4>
            ${index > 0 ? '<button type="button" class="remove-vehicle-btn" onclick="removeVehicle(this)">Αφαίρεση</button>' : ''}
        </div>

        <div class="form-grid">
            <div class="form-section">
                <h3>Βασικά Στοιχεία</h3>

                <div class="form-group">
                    <label class="required">Τύπος Οχήματος</label>
                    <select name="vehicles[${index}][vehicleType_id]" class="form-control" required>
                        <option value="">Επιλέξτε τύπο...</option>
                        <option value="1">Επιβατικό</option>
                        <option value="2">Φορτηγό</option>
                        <option value="3">Μοτοσικλέτα</option>
                        <option value="4">Λεωφορείο</option>
                        <option value="5">Ποδήλατο</option>
                        <option value="6">SUV</option>
                        <option value="7">Van</option>
                        <option value="8">Pickup</option>
                        <option value="9">Άλλο</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Αριθμός Κυκλοφορίας</label>
                    <input type="text" name="vehicles[${index}][vehicleLicensePlate]" class="form-control" placeholder="π.χ. ABC-1234">
                </div>

                <div class="form-group">
                    <label>Χρώμα</label>
                    <select name="vehicles[${index}][vehicleColor_id]" class="form-control">
                        <option value="">Επιλέξτε χρώμα...</option>
                        <option value="1">Λευκό</option>
                        <option value="2">Μαύρο</option>
                        <option value="3">Κόκκινο</option>
                        <option value="4">Μπλε</option>
                        <option value="5">Γκρι</option>
                        <option value="6">Ασημί</option>
                        <option value="7">Κίτρινο</option>
                        <option value="8">Πράσινο</option>
                        <option value="9">Άλλο</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Κατασκευαστής</label>
                    <select name="vehicles[${index}][vehicleManufacturer_id]" class="form-control">
                        <option value="">Επιλέξτε κατασκευαστή...</option>
                        <option value="1">Toyota</option>
                        <option value="2">Mercedes</option>
                        <option value="3">BMW</option>
                        <option value="4">Audi</option>
                        <option value="5">Ford</option>
                        <option value="6">Volkswagen</option>
                        <option value="7">Opel</option>
                        <option value="8">Peugeot</option>
                        <option value="9">Renault</option>
                        <option value="10">Fiat</option>
                        <option value="11">Άλλο</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Μοντέλο</label>
                    <input type="text" name="vehicles[${index}][vehicleModel_id]" class="form-control" placeholder="π.χ. Corolla, A4, Golf">
                </div>

                <div class="form-group">
                    <label>Έτος Κατασκευής</label>
                    <input type="number" name="vehicles[${index}][vehicleManufactureDate]" class="form-control" min="1950" max="2025">
                </div>
            </div>

            <div class="form-section">
                <h3>Τεχνικά Χαρακτηριστικά</h3>

                <div class="form-group">
                    <label>Κίνηση</label>
                    <select name="vehicles[${index}][vehicleWheelDrive_id]" class="form-control">
                        <option value="">Δεν καθορίστηκε</option>
                        <option value="1">Εμπρόσθια (FWD)</option>
                        <option value="2">Οπίσθια (RWD)</option>
                        <option value="3">Τετρακίνητη (4WD)</option>
                        <option value="4">AWD</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Θέση Οδηγού</label>
                    <select name="vehicles[${index}][vehicleDrivePosition_id]" class="form-control">
                        <option value="">Δεν καθορίστηκε</option>
                        <option value="1">Αριστερά</option>
                        <option value="2">Δεξιά</option>
                    </select>
                </div>

                <div class="form-group half">
                    <label>Μήκος (μ)</label>
                    <input type="number" name="vehicles[${index}][vehicleLength]" class="form-control" step="0.1" min="0">
                </div>

                <div class="form-group half">
                    <label>Πλάτος (μ)</label>
                    <input type="number" name="vehicles[${index}][vehicleWidth]" class="form-control" step="0.1" min="0">
                </div>

                <div class="form-group">
                    <label>Ισχύς Κινητήρα (HP)</label>
                    <input type="number" name="vehicles[${index}][vehicleEnginePower]" class="form-control" min="0">
                </div>

                <div class="form-group">
                    <label>Ίδιο Βάρος (kg)</label>
                    <input type="number" name="vehicles[${index}][vehicleTare]" class="form-control" min="0">
                </div>

                <div class="form-group">
                    <label>Αριθμός Αξόνων</label>
                    <input type="number" name="vehicles[${index}][vehicleAxles]" class="form-control" min="1" max="10">
                </div>

                <div class="form-group">
                    <label>Αριθμός Επιβαινόντων</label>
                    <input type="number" name="vehicles[${index}][vehicleOccupantsNumber]" class="form-control" min="0" max="100">
                </div>
            </div>

            <div class="form-section">
                <h3>Ζημίες & Συμβάντα</h3>

                <div class="form-group">
                    <label>Πιθανός Παράγοντας Ζημιάς</label>
                    <select name="vehicles[${index}][vehicleDamagePossibleFactor_id]" class="form-control">
                        <option value="">Δεν καθορίστηκε</option>
                        <option value="1">Υπερβολική Ταχύτητα</option>
                        <option value="2">Αλκοόλ/Ουσίες</option>
                        <option value="3">Κόπωση Οδηγού</option>
                        <option value="4">Μηχανικό Πρόβλημα</option>
                        <option value="5">Καιρικές Συνθήκες</option>
                        <option value="6">Απόσπαση Προσοχής</option>
                        <option value="7">Παραβίαση Σήμανσης</option>
                        <option value="8">Άλλο</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Σχόλια για Παράγοντες Ζημιάς</label>
                    <textarea name="vehicles[${index}][vehicleDPFComments]" class="form-control" rows="2"></textarea>
                </div>

                <div class="form-group">
                    <label>Εξετάστηκε το Όχημα</label>
                    <select name="vehicles[${index}][vehicleInspected_id]" class="form-control">
                        <option value="">Δεν καθορίστηκε</option>
                        <option value="1">Ναι</option>
                        <option value="2">Όχι</option>
                        <option value="3">Μερικώς</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Απότομη Στροφή/Εκτροπή</label>
                    <select name="vehicles[${index}][vehicleSwerved_id]" class="form-control">
                        <option value="">Δεν καθορίστηκε</option>
                        <option value="1">Ναι</option>
                        <option value="2">Όχι</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Επικίνδυνο Φορτίο</label>
                    <select name="vehicles[${index}][vehicleDangerousCargo_id]" class="form-control">
                        <option value="">Δεν καθορίστηκε</option>
                        <option value="1">Ναι</option>
                        <option value="2">Όχι</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Διασκορπισμένο Επικίνδυνο Φορτίο</label>
                    <select name="vehicles[${index}][vehicleScatteredDangerousCargo_id]" class="form-control">
                        <option value="">Δεν καθορίστηκε</option>
                        <option value="1">Ναι</option>
                        <option value="2">Όχι</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Αριθμός Συγκρούσεων</label>
                    <input type="number" name="vehicles[${index}][vehicleCollisions]" class="form-control" min="0" max="10">
                </div>

                <div class="form-group">
                    <label>Όχημα σε Φωτιά</label>
                    <select name="vehicles[${index}][vehicleOnFire_id]" class="form-control">
                        <option value="">Δεν καθορίστηκε</option>
                        <option value="1">Ναι</option>
                        <option value="2">Όχι</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Χρήση Πυροσβεστικού Εξοπλισμού</label>
                    <select name="vehicles[${index}][vehicleFirefightingEquipmentUsed_id]" class="form-control">
                        <option value="">Δεν καθορίστηκε</option>
                        <option value="1">Ναι</option>
                        <option value="2">Όχι</option>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h3>Συστήματα Ασφαλείας</h3>

                <div class="form-group">
                    <label>ABS</label>
                    <select name="vehicles[${index}][ABS_id]" class="form-control">
                        <option value="">Δεν καθορίστηκε</option>
                        <option value="1">Ναι - Λειτουργεί</option>
                        <option value="2">Ναι - Δε Λειτουργεί</option>
                        <option value="3">Όχι</option>
                        <option value="4">Άγνωστο</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>ESP</label>
                    <select name="vehicles[${index}][ESP_id]" class="form-control">
                        <option value="">Δεν καθορίστηκε</option>
                        <option value="1">Ναι - Λειτουργεί</option>
                        <option value="2">Ναι - Δε Λειτουργεί</option>
                        <option value="3">Όχι</option>
                        <option value="4">Άγνωστο</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>TCS (Έλεγχος Πρόσφυσης)</label>
                    <select name="vehicles[${index}][TCS_id]" class="form-control">
                        <option value="">Δεν καθορίστηκε</option>
                        <option value="1">Ναι - Λειτουργεί</option>
                        <option value="2">Ναι - Δε Λειτουργεί</option>
                        <option value="3">Όχι</option>
                        <option value="4">Άγνωστο</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Σχόλια για Ηλεκτρονικά Συστήματα</label>
                    <textarea name="vehicles[${index}][vehicleElectronicsComments]" class="form-control" rows="2"></textarea>
                </div>

                <div class="form-group">
                    <label>Γενικά Σχόλια</label>
                    <textarea name="vehicles[${index}][vehicleGeneralComments]" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </div>
    `;

        return div;
    }

    function addVehicle() {
        const container = document.getElementById('vehicle-forms');
        const vehicleForm = createVehicleForm(vehicleCount);
        container.appendChild(vehicleForm);
        vehicleCount++;
    }

    function removeVehicle(button) {
        if (confirm('Είστε σίγουροι ότι θέλετε να αφαιρέσετε αυτό το όχημα;')) {
            button.closest('.vehicle-form').remove();
            vehicleCount--;

            // Renumber remaining vehicles
            const vehicleForms = document.querySelectorAll('.vehicle-form');
            vehicleForms.forEach((form, index) => {
                const header = form.querySelector('.vehicle-header h4');
                header.textContent = `Όχημα ${index + 1}`;

                // Update form field names
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    if (input.name) {
                        input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                    }
                });
            });
        }
    }

    // Update the existing nextStep function to generate vehicle forms
    function nextStep() {
        if (validateCurrentStep()) {
            if (currentStep < totalSteps) {
                currentStep++;
                updateStepDisplay();
                scrollToTop();

                // Generate vehicle forms when entering step 2
                if (currentStep === 2) {
                    generateVehicleForms();
                }
            }
        }
    }


    function setupFileUpload() {
        const uploadArea = document.getElementById('file-upload-area');
        const fileInput = document.getElementById('images');

        uploadArea.addEventListener('click', () => fileInput.click());

        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', function(e) {
            handleFiles(e.target.files);
        });
    }

    function handleFiles(files) {
        Array.from(files).forEach(file => {
            if (validateFile(file)) {
                selectedFiles.push(file);
                addFileToList(file);
            }
        });
    }

    function validateFile(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        const maxSize = 5 * 1024 * 1024; // 5MB

        if (!allowedTypes.includes(file.type)) {
            alert(`Το αρχείο "${file.name}" δεν είναι υποστηριζόμενος τύπος εικόνας.`);
            return false;
        }

        if (file.size > maxSize) {
            alert(`Το αρχείο "${file.name}" είναι πολύ μεγάλο. Μέγιστο μέγεθος: 5MB.`);
            return false;
        }

        return true;
    }

    function addFileToList(file) {
        const fileList = document.getElementById('file-list');
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';

        fileItem.innerHTML = `
        <span class="file-item-name">📷 ${file.name} (${formatFileSize(file.size)})</span>
        <button type="button" class="file-item-remove" onclick="removeFile('${file.name}', this)">Αφαίρεση</button>
    `;

        fileList.appendChild(fileItem);
    }

    function removeFile(fileName, button) {
        selectedFiles = selectedFiles.filter(file => file.name !== fileName);
        button.parentElement.remove();
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }



    // Add form submission handler
    function submitForm() {
        if (validateCurrentStep()) {
            if (confirm('Είστε σίγουροι ότι θέλετε να υποβάλετε την εγγραφή; Δεν θα μπορείτε να την τροποποιήσετε.')) {
                // Update form action
                document.querySelector('input[name="action"]').value = 'submit';

                // Show loading state
                const submitBtn = document.getElementById('submit-btn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '⏳ Υποβολή...';

                // Submit form
                document.getElementById('accident-form').submit();
            }
        }
    }

    // Add event listener for submit button
    document.addEventListener('DOMContentLoaded', function() {
        // Add to your existing DOMContentLoaded function
        document.getElementById('submit-btn').addEventListener('click', function(e) {
            e.preventDefault();
            submitForm();
        });
    });

</script>
</body>
</html>