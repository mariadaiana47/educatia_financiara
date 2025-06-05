<?php
require_once 'config.php';

// VERIFICARE: Doar administratorii pot crea topicuri noi
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = 'Doar administratorii pot crea topicuri noi!';
    redirectTo('comunitate.php');
}

$page_title = 'Topic Nou - Comunitate - ' . SITE_NAME;

// Procesează formularul
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Token de securitate invalid!';
    } else {
        $titlu = sanitizeInput($_POST['titlu'] ?? '');
        $continut = sanitizeInput($_POST['continut'] ?? '');
        $pinned = isset($_POST['pinned']) ? 1 : 0;
        
        $errors = [];
        
        if (empty($titlu)) {
            $errors[] = 'Titlul este obligatoriu!';
        } elseif (strlen($titlu) < 5) {
            $errors[] = 'Titlul trebuie să aibă minim 5 caractere!';
        } elseif (strlen($titlu) > 200) {
            $errors[] = 'Titlul nu poate avea mai mult de 200 de caractere!';
        }
        
        if (empty($continut)) {
            $errors[] = 'Conținutul este obligatoriu!';
        } elseif (strlen($continut) < 20) {
            $errors[] = 'Conținutul trebuie să aibă minim 20 de caractere!';
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO topicuri_comunitate (autor_id, titlu, continut, pinned, data_creare, activ)
                    VALUES (?, ?, ?, ?, NOW(), 1)
                ");
                $stmt->execute([$_SESSION['user_id'], $titlu, $continut, $pinned]);
                
                $topic_id = $pdo->lastInsertId();
                
                $_SESSION['success_message'] = 'Topicul a fost creat cu succes!';
                redirectTo("topic.php?id=$topic_id");
                
            } catch (PDOException $e) {
                $errors[] = 'A apărut o eroare la salvarea topicului!';
                error_log("Error creating topic: " . $e->getMessage());
            }
        }
    }
}

include 'components/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Adaugă Topic Nou</h1>
                    <p class="text-muted">Creează o nouă discuție în comunitate</p>
                </div>
                <a href="comunitate.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Înapoi
                </a>
            </div>

            <!-- Informații pentru admin -->
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Notă pentru administrator:</strong> 
                Ca admin, ești responsabil pentru crearea topicurilor principale de discuție. 
                Asigură-te că subiectul este relevant pentru educația financiară și că respectă regulile comunității.
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <h6 class="alert-heading">Au apărut următoarele erori:</h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= sanitizeInput($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Formular -->
            <div class="card">
                <div class="card-body">
                    <form method="POST" id="topicForm">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="mb-4">
                            <label for="titlu" class="form-label">
                                <i class="fas fa-heading me-1"></i>Titlu Topic *
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="titlu" 
                                   name="titlu" 
                                   value="<?= isset($_POST['titlu']) ? sanitizeInput($_POST['titlu']) : '' ?>" 
                                   required 
                                   minlength="5" 
                                   maxlength="200"
                                   placeholder="Ex: Cum să economisești pentru prima casă?">
                            <div class="form-text">Minim 5 caractere, maxim 200 caractere</div>
                        </div>

                        <div class="mb-4">
                            <label for="continut" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Conținut *
                            </label>
                            <textarea class="form-control" 
                                      id="continut" 
                                      name="continut" 
                                      rows="8" 
                                      required 
                                      minlength="20"
                                      placeholder="Scrie aici conținutul topicului..."><?= isset($_POST['continut']) ? sanitizeInput($_POST['continut']) : '' ?></textarea>
                            <div class="form-text">Minim 20 caractere. Poți folosi Enter pentru paragrafe noi.</div>
                        </div>

                        <!-- Opțiuni admin -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="pinned" 
                                       name="pinned" 
                                       value="1"
                                       <?= isset($_POST['pinned']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pinned">
                                    <i class="fas fa-thumbtack me-1"></i>
                                    <strong>Marchează ca Pinned</strong> - Topicul va apărea întotdeauna în partea de sus
                                </label>
                            </div>
                        </div>

                        <!-- Sugestii pentru conținut -->
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-lightbulb me-1"></i>Sugestii pentru un topic de succes:
                                </h6>
                                <ul class="mb-0">
                                    <li>Alege un subiect relevant pentru educația financiară</li>
                                    <li>Formulează o întrebare clară sau prezintă o problemă specifică</li>
                                    <li>Oferă context și informații utile</li>
                                    <li>Încurajează discuția constructivă</li>
                                    <li>Evită limbajul ofensator sau promovarea produselor</li>
                                </ul>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus me-2"></i>Publică Topic
                            </button>
                            <a href="comunitate.php" class="btn btn-outline-secondary">
                                Anulează
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#topicForm textarea {
    min-height: 200px;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(44, 90, 160, 0.25);
}

.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}
</style>

<script>
// Auto-resize textarea
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('continut');
    
    function autoResize() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    }
    
    textarea.addEventListener('input', autoResize);
    
    // Validare în timp real
    const form = document.getElementById('topicForm');
    const titlu = document.getElementById('titlu');
    const continut = document.getElementById('continut');
    
    titlu.addEventListener('input', function() {
        if (this.value.length < 5) {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
        } else {
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
        }
    });
    
    continut.addEventListener('input', function() {
        if (this.value.length < 20) {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
        } else {
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
        }
    });
    
    // Confirmare înainte de trimitere
    form.addEventListener('submit', function(e) {
        if (titlu.value.length < 5 || continut.value.length < 20) {
            e.preventDefault();
            alert('Te rugăm să completezi corect toate câmpurile obligatorii!');
        }
    });
});
</script>

<?php include 'components/footer.php'; ?>