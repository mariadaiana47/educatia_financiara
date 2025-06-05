<?php
require_once 'config.php';

// BLOCARE ADMIN - Adminii nu pot accesa cursuri ca utilizatori
if (isLoggedIn() && isAdmin()) {
    $_SESSION['error_message'] = 'Administratorii nu pot cumpăra sau accesa cursuri. Folosește Admin Panel pentru gestionarea cursurilor.';
    redirectTo('admin/dashboard-admin.php');
}

// Verifică dacă există ID-ul cursului
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectTo('cursuri.php');
}

$curs_id = (int)$_GET['id'];

try {
    // Obține informațiile cursului
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(DISTINCT ic.user_id) as enrolled_count,
               (SELECT COUNT(*) FROM quiz_uri WHERE curs_id = c.id AND activ = 1) as quiz_count
        FROM cursuri c
        LEFT JOIN inscrieri_cursuri ic ON c.id = ic.curs_id
        WHERE c.id = ? AND c.activ = 1
        GROUP BY c.id
    ");
    $stmt->execute([$curs_id]);
    $curs = $stmt->fetch();
    
    if (!$curs) {
        $_SESSION['error_message'] = 'Cursul nu a fost găsit.';
        redirectTo('cursuri.php');
    }
    
    // Obține video-urile cursului (doar pentru utilizatori înscriși non-admin)
    $stmt = $pdo->prepare("
        SELECT v.*, 
               COALESCE((SELECT COUNT(*) FROM progres_video WHERE video_id = v.id AND user_id = ?), 0) as user_progress
        FROM video_cursuri v
        WHERE v.curs_id = ? AND v.activ = 1
        ORDER BY v.ordine ASC, v.data_creare ASC
    ");
    $stmt->execute([isLoggedIn() && !isAdmin() ? $_SESSION['user_id'] : 0, $curs_id]);
    $videos_curs = $stmt->fetchAll();

    // Obține exercițiile cursului (doar pentru utilizatori înscriși non-admin)
    $stmt = $pdo->prepare("
        SELECT e.*, 
               COALESCE((SELECT COUNT(*) FROM progres_exercitii WHERE exercitiu_id = e.id AND user_id = ?), 0) as user_completed
        FROM exercitii_cursuri e
        WHERE e.curs_id = ? AND e.activ = 1
        ORDER BY e.ordine ASC
    ");
    $stmt->execute([isLoggedIn() && !isAdmin() ? $_SESSION['user_id'] : 0, $curs_id]);
    $exercitii_curs = $stmt->fetchAll();
    
    // Verifică dacă utilizatorul este înscris (doar pentru non-admin)
    $is_enrolled = false;
    $enrollment_data = null;
    
    if (isLoggedIn() && !isAdmin()) {
        $stmt = $pdo->prepare("SELECT * FROM inscrieri_cursuri WHERE user_id = ? AND curs_id = ?");
        $stmt->execute([$_SESSION['user_id'], $curs_id]);
        $enrollment_data = $stmt->fetch();
        $is_enrolled = $enrollment_data !== false;
    }
    
    // Obține quiz-urile cursului (doar pentru utilizatori înscriși non-admin)
    $stmt = $pdo->prepare("
        SELECT q.*, 
               (SELECT COUNT(*) FROM intrebari_quiz WHERE quiz_id = q.id AND activ = 1) as questions_count,
               (SELECT rz.procentaj FROM rezultate_quiz rz WHERE rz.quiz_id = q.id AND rz.user_id = ? ORDER BY rz.data_realizare DESC LIMIT 1) as last_score
        FROM quiz_uri q
        WHERE q.curs_id = ? AND q.activ = 1
        ORDER BY q.id ASC
    ");
    $stmt->execute([isLoggedIn() && !isAdmin() ? $_SESSION['user_id'] : 0, $curs_id]);
    $quiz_uri = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Eroare la încărcarea cursului.';
    redirectTo('cursuri.php');
}

$page_title = $curs['titlu'] . ' - ' . SITE_NAME;

// Procesează acțiunile AJAX (doar pentru utilizatori non-admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Trebuie să fii autentificat']);
        exit;
    }
    
    if (isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Administratorii nu pot interacționa cu cursurile ca utilizatori']);
        exit;
    }
    
    switch ($_POST['action']) {
        case 'update_progress':
            $progress = min(100, max(0, (float)$_POST['progress']));
            
            try {
                if ($is_enrolled) {
                    $stmt = $pdo->prepare("
                        UPDATE inscrieri_cursuri 
                        SET progress = ?, 
                            finalizat = ?,
                            timp_petrecut = timp_petrecut + ?
                        WHERE user_id = ? AND curs_id = ?
                    ");
                    $stmt->execute([
                        $progress, 
                        $progress >= 100 ? 1 : 0,
                        (int)($_POST['time_spent'] ?? 0),
                        $_SESSION['user_id'], 
                        $curs_id
                    ]);
                    
                    echo json_encode(['success' => true, 'message' => 'Progres actualizat']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Nu ești înscris la acest curs']);
                }
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Eroare la actualizarea progresului']);
            }
            exit;
            
        case 'mark_lesson_complete':
            $lesson_id = (int)$_POST['lesson_id'];
            echo json_encode(['success' => true, 'message' => 'Lecția a fost marcată ca finalizată']);
            exit;
    }
}

include 'components/header.php';
?>

<div class="container py-4">
    <!-- Header curs -->
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Acasă</a></li>
                    <li class="breadcrumb-item"><a href="cursuri.php">Cursuri</a></li>
                    <li class="breadcrumb-item active"><?= sanitizeInput($curs['titlu']) ?></li>
                </ol>
            </nav>
            
            <h1 class="h2 mb-3"><?= sanitizeInput($curs['titlu']) ?></h1>
            
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="badge badge-level <?= $curs['nivel'] ?> fs-6">
                    <?= ucfirst($curs['nivel']) ?>
                </span>
                <span class="text-muted">
                    <i class="fas fa-clock me-1"></i><?= $curs['durata_minute'] ?> minute
                </span>
                <span class="text-muted">
                    <i class="fas fa-users me-1"></i><?= $curs['enrolled_count'] ?> înscriși
                </span>
                <?php if ($curs['quiz_count'] > 0): ?>
                    <span class="text-muted">
                        <i class="fas fa-question-circle me-1"></i><?= $curs['quiz_count'] ?> quiz-uri
                    </span>
                <?php endif; ?>
            </div>
            
            <!-- Progres doar pentru utilizatori non-admin înscriși -->
            <?php if (!isAdmin() && $is_enrolled && $enrollment_data): ?>
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar <?= $enrollment_data['finalizat'] ? 'bg-success' : 'bg-primary' ?>" 
                         role="progressbar" 
                         style="width: <?= $enrollment_data['progress'] ?>%"
                         id="courseProgress">
                    </div>
                </div>
                <small class="text-muted">
                    Progres: <?= number_format($enrollment_data['progress'], 1) ?>%
                    <?php if ($enrollment_data['finalizat']): ?>
                        <span class="badge bg-success ms-2">
                            <i class="fas fa-check me-1"></i>Finalizat
                        </span>
                    <?php endif; ?>
                </small>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4 text-md-end">
            <?php if (isLoggedIn() && !isAdmin()): ?>
                <?php if ($is_enrolled): ?>
                    <button class="btn btn-success btn-lg" disabled>
                        <i class="fas fa-check me-2"></i>Înscris
                    </button>
                <?php elseif (isInCart($_SESSION['user_id'], $curs_id)): ?>
                    <a href="cos.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-cart me-2"></i>În coș - Vezi coșul
                    </a>
                <?php else: ?>
                    <button class="btn btn-primary btn-lg" onclick="addToCart(<?= $curs_id ?>, this)">
                        <i class="fas fa-shopping-cart me-2"></i>Adaugă în coș - <?= formatPrice($curs['pret']) ?>
                    </button>
                <?php endif; ?>
            <?php elseif (!isLoggedIn()): ?>
                <a href="login.php?redirect=curs.php?id=<?= $curs_id ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i>Conectează-te pentru a cumpăra
                </a>
            <?php endif; ?>
            
            <!-- Mesaj pentru admin -->
            <?php if (isLoggedIn() && isAdmin()): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Modul Administrator:</strong> Poți gestiona acest curs din 
                    <a href="admin/content-manager.php" class="alert-link">Content Manager</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Conținutul principal -->
        <div class="col-lg-8">
            <!-- Descripție -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Despre acest curs
                    </h5>
                </div>
                <div class="card-body">
                    <p class="lead"><?= sanitizeInput($curs['descriere_scurta']) ?></p>
                    <div class="course-description">
                        <?= nl2br(sanitizeInput($curs['descriere'])) ?>
                    </div>
                    
                    <!-- Obiectivele cursului (doar pentru admin) -->
                    <?php if (isAdmin() && !empty($curs['obiective'])): ?>
                        <hr>
                        <h6 class="text-primary">
                            <i class="fas fa-bullseye me-2"></i>Obiectivele cursului:
                        </h6>
                        <div class="admin-course-info">
                            <?= nl2br(sanitizeInput($curs['obiective'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!isAdmin() && $is_enrolled): ?>
                <!-- Video Section -->
                <div class="card mb-4" id="video-section">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-play me-2"></i>Video-uri Curs
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($videos_curs)): ?>
                            <?php foreach ($videos_curs as $index => $video): ?>
                                <div class="video-item mb-4">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="badge bg-primary"><?= $video['ordine'] ?></span>
                                        </div>
                                        <div class="col">
                                            <h6><?= sanitizeInput($video['titlu']) ?></h6>
                                            <p class="text-muted mb-1"><?= sanitizeInput($video['descriere']) ?></p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= gmdate("i:s", $video['durata_secunde']) ?> minute
                                            </small>
                                        </div>
                                        <div class="col-auto">
                                            <button class="btn btn-primary" onclick="watchVideo('<?= sanitizeInput($video['url_video']) ?>', <?= $video['id'] ?>)">
                                                <i class="fas fa-play me-2"></i>Vizionează
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Video Player (inițial ascuns) -->
                                    <div class="video-player mt-3" id="video-player-<?= $video['id'] ?>" style="display: none;">
                                        <div class="ratio ratio-16x9">
                                            <iframe id="iframe-<?= $video['id'] ?>" 
                                                    src="" 
                                                    title="<?= sanitizeInput($video['titlu']) ?>"
                                                    allowfullscreen>
                                            </iframe>
                                        </div>
                                        <div class="mt-2">
                                            <button class="btn btn-success btn-sm" onclick="markVideoComplete(<?= $video['id'] ?>)">
                                                <i class="fas fa-check me-1"></i>Marchează ca finalizat
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-video fa-3x text-muted mb-3"></i>
                                <h6>Nu există video-uri pentru acest curs încă</h6>
                                <p class="text-muted">Video-urile vor fi adăugate în curând.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Exerciții Practice -->
                <div class="card mb-4" id="exercises-section">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tasks me-2"></i>Exerciții Practice
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($exercitii_curs)): ?>
                            <div class="row">
                                <?php foreach ($exercitii_curs as $exercitiu): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="exercise-card p-3 border rounded">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="exercise-icon <?= $exercitiu['tip'] ?> me-3">
                                                    <?php
                                                    $icons = [
                                                        'calculator' => 'fas fa-calculator',
                                                        'document' => 'fas fa-file-download',
                                                        'external_link' => 'fas fa-external-link-alt',
                                                        'quiz' => 'fas fa-question-circle'
                                                    ];
                                                    ?>
                                                    <i class="<?= $icons[$exercitiu['tip']] ?? 'fas fa-tasks' ?>"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0"><?= sanitizeInput($exercitiu['titlu']) ?></h6>
                                                    <span class="exercise-type-badge <?= $exercitiu['tip'] ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $exercitiu['tip'])) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <p class="small text-muted mb-3">
                                                <?= sanitizeInput($exercitiu['descriere']) ?>
                                            </p>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <?php if ($exercitiu['tip'] === 'external_link' && $exercitiu['link_extern']): ?>
                                                    <a href="<?= sanitizeInput($exercitiu['link_extern']) ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       target="_blank">
                                                        <i class="fas fa-external-link-alt me-1"></i>Deschide
                                                    </a>
                                                <?php elseif ($exercitiu['tip'] === 'document' && $exercitiu['fisier_descarcare']): ?>
                                                    <a href="uploads/exercitii/<?= sanitizeInput($exercitiu['fisier_descarcare']) ?>" 
                                                       class="btn btn-sm btn-outline-success" 
                                                       download>
                                                        <i class="fas fa-download me-1"></i>Descarcă
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="startExercise(<?= $exercitiu['id'] ?>)">
                                                        <i class="fas fa-play me-1"></i>Începe
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($exercitiu['user_completed']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Completat
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                <h6>Nu există exerciții pentru acest curs încă</h6>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quiz-uri -->
                <?php if (!empty($quiz_uri)): ?>
                    <div class="card mb-4" id="quiz-section">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-question-circle me-2"></i>Quiz-uri de evaluare
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($quiz_uri as $quiz): ?>
                                <div class="quiz-item p-3 border rounded mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="mb-1"><?= sanitizeInput($quiz['titlu']) ?></h6>
                                            <p class="text-muted small mb-1">
                                                <?= sanitizeInput($quiz['descriere']) ?>
                                            </p>
                                            <div class="d-flex gap-3 small text-muted">
                                                <span>
                                                    <i class="fas fa-questions me-1"></i>
                                                    <?= $quiz['questions_count'] ?> întrebări
                                                </span>
                                                <span>
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= $quiz['timp_limita'] > 0 ? $quiz['timp_limita'] . ' min' : 'Nelimitat' ?>
                                                </span>
                                                <span>
                                                    <i class="fas fa-trophy me-1"></i>
                                                    Nota de trecere: <?= $quiz['punctaj_minim_promovare'] ?>%
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-md-end">
                                            <?php if ($quiz['last_score'] !== null): ?>
                                                <div class="mb-2">
                                                    <span class="badge <?= $quiz['last_score'] >= $quiz['punctaj_minim_promovare'] ? 'bg-success' : 'bg-warning' ?>">
                                                        Ultima încercare: <?= number_format($quiz['last_score'], 1) ?>%
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <a href="quiz-start.php?quiz_id=<?= $quiz['id'] ?>" class="btn btn-primary">
                                                <i class="fas fa-play me-2"></i>
                                                <?= $quiz['last_score'] !== null ? 'Încearcă din nou' : 'Începe quiz-ul' ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php elseif (isAdmin()): ?>
                <!-- Preview pentru admin cu link-uri către gestionare -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <i class="fas fa-cogs fa-3x text-primary mb-3"></i>
                        <h5>Panou Administrare Curs</h5>
                        <p class="text-muted mb-4">
                            Ca administrator, poți gestiona conținutul acestui curs prin instrumentele de administrare.
                        </p>
                        <div class="row justify-content-center">
                            <div class="col-md-4 mb-3">
                                <a href="admin/video-manager.php?curs_id=<?= $curs_id ?>" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-video fa-2x mb-2 d-block"></i>
                                    Video Manager
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="admin/exercise-manager.php?curs_id=<?= $curs_id ?>" class="btn btn-outline-success w-100">
                                    <i class="fas fa-tasks fa-2x mb-2 d-block"></i>
                                    Exercise Manager
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="admin/quiz-manager.php?curs_id=<?= $curs_id ?>" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-question-circle fa-2x mb-2 d-block"></i>
                                    Quiz Manager
                                </a>
                            </div>
                        </div>
                        <hr>
                        <p class="small text-muted">
                            Pentru a vedea cum arată cursul pentru utilizatori, folosește un cont de test non-admin.
                        </p>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Preview pentru utilizatorii neînscriși -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                        <h5>Conținut restricționat</h5>
                        <p class="text-muted">
                            Pentru a accesa videoclipurile, materialele și quiz-urile acestui curs, 
                            trebuie să te înscrii mai întâi.
                        </p>
                        <?php if (isLoggedIn()): ?>
                            <?php if (isInCart($_SESSION['user_id'], $curs_id)): ?>
                                <a href="cos.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>Finalizează cumpărarea
                                </a>
                            <?php else: ?>
                                <button class="btn btn-primary" onclick="addToCart(<?= $curs_id ?>, this)">
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    Adaugă în coș - <?= formatPrice($curs['pret']) ?>
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="login.php?redirect=curs.php?id=<?= $curs_id ?>" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Conectează-te pentru a cumpăra
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Informații curs -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informații Curs
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span>Preț:</span>
                            <strong class="text-primary"><?= formatPrice($curs['pret']) ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span>Durată:</span>
                            <strong><?= $curs['durata_minute'] ?> minute</strong>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span>Nivel:</span>
                            <span class="badge badge-level <?= $curs['nivel'] ?>">
                                <?= ucfirst($curs['nivel']) ?>
                            </span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span>Înscriși:</span>
                            <strong><?= $curs['enrolled_count'] ?> persoane</strong>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span>Quiz-uri:</span>
                            <strong><?= $curs['quiz_count'] ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-2">
                            <span>Video-uri:</span>
                            <strong><?= count($videos_curs) ?></strong>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Ce vei învăța -->
            <?php if (!empty($curs['obiective'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>Ce vei învăța
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="objectives-content">
                            <?= nl2br(sanitizeInput($curs['obiective'])) ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>Ce vei învăța
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Concepte fundamentale de educație financiară
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Strategii practice și aplicabile
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Instrumente de calcul și planificare
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Metode de optimizare financiară
                            </li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Cursuri similare -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-thumbs-up me-2"></i>Cursuri similare
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT id, titlu, pret, nivel
                            FROM cursuri 
                            WHERE nivel = ? AND id != ? AND activ = 1
                            ORDER BY RAND()
                            LIMIT 3
                        ");
                        $stmt->execute([$curs['nivel'], $curs_id]);
                        $cursuri_similare = $stmt->fetchAll();
                        
                        if (!empty($cursuri_similare)):
                            foreach ($cursuri_similare as $curs_similar):
                    ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="curs.php?id=<?= $curs_similar['id'] ?>" class="text-decoration-none">
                                                <?= sanitizeInput($curs_similar['titlu']) ?>
                                            </a>
                                        </h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge badge-level <?= $curs_similar['nivel'] ?>">
                                                <?= ucfirst($curs_similar['nivel']) ?>
                                            </span>
                                            <strong class="text-primary">
                                                <?= formatPrice($curs_similar['pret']) ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                    <?php
                            endforeach;
                        else:
                    ?>
                            <p class="text-muted">Nu există cursuri similare momentan.</p>
                    <?php
                        endif;
                    } catch (PDOException $e) {
                        echo '<p class="text-muted">Nu s-au putut încărca cursurile similare.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript pentru funcționalități interactive -->
<script>
let courseProgress = <?= (!isAdmin() && $is_enrolled) ? $enrollment_data['progress'] : 0 ?>;
let timeSpent = 0;
let startTime = Date.now();

// Funcții pentru video și exerciții (doar pentru non-admin)
<?php if (!isAdmin()): ?>
function watchVideo(videoUrl, videoId) {
    // Ascunde alte video-uri
    document.querySelectorAll('.video-player').forEach(player => {
        player.style.display = 'none';
    });
    
    // Afișează video-ul curent
    const player = document.getElementById('video-player-' + videoId);
    const iframe = document.getElementById('iframe-' + videoId);
    
    // Convertește URL-ul YouTube pentru embed
    let embedUrl = videoUrl;
    if (videoUrl.includes('youtube.com/watch?v=')) {
        const videoIdYT = videoUrl.split('v=')[1].split('&')[0];
        embedUrl = `https://www.youtube.com/embed/${videoIdYT}`;
    } else if (videoUrl.includes('youtu.be/')) {
        const videoIdYT = videoUrl.split('youtu.be/')[1].split('?')[0];
        embedUrl = `https://www.youtube.com/embed/${videoIdYT}`;
    }
    
    iframe.src = embedUrl;
    player.style.display = 'block';
    
    // Scroll la video
    player.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function markVideoComplete(videoId) {
    const button = event.target;
    const originalHtml = button.innerHTML;
    
    // Disable button și show loading
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Se marchează...';
    
    fetch('ajax/mark-video-complete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `video_id=${videoId}&course_id=<?= $curs_id ?>`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Schimbă butonul în success state
            button.className = 'btn btn-success btn-sm';
            button.innerHTML = '<i class="fas fa-check me-1"></i>Finalizat!';
            button.disabled = true;
            
            // Actualizează progresul cursului dacă există
            const courseProgress = document.getElementById('courseProgress');
            if (courseProgress && data.course_progress) {
                courseProgress.style.width = data.course_progress.progres_real + '%';
                
                // Actualizează textul de progres
                const progressTexts = document.querySelectorAll('.course-progress-text');
                progressTexts.forEach(text => {
                    text.textContent = data.course_progress.progres_real.toFixed(1) + '%';
                });
                
                // Schimbă culoarea la verde dacă este completat
                if (data.course_progress.progres_real >= 100) {
                    courseProgress.classList.remove('bg-primary');
                    courseProgress.classList.add('bg-success');
                }
            }
            
            // Show success notification
            showAlert('success', data.message);
            
            // Celebrare cu confetti dacă cursul e completat
            if (data.course_progress && data.course_progress.finalizat) {
                setTimeout(() => {
                    showAlert('success', '🎉 Felicitări! Ai finalizat acest curs!');
                }, 1000);
            }
            
        } else {
            // Restore button on error
            button.disabled = false;
            button.innerHTML = originalHtml;
            showAlert('error', data.message || 'Eroare la marcarea video-ului');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        button.disabled = false;
        button.innerHTML = originalHtml;
        showAlert('error', 'Eroare de conexiune: ' + error.message);
    });
}

function startExercise(exerciseId) {
    window.location.href = 'exercitiu.php?id=' + exerciseId;
}

function updateCourseProgress() {
    fetch('ajax/get-course-progress.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `course_id=<?= $curs_id ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const progressBar = document.getElementById('courseProgress');
            if (progressBar) {
                progressBar.style.width = data.progress + '%';
                if (data.progress >= 100) {
                    progressBar.classList.remove('bg-primary');
                    progressBar.classList.add('bg-success');
                }
            }
        }
    });
}

// Funcții pentru marcarea progresului
function markVideoWatched() {
    updateProgress(30, 'Video vizionat');
}

function markContentRead() {
    updateProgress(50, 'Conținut citit');
}

function markExercisesComplete() {
    updateProgress(80, 'Exerciții completate');
}

function updateProgress(newProgress, message) {
    if (!<?= $is_enrolled ? 'true' : 'false' ?>) {
        alert('Trebuie să fii înscris la acest curs pentru a marca progresul.');
        return;
    }
    
    if (newProgress > courseProgress) {
        courseProgress = newProgress;
        timeSpent = Math.floor((Date.now() - startTime) / 1000 / 60); // în minute
        
        fetch('curs.php?id=<?= $curs_id ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_progress&progress=${courseProgress}&time_spent=${timeSpent}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizează bara de progres
                const progressBar = document.getElementById('courseProgress');
                if (progressBar) {
                    progressBar.style.width = courseProgress + '%';
                    if (courseProgress >= 100) {
                        progressBar.classList.remove('bg-primary');
                        progressBar.classList.add('bg-success');
                    }
                }
                
                // Afișează mesaj de succes
                showAlert('success', message + ' - Progres: ' + courseProgress + '%');
                
                // Felicitări pentru finalizare
                if (courseProgress >= 100) {
                    setTimeout(() => {
                        showAlert('success', '🎉 Felicitări! Ai finalizat cursul!');
                    }, 1000);
                }
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Eroare la actualizarea progresului');
        });
    } else {
        showAlert('info', 'Ai marcat deja această secțiune ca finalizată.');
    }
}

// Tracking timp petrecut pe pagină (doar pentru utilizatori înscriși)
window.addEventListener('beforeunload', function() {
    if (<?= $is_enrolled ? 'true' : 'false' ?>) {
        const totalTime = Math.floor((Date.now() - startTime) / 1000 / 60);
        if (totalTime > 0) {
            navigator.sendBeacon('curs.php?id=<?= $curs_id ?>', 
                new URLSearchParams({
                    action: 'update_progress',
                    progress: courseProgress,
                    time_spent: totalTime
                })
            );
        }
    }
});
<?php endif; ?>

// Funcție pentru afișarea alertelor
function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.custom-alert');
    existingAlerts.forEach(alert => alert.remove());

    const alertContainer = document.querySelector('.container');
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 'alert-info';
    const iconClass = type === 'success' ? 'fas fa-check-circle' : 
                     type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-info-circle';

    const alertHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show mt-3 custom-alert" role="alert" style="animation: slideInRight 0.3s ease;">
            <i class="${iconClass} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    alertContainer.insertAdjacentHTML('afterbegin', alertHTML);

    // Auto-hide după 5 secunde
    setTimeout(() => {
        const newAlert = alertContainer.querySelector('.custom-alert');
        if (newAlert) {
            newAlert.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (newAlert.parentElement) {
                    newAlert.remove();
                }
            }, 300);
        }
    }, 5000);
}

// Smooth scroll pentru secțiuni
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>

<?php include 'components/footer.php'; ?>