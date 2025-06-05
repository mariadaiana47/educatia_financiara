<?php
require_once 'config.php';

// Verifică dacă utilizatorul este conectat
if (!isLoggedIn()) {
    redirectTo('login.php');
}

$page_title = 'Dashboard - ' . SITE_NAME;
$current_user = getCurrentUser();

// DASHBOARD NOU - FOCUSAT PE ACTIVITATE ZILNICĂ ȘI OBIECTIVE
try {
    // 1. ACTIVITATEA DE ASTĂZI - ce a făcut utilizatorul azi
    $stmt = $pdo->prepare("
        SELECT 'video_watched' as tip, v.titlu as detalii, pv.data_completare as timestamp_activitate
        FROM progres_video pv
        JOIN video_cursuri v ON pv.video_id = v.id
        WHERE pv.user_id = ? AND DATE(pv.data_completare) = CURDATE() AND pv.data_completare IS NOT NULL
        
        UNION ALL
        
        SELECT 'quiz_taken' as tip, q.titlu as detalii, rq.data_realizare as timestamp_activitate
        FROM rezultate_quiz rq
        JOIN quiz_uri q ON rq.quiz_id = q.id
        WHERE rq.user_id = ? AND DATE(rq.data_realizare) = CURDATE()
        
        UNION ALL
        
        SELECT 'course_started' as tip, c.titlu as detalii, ic.data_inscriere as timestamp_activitate
        FROM inscrieri_cursuri ic
        JOIN cursuri c ON ic.curs_id = c.id
        WHERE ic.user_id = ? AND DATE(ic.data_inscriere) = CURDATE()
        
        ORDER BY timestamp_activitate DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $activitate_astazi = $stmt->fetchAll();
    
    // 2. STREAK-uri și OBIECTIVE ZILNICE
    // Calculează câte zile consecutive a fost activ
    $stmt = $pdo->prepare("
        SELECT DISTINCT DATE(data_activitate) as zi_activa
        FROM (
            SELECT data_completare as data_activitate FROM progres_video WHERE user_id = ? AND data_completare IS NOT NULL
            UNION
            SELECT data_realizare as data_activitate FROM rezultate_quiz WHERE user_id = ?
            UNION  
            SELECT data_inscriere as data_activitate FROM inscrieri_cursuri WHERE user_id = ?
        ) AS all_activity
        WHERE DATE(data_activitate) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ORDER BY zi_activa DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $zile_active = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Calculează streak-ul curent
    $streak_curent = 0;
    $data_curenta = new DateTime();
    
    foreach ($zile_active as $zi) {
        $data_activitate = new DateTime($zi);
        $diferenta = $data_curenta->diff($data_activitate)->days;
        
        if ($diferenta === $streak_curent) {
            $streak_curent++;
        } else {
            break;
        }
    }
    
    // 3. OBIECTIVE SĂPTĂMÂNALE - ceva nou și motivant
    $saptamana_start = date('Y-m-d', strtotime('monday this week'));
    
    // Video-uri vizionate această săptămână
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM progres_video 
        WHERE user_id = ? AND completat = 1 AND DATE(data_completare) >= ?
    ");
    $stmt->execute([$_SESSION['user_id'], $saptamana_start]);
    $videouri_saptamana = $stmt->fetchColumn();
    
    // Quiz-uri realizate această săptămână
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM rezultate_quiz 
        WHERE user_id = ? AND DATE(data_realizare) >= ?
    ");
    $stmt->execute([$_SESSION['user_id'], $saptamana_start]);
    $quiz_saptamana = $stmt->fetchColumn();
    
    // Timp petrecut învățând această săptămână (în minute)
    $stmt = $pdo->prepare("
        SELECT SUM(ic.timp_petrecut) FROM inscrieri_cursuri ic
        WHERE ic.user_id = ? AND ic.data_inscriere >= ?
    ");
    $stmt->execute([$_SESSION['user_id'], $saptamana_start]);
    $timp_invatare_saptamana = $stmt->fetchColumn() ?: 0;
    
    // 4. URMĂTORUL PAS RECOMANDAT - foarte specific și personalizat
    $stmt = $pdo->prepare("
        SELECT c.id, c.titlu, c.descriere_scurta, c.imagine, 
               ic.progress, ic.finalizat,
               (SELECT COUNT(*) FROM video_cursuri WHERE curs_id = c.id) as total_videouri,
               (SELECT COUNT(*) FROM progres_video pv 
                JOIN video_cursuri vc ON pv.video_id = vc.id 
                WHERE vc.curs_id = c.id AND pv.user_id = ? AND pv.completat = 1) as videouri_complete
        FROM inscrieri_cursuri ic
        JOIN cursuri c ON ic.curs_id = c.id
        WHERE ic.user_id = ? AND ic.finalizat = 0 AND c.activ = 1
        ORDER BY ic.data_inscriere DESC, ic.progress DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $urmatorul_curs = $stmt->fetch();
    
    // Următorul video de urmărit
    $urmatorul_video = null;
    if ($urmatorul_curs) {
        $stmt = $pdo->prepare("
            SELECT vc.id, vc.titlu, vc.durata_secunde
            FROM video_cursuri vc
            LEFT JOIN progres_video pv ON vc.id = pv.video_id AND pv.user_id = ?
            WHERE vc.curs_id = ? AND vc.activ = 1 AND (pv.completat = 0 OR pv.id IS NULL)
            ORDER BY vc.ordine ASC
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id'], $urmatorul_curs['id']]);
        $urmatorul_video = $stmt->fetch();
    }
    
    // 5. ÎNVĂȚARE COLABORATIVĂ - ce fac alții acum
    $stmt = $pdo->prepare("
        SELECT u.nume, c.titlu as curs_titlu, ic.data_inscriere
        FROM inscrieri_cursuri ic
        JOIN users u ON ic.user_id = u.id
        JOIN cursuri c ON ic.curs_id = c.id
        WHERE u.id != ? AND DATE(ic.data_inscriere) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY ic.data_inscriere DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $activitate_comunitate = $stmt->fetchAll();
    
    // 6. RECOMPENSE ȘI LEVEL-uri - gamificarea
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT ic.curs_id) as cursuri_inscrise,
            COUNT(DISTINCT CASE WHEN ic.finalizat = 1 THEN ic.curs_id END) as cursuri_finalizate,
            COUNT(DISTINCT pv.video_id) as total_videouri_vazute,
            COUNT(DISTINCT CASE WHEN rq.promovat = 1 THEN rq.id END) as quiz_promovate,
            SUM(ic.timp_petrecut) as timp_total_minute
        FROM inscrieri_cursuri ic
        LEFT JOIN progres_video pv ON pv.user_id = ic.user_id AND pv.completat = 1
        LEFT JOIN rezultate_quiz rq ON rq.user_id = ic.user_id
        WHERE ic.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats_gamificare = $stmt->fetch();
    
    // Calculează level-ul utilizatorului
    $puncte_totale = ($stats_gamificare['cursuri_finalizate'] * 100) + 
                     ($stats_gamificare['total_videouri_vazute'] * 10) + 
                     ($stats_gamificare['quiz_promovate'] * 25) +
                     floor(($stats_gamificare['timp_total_minute'] ?: 0) / 60 * 5);
    
    $level_curent = floor($puncte_totale / 100) + 1;
    $puncte_pentru_urmator = ($level_curent * 100) - $puncte_totale;
    
    // 7. QUICK WINS - acțiuni rapide de 5 minute
    $quick_wins = [
        ['titlu' => 'Urmărește un video scurt', 'durata' => '5 min', 'puncte' => 10, 'icon' => 'play'],
        ['titlu' => 'Încearcă un quiz rapid', 'durata' => '3 min', 'puncte' => 25, 'icon' => 'question-circle'],
        ['titlu' => 'Citește un articol din blog', 'durata' => '7 min', 'puncte' => 5, 'icon' => 'newspaper'],
        ['titlu' => 'Calculează-ți bugetul lunar', 'durata' => '10 min', 'puncte' => 15, 'icon' => 'calculator']
    ];
    
} catch (PDOException $e) {
    // Valori default în caz de eroare
    $activitate_astazi = [];
    $streak_curent = 0;
    $videouri_saptamana = 0;
    $quiz_saptamana = 0;
    $timp_invatare_saptamana = 0;
    $urmatorul_curs = null;
    $urmatorul_video = null;
    $activitate_comunitate = [];
    $stats_gamificare = ['cursuri_inscrise' => 0, 'cursuri_finalizate' => 0, 'total_videouri_vazute' => 0, 'quiz_promovate' => 0, 'timp_total_minute' => 0];
    $level_curent = 1;
    $puncte_pentru_urmator = 100;
    $quick_wins = [];
}

include 'components/header.php';
?>

<style>
.dashboard-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.dashboard-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 200px;
    height: 200px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.dashboard-hero h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.dashboard-hero .time-greeting {
    opacity: 0.9;
    font-size: 1rem;
}

.streak-card {
    background: linear-gradient(45deg, #FF6B6B, #FF8E53);
    color: white;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    border: none;
    box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
}

.streak-number {
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.level-card {
    background: linear-gradient(45deg, #4ECDC4, #44A08D);
    color: white;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    border: none;
    box-shadow: 0 8px 25px rgba(78, 205, 196, 0.3);
}

.level-number {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
}

.progress-ring {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: conic-gradient(from 0deg, #667eea 0%, #667eea var(--progress), #e9ecef var(--progress));
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    position: relative;
}

.progress-ring::before {
    content: '';
    width: 60px;
    height: 60px;
    background: white;
    border-radius: 50%;
    position: absolute;
}

.progress-ring .progress-text {
    position: relative;
    z-index: 1;
    font-weight: 700;
    color: #333;
}

.today-activity {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: white;
    border-radius: 10px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.activity-item:hover {
    transform: translateY(-2px);
}

.activity-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.3rem;
    color: white;
}

.activity-icon.video { background: linear-gradient(45deg, #FF6B6B, #FF8E53); }
.activity-icon.quiz { background: linear-gradient(45deg, #4ECDC4, #44A08D); }
.activity-icon.course { background: linear-gradient(45deg, #667eea, #764ba2); }

.next-step-card {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.next-step-card::before {
    content: '🎯';
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 2rem;
    opacity: 0.3;
}

.quick-win-item {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    margin-bottom: 1rem;
}

.quick-win-item:hover {
    border-color: #667eea;
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.quick-win-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
}

.week-goal-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
}

.goal-progress {
    background: #e9ecef;
    border-radius: 25px;
    height: 8px;
    overflow: hidden;
    margin: 0.5rem 0;
}

.goal-fill {
    background: linear-gradient(90deg, #667eea, #764ba2);
    height: 100%;
    border-radius: 25px;
    transition: width 0.3s ease;
}

.community-pulse {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 1.5rem;
}

.community-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.user-avatar {
    width: 35px;
    height: 35px;
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.time-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

@media (max-width: 768px) {
    .dashboard-hero {
        padding: 1.5rem;
    }
    
    .dashboard-hero h1 {
        font-size: 1.5rem;
    }
    
    .streak-number, .level-number {
        font-size: 2rem;
    }
    
    .activity-item, .quick-win-item {
        padding: 1rem;
    }
}
</style>

<div class="container py-4">
    <!-- Dashboard Hero cu salut personalizat -->
    <div class="dashboard-hero">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1>
                    <?php
                    $ora = date('H');
                    $salut = $ora < 12 ? 'Bună dimineața' : ($ora < 18 ? 'Bună ziua' : 'Bună seara');
                    echo $salut . ', ' . htmlspecialchars($current_user['nume']);
                    ?>! 
                </h1>
                <p class="time-greeting mb-0">
                    <?php if (!empty($activitate_astazi)): ?>
                        Ai fost activ astăzi! 🔥 Continuă să înveți!
                    <?php else: ?>
                        Gata să începi o nouă zi de învățare? 🚀
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="text-light">
                    <i class="fas fa-calendar-day me-2"></i>
                    <?= date('d F Y') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Streak și Level - informații motivante -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="card streak-card h-100">
                <div class="card-body">
                    <div class="streak-number"><?= $streak_curent ?></div>
                    <div>Zile consecutive</div>
                    <small class="opacity-75">Keep it up! 🔥</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-6 mb-3">
            <div class="card level-card h-100">
                <div class="card-body">
                    <div class="level-number">Level <?= $level_curent ?></div>
                    <div><?= $puncte_pentru_urmator ?> puncte pentru următorul nivel</div>
                    <small class="opacity-75">Learner 📚</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-6 mb-3">
            <div class="card week-goal-card h-100">
                <div class="card-body text-center">
                    <h6><i class="fas fa-play-circle me-2 text-primary"></i>Video-uri săptămâna</h6>
                    <div class="progress-ring" style="--progress: <?= min(100, ($videouri_saptamana / 5) * 100) ?>%">
                        <div class="progress-text"><?= $videouri_saptamana ?>/5</div>
                    </div>
                    <small class="text-muted">Obiectiv: 5 video-uri</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-6 mb-3">
            <div class="card week-goal-card h-100">
                <div class="card-body text-center">
                    <h6><i class="fas fa-clock me-2 text-success"></i>Timp învățare</h6>
                    <div class="progress-ring" style="--progress: <?= min(100, ($timp_invatare_saptamana / 120) * 100) ?>%">
                        <div class="progress-text"><?= floor($timp_invatare_saptamana / 60) ?>h</div>
                    </div>
                    <small class="text-muted">Obiectiv: 2 ore/săptămână</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Activitatea de astăzi -->
            <div class="today-activity">
                <h5 class="mb-3">
                    <i class="fas fa-calendar-check me-2 text-primary"></i>
                    Ce ai făcut astăzi
                </h5>
                
                <?php if (!empty($activitate_astazi)): ?>
                    <?php foreach ($activitate_astazi as $activitate): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?= $activitate['tip'] === 'video_watched' ? 'video' : ($activitate['tip'] === 'quiz_taken' ? 'quiz' : 'course') ?>">
                                <i class="fas fa-<?= $activitate['tip'] === 'video_watched' ? 'play' : ($activitate['tip'] === 'quiz_taken' ? 'question' : 'graduation-cap') ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <?php
                                    switch($activitate['tip']) {
                                        case 'video_watched':
                                            echo "Ai urmărit: " . htmlspecialchars($activitate['detalii']);
                                            break;
                                        case 'quiz_taken':
                                            echo "Ai încercat quiz-ul: " . htmlspecialchars($activitate['detalii']);
                                            break;
                                        case 'course_started':
                                            echo "Ai început cursul: " . htmlspecialchars($activitate['detalii']);
                                            break;
                                    }
                                    ?>
                                </h6>
                                <small class="text-muted">
                                    <?= date('H:i', strtotime($activitate['timestamp_activitate'])) ?>
                                </small>
                            </div>
                            <span class="time-badge">+<?= $activitate['tip'] === 'quiz_taken' ? '25' : ($activitate['tip'] === 'video_watched' ? '10' : '50') ?> puncte</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Încă nu ai activitate astăzi</h6>
                        <p class="text-muted">Începe cu o acțiune rapidă de mai jos!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Următorul pas recomandat -->
            <?php if ($urmatorul_curs): ?>
                <div class="next-step-card">
                    <h5 class="mb-3">Următorul tău pas recomandat</h5>
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-2"><?= htmlspecialchars($urmatorul_curs['titlu']) ?></h6>
                            <?php if ($urmatorul_video): ?>
                                <p class="mb-2 opacity-90">
                                    <i class="fas fa-play me-2"></i>
                                    Urmează: "<?= htmlspecialchars($urmatorul_video['titlu']) ?>"
                                    (<?= floor($urmatorul_video['durata_secunde'] / 60) ?> min)
                                </p>
                            <?php endif; ?>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-light" style="width: <?= $urmatorul_curs['progress'] ?>%">
                                    <?= number_format($urmatorul_curs['progress'], 1) ?>%
                                </div>
                            </div>
                            <small class="opacity-75">
                                <?= $urmatorul_curs['videouri_complete'] ?>/<?= $urmatorul_curs['total_videouri'] ?> lecții completate
                            </small>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="curs.php?id=<?= $urmatorul_curs['id'] ?>" class="btn btn-light btn-lg">
                                <i class="fas fa-play me-2"></i>Continuă
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quick Wins - acțiuni rapide -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2 text-warning"></i>
                        Quick Wins - Acțiuni rapide (5-10 min)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($quick_wins as $action): ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="quick-win-item">
                                    <div class="quick-win-icon">
                                        <i class="fas fa-<?= $action['icon'] ?>"></i>
                                    </div>
                                    <h6><?= $action['titlu'] ?></h6>
                                    <p class="text-muted small mb-2"><?= $action['durata'] ?></p>
                                    <span class="badge bg-primary">+<?= $action['puncte'] ?> puncte</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Obiective săptămânale -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-target me-2 text-success"></i>
                        Obiective săptămâna aceasta
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">Quiz-uri realizate</small>
                            <small class="text-muted"><?= $quiz_saptamana ?>/3</small>
                        </div>
                        <div class="goal-progress">
                            <div class="goal-fill" style="width: <?= min(100, ($quiz_saptamana / 3) * 100) ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">Ore de învățare</small>
                            <small class="text-muted"><?= number_format($timp_invatare_saptamana / 60, 1) ?>/2.0</small>
                        </div>
                        <div class="goal-progress">
                            <div class="goal-fill" style="width: <?= min(100, ($timp_invatare_saptamana / 120) * 100) ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <?php
                            $progres_total = (($videouri_saptamana / 5) + ($quiz_saptamana / 3) + ($timp_invatare_saptamana / 120)) / 3 * 100;
                            echo "Progres general: " . number_format($progres_total, 1) . "%";
                            ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Pulsul comunității -->
            <div class="community-pulse">
                <h6 class="mb-3">
                    <i class="fas fa-users me-2 text-info"></i>
                    Ce fac alții acum
                </h6>
                
                <?php if (!empty($activitate_comunitate)): ?>
                    <?php foreach ($activitate_comunitate as $activitate): ?>
                        <div class="community-item">
                            <div class="user-avatar">
                                <?= strtoupper(substr($activitate['nume'], 0, 2)) ?>
                            </div>
                            <div class="flex-grow-1">
                                <small>
                                    <strong><?= htmlspecialchars(explode(' ', $activitate['nume'])[0]) ?></strong>
                                    a început 
                                    <em><?= htmlspecialchars($activitate['curs_titlu']) ?></em>
                                </small>
                                <br>
                                <small class="text-muted">
                                    <?php
                                    $timp_scurs = time() - strtotime($activitate['data_inscriere']);
                                    if ($timp_scurs < 3600) {
                                        echo floor($timp_scurs / 60) . " minute";
                                    } elseif ($timp_scurs < 86400) {
                                        echo floor($timp_scurs / 3600) . " ore";
                                    } else {
                                        echo floor($timp_scurs / 86400) . " zile";
                                    }
                                    ?> în urmă
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-users fa-2x text-muted mb-2"></i>
                        <p class="text-muted small">Fii primul care începe astăzi!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mini-calendar cu streak vizual -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-fire me-2 text-danger"></i>
                        Activitatea ta în ultimele 7 zile
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <?php
                        for ($i = 6; $i >= 0; $i--) {
                            $data_check = date('Y-m-d', strtotime("-$i days"));
                            $este_activ = in_array($data_check, $zile_active);
                            $zi_nume = date('D', strtotime($data_check));
                            
                            // Traduce ziua în română
                            $zile_ro = ['Mon' => 'L', 'Tue' => 'M', 'Wed' => 'M', 'Thu' => 'J', 'Fri' => 'V', 'Sat' => 'S', 'Sun' => 'D'];
                            $zi_scurta = $zile_ro[$zi_nume] ?? $zi_nume;
                        ?>
                            <div class="text-center">
                                <div class="streak-day <?= $este_activ ? 'active' : 'inactive' ?>" 
                                     style="width: 30px; height: 30px; border-radius: 50%; 
                                            background: <?= $este_activ ? 'linear-gradient(45deg, #FF6B6B, #FF8E53)' : '#e9ecef' ?>;
                                            color: <?= $este_activ ? 'white' : '#999' ?>;
                                            display: flex; align-items: center; justify-content: center;
                                            font-weight: 600; font-size: 0.8rem; margin-bottom: 0.5rem;">
                                    <?= $este_activ ? '🔥' : '○' ?>
                                </div>
                                <small class="text-muted"><?= $zi_scurta ?></small>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Motivație și încurajare -->
            <div class="card mt-4" style="background: linear-gradient(135deg, #ffeaa7, #fdcb6e); border: none;">
                <div class="card-body text-center">
                    <h6 class="mb-2">💡 Sfatul zilei</h6>
                    <p class="mb-0 small">
                        <?php
                        $sfaturi = [
                            "Investiția în educație aduce întotdeauna cel mai bun câștig. - Benjamin Franklin",
                            "Începe de unde ești, folosește ce ai, fă ce poți. - Arthur Ashe",
                            "Succesul este suma unor mici eforturi repetate zi de zi. - Robert Collier",
                            "O călătorie de o mie de mile începe cu un singur pas. - Lao Tzu",
                            "Nu număra zilele, fă ca zilele să conteze. - Muhammad Ali"
                        ];
                        echo $sfaturi[date('w')]; // Sfat diferit pentru fiecare zi a săptămânii
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer cu acțiuni rapide -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-1">Gata să înveți ceva nou astăzi? 🎓</h5>
                            <p class="mb-0 opacity-75">Alege din acțiunile rapide de mai sus sau explorează cursurile noastre</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="cursuri.php" class="btn btn-light me-2">
                                <i class="fas fa-search me-2"></i>Explorează Cursuri
                            </a>
                            <a href="quiz.php" class="btn btn-outline-light">
                                <i class="fas fa-brain me-2"></i>Quiz Rapid
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Animații pentru progress rings
document.addEventListener('DOMContentLoaded', function() {
    // Animează progress rings
    const rings = document.querySelectorAll('.progress-ring');
    rings.forEach(ring => {
        const progress = ring.style.getPropertyValue('--progress');
        ring.style.setProperty('--progress', '0%');
        setTimeout(() => {
            ring.style.transition = 'all 1s ease-in-out';
            ring.style.setProperty('--progress', progress);
        }, 500);
    });
    
    // Animează goal fills
    const goals = document.querySelectorAll('.goal-fill');
    goals.forEach(goal => {
        const width = goal.style.width;
        goal.style.width = '0%';
        setTimeout(() => {
            goal.style.width = width;
        }, 800);
    });
    
    // Click pe quick wins
    document.querySelectorAll('.quick-win-item').forEach(item => {
        item.addEventListener('click', function() {
            const icon = this.querySelector('.quick-win-icon i').className;
            
            if (icon.includes('play')) {
                window.location.href = 'cursuri.php';
            } else if (icon.includes('question')) {
                window.location.href = 'quiz.php';
            } else if (icon.includes('newspaper')) {
                window.location.href = 'blog.php';
            } else if (icon.includes('calculator')) {
                window.location.href = 'instrumente.php';
            }
        });
    });
});

// Actualizează ora în timp real
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleString('ro-RO', {
        year: 'numeric',
        month: 'long', 
        day: 'numeric'
    });
    
    const timeElement = document.querySelector('.dashboard-hero .text-light');
    if (timeElement) {
        timeElement.innerHTML = '<i class="fas fa-calendar-day me-2"></i>' + timeString;
    }
}

// Actualizează timpul la fiecare minut
setInterval(updateTime, 60000);
</script>

<?php include 'components/footer.php'; ?>