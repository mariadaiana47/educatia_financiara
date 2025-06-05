<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = MSG_ERROR_ACCESS_DENIED;
    redirectTo('../login.php');
}

$page_title = 'Content Manager - Admin';

// Procesare AJAX pentru acțiuni rapide
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF invalid.']);
        exit;
    }
    
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'toggle_course_status':
                $curs_id = (int)$_POST['curs_id'];
                
                $stmt = $pdo->prepare("UPDATE cursuri SET activ = NOT activ WHERE id = ?");
                $stmt->execute([$curs_id]);
                
                $stmt = $pdo->prepare("SELECT activ FROM cursuri WHERE id = ?");
                $stmt->execute([$curs_id]);
                $nou_status = $stmt->fetchColumn();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Status actualizat cu succes!',
                    'nou_status' => $nou_status
                ]);
                break;
                
            case 'delete_course':
                $curs_id = (int)$_POST['curs_id'];
                
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM inscrieri_cursuri WHERE curs_id = ?");
                $stmt->execute([$curs_id]);
                $inscrieri = $stmt->fetchColumn();
                
                if ($inscrieri > 0) {
                    echo json_encode(['success' => false, 'message' => "Nu poți șterge cursul. Există $inscrieri utilizatori înscriși."]);
                    break;
                }
                
                // Șterge imaginea dacă există
                $stmt = $pdo->prepare("SELECT imagine FROM cursuri WHERE id = ?");
                $stmt->execute([$curs_id]);
                $imagine = $stmt->fetchColumn();
                
                if ($imagine) {
                    $upload_dir = __DIR__ . '/../uploads/cursuri/';
                    if (file_exists($upload_dir . $imagine)) {
                        unlink($upload_dir . $imagine);
                    }
                }
                
                $stmt = $pdo->prepare("DELETE FROM cursuri WHERE id = ?");
                $stmt->execute([$curs_id]);
                
                echo json_encode(['success' => true, 'message' => 'Cursul a fost șters cu succes!']);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Acțiune necunoscută.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Procesare pentru formularele normale (creare/editare)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Token CSRF invalid.';
        header('Location: content-manager.php');
        exit;
    }
    
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'create_course':
                $titlu = sanitizeInput($_POST['titlu']);
                $descriere = sanitizeInput($_POST['descriere']);
                $descriere_scurta = sanitizeInput($_POST['descriere_scurta']);
                $pret = (float)$_POST['pret'];
                $nivel = sanitizeInput($_POST['nivel']);
                $obiective = sanitizeInput($_POST['obiective']);
                $prerequisite = sanitizeInput($_POST['prerequisite']);
                $featured = isset($_POST['featured']) ? 1 : 0;
                
                if (empty($titlu)) {
                    throw new Exception('Titlul cursului este obligatoriu.');
                }
                if (empty($descriere_scurta)) {
                    throw new Exception('Descrierea scurtă este obligatorie.');
                }
                if ($pret < 0) {
                    throw new Exception('Prețul nu poate fi negativ.');
                }
                if (!in_array($nivel, ['incepator', 'intermediar', 'avansat'])) {
                    throw new Exception('Nivelul selectat nu este valid.');
                }
                
                $imagine = null;
                if (isset($_FILES['imagine']) && $_FILES['imagine']['error'] === 0) {
                    $upload_dir = __DIR__ . '/../uploads/cursuri/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!in_array($_FILES['imagine']['type'], $allowed_types)) {
                        throw new Exception('Tipul de fișier nu este permis. Doar JPEG, PNG și GIF.');
                    }
                    
                    if ($_FILES['imagine']['size'] > 5 * 1024 * 1024) {
                        throw new Exception('Fișierul este prea mare. Maxim 5MB.');
                    }
                    
                    $file_ext = pathinfo($_FILES['imagine']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'curs_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['imagine']['tmp_name'], $upload_path)) {
                        $imagine = $new_filename;
                    }
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO cursuri (titlu, descriere, descriere_scurta, pret, durata_minute, nivel, imagine, 
                                       obiective, prerequisite, featured, certificat_disponibil, nota_minima_certificat, 
                                       data_creare, activ) 
                    VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, 1, 7.00, NOW(), 1)
                ");
                
                if ($stmt->execute([
                    $titlu, $descriere, $descriere_scurta, $pret, $nivel, $imagine,
                    $obiective, $prerequisite, $featured
                ])) {
                    $_SESSION['success_message'] = 'Cursul a fost creat cu succes!';
                } else {
                    throw new Exception('Inserarea în baza de date a eșuat.');
                }
                break;
                
            case 'edit_course':
                $curs_id = (int)$_POST['curs_id'];
                $titlu = sanitizeInput($_POST['titlu']);
                $descriere = sanitizeInput($_POST['descriere']);
                $descriere_scurta = sanitizeInput($_POST['descriere_scurta']);
                $pret = (float)$_POST['pret'];
                $nivel = sanitizeInput($_POST['nivel']);
                $obiective = sanitizeInput($_POST['obiective']);
                $prerequisite = sanitizeInput($_POST['prerequisite']);
                $featured = isset($_POST['featured']) ? 1 : 0;
                
                if (empty($titlu)) {
                    throw new Exception('Titlul cursului este obligatoriu.');
                }
                if (empty($descriere_scurta)) {
                    throw new Exception('Descrierea scurtă este obligatorie.');
                }
                if ($pret < 0) {
                    throw new Exception('Prețul nu poate fi negativ.');
                }
                if (!in_array($nivel, ['incepator', 'intermediar', 'avansat'])) {
                    throw new Exception('Nivelul selectat nu este valid.');
                }
                
                $stmt = $pdo->prepare("SELECT imagine FROM cursuri WHERE id = ?");
                $stmt->execute([$curs_id]);
                $curs_existent = $stmt->fetch();
                
                if (!$curs_existent) {
                    throw new Exception('Cursul nu a fost găsit.');
                }
                
                $imagine = $curs_existent['imagine'];
                if (isset($_FILES['imagine']) && $_FILES['imagine']['error'] === 0) {
                    $upload_dir = __DIR__ . '/../uploads/cursuri/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!in_array($_FILES['imagine']['type'], $allowed_types)) {
                        throw new Exception('Tipul de fișier nu este permis. Doar JPEG, PNG și GIF.');
                    }
                    
                    if ($_FILES['imagine']['size'] > 5 * 1024 * 1024) {
                        throw new Exception('Fișierul este prea mare. Maxim 5MB.');
                    }
                    
                    if ($imagine && file_exists($upload_dir . $imagine)) {
                        unlink($upload_dir . $imagine);
                    }
                    
                    $file_ext = pathinfo($_FILES['imagine']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'curs_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['imagine']['tmp_name'], $upload_path)) {
                        $imagine = $new_filename;
                    }
                }
                
                $stmt = $pdo->prepare("
                    UPDATE cursuri 
                    SET titlu = ?, descriere = ?, descriere_scurta = ?, pret = ?, nivel = ?, imagine = ?,
                        obiective = ?, prerequisite = ?, featured = ?, data_actualizare = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $titlu, $descriere, $descriere_scurta, $pret, $nivel, $imagine,
                    $obiective, $prerequisite, $featured, $curs_id
                ]);
                
                $_SESSION['success_message'] = 'Cursul a fost actualizat cu succes!';
                break;
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    header('Location: content-manager.php');
    exit;
}

// Încărcare date cursuri cu calcul venituri CORECT ca în profil.php
try {
    $stmt = $pdo->query("
        SELECT c.*, 
               COUNT(DISTINCT vc.id) as total_videos,
               COUNT(DISTINCT ec.id) as total_exercitii,
               COUNT(DISTINCT q.id) as total_quiz,
               COUNT(DISTINCT ic.user_id) as total_inscrisi
        FROM cursuri c
        LEFT JOIN video_cursuri vc ON c.id = vc.curs_id AND vc.activ = 1
        LEFT JOIN exercitii_cursuri ec ON c.id = ec.curs_id AND ec.activ = 1
        LEFT JOIN quiz_uri q ON c.id = q.curs_id AND q.activ = 1
        LEFT JOIN inscrieri_cursuri ic ON c.id = ic.curs_id
        GROUP BY c.id
        ORDER BY c.data_creare DESC
    ");
    $cursuri = $stmt->fetchAll();
    
    // CALCUL VENITURI TOTALE - ACEEAȘI LOGICĂ CA ÎN PROFIL.PHP
    $stmt = $pdo->query("
        SELECT SUM(pret * enrolled_count) as total_revenue 
        FROM (
            SELECT c.pret, COUNT(ic.user_id) as enrolled_count 
            FROM cursuri c 
            LEFT JOIN inscrieri_cursuri ic ON c.id = ic.curs_id 
            GROUP BY c.id, c.pret
        ) as revenue_calc
    ");
    $total_revenue = $stmt->fetchColumn() ?: 0;
    
} catch (PDOException $e) {
    $cursuri = [];
    $total_revenue = 0;
}

include '../components/header.php';
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .completation { 
            padding: 10px;
        }

        .course-content-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 2rem;
            background: white;
            display: flex;
            flex-direction: column;
        }

        .course-content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .course-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            position: relative;
        }

        .course-level-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .course-level-badge.incepator {
            background: rgba(40, 167, 69, 0.8);
        }

        .course-level-badge.intermediar {
            background: rgba(255, 193, 7, 0.8);
        }

        .course-level-badge.avansat {
            background: rgba(220, 53, 69, 0.8);
        }

        .course-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1.3;
            color: #e9ecef;
        }

        .course-title:hover {
            color: #e9ecef
        }
        
        .course-price {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            opacity: 0.9;
            color: #e9ecef;
        }

        .content-stats {
            margin-bottom: 1.5rem;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-item i {
            margin-right: 0.5rem;
            width: 16px;
            text-align: center;
        }

        .content-completion {
            margin-bottom: 1rem;
        }

        .completion-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .completion-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            border-radius: 4px;
            width: 0%;
            transition: width 1s ease-in-out;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
            padding: 10px;
        }

        .course-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
            margin-top: 1rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .course-actions .btn {
            flex: 1;
            min-width: 44px;
            height: 44px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .course-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .action-buttons .btn {
            transition: all 0.2s ease;
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem;
        }

        .action-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .empty-state {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            text-align: center;
        }

        .form-floating textarea {
            height: 120px;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .toast.success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .toast.error {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        /* Loading state */
        .btn-loading {
            position: relative;
            pointer-events: none;
        }

        .btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .stat-row {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
                gap: 0.25rem;
            }
            
            .course-actions {
                flex-wrap: wrap;
                gap: 0.25rem;
            }
            
            .course-actions .btn {
                flex: 0 0 calc(25% - 0.2rem);
                min-width: 40px;
                height: 40px;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 mb-2">
                <i class="fas fa-cogs me-2"></i>Content Manager
            </h1>
            <p class="text-muted">
                Gestionează cursuri, video-uri, exerciții și quiz-uri
            </p>
        </div>
        <div class="col-md-4 text-md-end">
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#createCourseModal">
                <i class="fas fa-plus me-2"></i>Creează Curs Nou
            </button>
            <a href="dashboard-admin.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Înapoi la Dashboard
            </a>
        </div>
    </div>

    <?= displaySessionMessages() ?>

    <!-- Statistici generale -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?= count($cursuri) ?></h3>
                    <p class="mb-0">Total Cursuri</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?= array_sum(array_column($cursuri, 'total_videos')) ?></h3>
                    <p class="mb-0">Total Video-uri</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3><?= array_sum(array_column($cursuri, 'total_exercitii')) ?></h3>
                    <p class="mb-0">Total Exerciții</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?= formatPrice($total_revenue) ?></h3>
                    <p class="mb-0">Venituri Totale</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista cursurilor -->
    <div class="row">
        <?php if (!empty($cursuri)): ?>
            <?php foreach ($cursuri as $curs): ?>
                <?php
                // Calculează veniturile pentru acest curs specific
                $venituri_curs = $curs['pret'] * $curs['total_inscrisi'];
                ?>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="course-content-card">
                        <div class="course-header">
                            <div class="course-level-badge <?= $curs['nivel'] ?>">
                                <?= ucfirst($curs['nivel']) ?>
                            </div>
                            <?php if (!$curs['activ']): ?>
                                <div class="course-level-badge" style="top: 3.5rem; background: rgba(220, 53, 69, 0.8);">
                                    Inactiv
                                </div>
                            <?php endif; ?>
                            <h5 class="course-title"><?= sanitizeInput($curs['titlu']) ?></h5>
                            <p class="course-price"><?= formatPrice($curs['pret']) ?></p>
                            <div class="mt-2">
                                <small style="opacity: 0.8;">
                                    <i class="fas fa-chart-line me-1"></i>
                                    Venituri: <?= formatPrice($venituri_curs) ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div class="content-stats">
                                <div class="stat-row">
                                    <div class="stat-item">
                                        <i class="fas fa-video text-primary"></i>
                                        <span><?= $curs['total_videos'] ?> Video-uri</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-tasks text-success"></i>
                                        <span><?= $curs['total_exercitii'] ?> Exerciții</span>
                                    </div>
                                </div>
                                
                                <div class="stat-row">
                                    <div class="stat-item">
                                        <i class="fas fa-question-circle text-warning"></i>
                                        <span><?= $curs['total_quiz'] ?> Quiz-uri</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-users text-info"></i>
                                        <span><?= $curs['total_inscrisi'] ?> Înscriși</span>
                                    </div>
                                </div>
                                
                                <div class="stat-row">
                                    <div class="stat-item">
                                        <i class="fas fa-clock text-secondary"></i>
                                        <span><?= $curs['durata_minute'] ?> min</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-calendar text-muted"></i>
                                        <span><?= date('d.m.Y', strtotime($curs['data_creare'])) ?></span>
                                    </div>
                                </div>
                            </div>
                            

                            <div class="content-completion">
                                <?php 
                                $completion = 0;
                                if ($curs['total_videos'] > 0) $completion += 25;
                                if ($curs['total_exercitii'] > 0) $completion += 25;
                                if ($curs['total_quiz'] > 0) $completion += 25;
                                if (!empty($curs['obiective'])) $completion += 25;
                                ?>
                                <div class="completation">
                                <div class="completion-bar">
                                    <div class="completion-fill" data-completion="<?= $completion ?>"></div>
                                </div>
                                <small class="text-muted">Completare conținut: <?= $completion ?>%</small>
                            </div>
                            </div>
                            
                            <div class="action-buttons">
                                <a href="video-manager.php?curs_id=<?= $curs['id'] ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-video me-1"></i>Video-uri
                                </a>
                                <a href="exercise-manager.php?curs_id=<?= $curs['id'] ?>" 
                                   class="btn btn-sm btn-success">
                                    <i class="fas fa-tasks me-1"></i>Exerciții
                                </a>
                                <a href="quiz-manager.php?curs_id=<?= $curs['id'] ?>" 
                                   class="btn btn-sm btn-warning">
                                    <i class="fas fa-question-circle me-1"></i>Quiz-uri
                                </a>
                            </div>
                            
                            <div class="course-actions">
                                <button class="btn btn-outline-primary" 
                                        onclick="editCourse(<?= $curs['id'] ?>)" 
                                        title="Editează cursul">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <button class="btn btn-outline-<?= $curs['activ'] ? 'warning' : 'success' ?>" 
                                        onclick="toggleCourseStatus(<?= $curs['id'] ?>)"
                                        title="<?= $curs['activ'] ? 'Dezactivează' : 'Activează' ?> cursul"
                                        data-course-id="<?= $curs['id'] ?>"
                                        data-current-status="<?= $curs['activ'] ?>">
                                    <i class="fas fa-<?= $curs['activ'] ? 'eye-slash' : 'eye' ?>"></i>
                                </button>
                                
                                <button class="btn btn-outline-danger" 
                                        onclick="deleteCourse(<?= $curs['id'] ?>)"
                                        title="Șterge cursul"
                                        data-course-id="<?= $curs['id'] ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-graduation-cap fa-4x text-muted mb-3"></i>
                    <h4>Nu există cursuri create încă</h4>
                    <p class="text-muted">Creează primul curs pentru a începe.</p>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createCourseModal">
                        <i class="fas fa-plus me-2"></i>Creează primul curs
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Creează Curs -->
<div class="modal fade" id="createCourseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" enctype="multipart/form-data" id="createCourseForm">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="create_course">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Creează Curs Nou
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-floating mb-3">
                                <input type="text" name="titlu" class="form-control" 
                                       placeholder="Titlul cursului" required>
                                <label>Titlul cursului *</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input type="number" name="pret" class="form-control" 
                                       placeholder="Preț" min="0" step="0.01" required>
                                <label>Preț (RON) *</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <textarea name="descriere_scurta" class="form-control" 
                                  placeholder="Descrierea scurtă" required></textarea>
                        <label>Descrierea scurtă *</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <textarea name="descriere" class="form-control" 
                                  placeholder="Descrierea completă"></textarea>
                        <label>Descrierea completă</label>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select name="nivel" class="form-select" required>
                                    <option value="">Selectează nivelul</option>
                                    <option value="incepator">Începător</option>
                                    <option value="intermediar">Intermediar</option>
                                    <option value="avansat">Avansat</option>
                                </select>
                                <label>Nivel *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 pt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="featured" id="featured">
                                    <label class="form-check-label" for="featured">
                                        Curs recomandat (featured)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <textarea name="obiective" class="form-control" 
                                  placeholder="Ce vor învăța studenții"></textarea>
                        <label>Obiectivele cursului</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <textarea name="prerequisite" class="form-control" 
                                  placeholder="Cunoștințe necesare"></textarea>
                        <label>Cunoștințe necesare (prerequisite)</label>
                    </div>
                    
                    <div class="mb-3">
                        <label for="imagine" class="form-label">Imaginea cursului</label>
                        <input type="file" name="imagine" class="form-control" accept="image/*">
                        <div class="form-text">Formatul acceptat: JPEG, PNG, GIF. Mărimea maximă: 5MB.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Creează Cursul
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editează Curs -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" enctype="multipart/form-data" id="editCourseForm">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="edit_course">
            <input type="hidden" name="curs_id" id="edit_curs_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editează Curs
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-floating mb-3">
                                <input type="text" name="titlu" id="edit_titlu" class="form-control" 
                                       placeholder="Titlul cursului" required>
                                <label>Titlul cursului *</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input type="number" name="pret" id="edit_pret" class="form-control" 
                                       placeholder="Preț" min="0" step="0.01" required>
                                <label>Preț (RON) *</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <textarea name="descriere_scurta" id="edit_descriere_scurta" class="form-control" 
                                  placeholder="Descrierea scurtă" required></textarea>
                        <label>Descrierea scurtă *</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <textarea name="descriere" id="edit_descriere" class="form-control" 
                                  placeholder="Descrierea completă"></textarea>
                        <label>Descrierea completă</label>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select name="nivel" id="edit_nivel" class="form-select" required>
                                    <option value="">Selectează nivelul</option>
                                    <option value="incepator">Începător</option>
                                    <option value="intermediar">Intermediar</option>
                                    <option value="avansat">Avansat</option>
                                </select>
                                <label>Nivel *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 pt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="featured" id="edit_featured">
                                    <label class="form-check-label" for="edit_featured">
                                        Curs recomandat (featured)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <textarea name="obiective" id="edit_obiective" class="form-control" 
                                  placeholder="Ce vor învăța studenții"></textarea>
                        <label>Obiectivele cursului</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <textarea name="prerequisite" id="edit_prerequisite" class="form-control" 
                                  placeholder="Cunoștințe necesare"></textarea>
                        <label>Cunoștințe necesare (prerequisite)</label>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_imagine" class="form-label">Schimbă imaginea cursului</label>
                        <input type="file" name="imagine" id="edit_imagine" class="form-control" accept="image/*">
                        <div class="form-text">Lasă gol pentru a păstra imaginea actuală. Formatul acceptat: JPEG, PNG, GIF. Mărimea maximă: 5MB.</div>
                        <div id="current_image_preview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salvează Modificările
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Configurare CSRF Token pentru AJAX
const CSRF_TOKEN = '<?= generateCSRFToken() ?>';

// Funcție pentru afișarea toast-urilor
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    const toastId = 'toast_' + Date.now();
    
    const toastHTML = `
        <div class="toast ${type}" role="alert" id="${toastId}" data-bs-autohide="true" data-bs-delay="4000">
            <div class="toast-header">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                <strong class="me-auto">${type === 'success' ? 'Succes' : 'Eroare'}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Șterge toast-ul după ce se ascunde
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

// Funcție pentru toggle status curs
function toggleCourseStatus(cursId) {
    const button = document.querySelector(`[data-course-id="${cursId}"][data-current-status]`);
    const currentStatus = button.dataset.currentStatus === '1';
    const actionText = currentStatus ? 'dezactivarea' : 'activarea';
    
    if (!confirm(`Ești sigur că vrei să continui cu ${actionText} acestui curs?`)) {
        return;
    }
    
    // Adaugă loading state
    button.classList.add('btn-loading');
    button.disabled = true;
    
    fetch('content-manager.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            action: 'toggle_course_status',
            curs_id: cursId,
            csrf_token: CSRF_TOKEN
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizează butonul
            const newStatus = data.nou_status;
            button.dataset.currentStatus = newStatus;
            button.className = `btn btn-outline-${newStatus ? 'warning' : 'success'}`;
            button.title = `${newStatus ? 'Dezactivează' : 'Activează'} cursul`;
            button.innerHTML = `<i class="fas fa-${newStatus ? 'eye-slash' : 'eye'}"></i>`;
            
            // Actualizează badge-ul de status din header
            const courseCard = button.closest('.course-content-card');
            const statusBadge = courseCard.querySelector('.course-header .course-level-badge[style*="top: 3.5rem"]');
            
            if (newStatus) {
                // Curs activ - șterge badge-ul de inactiv dacă există
                if (statusBadge) {
                    statusBadge.remove();
                }
            } else {
                // Curs inactiv - adaugă badge-ul de inactiv dacă nu există
                if (!statusBadge) {
                    const newBadge = document.createElement('div');
                    newBadge.className = 'course-level-badge';
                    newBadge.style = 'top: 3.5rem; background: rgba(220, 53, 69, 0.8);';
                    newBadge.textContent = 'Inactiv';
                    courseCard.querySelector('.course-header').appendChild(newBadge);
                }
            }
            
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('A apărut o eroare neașteptată', 'error');
    })
    .finally(() => {
        // Elimină loading state
        button.classList.remove('btn-loading');
        button.disabled = false;
    });
}

// Funcție pentru ștergerea cursului
function deleteCourse(cursId) {
    if (!confirm('Ești sigur că vrei să ștergi acest curs? Această acțiune nu poate fi anulată!')) {
        return;
    }
    
    const button = document.querySelector(`[data-course-id="${cursId}"].btn-outline-danger`);
    
    // Adaugă loading state
    button.classList.add('btn-loading');
    button.disabled = true;
    
    fetch('content-manager.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            action: 'delete_course',
            curs_id: cursId,
            csrf_token: CSRF_TOKEN
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Elimină cardul cursului cu animație
            const courseCard = button.closest('.course-content-card').parentElement;
            courseCard.style.transition = 'all 0.5s ease';
            courseCard.style.transform = 'scale(0.8)';
            courseCard.style.opacity = '0';
            
            setTimeout(() => {
                courseCard.remove();
                
                // Verifică dacă mai sunt cursuri
                const remainingCards = document.querySelectorAll('.course-content-card');
                if (remainingCards.length === 0) {
                    location.reload(); // Reîncarcă pentru a afișa empty state
                }
            }, 500);
            
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('A apărut o eroare neașteptată', 'error');
    })
    .finally(() => {
        // Elimină loading state
        button.classList.remove('btn-loading');
        button.disabled = false;
    });
}

// Funcție pentru editarea cursului
function editCourse(cursId) {
    const cursuri = <?= json_encode($cursuri) ?>;
    const curs = cursuri.find(c => c.id == cursId);
    
    if (!curs) {
        showToast('Cursul nu a fost găsit!', 'error');
        return;
    }
    
    // Populează formularul de editare
    document.getElementById('edit_curs_id').value = curs.id;
    document.getElementById('edit_titlu').value = curs.titlu;
    document.getElementById('edit_pret').value = curs.pret;
    document.getElementById('edit_descriere_scurta').value = curs.descriere_scurta || '';
    document.getElementById('edit_descriere').value = curs.descriere || '';
    document.getElementById('edit_nivel').value = curs.nivel;
    document.getElementById('edit_obiective').value = curs.obiective || '';
    document.getElementById('edit_prerequisite').value = curs.prerequisite || '';
    document.getElementById('edit_featured').checked = curs.featured == 1;
    
    // Afișează preview pentru imaginea curentă
    const previewDiv = document.getElementById('current_image_preview');
    if (curs.imagine) {
        previewDiv.innerHTML = `
            <div class="current-image">
                <small class="text-muted">Imaginea curentă:</small><br>
                <img src="../uploads/cursuri/${curs.imagine}" alt="Imaginea cursului" 
                     style="max-width: 200px; max-height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e9ecef;">
            </div>
        `;
    } else {
        previewDiv.innerHTML = '<small class="text-muted">Nu există imagine încărcată</small>';
    }
    
    // Afișează modal-ul
    const modal = new bootstrap.Modal(document.getElementById('editCourseModal'));
    modal.show();
}

// Animație pentru barele de completare
document.addEventListener('DOMContentLoaded', function() {
    const completionBars = document.querySelectorAll('.completion-fill');
    
    completionBars.forEach(bar => {
        const completion = bar.dataset.completion;
        setTimeout(() => {
            bar.style.width = completion + '%';
        }, 300);
    });
});

// Validare și trimitere formular creare
document.getElementById('createCourseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    
    // Validare de bază
    const titlu = formData.get('titlu').trim();
    const descriereScurta = formData.get('descriere_scurta').trim();
    const pret = parseFloat(formData.get('pret'));
    const nivel = formData.get('nivel');
    
    if (!titlu) {
        showToast('Titlul cursului este obligatoriu!', 'error');
        return;
    }
    
    if (!descriereScurta) {
        showToast('Descrierea scurtă este obligatorie!', 'error');
        return;
    }
    
    if (isNaN(pret) || pret < 0) {
        showToast('Prețul trebuie să fie un număr pozitiv!', 'error');
        return;
    }
    
    if (!nivel) {
        showToast('Te rog selectează nivelul cursului!', 'error');
        return;
    }
    
    // Adaugă loading state
    submitButton.classList.add('btn-loading');
    submitButton.disabled = true;
    
    // Trimite formularul
    fetch('content-manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        }
        return response.text();
    })
    .then(() => {
        // Reîncarcă pagina pentru a vedea noul curs
        window.location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('A apărut o eroare la crearea cursului', 'error');
    })
    .finally(() => {
        submitButton.classList.remove('btn-loading');
        submitButton.disabled = false;
    });
});

// Validare și trimitere formular editare
document.getElementById('editCourseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    
    // Validare de bază
    const titlu = formData.get('titlu').trim();
    const descriereScurta = formData.get('descriere_scurta').trim();
    const pret = parseFloat(formData.get('pret'));
    const nivel = formData.get('nivel');
    
    if (!titlu) {
        showToast('Titlul cursului este obligatoriu!', 'error');
        return;
    }
    
    if (!descriereScurta) {
        showToast('Descrierea scurtă este obligatorie!', 'error');
        return;
    }
    
    if (isNaN(pret) || pret < 0) {
        showToast('Prețul trebuie să fie un număr pozitiv!', 'error');
        return;
    }
    
    if (!nivel) {
        showToast('Te rog selectează nivelul cursului!', 'error');
        return;
    }
    
    // Adaugă loading state
    submitButton.classList.add('btn-loading');
    submitButton.disabled = true;
    
    // Trimite formularul
    fetch('content-manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        }
        return response.text();
    })
    .then(() => {
        // Închide modal-ul și reîncarcă pagina
        bootstrap.Modal.getInstance(document.getElementById('editCourseModal')).hide();
        window.location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('A apărut o eroare la actualizarea cursului', 'error');
    })
    .finally(() => {
        submitButton.classList.remove('btn-loading');
        submitButton.disabled = false;
    });
});

// Preview pentru imaginile încărcate
document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Verifică mărimea fișierului
            if (file.size > 5 * 1024 * 1024) {
                showToast('Fișierul este prea mare! Mărimea maximă permisă este 5MB.', 'error');
                this.value = '';
                return;
            }
            
            // Verifică tipul fișierului
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showToast('Tipul de fișier nu este permis! Folosește doar JPEG, PNG sau GIF.', 'error');
                this.value = '';
                return;
            }
            
            // Afișează preview-ul
            const reader = new FileReader();
            reader.onload = function(e) {
                let previewContainer = input.parentNode.querySelector('.image-preview');
                if (!previewContainer) {
                    previewContainer = document.createElement('div');
                    previewContainer.className = 'image-preview mt-2';
                    input.parentNode.appendChild(previewContainer);
                }
                
                previewContainer.innerHTML = `
                    <div class="image-preview-item">
                        <small class="text-muted">Preview:</small><br>
                        <img src="${e.target.result}" alt="Preview" 
                             style="max-width: 200px; max-height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e9ecef;">
                        <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearImagePreview(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            };
            reader.readAsDataURL(file);
        }
    });
});

// Funcție pentru ștergerea preview-ului imaginii
function clearImagePreview(button) {
    const previewContainer = button.closest('.image-preview');
    const fileInput = previewContainer.parentNode.querySelector('input[type="file"]');
    
    fileInput.value = '';
    previewContainer.remove();
}

// Auto-resize pentru textarea-uri
document.querySelectorAll('textarea').forEach(textarea => {
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
});

// Afișează mesajele din sesiune ca toast-uri
<?php if (!empty($_SESSION['success_message'])): ?>
    showToast('<?= addslashes($_SESSION['success_message']) ?>', 'success');
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error_message'])): ?>
    showToast('<?= addslashes($_SESSION['error_message']) ?>', 'error');
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>