<?php
require_once 'config.php';

// Verifică dacă utilizatorul este logat
if (!isLoggedIn()) {
    $_SESSION['error_message'] = 'Trebuie să fii conectat pentru a accesa exercițiile.';
    redirectTo('login.php');
}

$exercitiu_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($exercitiu_id <= 0) {
    $_SESSION['error_message'] = 'ID exercițiu invalid.';
    redirectTo('cursurile-mele.php');
}

try {
    // Obține informațiile despre exercițiu și curs
    $stmt = $pdo->prepare("
        SELECT e.*, c.titlu as curs_titlu, c.id as curs_id,
               ic.id as inscriere_id
        FROM exercitii_cursuri e
        INNER JOIN cursuri c ON e.curs_id = c.id
        LEFT JOIN inscrieri_cursuri ic ON c.id = ic.curs_id AND ic.user_id = ?
        WHERE e.id = ? AND e.activ = 1 AND c.activ = 1
    ");
    $stmt->execute([$_SESSION['user_id'], $exercitiu_id]);
    $exercitiu = $stmt->fetch();

    if (!$exercitiu) {
        $_SESSION['error_message'] = 'Exercițiul nu a fost găsit.';
        redirectTo('cursurile-mele.php');
    }

    // Verifică dacă utilizatorul este înscris la curs
    if (!$exercitiu['inscriere_id']) {
        $_SESSION['error_message'] = 'Nu ești înscris la acest curs.';
        redirectTo('curs.php?id=' . $exercitiu['curs_id']);
    }

    // FIXED: Query simplu fără data_progres
    $stmt = $pdo->prepare("
        SELECT completat, data_completare
        FROM progres_exercitii 
        WHERE user_id = ? AND exercitiu_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $exercitiu_id]);
    $progres = $stmt->fetch();

    $page_title = $exercitiu['titlu'] . ' - ' . SITE_NAME;

} catch (PDOException $e) {
    error_log("Error in exercitiu.php: " . $e->getMessage());
    $_SESSION['error_message'] = 'Eroare la încărcarea exercițiului.';
    redirectTo('cursurile-mele.php');
}

include 'components/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="cursurile-mele.php">Cursurile Mele</a></li>
            <li class="breadcrumb-item"><a href="curs.php?id=<?= $exercitiu['curs_id'] ?>"><?= sanitizeInput($exercitiu['curs_titlu']) ?></a></li>
            <li class="breadcrumb-item active">Exercițiu</li>
        </ol>
    </nav>

    <!-- Header Exercițiu -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h4 mb-1">
                                <i class="fas fa-calculator me-2"></i>
                                <?= sanitizeInput($exercitiu['titlu']) ?>
                            </h1>
                            <small class="opacity-75">
                                Curs: <?= sanitizeInput($exercitiu['curs_titlu']) ?>
                            </small>
                        </div>
                        <?php if ($progres && $progres['completat']): ?>
                            <div class="badge bg-success fs-6">
                                <i class="fas fa-check-circle me-1"></i>
                                Completat
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= sanitizeInput($exercitiu['descriere']) ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h6><i class="fas fa-info-circle me-2"></i>Informații</h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <small class="text-muted d-block">Tip</small>
                            <span class="badge bg-secondary"><?= ucfirst($exercitiu['tip']) ?></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Ordine</small>
                            <span class="fw-bold">#<?= $exercitiu['ordine'] ?></span>
                        </div>
                    </div>
                    <?php if ($progres && $progres['completat']): ?>
                        <div class="mt-3 pt-2 border-top">
                            <small class="text-muted d-block">Completat la</small>
                            <small class="fw-bold"><?= date('d.m.Y H:i', strtotime($progres['data_completare'])) ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Conținutul Exercițiului -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div id="exercitiu-content">
                        <?php
                        // Determină ce calculator să afișeze bazat pe titlul exercițiului
                        $calculator_type = determineCalculatorType($exercitiu['titlu']);
                        $calculator_path = "calculators/{$calculator_type}.php";
                        
                        if (file_exists($calculator_path)) {
                            include $calculator_path;
                        } else {
                            // Fallback la calculatorul default
                            if (file_exists('calculators/default_calculator.php')) {
                                include 'calculators/default_calculator.php';
                            } else {
                                echo "<div class='alert alert-warning'>";
                                echo "<h5>Calculator în dezvoltare</h5>";
                                echo "<p>Acest calculator va fi disponibil în curând.</p>";
                                echo "<p><strong>Exercițiu:</strong> " . sanitizeInput($exercitiu['titlu']) . "</p>";
                                echo "</div>";
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Butoane Acțiuni -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-center">
                <a href="curs.php?id=<?= $exercitiu['curs_id'] ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Înapoi la Curs
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Funcții pentru gestionarea progresului
function markAsComplete() {
    updateExerciseProgress(true);
}

function markAsIncomplete() {
    updateExerciseProgress(false);
}

function updateExerciseProgress(completed) {
    const formData = new FormData();
    formData.append('action', 'update_progress');
    formData.append('exercise_id', <?= $exercitiu_id ?>);
    formData.append('completed', completed ? 1 : 0);
    formData.append('csrf_token', '<?= generateCSRFToken() ?>');

    fetch('ajax/exercise-progress.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Eroare: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('A apărut o eroare');
    });
}

function saveProgress() {
    // Salvează progresul curent al calculatorului
    const calculatorData = gatherCalculatorData();
    
    const formData = new FormData();
    formData.append('action', 'save_progress');
    formData.append('exercise_id', <?= $exercitiu_id ?>);
    formData.append('progress_data', JSON.stringify(calculatorData));
    formData.append('csrf_token', '<?= generateCSRFToken() ?>');

    fetch('ajax/exercise-progress.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Progresul a fost salvat!', 'success');
        } else {
            showNotification('Eroare la salvarea progresului', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('A apărut o eroare', 'error');
    });
}

// Funcție pentru colectarea datelor din calculator (va fi suprascrisă de fiecare calculator)
function gatherCalculatorData() {
    return {};
}

// Funcție pentru notificări
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}
</script>

<?php include 'components/footer.php'; ?>

<?php
/**
 * Determină tipul de calculator bazat pe titlul exercițiului
 */
function determineCalculatorType($titlu) {
    $titlu_lower = strtolower($titlu);
    
    // Mapare titluri la tipuri de calculatoare
    $mappings = [
        'dobândă compusă' => 'compound_interest',
        'dobanda compusa' => 'compound_interest',
        'compound interest' => 'compound_interest',
        'economisire' => 'savings_plan',
        'economii' => 'savings_plan',
        'savings' => 'savings_plan',
        'planificator' => 'savings_plan',
        'planificatorul' => 'savings_plan',
        'challenge' => 'expense_tracker',
        'cheltuieli' => 'expense_tracker',
        'expense' => 'expense_tracker',
        'tracking' => 'expense_tracker',
        'găsește' => 'expense_tracker',
        'gaseste' => 'expense_tracker',
        'profil de risc' => 'risk_profile',
        'test profil' => 'risk_profile',
        'portofoliu virtual' => 'portfolio_simulator',
        'simulare portofoliu' => 'portfolio_simulator',
        'dollar cost averaging' => 'dca_simulator',
        'simulare dollar' => 'dca_simulator',
        'dca' => 'dca_simulator',
        'necesar pensie' => 'retirement_calculator',
        'calculator necesar' => 'retirement_calculator',
        'start la 25' => 'age_comparison',
        'start la 35' => 'age_comparison',
        'comparația dramatică' => 'age_comparison',
        '25 vs 35' => 'age_comparison'
    ];
    
    foreach ($mappings as $keyword => $calculator) {
        if (strpos($titlu_lower, $keyword) !== false) {
            return $calculator;
        }
    }
    
    // Default calculator
    return 'default_calculator';
}
?>