<?php
require_once '../config.php';

// Verifică dacă utilizatorul este admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = MSG_ERROR_ACCESS_DENIED;
    redirectTo('../login.php');
}

$page_title = 'Gestionare Exerciții - Admin';

// Obține ID-ul cursului din URL
$curs_id = isset($_GET['curs_id']) ? (int)$_GET['curs_id'] : 0;

// Verifică dacă cursul există
try {
    $stmt = $pdo->prepare("SELECT * FROM cursuri WHERE id = ?");
    $stmt->execute([$curs_id]);
    $curs = $stmt->fetch();
    
    if (!$curs) {
        $_SESSION['error_message'] = 'Cursul nu a fost găsit.';
        redirectTo('content-manager.php');
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Eroare la încărcarea cursului.';
    redirectTo('content-manager.php');
}

// Procesează acțiunile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Token CSRF invalid.';
        redirectTo("exercise-manager.php?curs_id=$curs_id");
    }
    
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'add_exercise':
                $titlu = sanitizeInput($_POST['titlu']);
                $descriere = sanitizeInput($_POST['descriere']);
                $tip = sanitizeInput($_POST['tip']);
                $ordine = (int)$_POST['ordine'];
                $link_extern = isset($_POST['link_extern']) ? sanitizeInput($_POST['link_extern']) : null;
                $fisier_descarcare = null;
                
                // Procesare fișier pentru descărcare
                if ($tip === 'document' && isset($_FILES['fisier_descarcare']) && $_FILES['fisier_descarcare']['error'] === 0) {
                    $upload_dir = '../uploads/exercitii/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_ext = pathinfo($_FILES['fisier_descarcare']['name'], PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '.' . $file_ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['fisier_descarcare']['tmp_name'], $upload_path)) {
                        $fisier_descarcare = $new_filename;
                    }
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO exercitii_cursuri (curs_id, titlu, descriere, tip, link_extern, fisier_descarcare, ordine, activ, data_creare) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([$curs_id, $titlu, $descriere, $tip, $link_extern, $fisier_descarcare, $ordine]);
                
                $_SESSION['success_message'] = 'Exercițiu adăugat cu succes!';
                break;
                
            case 'edit_exercise':
                $exercise_id = (int)$_POST['exercise_id'];
                $titlu = sanitizeInput($_POST['titlu']);
                $descriere = sanitizeInput($_POST['descriere']);
                $tip = sanitizeInput($_POST['tip']);
                $ordine = (int)$_POST['ordine'];
                $link_extern = isset($_POST['link_extern']) ? sanitizeInput($_POST['link_extern']) : null;
                
                // Verifică dacă se încarcă un fișier nou
                $fisier_descarcare = null;
                if ($tip === 'document' && isset($_FILES['fisier_descarcare']) && $_FILES['fisier_descarcare']['error'] === 0) {
                    $upload_dir = '../uploads/exercitii/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_ext = pathinfo($_FILES['fisier_descarcare']['name'], PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '.' . $file_ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['fisier_descarcare']['tmp_name'], $upload_path)) {
                        $fisier_descarcare = $new_filename;
                        
                        // Șterge fișierul vechi
                        $stmt = $pdo->prepare("SELECT fisier_descarcare FROM exercitii_cursuri WHERE id = ?");
                        $stmt->execute([$exercise_id]);
                        $old_file = $stmt->fetchColumn();
                        if ($old_file && file_exists($upload_dir . $old_file)) {
                            unlink($upload_dir . $old_file);
                        }
                    }
                }
                
                // Actualizează exercițiul
                if ($fisier_descarcare) {
                    $stmt = $pdo->prepare("
                        UPDATE exercitii_cursuri 
                        SET titlu = ?, descriere = ?, tip = ?, link_extern = ?, fisier_descarcare = ?, ordine = ?
                        WHERE id = ? AND curs_id = ?
                    ");
                    $stmt->execute([$titlu, $descriere, $tip, $link_extern, $fisier_descarcare, $ordine, $exercise_id, $curs_id]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE exercitii_cursuri 
                        SET titlu = ?, descriere = ?, tip = ?, link_extern = ?, ordine = ?
                        WHERE id = ? AND curs_id = ?
                    ");
                    $stmt->execute([$titlu, $descriere, $tip, $link_extern, $ordine, $exercise_id, $curs_id]);
                }
                
                $_SESSION['success_message'] = 'Exercițiu actualizat cu succes!';
                break;
                
            case 'delete_exercise':
                $exercise_id = (int)$_POST['exercise_id'];
                
                // Șterge fișierul asociat dacă există
                $stmt = $pdo->prepare("SELECT fisier_descarcare FROM exercitii_cursuri WHERE id = ? AND curs_id = ?");
                $stmt->execute([$exercise_id, $curs_id]);
                $file = $stmt->fetchColumn();
                
                if ($file && file_exists('../uploads/exercitii/' . $file)) {
                    unlink('../uploads/exercitii/' . $file);
                }
                
                $stmt = $pdo->prepare("DELETE FROM exercitii_cursuri WHERE id = ? AND curs_id = ?");
                $stmt->execute([$exercise_id, $curs_id]);
                
                $_SESSION['success_message'] = 'Exercițiu șters cu succes!';
                break;
                
            case 'toggle_status':
                $exercise_id = (int)$_POST['exercise_id'];
                
                $stmt = $pdo->prepare("UPDATE exercitii_cursuri SET activ = NOT activ WHERE id = ? AND curs_id = ?");
                $stmt->execute([$exercise_id, $curs_id]);
                
                $_SESSION['success_message'] = 'Status exercițiu actualizat!';
                break;
        }
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'A apărut o eroare: ' . $e->getMessage();
    }
    
    redirectTo("exercise-manager.php?curs_id=$curs_id");
}

// Obține lista exercițiilor
try {
    $stmt = $pdo->prepare("
        SELECT e.*, 
               COALESCE((SELECT COUNT(*) FROM progres_exercitii WHERE exercitiu_id = e.id), 0) as completari_totale
        FROM exercitii_cursuri e
        WHERE e.curs_id = ?
        ORDER BY e.ordine ASC, e.data_creare ASC
    ");
    $stmt->execute([$curs_id]);
    $exercises = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $exercises = [];
}

include '../components/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="content-manager.php">Content Manager</a></li>
                    <li class="breadcrumb-item active">Manager Exerciții</li>
                </ol>
            </nav>
            
            <h1 class="h2 mb-2">
                <i class="fas fa-tasks me-2"></i>Gestionare Exerciții
            </h1>
            <h4 class="text-primary"><?= sanitizeInput($curs['titlu']) ?></h4>
            <p class="text-muted">
                Adaugă și gestionează exercițiile practice pentru acest curs
            </p>
        </div>
        <div class="col-md-4 text-md-end">
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addExerciseModal">
                <i class="fas fa-plus me-2"></i>Adaugă Exercițiu
            </button>
            <a href="../curs.php?id=<?= $curs_id ?>" class="btn btn-outline-primary">
                <i class="fas fa-eye me-2"></i>Vezi Cursul
            </a>
        </div>
    </div>

    <?= displaySessionMessages() ?>

    <!-- Statistici -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?= count($exercises) ?></h3>
                    <p class="mb-0">Total Exerciții</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?= count(array_filter($exercises, function($e) { return $e['activ']; })) ?></h3>
                    <p class="mb-0">Exerciții Active</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3><?= count(array_filter($exercises, function($e) { return $e['tip'] === 'calculator'; })) ?></h3>
                    <p class="mb-0">Calculatoare</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?= array_sum(array_column($exercises, 'completari_totale')) ?></h3>
                    <p class="mb-0">Completări Totale</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista Exercițiilor -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Lista Exercițiilor
            </h5>
        </div>
        <div class="card-body">
            <?php if (!empty($exercises)): ?>
                <div class="exercise-list">
                    <?php foreach ($exercises as $exercise): ?>
                        <div class="exercise-item <?= !$exercise['activ'] ? 'inactive' : '' ?>">
                            <div class="row align-items-center p-3">
                                <div class="col-auto">
                                    <span class="badge bg-secondary"><?= $exercise['ordine'] ?></span>
                                </div>
                                
                                <div class="col-auto">
                                    <div class="exercise-icon">
                                        <?php
                                        $icons = [
                                            'calculator' => 'fas fa-calculator text-primary',
                                            'document' => 'fas fa-file-download text-success',
                                            'external_link' => 'fas fa-external-link-alt text-info',
                                            'quiz' => 'fas fa-question-circle text-warning'
                                        ];
                                        ?>
                                        <i class="<?= $icons[$exercise['tip']] ?? 'fas fa-tasks' ?> fs-4"></i>
                                    </div>
                                </div>
                                
                                <div class="col">
                                    <div class="exercise-info">
                                                                                <h6><?= sanitizeInput($exercise['titlu']) ?></h6>
                                        <p class="mb-1 text-muted"><?= sanitizeInput($exercise['descriere']) ?></p>
                                        <small class="text-muted">
                                            Completări: <?= $exercise['completari_totale'] ?> · 
                                            Creat la: <?= date('d.m.Y', strtotime($exercise['data_creare'])) ?>
                                        </small>
                                    </div>
                                </div>

                                <div class="col-auto text-end">
                                    <form method="post" class="d-inline me-2" action="exercise-manager.php?curs_id=<?= $curs_id ?>" onsubmit="return confirm('Sigur dorești să schimbi statusul exercițiului?');">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="exercise_id" value="<?= $exercise['id'] ?>">
                                        <button class="btn btn-sm <?= $exercise['activ'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>">
                                            <i class="fas <?= $exercise['activ'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                        </button>
                                    </form>

                                    <button class="btn btn-sm btn-outline-primary me-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editExerciseModal<?= $exercise['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <form method="post" class="d-inline" action="exercise-manager.php?curs_id=<?= $curs_id ?>" onsubmit="return confirm('Sigur dorești să ștergi acest exercițiu?');">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="delete_exercise">
                                        <input type="hidden" name="exercise_id" value="<?= $exercise['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Edit Exercise -->
                        <div class="modal fade" id="editExerciseModal<?= $exercise['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form method="post" enctype="multipart/form-data" action="exercise-manager.php?curs_id=<?= $curs_id ?>">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="edit_exercise">
                                    <input type="hidden" name="exercise_id" value="<?= $exercise['id'] ?>">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editare Exercițiu</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Închide"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Titlu</label>
                                                <input type="text" name="titlu" class="form-control" value="<?= sanitizeInput($exercise['titlu']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Descriere</label>
                                                <textarea name="descriere" class="form-control"><?= sanitizeInput($exercise['descriere']) ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Tip</label>
                                                <select name="tip" class="form-select">
                                                    <option value="calculator" <?= $exercise['tip'] === 'calculator' ? 'selected' : '' ?>>Calculator</option>
                                                    <option value="document" <?= $exercise['tip'] === 'document' ? 'selected' : '' ?>>Document</option>
                                                    <option value="external_link" <?= $exercise['tip'] === 'external_link' ? 'selected' : '' ?>>Link extern</option>
                                                    <option value="quiz" <?= $exercise['tip'] === 'quiz' ? 'selected' : '' ?>>Quiz</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Ordine</label>
                                                <input type="number" name="ordine" class="form-control" value="<?= (int)$exercise['ordine'] ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Link Extern (opțional)</label>
                                                <input type="url" name="link_extern" class="form-control" value="<?= sanitizeInput($exercise['link_extern']) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Încarcă fișier nou (opțional)</label>
                                                <input type="file" name="fisier_descarcare" class="form-control">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary">Salvează modificările</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">Nu există exerciții înregistrate pentru acest curs.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Adaugă Exercițiu -->
<div class="modal fade" id="addExerciseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" enctype="multipart/form-data" action="exercise-manager.php?curs_id=<?= $curs_id ?>">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="add_exercise">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adaugă Exercițiu Nou</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Închide"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Titlu</label>
                        <input type="text" name="titlu" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descriere</label>
                        <textarea name="descriere" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tip</label>
                        <select name="tip" class="form-select" required>
                            <option value="calculator">Calculator</option>
                            <option value="document">Document</option>
                            <option value="external_link">Link extern</option>
                            <option value="quiz">Quiz</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ordine</label>
                        <input type="number" name="ordine" class="form-control" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link Extern (opțional)</label>
                        <input type="url" name="link_extern" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fișier Descărcabil (doar pentru tipul document)</label>
                        <input type="file" name="fisier_descarcare" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Adaugă Exercițiu</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../components/footer.php'; ?>
