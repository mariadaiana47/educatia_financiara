<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirectTo('dashboard.php');
}

$page_title = 'Conectare - ' . SITE_NAME;
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Sesiunea a expirat. Te rugăm să încerci din nou.';
    } else {
        $email = sanitizeInput($_POST['email']);
        $parola = $_POST['parola'];

        if (empty($email) || empty($parola)) {
            $error_message = 'Te rugăm să completezi toate câmpurile.';
        } elseif (!isValidEmail($email)) {
            $error_message = 'Te rugăm să introduci o adresă de email validă.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, nume, email, parola, rol, activ FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($parola, $user['parola'])) {
                    if (!$user['activ']) {
                        $error_message = 'Contul tău a fost dezactivat. Te rugăm să contactezi administratorul.';
                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['nume'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['rol'];

                        $stmt = $pdo->prepare("UPDATE users SET data_actualizare = NOW() WHERE id = ?");
                        $stmt->execute([$user['id']]);

                        $_SESSION['success_message'] = MSG_SUCCESS_LOGIN;

                        // Verificăm rolul utilizatorului pentru redirecționare
                        if ($user['rol'] === 'administrator' || $user['rol'] === 'admin') {
                            // Redirecționăm administratorii către dashboard-ul de admin
                            redirectTo('admin/dashboard-admin.php');
                        } else {
                            // Pentru utilizatorii normali, verificăm dacă există un redirect specificat
                            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard.php';
                            redirectTo($redirect);
                        }
                    }
                } else {
                    $error_message = MSG_ERROR_LOGIN;
                }
            } catch (PDOException $e) {
                $error_message = 'A apărut o eroare la conectare. Te rugăm să încerci din nou.';
                error_log("Login error: " . $e->getMessage());
            }
        }
    }
}

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
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
                            <img src="assets/logo.png" alt="Logo" class="mb-3" style="height: 60px;"
                                onerror="this.style.display='none'">
                            <h2 class="card-title text-primary fw-bold">Conectare</h2>
                            <p class="text-muted">Bine ai revenit! Conectează-te la contul tău</p>
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

                        <form method="POST" action="login.php" id="loginForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Adresa de Email
                                </label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email"
                                    placeholder="exemplu@email.ro"
                                    value="<?= isset($_POST['email']) ? sanitizeInput($_POST['email']) : '' ?>"
                                    required>
                                <div class="invalid-feedback">
                                    Te rugăm să introduci o adresă de email validă.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="parola" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Parola
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control form-control-lg" id="parola"
                                        name="parola" placeholder="Introdu parola ta" required>
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePassword('parola', this)" title="Afișează/ascunde parola">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Te rugăm să introduci parola.
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                                    <label class="form-check-label" for="remember_me">
                                        Ține-mă minte
                                    </label>
                                </div>
                                <a href="recuperare-parola.php" class="text-decoration-none">
                                    Ai uitat parola?
                                </a>
                            </div>

                            <button type="submit" name="login" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Conectează-te
                            </button>

                            <div class="text-center">
                                <p class="mb-0">Nu ai cont încă?</p>
                                <a href="register.php" class="btn btn-outline-primary">
                                    <i class="fas fa-user-plus me-2"></i>Creează cont nou
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
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('loginForm');

        form.addEventListener('submit', function (e) {
            if (!validateLoginForm()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });

        document.getElementById('email').focus();
    });

    function validateLoginForm() {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('parola').value;

        let isValid = true;

        const emailField = document.getElementById('email');
        if (!email || !validateEmail(email)) {
            emailField.classList.add('is-invalid');
            isValid = false;
        } else {
            emailField.classList.remove('is-invalid');
            emailField.classList.add('is-valid');
        }

        const passwordField = document.getElementById('parola');
        if (!password || password.length < 1) {
            passwordField.classList.add('is-invalid');
            isValid = false;
        } else {
            passwordField.classList.remove('is-invalid');
            passwordField.classList.add('is-valid');
        }

        return isValid;
    }

    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(function (alert) {
            if (bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);
</script>

<?php include 'components/footer.php'; ?>