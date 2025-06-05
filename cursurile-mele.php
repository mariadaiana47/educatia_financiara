<?php
require_once 'config.php';

// Verifică dacă utilizatorul este conectat
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'cursurile-mele.php';
    redirectTo('login.php');
}

$page_title = 'Cursurile Mele - ' . SITE_NAME;
$current_user = getCurrentUser();

// Filtre
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';
$nivel_filter = isset($_GET['nivel']) ? sanitizeInput($_GET['nivel']) : 'all';

try {
    // Query pentru cursurile utilizatorului cu progres real calculat pe baza activităților
    $where_conditions = ['ic.user_id = ?'];
    $params = [$_SESSION['user_id']];
    
    if ($status_filter !== 'all') {
        if ($status_filter === 'finalizat') {
            $where_conditions[] = 'ic.finalizat = 1';
        } elseif ($status_filter === 'in_progres') {
            $where_conditions[] = 'ic.finalizat = 0';
        } elseif ($status_filter === 'neincepute') {
            // Filtrăm în PHP după calcularea progresului real
        }
    }
    
    if ($nivel_filter !== 'all') {
        $where_conditions[] = 'c.nivel = ?';
        $params[] = $nivel_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Obține cursurile cu toate activitățile
    $stmt = $pdo->prepare("
        SELECT c.*, ic.finalizat, ic.data_inscriere, ic.timp_petrecut,
               -- Contorizează quiz-urile
               (SELECT COUNT(*) FROM quiz_uri WHERE curs_id = c.id AND activ = 1) as total_quiz,
               (SELECT COUNT(DISTINCT rq.quiz_id) FROM rezultate_quiz rq 
                INNER JOIN quiz_uri q ON rq.quiz_id = q.id 
                WHERE q.curs_id = c.id AND rq.user_id = ? AND rq.promovat = 1) as quiz_completate,
               
               -- Contorizează video-urile
               (SELECT COUNT(*) FROM video_cursuri WHERE curs_id = c.id AND activ = 1) as total_videos,
               (SELECT COUNT(*) FROM progres_video pv 
                INNER JOIN video_cursuri vc ON pv.video_id = vc.id 
                WHERE vc.curs_id = c.id AND pv.user_id = ? AND pv.completat = 1) as videos_completate,
               
               -- Contorizează exercițiile
               (SELECT COUNT(*) FROM exercitii_cursuri WHERE curs_id = c.id AND activ = 1) as total_exercitii,
               (SELECT COUNT(*) FROM progres_exercitii pe 
                INNER JOIN exercitii_cursuri ec ON pe.exercitiu_id = ec.id 
                WHERE ec.curs_id = c.id AND pe.user_id = ? AND pe.completat = 1) as exercitii_completate,
               
               -- Media quiz-urilor
               (SELECT AVG(rq.procentaj) FROM rezultate_quiz rq 
                INNER JOIN quiz_uri q ON rq.quiz_id = q.id 
                WHERE q.curs_id = c.id AND rq.user_id = ? AND rq.promovat = 1) as media_quiz
        FROM inscrieri_cursuri ic
        JOIN cursuri c ON ic.curs_id = c.id
        WHERE $where_clause AND c.activ = 1
        ORDER BY ic.data_inscriere DESC
    ");
    $stmt->execute(array_merge($params, [$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]));
    $cursuri_rezultat = $stmt->fetchAll();
    
    // Calculează progresul real pentru fiecare curs
    $cursuri_mele = [];
    $total_progres = 0;
    $cursuri_cu_progres = 0;
    $cursuri_finalizate = 0;
    
    foreach ($cursuri_rezultat as $curs) {
        // Calculează progresul real pe baza activităților completate
        $total_activitati = $curs['total_quiz'] + $curs['total_videos'] + $curs['total_exercitii'];
        $activitati_completate = $curs['quiz_completate'] + $curs['videos_completate'] + $curs['exercitii_completate'];
        
        $progres_real = $total_activitati > 0 ? ($activitati_completate / $total_activitati) * 100 : 0;
        
        // Adaugă progresul real la curs
        $curs['progres_real'] = $progres_real;
        
        // Determină statusul cursului
        if ($progres_real >= 100) {
            $cursuri_finalizate++;
            $curs['status_real'] = 'finalizat';
        } elseif ($progres_real > 0) {
            $curs['status_real'] = 'in_progres';
        } else {
            $curs['status_real'] = 'neincepute';
        }
        
        // Aplică filtrul de status după calcularea progresului real
        if ($status_filter === 'neincepute' && $curs['status_real'] !== 'neincepute') {
            continue;
        }
        if ($status_filter === 'in_progres' && $curs['status_real'] !== 'in_progres') {
            continue;
        }
        if ($status_filter === 'finalizat' && $curs['status_real'] !== 'finalizat') {
            continue;
        }
        
        $cursuri_mele[] = $curs;
        
        // Calculează pentru statistici
        if ($progres_real > 0) {
            $total_progres += $progres_real;
            $cursuri_cu_progres++;
        }
    }
    
    // Statistici generale
    $total_cursuri = count($cursuri_rezultat);
    $progres_mediu = $cursuri_cu_progres > 0 ? $total_progres / $cursuri_cu_progres : 0;
    $timp_total = array_sum(array_column($cursuri_rezultat, 'timp_petrecut'));
    
} catch (PDOException $e) {
    $cursuri_mele = [];
    $total_cursuri = 0;
    $cursuri_finalizate = 0;
    $progres_mediu = 0;
    $timp_total = 0;
}

include 'components/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 mb-2">
                <i class="fas fa-graduation-cap me-2"></i>Cursurile Mele
                <button class="btn btn-sm btn-outline-primary ms-2" onclick="refreshProgress()" id="refreshBtn">
                    <i class="fas fa-sync-alt me-1"></i>Actualizează progresul
                </button>
            </h1>
            <p class="text-muted">
                Progresul tău real în învățarea educației financiare
            </p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="cursuri.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Explorează mai multe cursuri
            </a>
        </div>
    </div>

    <!-- Statistici rapide cu progres real -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 id="totalCursuri"><?= $total_cursuri ?></h3>
                    <p class="mb-0">Cursuri Înscrise</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 id="cursuriFinaliz"><?= $cursuri_finalizate ?></h3>
                    <p class="mb-0">Cursuri Finalizate</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3 id="progresMediu"><?= number_format($progres_mediu, 1) ?>%</h3>
                    <p class="mb-0">Progres Mediu Real</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?= floor($timp_total / 60) ?>h</h3>
                    <p class="mb-0">Timp Petrecut</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtre -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Toate cursurile</option>
                        <option value="neincepute" <?= $status_filter === 'neincepute' ? 'selected' : '' ?>>Neînceput</option>
                        <option value="in_progres" <?= $status_filter === 'in_progres' ? 'selected' : '' ?>>În progres</option>
                        <option value="finalizat" <?= $status_filter === 'finalizat' ? 'selected' : '' ?>>Finalizate</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="nivel" class="form-label">Nivel</label>
                    <select class="form-select" id="nivel" name="nivel">
                        <option value="all" <?= $nivel_filter === 'all' ? 'selected' : '' ?>>Toate nivelurile</option>
                        <option value="incepator" <?= $nivel_filter === 'incepator' ? 'selected' : '' ?>>Începător</option>
                        <option value="intermediar" <?= $nivel_filter === 'intermediar' ? 'selected' : '' ?>>Intermediar</option>
                        <option value="avansat" <?= $nivel_filter === 'avansat' ? 'selected' : '' ?>>Avansat</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i>Filtrează
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista cursurilor -->
    <?php if (!empty($cursuri_mele)): ?>
        <div class="row" id="coursesList">
            <?php foreach ($cursuri_mele as $curs): ?>
                <div class="col-lg-6 col-md-6 mb-4" data-course-id="<?= $curs['id'] ?>">
                    <div class="card h-100 course-card-enrolled">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h5 class="card-title">
                                        <a href="curs.php?id=<?= $curs['id'] ?>" class="text-decoration-none">
                                            <?= sanitizeInput($curs['titlu']) ?>
                                        </a>
                                    </h5>
                                    <span class="badge badge-level <?= $curs['nivel'] ?>">
                                        <?= ucfirst($curs['nivel']) ?>
                                    </span>
                                </div>
                                
                                <?php if ($curs['progres_real'] >= 100): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>Finalizat
                                    </span>
                                <?php elseif ($curs['progres_real'] > 0): ?>
                                    <span class="badge bg-primary">
                                        <i class="fas fa-play me-1"></i>În progres
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-pause me-1"></i>Neînceput
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="card-text text-muted">
                                <?= sanitizeInput($curs['descriere_scurta'] ?: truncateText($curs['descriere'], 100)) ?>
                            </p>
                            
                            <!-- Progres Real -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Progres Real</small>
                                    <small class="text-muted course-progress-text"><?= number_format($curs['progres_real'], 1) ?>%</small>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar course-progress-bar <?= $curs['progres_real'] >= 100 ? 'bg-success' : '' ?>" 
                                         role="progressbar" 
                                         style="width: <?= $curs['progres_real'] ?>%">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Detalii progres -->
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <small class="text-muted d-block">Quiz-uri</small>
                                    <strong class="course-quiz-progress"><?= $curs['quiz_completate'] ?>/<?= $curs['total_quiz'] ?></strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Video-uri</small>
                                    <strong class="course-video-progress"><?= $curs['videos_completate'] ?>/<?= $curs['total_videos'] ?></strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Exerciții</small>
                                    <strong class="course-exercise-progress"><?= $curs['exercitii_completate'] ?>/<?= $curs['total_exercitii'] ?></strong>
                                </div>
                            </div>
                            
                            <!-- Informații suplimentare -->
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">Media Quiz</small>
                                    <strong><?= $curs['media_quiz'] ? number_format($curs['media_quiz'], 1) . '%' : 'N/A' ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Înscris pe</small>
                                    <strong><?= date('d.m.Y', strtotime($curs['data_inscriere'])) ?></strong>
                                </div>
                            </div>
                            
                            <!-- Acțiuni -->
                            <div class="d-grid gap-2">
                                <?php if ($curs['progres_real'] >= 100): ?>
                                    <a href="curs.php?id=<?= $curs['id'] ?>" class="btn btn-outline-success">
                                        <i class="fas fa-medal me-2"></i>Vezi cursul finalizat
                                    </a>
                                <?php elseif ($curs['progres_real'] > 0): ?>
                                    <a href="curs.php?id=<?= $curs['id'] ?>" class="btn btn-primary">
                                        <i class="fas fa-play me-2"></i>Continuă cursul
                                    </a>
                                <?php else: ?>
                                    <a href="curs.php?id=<?= $curs['id'] ?>" class="btn btn-success">
                                        <i class="fas fa-play me-2"></i>Începe cursul
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($curs['total_quiz'] > 0): ?>
                                    <a href="quiz.php?curs_id=<?= $curs['id'] ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-question-circle me-2"></i>
                                        Quiz-uri (<?= $curs['total_quiz'] ?>)
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Progres general -->
        <?php if ($cursuri_finalizate < $total_cursuri): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5>
                                <i class="fas fa-trophy text-warning me-2"></i>
                                Continuă să înveți!
                            </h5>
                            <p class="text-muted">
                                Ai finalizat <span id="statsFinalizate"><?= $cursuri_finalizate ?></span> din <span id="statsTotal"><?= $total_cursuri ?></span> cursuri. 
                                Progresul tău mediu real este <span id="statsProgres"><?= number_format($progres_mediu, 1) ?>%</span>.
                            </p>
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar bg-warning" role="progressbar" 
                                     style="width: <?= $total_cursuri > 0 ? ($cursuri_finalizate / $total_cursuri) * 100 : 0 ?>%"
                                     id="generalProgress">
                                </div>
                            </div>
                            <a href="cursuri.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Adaugă mai multe cursuri
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- Stare goală -->
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-graduation-cap fa-4x text-muted"></i>
            </div>
            <h3 class="mb-3">Nu ești înscris la niciun curs încă</h3>
            <p class="text-muted mb-4">
                Începe călătoria ta în educația financiară! Explorează cursurile noastre și 
                alege cele care te interesează cel mai mult.
            </p>
            <a href="cursuri.php" class="btn btn-primary btn-lg">
                <i class="fas fa-search me-2"></i>Explorează cursurile
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.course-card-enrolled {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.course-card-enrolled:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.course-card-enrolled .progress {
    height: 8px;
    border-radius: 4px;
}

.course-card-enrolled .badge-level.incepator {
    background-color: #28a745;
    color: white;
}

.course-card-enrolled .badge-level.intermediar {
    background-color: #ffc107;
    color: #212529;
}

.course-card-enrolled .badge-level.avansat {
    background-color: #dc3545;
    color: white;
}

.course-card-enrolled .card-title a {
    color: var(--dark-color);
    transition: color 0.3s ease;
}

.course-card-enrolled .card-title a:hover {
    color: var(--primary-color);
}

.course-progress-bar {
    transition: width 0.5s ease;
}

.refresh-animation {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<script>
// Funcție pentru actualizarea progresului în timp real
function refreshProgress() {
    const button = document.getElementById('refreshBtn');
    const icon = button.querySelector('i');
    
    // Animație loading
    icon.classList.add('refresh-animation');
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner refresh-animation me-1"></i>Se actualizează...';
    
    fetch('ajax/get-course-progress-realtime.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=refresh_all_progress'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateProgressDisplay(data.courses);
            updateGeneralStats(data.stats);
            showNotification('Progresul a fost actualizat!', 'success');
        } else {
            showNotification('Eroare la actualizarea progresului', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Eroare de conexiune', 'error');
    })
    .finally(() => {
        // Resetează butonul
        setTimeout(() => {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Actualizează progresul';
        }, 1000);
    });
}

function updateProgressDisplay(courses) {
    courses.forEach(course => {
        const courseCard = document.querySelector(`[data-course-id="${course.id}"]`);
        if (courseCard) {
            // Actualizează bara de progres
            const progressBar = courseCard.querySelector('.course-progress-bar');
            const progressText = courseCard.querySelector('.course-progress-text');
            
            if (progressBar && progressText) {
                progressBar.style.width = course.progres_real + '%';
                progressText.textContent = course.progres_real.toFixed(1) + '%';
                
                // Schimbă culoarea dacă este completat
                if (course.progres_real >= 100) {
                    progressBar.classList.add('bg-success');
                } else {
                    progressBar.classList.remove('bg-success');
                }
            }
            
            // Actualizează contoarele
            const quizProgress = courseCard.querySelector('.course-quiz-progress');
            const videoProgress = courseCard.querySelector('.course-video-progress');
            const exerciseProgress = courseCard.querySelector('.course-exercise-progress');
            
            if (quizProgress) quizProgress.textContent = `${course.quiz_completate}/${course.total_quiz}`;
            if (videoProgress) videoProgress.textContent = `${course.videos_completate}/${course.total_videos}`;
            if (exerciseProgress) exerciseProgress.textContent = `${course.exercitii_completate}/${course.total_exercitii}`;
        }
    });
}

function updateGeneralStats(stats) {
    document.getElementById('totalCursuri').textContent = stats.total_cursuri;
    document.getElementById('cursuriFinaliz').textContent = stats.cursuri_finalizate;
    document.getElementById('progresMediu').textContent = stats.progres_mediu.toFixed(1) + '%';
    
    // Actualizează și textul de rezumat
    document.getElementById('statsFinalizate').textContent = stats.cursuri_finalizate;
    document.getElementById('statsTotal').textContent = stats.total_cursuri;
    document.getElementById('statsProgres').textContent = stats.progres_mediu.toFixed(1) + '%';
    
    // Actualizează bara de progres generală
    const generalProgress = document.getElementById('generalProgress');
    if (generalProgress && stats.total_cursuri > 0) {
        const percentage = (stats.cursuri_finalizate / stats.total_cursuri) * 100;
        generalProgress.style.width = percentage + '%';
    }
}

function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 'alert-info';
    const iconClass = type === 'success' ? 'fas fa-check-circle' : 
                     type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-info-circle';

    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="${iconClass} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Auto-refresh la fiecare 30 de secunde
setInterval(refreshProgress, 30000);

// Refresh când utilizatorul revine pe pagină
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        refreshProgress();
    }
});
</script>

<?php include 'components/footer.php'; ?>