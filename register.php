<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirectTo('dashboard.php');
}

$page_title = 'Înregistrare - ' . SITE_NAME;
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Sesiunea a expirat. Te rugăm să încerci din nou.';
    } else {
        $nume = sanitizeInput($_POST['nume']);
        $email = sanitizeInput($_POST['email']);
        $parola = $_POST['parola'];
        $confirma_parola = $_POST['confirma_parola'];
        
        $errors = [];
        
        if (empty($nume) || strlen($nume) < 2) {
            $errors[] = 'Numele trebuie să aibă minimum 2 caractere.';
        }
        
        if (empty($email) || !isValidEmail($email)) {
            $errors[] = 'Te rugăm să introduci o adresă de email validă.';
        }
        
        if (empty($parola) || !isStrongPassword($parola)) {
            $errors[] = 'Parola trebuie să aibă minimum 8 caractere, o literă mare, o literă mică și o cifră.';
        }
        
        if ($parola !== $confirma_parola) {
            $errors[] = 'Parolele nu se potrivesc.';
        }
        
        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $error_message = MSG_ERROR_EMAIL_EXISTS;
                } else {
                    $parola_hash = password_hash($parola, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO users (nume, email, parola, rol, data_inregistrare) 
                        VALUES (?, ?, ?, 'user', NOW())
                    ");
                    
                    if ($stmt->execute([$nume, $email, $parola_hash])) {
                        $user_id = $pdo->lastInsertId();
                        
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['user_name'] = $nume;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_role'] = 'user';
                        
                        $_SESSION['success_message'] = MSG_SUCCESS_REGISTER;
                        
                        redirectTo('dashboard.php');
                    } else {
                        $error_message = 'A apărut o eroare la crearea contului. Te rugăm să încerci din nou.';
                    }
                }
            } catch (PDOException $e) {
                $error_message = 'A apărut o eroare la înregistrare. Te rugăm să încerci din nou.';
                error_log("Registration error: " . $e->getMessage());
            }
        }
    }
}

include 'components/header.php';
?>

<div class="auth-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <img src="assets/logo.png" alt="Logo" class="mb-3" style="height: 60px;" onerror="this.style.display='none'">
                            <h2 class="card-title text-primary fw-bold">Creează cont nou</h2>
                            <p class="text-muted">Alătură-te comunității noastre și începe să înveți despre educația financiară</p>
                        </div>

                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= $error_message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="register.php" id="registerForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="mb-3">
                                <label for="nume" class="form-label">
                                    <i class="fas fa-user me-2"></i>Numele complet
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="nume" 
                                       name="nume"
                                       placeholder="Introdu numele tău complet"
                                       value="<?= isset($_POST['nume']) ? sanitizeInput($_POST['nume']) : '' ?>"
                                       required
                                       minlength="2">
                                <div class="invalid-feedback">
                                    Numele trebuie să aibă minimum 2 caractere.
                                </div>
                                <div class="valid-feedback">
                                    Perfect!
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Adresa de Email
                                </label>
                                <input type="email" 
                                       class="form-control form-control-lg" 
                                       id="email" 
                                       name="email"
                                       placeholder="exemplu@email.ro"
                                       value="<?= isset($_POST['email']) ? sanitizeInput($_POST['email']) : '' ?>"
                                       required>
                                <div class="invalid-feedback">
                                    Te rugăm să introduci o adresă de email validă.
                                </div>
                                <div class="valid-feedback">
                                    Perfect!
                                </div>
                                <small class="form-text text-muted">
                                    Vom folosi această adresă pentru a-ți trimite actualizări importante.
                                </small>
                            </div>

                            <div class="mb-3">
                                <label for="parola" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Parola
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control form-control-lg" 
                                           id="parola" 
                                           name="parola"
                                           placeholder="Creează o parolă sigură"
                                           required
                                           minlength="8">
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            onclick="togglePassword('parola', this)"
                                            title="Afișează/ascunde parola">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Parola trebuie să aibă minimum 8 caractere, o literă mare, o literă mică și o cifră.
                                </div>
                                <div class="valid-feedback">
                                    Parolă sigură!
                                </div>
                                
                                <div class="password-strength mt-2">
                                    <div class="progress" style="height: 4px;">
                                        <div class="progress-bar" id="passwordStrengthBar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small id="passwordStrengthText" class="form-text text-muted">
                                        Parola trebuie să conțină: minimum 8 caractere, o literă mare, o literă mică și o cifră
                                    </small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="confirma_parola" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Confirmă Parola
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control form-control-lg" 
                                           id="confirma_parola" 
                                           name="confirma_parola"
                                           placeholder="Confirmă parola"
                                           required>
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            onclick="togglePassword('confirma_parola', this)"
                                            title="Afișează/ascunde parola">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Parolele nu se potrivesc.
                                </div>
                                <div class="valid-feedback">
                                    Parolele se potrivesc!
                                </div>
                            </div>

                            <button type="submit" name="register" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i>Creează contul
                            </button>

                            <div class="text-center">
                                <p class="mb-0">Ai deja cont?</p>
                                <a href="login.php" class="btn btn-outline-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Conectează-te
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="index.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>
                        Înapoi la pagina principală
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const form = document.getElementById('registerForm');
    const passwordField = document.getElementById('parola');
    const confirmPasswordField = document.getElementById('confirma_parola');
    const emailField = document.getElementById('email');
    const nameField = document.getElementById('nume');
    
    nameField.addEventListener('input', function() {
        const name = this.value.trim();
        if (name.length >= 2) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
            if (name.length > 0) {
                this.classList.add('is-invalid');
            }
        }
    });
    
    emailField.addEventListener('input', function() {
        const email = this.value.trim();
        if (validateEmail(email)) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
            if (email.length > 0) {
                this.classList.add('is-invalid');
            }
        }
    });
    
    passwordField.addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('passwordStrengthBar');
        const strengthText = document.getElementById('passwordStrengthText');
        
        if (password.length === 0) {
            strengthBar.style.width = '0%';
            strengthBar.className = 'progress-bar';
            strengthText.textContent = 'Parola trebuie să conțină: minimum 8 caractere, o literă mare, o literă mică și o cifră';
            return;
        }
        
        let strength = 0;
        let feedback = [];
        
        if (password.length >= 8) {
            strength += 25;
        } else {
            feedback.push('minimum 8 caractere');
        }
        
        if (/[A-Z]/.test(password)) {
            strength += 25;
        } else {
            feedback.push('o literă mare');
        }
        
        if (/[a-z]/.test(password)) {
            strength += 25;
        } else {
            feedback.push('o literă mică');
        }
        
        if (/[0-9]/.test(password)) {
            strength += 25;
        } else {
            feedback.push('o cifră');
        }
        
        strengthBar.style.width = strength + '%';
        
        if (strength < 50) {
            strengthBar.className = 'progress-bar weak';
            strengthText.textContent = 'Parolă slabă - mai trebuie: ' + feedback.join(', ');
            strengthText.className = 'form-text text-danger';
        } else if (strength < 100) {
            strengthBar.className = 'progress-bar medium';
            strengthText.textContent = 'Parolă medie - mai trebuie: ' + feedback.join(', ');
            strengthText.className = 'form-text text-warning';
        } else {
            strengthBar.className = 'progress-bar strong';
            strengthText.textContent = 'Parolă sigură!';
            strengthText.className = 'form-text text-success';
        }
        
        if (validatePassword(password)) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        }
        
        if (confirmPasswordField.value) {
            validatePasswordConfirmation();
        }
    });
    
    confirmPasswordField.addEventListener('input', validatePasswordConfirmation);
    
    function validatePasswordConfirmation() {
        const password = passwordField.value;
        const confirmPassword = confirmPasswordField.value;
        
        if (confirmPassword && password === confirmPassword) {
            confirmPasswordField.classList.remove('is-invalid');
            confirmPasswordField.classList.add('is-valid');
        } else if (confirmPassword) {
            confirmPasswordField.classList.remove('is-valid');
            confirmPasswordField.classList.add('is-invalid');
        }
    }
    
    form.addEventListener('submit', function(e) {
        if (!validateRegisterForm()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
    
    nameField.focus();
});

function validatePassword(password) {
    return password.length >= 8 && 
           /[A-Z]/.test(password) && 
           /[a-z]/.test(password) && 
           /[0-9]/.test(password);
}

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function showToast(message, type = 'info') {
    // Implementare simplă pentru toast
    const alertClass = type === 'error' ? 'alert-danger' : 'alert-info';
    const toastHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; max-width: 350px;">
            <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('afterbegin', toastHtml);
    
    // Auto-dismiss după 5 secunde
    setTimeout(() => {
        const toast = document.querySelector('.alert.position-fixed');
        if (toast) {
            toast.remove();
        }
    }, 5000);
}

function validateRegisterForm() {
    const name = document.getElementById('nume').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('parola').value;
    const confirmPassword = document.getElementById('confirma_parola').value;
    
    let isValid = true;
    let errors = [];
    
    if (!name || name.length < 2) {
        errors.push('Numele trebuie să aibă minimum 2 caractere.');
        isValid = false;
    }
    
    if (!email || !validateEmail(email)) {
        errors.push('Te rugăm să introduci o adresă de email validă.');
        isValid = false;
    }

    if (!password || !validatePassword(password)) {
        errors.push('Parola trebuie să aibă minimum 8 caractere, o literă mare, o literă mică și o cifră.');
        isValid = false;
    }
    
    if (password !== confirmPassword) {
        errors.push('Parolele nu se potrivesc.');
        isValid = false;
    }
    
    if (!isValid) {
        showToast(errors.join('<br>'), 'error');
    }
    
    return isValid;
}

setTimeout(function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
        if (bootstrap.Alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    });
}, 5000);
</script>

<?php include 'components/footer.php'; ?>