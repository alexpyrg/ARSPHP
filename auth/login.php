<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Είσοδος - Σύστημα Καταγραφής Ατυχημάτων</title>
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
        }

        .login-container {
            background: white;
            width: 100%;
            max-width: 400px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e1e5e9;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .login-header p {
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

        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }

        .login-footer a {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }

        .login-footer a:hover {
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

        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <h1>Είσοδος στο Σύστημα</h1>
        <p>Σύστημα Καταγραφής Ατυχημάτων</p>
    </div>

    <div id="alert-container"></div>

    <form id="loginForm" novalidate>
        <div class="form-group">
            <label for="username" class="form-label">Όνομα Χρήστη</label>
            <input
                type="text"
                id="username"
                name="username"
                class="form-control"
                placeholder="Εισάγετε το όνομα χρήστη"
                required
                autocomplete="username"
            >
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Κωδικός Πρόσβασης</label>
            <div class="password-toggle">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Εισάγετε τον κωδικό πρόσβασης"
                    required
                    autocomplete="current-password"
                >
                <button type="button" class="password-toggle-btn" onclick="togglePassword()">
                    👁️
                </button>
            </div>
        </div>

        <button type="submit" id="loginBtn" class="btn btn-primary">
            Είσοδος
        </button>

        <div class="loading" id="loading">
            Γίνεται επαλήθευση...
        </div>
    </form>

    <div class="login-footer">
        <p><a href="register.php">Δεν έχετε λογαριασμό; Εγγραφείτε εδώ</a></p>
        <p><a href="forgot_password.php">Ξεχάσατε τον κωδικό σας;</a></p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const loading = document.getElementById('loading');
        const alertContainer = document.getElementById('alert-container');

        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Clear previous alerts
            alertContainer.innerHTML = '';

            // Get form data
            const formData = new FormData(loginForm);
            const username = formData.get('username').trim();
            const password = formData.get('password');

            // Validate inputs
            if (!username || !password) {
                showAlert('Παρακαλώ συμπληρώστε όλα τα πεδία!', 'danger');
                return;
            }

            // Show loading state
            loginBtn.disabled = true;
            loginBtn.textContent = 'Γίνεται Είσοδος...';
            loading.style.display = 'block';

            // Submit form
            fetch('login_process.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Επιτυχής είσοδος! Ανακατεύθυνση...', 'success');
                        setTimeout(() => {
                            window.location.href = data.redirect || '../dashboard/';
                        }, 1000);
                    } else {
                        showAlert(data.message || 'Σφάλμα κατά την είσοδο. Προσπαθήστε ξανά.', 'danger');
                        resetForm();
                    }
                })
                .catch(error => {
                    console.error('Login error:', error);
                    showAlert('Σφάλμα σύνδεσης. Προσπαθήστε ξανά.', 'danger');
                    resetForm();
                });
        });

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
            loginBtn.disabled = false;
            loginBtn.textContent = 'Είσοδος';
            loading.style.display = 'none';

            // Clear form errors
            document.querySelectorAll('.form-control').forEach(input => {
                input.classList.remove('error');
            });
        }

        // Real-time validation
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });

            input.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('error');
                }
            });
        });
    });

    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.querySelector('.password-toggle-btn');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleBtn.textContent = '👁';
        } else {
            passwordInput.type = 'password';
            toggleBtn.textContent = '👁️';
        }
    }
</script>
</body>
</html>