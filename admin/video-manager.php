<?php
require_once '../config.php';

// Verifică dacă utilizatorul este admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = MSG_ERROR_ACCESS_DENIED;
    header("Location: ../login.php");
    exit();
}

$page_title = 'Gestionare Video Cursuri - Admin';

// Obține ID-ul cursului din URL
$curs_id = isset($_GET['curs_id']) ? (int)$_GET['curs_id'] : 0;

// Verifică dacă cursul există
try {
    $stmt = $pdo->prepare("SELECT * FROM cursuri WHERE id = ?");
    $stmt->execute([$curs_id]);
    $curs = $stmt->fetch();
    
    if (!$curs) {
        $_SESSION['error_message'] = 'Cursul nu a fost găsit.';
        header("Location: content-manager.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Eroare la încărcarea cursului.';
    header("Location: content-manager.php");
    exit();
}

// Procesează acțiunile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Token CSRF invalid.';
        header("Location: video-manager.php?curs_id=$curs_id");
        exit();
    }
    
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'add_video':
                $titlu_video = sanitizeInput($_POST['titlu_video']);
                $descriere_video = sanitizeInput($_POST['descriere_video']);
                $url_video = sanitizeInput($_POST['url_video']);
                $durata_secunde = (int)$_POST['durata_secunde'];
                $ordine = (int)$_POST['ordine'];
                $preview = isset($_POST['preview']) ? 1 : 0;
                
                $stmt = $pdo->prepare("
                    INSERT INTO video_cursuri (curs_id, titlu, descriere, url_video, durata_secunde, ordine, preview, activ, data_creare) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([$curs_id, $titlu_video, $descriere_video, $url_video, $durata_secunde, $ordine, $preview]);
                
                // *** ACTUALIZEAZĂ DURATA CURSULUI ***
                $durata_noua = updateCourseDuration($curs_id);
                
                $_SESSION['success_message'] = "Video adăugat cu succes! Durata cursului actualizată la $durata_noua minute.";
                break;
                
            case 'edit_video':
                $video_id = (int)$_POST['video_id'];
                $titlu_video = sanitizeInput($_POST['titlu_video']);
                $descriere_video = sanitizeInput($_POST['descriere_video']);
                $url_video = sanitizeInput($_POST['url_video']);
                $durata_secunde = (int)$_POST['durata_secunde'];
                $ordine = (int)$_POST['ordine'];
                $preview = isset($_POST['preview']) ? 1 : 0;
                
                $stmt = $pdo->prepare("
                    UPDATE video_cursuri 
                    SET titlu = ?, descriere = ?, url_video = ?, durata_secunde = ?, ordine = ?, preview = ?, data_actualizare = NOW()
                    WHERE id = ? AND curs_id = ?
                ");
                $stmt->execute([$titlu_video, $descriere_video, $url_video, $durata_secunde, $ordine, $preview, $video_id, $curs_id]);
                
                // *** ACTUALIZEAZĂ DURATA CURSULUI ***
                $durata_noua = updateCourseDuration($curs_id);
                
                $_SESSION['success_message'] = "Video actualizat cu succes! Durata cursului recalculată la $durata_noua minute.";
                break;
                
            case 'delete_video':
                $video_id = (int)$_POST['video_id'];
                
                $stmt = $pdo->prepare("DELETE FROM video_cursuri WHERE id = ? AND curs_id = ?");
                $stmt->execute([$video_id, $curs_id]);
                
                // *** ACTUALIZEAZĂ DURATA CURSULUI ***
                $durata_noua = updateCourseDuration($curs_id);
                
                $_SESSION['success_message'] = "Video șters cu succes! Durata cursului actualizată la $durata_noua minute.";
                break;
                
            case 'toggle_status':
                $video_id = (int)$_POST['video_id'];
                
                $stmt = $pdo->prepare("UPDATE video_cursuri SET activ = NOT activ WHERE id = ? AND curs_id = ?");
                $stmt->execute([$video_id, $curs_id]);
                
                // *** ACTUALIZEAZĂ DURATA CURSULUI ***
                $durata_noua = updateCourseDuration($curs_id);
                
                $_SESSION['success_message'] = "Status video actualizat! Durata cursului recalculată la $durata_noua minute.";
                break;
                
            case 'reorder_videos':
                $video_orders = json_decode($_POST['video_orders'], true);
                
                foreach ($video_orders as $video_id => $new_order) {
                    $stmt = $pdo->prepare("UPDATE video_cursuri SET ordine = ? WHERE id = ? AND curs_id = ?");
                    $stmt->execute([$new_order, $video_id, $curs_id]);
                }
                
                $_SESSION['success_message'] = 'Ordinea video-urilor a fost actualizată!';
                break;
        }
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'A apărut o eroare: ' . $e->getMessage();
    }
    
    // Folosește header direct în loc de redirectTo
    header("Location: video-manager.php?curs_id=$curs_id");
    exit();
}

// Obține lista video-urilor pentru cursul curent
try {
    $stmt = $pdo->prepare("
        SELECT v.*, 
               COALESCE((SELECT COUNT(*) FROM progres_video WHERE video_id = v.id), 0) as vizualizari_totale,
               COALESCE((SELECT COUNT(*) FROM progres_video WHERE video_id = v.id AND completat = 1), 0) as completari_totale
        FROM video_cursuri v
        WHERE v.curs_id = ?
        ORDER BY v.ordine ASC, v.data_creare ASC
    ");
    $stmt->execute([$curs_id]);
    $videos = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $videos = [];
}

include '../components/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard-admin.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="content-manager.php">Content Manager</a></li>
                    <li class="breadcrumb-item active">Video Manager</li>
                </ol>
            </nav>
            
            <h1 class="h2 mb-2">
                <i class="fas fa-video me-2"></i>Gestionare Video
            </h1>
            <h4 class="text-primary"><?= sanitizeInput($curs['titlu']) ?></h4>
            <p class="text-muted">
                Adaugă și gestionează video-urile pentru acest curs
                <br><small><strong>Durata actuală curs:</strong> <?= $curs['durata_minute'] ?> minute</small>
            </p>
        </div>
        <div class="col-md-4 text-md-end">
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addVideoModal">
                <i class="fas fa-plus me-2"></i>Adaugă Video
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
                    <h3><?= count($videos) ?></h3>
                    <p class="mb-0">Total Video-uri</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?= count(array_filter($videos, function($v) { return $v['activ']; })) ?></h3>
                    <p class="mb-0">Video-uri Active</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3><?= gmdate("H:i:s", array_sum(array_column($videos, 'durata_secunde'))) ?></h3>
                    <p class="mb-0">Durată Totală</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?= array_sum(array_column($videos, 'vizualizari_totale')) ?></h3>
                    <p class="mb-0">Vizualizări Totale</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista Video-urilor -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Lista Video-urilor
            </h5>
            <div>
                <button class="btn btn-sm btn-outline-secondary" onclick="toggleSortMode()">
                    <i class="fas fa-sort me-1"></i>Reordonează
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($videos)): ?>
                <div id="videoList" class="video-list">
                    <?php foreach ($videos as $video): ?>
                        <div class="video-item <?= !$video['activ'] ? 'inactive' : '' ?>" data-video-id="<?= $video['id'] ?>">
                            <div class="row align-items-center p-3">
                                <div class="col-auto">
                                    <div class="drag-handle" style="display: none;">
                                        <i class="fas fa-grip-vertical"></i>
                                    </div>
                                    <span class="badge bg-secondary"><?= $video['ordine'] ?></span>
                                </div>
                                
                                <div class="col-auto">
                                    <div class="video-thumbnail">
                                        <i class="fas fa-play"></i>
                                    </div>
                                </div>
                                
                                <div class="col">
                                    <div class="video-info">
                                        <h6><?= sanitizeInput($video['titlu']) ?></h6>
                                        <p class="text-muted mb-1"><?= sanitizeInput($video['descriere']) ?></p>
                                        <div class="video-stats">
                                            <span class="me-3">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= gmdate("i:s", $video['durata_secunde']) ?> min
                                            </span>
                                            <span class="me-3">
                                                <i class="fas fa-eye me-1"></i>
                                                <?= $video['vizualizari_totale'] ?> vizualizări
                                            </span>
                                            <span class="me-3">
                                                <i class="fas fa-check-circle me-1"></i>
                                                <?= $video['completari_totale'] ?> completări
                                            </span>
                                            <?php if ($video['preview']): ?>
                                                <span class="preview-badge">Preview</span>
                                            <?php endif; ?>
                                            <?php if (!$video['activ']): ?>
                                                <span class="badge bg-danger">Inactiv</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-auto">
                                    <div class="btn-group-vertical" role="group">
                                        <button class="btn btn-sm btn-outline-warning" 
                                                onclick="toggleVideoStatus(<?= $video['id'] ?>)"
                                                title="<?= $video['activ'] ? 'Dezactivează' : 'Activează' ?>">
                                            <i class="fas fa-<?= $video['activ'] ? 'eye-slash' : 'eye' ?>"></i>
                                        </button>
                                        
                                        <a href="<?= sanitizeInput($video['url_video']) ?>" 
                                           target="_blank"
                                           class="btn btn-sm btn-outline-info"
                                           title="Vezi video">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                        
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteVideo(<?= $video['id'] ?>)"
                                                title="Șterge">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Edit Video -->
                        <div class="modal fade" id="editVideoModal<?= $video['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <form method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="edit_video">
                                    <input type="hidden" name="video_id" value="<?= $video['id'] ?>">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editare Video</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Închide"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="titlu_video" class="form-control" value="<?= sanitizeInput($video['titlu']) ?>" required>
                                                        <label>Titlu Video *</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-floating mb-3">
                                                        <input type="number" name="ordine" class="form-control" value="<?= (int)$video['ordine'] ?>" required>
                                                        <label>Ordinea în curs *</label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-floating mb-3">
                                                <textarea name="descriere_video" class="form-control" style="height: 100px"><?= sanitizeInput($video['descriere']) ?></textarea>
                                                <label>Descrierea video-ului</label>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <div class="form-floating mb-3">
                                                        <input type="url" name="url_video" class="form-control" value="<?= sanitizeInput($video['url_video']) ?>" required>
                                                        <label>URL Video *</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-floating mb-3">
                                                        <input type="number" name="durata_secunde" class="form-control" value="<?= (int)$video['durata_secunde'] ?>" required>
                                                        <label>Durata (secunde) *</label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="preview" <?= $video['preview'] ? 'checked' : '' ?>>
                                                <label class="form-check-label">
                                                    <strong>Video Preview</strong> - Poate fi vizionat fără înscriere la curs
                                                </label>
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
                <div class="text-center py-5">
                    <i class="fas fa-video fa-3x text-muted mb-3"></i>
                    <h6>Nu există video-uri pentru acest curs</h6>
                    <p class="text-muted">Adaugă primul video pentru a începe.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVideoModal">
                        <i class="fas fa-plus me-2"></i>Adaugă primul video
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Adaugă Video -->
<div class="modal fade" id="addVideoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="add_video">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adaugă Video Nou</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-floating mb-3">
                                <input type="text" name="titlu_video" class="form-control" 
                                       placeholder="Titlul video-ului" required>
                                <label>Titlul video-ului *</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input type="number" name="ordine" class="form-control" 
                                       placeholder="Ordinea" value="<?= count($videos) + 1 ?>" required>
                                <label>Ordinea în curs *</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <textarea name="descriere_video" class="form-control" 
                                  placeholder="Descrierea video-ului" style="height: 100px"></textarea>
                        <label>Descrierea video-ului</label>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-floating mb-3">
                                <input type="url" name="url_video" class="form-control" 
                                       placeholder="https://youtube.com/watch?v=..." required>
                                <label>URL Video (YouTube, Vimeo, etc.) *</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input type="number" name="durata_secunde" class="form-control" 
                                       placeholder="Durata în secunde" required>
                                <label>Durata (secunde) *</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="preview">
                        <label class="form-check-label">
                            <strong>Video Preview</strong> - Poate fi vizionat fără înscriere la curs
                        </label>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Sfat:</strong> Asigură-te că video-ul este public sau poate fi embedat. 
                        Pentru YouTube, copiază URL-ul complet (ex: https://www.youtube.com/watch?v=abc123).
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Adaugă Video
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editează Video -->
<div class="modal fade" id="editVideoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="edit_video">
            <input type="hidden" name="video_id" id="edit_video_id">
            
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editează Video</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-floating mb-3">
                                <input type="text" name="titlu_video" id="edit_titlu_video" class="form-control" required>
                                <label>Titlul video-ului *</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input type="number" name="ordine" id="edit_ordine" class="form-control" required>
                                <label>Ordinea în curs *</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <textarea name="descriere_video" id="edit_descriere_video" class="form-control" style="height: 100px"></textarea>
                        <label>Descrierea video-ului</label>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-floating mb-3">
                                <input type="url" name="url_video" id="edit_url_video" class="form-control" required>
                                <label>URL Video *</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input type="number" name="durata_secunde" id="edit_durata_secunde" class="form-control" required>
                                <label>Durata (secunde) *</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="preview" id="edit_preview">
                        <label class="form-check-label">
                            <strong>Video Preview</strong> - Poate fi vizionat fără înscriere la curs
                        </label>
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

<style>
.video-item {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.video-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.video-item.inactive {
    opacity: 0.6;
    background-color: #f8f9fa;
}

.video-thumbnail {
    width: 60px;
    height: 40px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.video-stats span {
    display: inline-flex;
    align-items: center;
    font-size: 0.85rem;
}

.preview-badge {
    background: #28a745;
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.drag-handle {
    cursor: move;
    color: #6c757d;
    padding: 0.5rem;
}

.btn-group-vertical .btn {
    margin-bottom: 0.25rem;
}
</style>

<script>
let sortMode = false;

function toggleSortMode() {
    sortMode = !sortMode;
    const handles = document.querySelectorAll('.drag-handle');
    const button = document.querySelector('button[onclick="toggleSortMode()"]');
    
    if (sortMode) {
        handles.forEach(handle => handle.style.display = 'block');
        button.innerHTML = '<i class="fas fa-check me-1"></i>Salvează Ordinea';
        button.className = 'btn btn-sm btn-success';
    } else {
        handles.forEach(handle => handle.style.display = 'none');
        button.innerHTML = '<i class="fas fa-sort me-1"></i>Reordonează';
        button.className = 'btn btn-sm btn-outline-secondary';
        saveSortOrder();
    }
}

function saveSortOrder() {
    const videoItems = document.querySelectorAll('.video-item');
    const orders = {};
    
    videoItems.forEach((item, index) => {
        const videoId = item.dataset.videoId;
        orders[videoId] = index + 1;
    });
    
    const formData = new FormData();
    formData.append('csrf_token', '<?= generateCSRFToken() ?>');
    formData.append('action', 'reorder_videos');
    formData.append('video_orders', JSON.stringify(orders));
    
    fetch('video-manager.php?curs_id=<?= $curs_id ?>', {
        method: 'POST',
        body: formData
    }).then(() => {
        location.reload();
    });
}

function editVideo(videoId) {
    const videoData = <?= json_encode($videos) ?>.find(v => v.id == videoId);
    
    if (videoData) {
        document.getElementById('edit_video_id').value = videoData.id;
        document.getElementById('edit_titlu_video').value = videoData.titlu;
        document.getElementById('edit_descriere_video').value = videoData.descriere || '';
        document.getElementById('edit_url_video').value = videoData.url_video;
        document.getElementById('edit_durata_secunde').value = videoData.durata_secunde;
        document.getElementById('edit_ordine').value = videoData.ordine;
        document.getElementById('edit_preview').checked = videoData.preview == 1;
        
        const modal = new bootstrap.Modal(document.getElementById('editVideoModal'));
        modal.show();
    }
}

function toggleVideoStatus(videoId) {
    if (confirm('Ești sigur că vrei să modifici statusul acestui video?')) {
        const formData = new FormData();
        formData.append('csrf_token', '<?= generateCSRFToken() ?>');
        formData.append('action', 'toggle_status');
        formData.append('video_id', videoId);
        
        fetch('video-manager.php?curs_id=<?= $curs_id ?>', {
            method: 'POST',
            body: formData
        }).then(() => {
            location.reload();
        });
    }
}

function deleteVideo(videoId) {
    if (confirm('Ești sigur că vrei să ștergi acest video? Această acțiune nu poate fi anulată.')) {
        const formData = new FormData();
        formData.append('csrf_token', '<?= generateCSRFToken() ?>');
        formData.append('action', 'delete_video');
        formData.append('video_id', videoId);
        
        fetch('video-manager.php?curs_id=<?= $curs_id ?>', {
            method: 'POST',
            body: formData
        }).then(() => {
            location.reload();
        });
    }
}
</script>

<?php include '../components/footer.php'; ?>-outline-primary" 
                                                onclick="editVideo(<?= $video['id'] ?>)"
                                                title="Editează">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button class="btn btn-sm btn