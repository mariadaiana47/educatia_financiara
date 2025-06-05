<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirectTo('dashboard.php');
}

$page_title = 'Recuperare Parolă - ' . SITE_NAME;
$error_message = '';
$success_message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recover'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Sesiunea a expirat. Te rugăm să încerci din nou.';
    } else {
        $email = sanitizeInput($_POST['email']);
        

        if (empty($email) || !isValidEmail($email)) {
            $error_message = 'Te rugăm să introduci o adresă de email validă.';
        } else {
            try {

                $stmt = $pdo->prepare("SELECT id, nume FROM users WHERE email = ? AND activ = TRUE");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                
                    $success_message = "Dacă adresa de email există în sistemul nostru, vei primi în curând instrucțiuni pentru resetarea parolei.";
                    

                    error_log("Password reset requested for: $email");
                } else {
                   
                    $success_message = "Dacă adresa de email există în sistemul nostru, vei primi în curând instrucțiuni pentru resetarea parolei.";
                }
            } catch (PDOException $e) {
                $error_message = 'A apărut o eroare. Te rugăm să încerci din nou.';
                error_log("Password recovery error: " . $e->getMessage());
            }
        }
    }
}

include 'components/header.php';
?>

<div class="auth-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <img src="assets/logo.png" alt="Logo" class="mb-3" style="height: 60px;" onerror="this.style.display='none'">
                            <h2 class="card-title text-primary fw-bold">Recuperare Parolă</h2>
                            <p class="text-muted">Introdu adresa de email și îți vom trimite instrucțiuni pentru resetarea parolei</p>
                        </div>

                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= sanitizeInput($error_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= sanitizeInput($success_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="recuperare-parola.php" id="recoverForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="mb-4">
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
                                <small class="form-text text-muted">
                                    Vei primi un email cu instrucțiuni pentru resetarea parolei.
                                </small>
                            </div>

                            <button type="submit" name="recover" class="btn btn-primary btn-lg w-100 mb-4">
                                <i class="fas fa-paper-plane me-2"></i>Trimite instrucțiuni
                            </button>
                        </form>

                        <div class="text-center">
                            <div class="mb-3">
                                <a href="login.php" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-arrow-left me-2"></i>Înapoi la conectare
                                </a>
                                <a href="register.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-user-plus me-2"></i>Creează cont nou
                                </a>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="text-muted mb-2">
                                <i class="fas fa-info-circle me-2"></i>Informații utile:
                            </h6>
                            <ul class="list-unstyled small text-muted mb-0">
                                <li><i class="fas fa-check me-2 text-success"></i>Verifică și folderul spam/junk</li>
                                <li><i class="fas fa-check me-2 text-success"></i>Link-ul de resetare expiră în 24 ore</li>
                                <li><i class="fas fa-check me-2 text-success"></i>Poți solicita o nouă recuperare oricând</li>
                            </ul>
                        </div>
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
    const form = document.getElementById('recoverForm');
    const emailField = document.getElementById('email');
    
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
    
    form.addEventListener('submit', function(e) {
        const email = emailField.value.trim();
        
        if (!email || !validateEmail(email)) {
            e.preventDefault();
            e.stopPropagation();
            emailField.classList.add('is-invalid');
        }
        
        form.classList.add('was-validated');
    });
    
    emailField.focus();
});
)
setTimeout(function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
        if (bootstrap.Alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    });
}, 8000);
</script>

<?php include 'components/footer.php'; ?>