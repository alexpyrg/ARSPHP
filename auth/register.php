<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Εγγραφή - Σύστημα Καταγραφής Ατυχημάτων</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }

        .register-container {
            background: white;
            width: 100%;
            max-width: 450px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e1e5e9;
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h1 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .register-header p {
            color: #6c757d;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            color: #2c3e50;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .form-label .required {
            color: #dc3545;
            margin-left: 3px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            background-color: #fff;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .form-control.error {
            border-color: #dc3545;
        }

        .form-control.success {
            border-color: #28a745;
        }

        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            background-color: #fff;
            font-size: 14px;
            cursor: pointer;
            transition: border-color 0.2s ease;
        }

        .form-select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            text-align: center;
            transition: background-color 0.2s ease;
            width: 100%;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-primary:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }

        .alert {
            padding: 12px 16px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeaa7;
        }

        .register-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }

        .register-footer a {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }

        .register-footer a:hover {
            text-decoration: underline;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 10px;
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6c757d;
            font-size: 14px;
        }

        .password-toggle-btn:hover {
            color: #2c3e50;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }

        .password-strength.weak {
            color: #dc3545;
        }

        .password-strength.medium {
            color: #ffc107;
        }

        .password-strength.strong {
            color: #28a745;
        }

        .form-help {
            font-size: 12px;
            color: #6c757d;
            margin-top: 4px;
        }

        .role-info {
            background-color: #f8f9fa;
            padding: 12px;
            border: 1px solid #e1e5e9;
            margin-top: 5px;
            font-size: 12px;
            color: #6c757d;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 500;
            color: white;
            border-radius: 4px;
            margin-right: 5px;
        }

        .badge-primary { background-color: #007bff; }
        .badge-warning { background-color: #ffc107; color: #212529; }
        .badge-danger { background-color: #dc3545; }

        @media (max-width: 480px) {
            .register-container {
                margin: 20px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
<div class="register-container">
    <div class="register-header">
        <h1>Εγγραφή Χρήστη</h1>
        <p>Σύστημα Καταγραφής Ατυχημάτων</p>
    </div>

    <div id="alert-container"></div>

    <form id="registerForm" novalidate>
        <div class="form-group">
            <label for="username" class="form-label">
                Όνομα Χρήστη <span class="required">*</span>
            </label>
            <input
                type="text"
                id="username"
                name="username"
                class="form-control"
                placeholder="Εισάγετε το όνομα χρήστη"
                required
                autocomplete="username"
                minlength="3"
            >
            <div class="form-help">Τουλάχιστον 3 χαρακτήρες</div>
        </div>

        <div class="form-group">
            <label for="email" class="form-label">
                Email <span class="required">*</span>
            </label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                placeholder="Εισάγετε το email σας"
                required
                autocomplete="email"
            >
            <div class="form-help">Θα χρησιμοποιηθεί για ειδοποιήσεις</div>
        </div>

        <div class="form-group">
            <label for="role" class="form-label">
                Ρόλος Χρήστη <span class="required">*</span>
            </label>
            <select id="role" name="role" class="form-select" required>
                <option value="">Επιλέξτε ρόλο</option>
                <option value="registrar">Καταχωρητής</option>
                <option value="expert">Εμπειρογνώμονας</option>
                <option value="admin">Διαχειριστής</option>
            </select>
            <div id="role-info" class="role-info" style="display: none;"></div>
        </div>

        <div class="form-group">
            <label for="password" class="form-label">
                Κωδικός Πρόσβασης <span class="required">*</span>
            </label>
            <div class="password-toggle">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Εισάγετε τον κωδικό πρόσβασης"
                    required
                    autocomplete="new-password"
                    minlength="8"
                >
                <button type="button" class="password-toggle-btn" onclick="togglePassword('password')">
                    👁️
                </button>
            </div>
            <div id="password-strength" class="password-strength"></div>
            <div class="form-help">Τουλάχιστον 8 χαρακτήρες</div>
        </div>

        <div class="form-group">
            <label for="confirm_password" class="form-label">
                Επιβεβαίωση Κωδικού <span class="required">*</span>
            </label>
            <div class="password-toggle">
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="form-control"
                    placeholder="Επιβεβαιώστε τον κωδικό πρόσβασης"
                    required
                    autocomplete="new-password"
                >
                <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password')">
                    👁️
                </button>
            </div>
        </div>

        <button type="submit" id="registerBtn" class="btn btn-primary">
            Εγγραφή
        </button>

        <div class="loading" id="loading">
            Γίνεται εγγραφή...
        </div>
    </form>

    <div class="register-footer">
        <p><a href="login.php">Έχετε ήδη λογαριασμό; Συνδεθείτε εδώ</a></p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');
        const loading = document.getElementById('loading');
        const alertContainer = document.getElementById('alert-container');
        const roleSelect = document.getElementById('role');
        const roleInfo = document.getElementById('role-info');

        // Role descriptions
        const roleDescriptions = {
            'registrar': {
                title: 'Καταχωρητής',
                badge: 'badge-primary',
                description: 'Μπορεί να δημιουργεί και να επεξεργάζεται τις δικές του αναφορές ατυχημάτων.'
            },
            'expert': {
                title: 'Εμπειρογνώμονας',
                badge: 'badge-warning',
                description: 'Μπορεί να βλέπει όλες τις αναφορές, να κάνει ερωτήσεις και να προσθέτει σχόλια.'
            },
            'admin': {
                title: 'Διαχειριστής',
                badge: 'badge-danger',
                description: 'Πλήρης πρόσβαση στο σύστημα, διαχείριση χρηστών και ρυθμίσεων.'
            }
        };

        // Show role info when role is selected
        roleSelect.addEventListener('change', function() {
            const selectedRole = this.value;
            if (selectedRole && roleDescriptions[selectedRole]) {
                const roleData = roleDescriptions[selectedRole];
                roleInfo.innerHTML = `
                        <span class="badge ${roleData.badge}">${roleData.title}</span>
                        ${roleData.description}
                    `;
                roleInfo.style.display = 'block';
            } else {
                roleInfo.style.display = 'none';
            }
        });

        // Password strength checker
        const passwordInput = document.getElementById('password');
        const passwordStrength = document.getElementById('password-strength');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);

            passwordStrength.textContent = strength.text;
            passwordStrength.className = `password-strength ${strength.class}`;
        });

        // Form submission
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Clear previous alerts
            alertContainer.innerHTML = '';

            // Get form data
            const formData = new FormData(registerForm);
            const username = formData.get('username').trim();
            const email = formData.get('email').trim();
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            const role = formData.get('role');

            // Validate inputs
            const validationErrors = validateForm(username, email, password, confirmPassword, role);
            if (validationErrors.length > 0) {
                validationErrors.forEach(error => showAlert(error, 'danger'));
                return;
            }

            // Show loading state
            registerBtn.disabled = true;
            registerBtn.textContent = 'Γίνεται Εγγραφή...';
            loading.style.display = 'block';

            // Submit form
            fetch('register_process.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Επιτυχής εγγραφή! Ανακατεύθυνση στη σελίδα σύνδεσης...', 'success');
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 2000);
                    } else {
                        showAlert(data.message || 'Σφάλμα κατά την εγγραφή. Προσπαθήστε ξανά.', 'danger');
                        resetForm();
                    }
                })
                .catch(error => {
                    console.error('Registration error:', error);
                    showAlert('Σφάλμα σύνδεσης. Προσπαθήστε ξανά.', 'danger');
                    resetForm();
                });
        });

        function validateForm(username, email, password, confirmPassword, role) {
            const errors = [];

            if (!username || username.length < 3) {
                errors.push('Το όνομα χρήστη πρέπει να είναι τουλάχιστον 3 χαρακτήρες!');
            }

            if (!email || !isValidEmail(email)) {
                errors.push('Το email δεν είναι σωστό!');
            }

            if (!password || password.length < 8) {
                errors.push('Ο κωδικός πρόσβασης πρέπει να είναι τουλάχιστον 8 χαρακτήρες!');
            }

            if (password !== confirmPassword) {
                errors.push('Οι κωδικοί πρόσβασης δεν ταιριάζουν!');
            }

            if (!role) {
                errors.push('Παρακαλώ επιλέξτε ρόλο χρήστη!');
            }

            return errors;
        }

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function checkPasswordStrength(password) {
            if (password.length < 8) {
                return { text: 'Πολύ αδύναμος', class: 'weak' };
            }

            let score = 0;
            if (password.length >= 12) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^a-zA-Z0-9]/.test(password)) score++;

            if (score < 3) {
                return { text: 'Αδύναμος', class: 'weak' };
            } else if (score < 4) {
                return { text: 'Μέτριος', class: 'medium' };
            } else {
                return { text: 'Δυνατός', class: 'strong' };
            }
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            alertContainer.appendChild(alertDiv);

            // Auto-hide success alerts
            if (type === 'success') {
                setTimeout(() => {
                    alertDiv.remove();
                }, 3000);
            }
        }

        function resetForm() {
            registerBtn.disabled = false;
            registerBtn.textContent = 'Εγγραφή';
            loading.style.display = 'none';

            // Clear form errors
            document.querySelectorAll('.form-control, .form-select').forEach(input => {
                input.classList.remove('error', 'success');
            });
        }

        // Real-time validation
        document.querySelectorAll('.form-control, .form-select').forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });

            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
            });
        });

        function validateField(field) {
            const value = field.value.trim();
            let isValid = true;

            switch(field.name) {
                case 'username':
                    isValid = value.length >= 3;
                    break;
                case 'email':
                    isValid = isValidEmail(value);
                    break;
                case 'password':
                    isValid = value.length >= 8;
                    break;
                case 'confirm_password':
                    const password = document.getElementById('password').value;
                    isValid = value === password;
                    break;
                case 'role':
                    isValid = value !== '';
                    break;
                default:
                    isValid = value !== '';
            }

            if (isValid) {
                field.classList.remove('error');
                field.classList.add('success');
            } else {
                field.classList.remove('success');
                field.classList.add('error');
            }
        }
    });

    function togglePassword(fieldId) {
        const passwordInput = document.getElementById(fieldId);
        const toggleBtn = passwordInput.nextElementSibling;

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleBtn.textContent = '👁️';
        } else {
            passwordInput.type = 'password';
            toggleBtn.textContent = '👁️';
        }
    }
</script>
</body>
</html>