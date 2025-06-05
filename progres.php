<?php
require_once 'config.php';

// Verifică dacă utilizatorul este conectat
if (!isLoggedIn()) {
    redirectTo('login.php');
}

$user_id = $_SESSION['user_id'];
$page_title = 'Progresul Meu - ' . SITE_NAME;

try {
    // Statistici generale utilizator cu progres consistent
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT ic.curs_id) as cursuri_inscrise,
            COUNT(DISTINCT CASE WHEN rq.promovat = 1 THEN rq.quiz_id END) as quiz_promovate,
            COUNT(DISTINCT rq.quiz_id) as quiz_incercate
        FROM inscrieri_cursuri ic
        LEFT JOIN quiz_uri q ON ic.curs_id = q.curs_id
        LEFT JOIN rezultate_quiz rq ON q.id = rq.quiz_id AND rq.user_id = ?
        WHERE ic.user_id = ?
    ");
    $stmt->execute([$user_id, $user_id]);
    $statistici_basic = $stmt->fetch();

    // Progres pe cursuri cu sistemul nou (identic cu cursurile-mele.php)
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.titlu,
            c.descriere,
            c.pret,
            ic.data_inscriere,
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
             WHERE q.curs_id = c.id AND rq.user_id = ? AND rq.promovat = 1) as media_quiz,
            
            MAX(rq.data_realizare) as ultima_activitate
        FROM inscrieri_cursuri ic
        INNER JOIN cursuri c ON ic.curs_id = c.id
        LEFT JOIN quiz_uri q ON c.id = q.curs_id AND q.activ = 1
        LEFT JOIN rezultate_quiz rq ON q.id = rq.quiz_id AND rq.user_id = ?
        WHERE ic.user_id = ? AND c.activ = 1
        GROUP BY c.id
        ORDER BY ic.data_inscriere DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    $cursuri_raw = $stmt->fetchAll();

    // Calculează progresul real pentru fiecare curs (identic cu cursurile-mele.php)
    $cursuri = [];
    $total_progres_real = 0;
    $cursuri_cu_progres = 0;
    $cursuri_finalizate = 0;

    foreach ($cursuri_raw as $curs) {
        // Calculează progresul real pe baza activităților completate
        $total_activitati = $curs['total_quiz'] + $curs['total_videos'] + $curs['total_exercitii'];
        $activitati_completate = $curs['quiz_completate'] + $curs['videos_completate'] + $curs['exercitii_completate'];
        
        $progres_real = $total_activitati > 0 ? ($activitati_completate / $total_activitati) * 100 : 0;
        $progres_real = round($progres_real, 1);
        
        // Adaugă progresul real la curs
        $curs['progres_real'] = $progres_real;
        $curs['total_activitati'] = $total_activitati;
        $curs['activitati_completate'] = $activitati_completate;
        
        $cursuri[] = $curs;
        
        // Calculează pentru statistici
        if ($progres_real >= 100) {
            $cursuri_finalizate++;
        }
        
        if ($progres_real > 0) {
            $total_progres_real += $progres_real;
            $cursuri_cu_progres++;
        }
    }

    // Calculează media generală reală
    $media_generala_reala = $cursuri_cu_progres > 0 ? $total_progres_real / $cursuri_cu_progres : 0;
    $media_generala_reala = round($media_generala_reala, 1);

    // Combină statisticile
    $statistici = [
        'cursuri_inscrise' => $statistici_basic['cursuri_inscrise'],
        'quiz_promovate' => $statistici_basic['quiz_promovate'],
        'quiz_incercate' => $statistici_basic['quiz_incercate'],
        'media_generale' => $media_generala_reala, // Folosește media reală
        'cursuri_finalizate' => $cursuri_finalizate,
        'cursuri_cu_progres' => $cursuri_cu_progres
    ];

    // Activitatea recentă
    $stmt = $pdo->prepare("
        SELECT 
            rq.*,
            q.titlu as quiz_titlu,
            c.titlu as curs_titlu
        FROM rezultate_quiz rq
        INNER JOIN quiz_uri q ON rq.quiz_id = q.id
        LEFT JOIN cursuri c ON q.curs_id = c.id
        WHERE rq.user_id = ?
        ORDER BY rq.data_realizare DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $activitate_recenta = $stmt->fetchAll();

    // Realizări și badge-uri
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN rq.procentaj = 100 THEN 1 END) as quiz_perfecte,
            COUNT(CASE WHEN rq.procentaj >= 90 THEN 1 END) as quiz_excelente,
            COUNT(CASE WHEN rq.timp_completare <= 300 THEN 1 END) as quiz_rapide,
            MIN(rq.data_realizare) as primul_quiz,
            MAX(rq.data_realizare) as ultimul_quiz
        FROM rezultate_quiz rq
        WHERE rq.user_id = ? AND rq.promovat = 1
    ");
    $stmt->execute([$user_id]);
    $realizari = $stmt->fetch();

} catch (PDOException $e) {
    $statistici = [];
    $cursuri = [];
    $activitate_recenta = [];
    $realizari = [];
}

include 'components/header.php';
?>

<style>
.progress-page {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.progress-hero {
    background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05));
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 3rem 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255,255,255,0.2);
    text-align: center;
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    border: 1px solid rgba(0,0,0,0.1);
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #444;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.section-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    border: 1px solid rgba(0,0,0,0.1);
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
}

.section-title i {
    margin-right: 0.75rem;
    color: #667eea;
}

.course-progress-item {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.course-progress-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    border-color: #667eea;
}

.course-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.course-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.course-description {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.4;
    margin: 0;
}

.course-status {
    text-align: right;
    flex-shrink: 0;
}

.progress-percentage {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: 700;
    font-size: 1rem;
    margin-bottom: 0.5rem;
    display: inline-block;
    min-width: 80px;
}

.enrollment-date {
    font-size: 0.85rem;
    color: white;
    margin: 0;
    background: rgba(0,0,0,0.2);
    padding: 0.25rem 0.5rem;
    border-radius: 5px;
}

.progress-bar-container {
    background: #e9ecef;
    border-radius: 12px;
    height: 12px;
    margin-bottom: 1.5rem;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 12px;
    transition: width 0.6s ease;
    position: relative;
}

.progress-bar-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, rgba(255,255,255,0.3), transparent, rgba(255,255,255,0.3));
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.course-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.stat-item {
    text-align: center;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #e9ecef;
}

.stat-number {
    font-size: 1.1rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.25rem;
}

.stat-text {
    font-size: 0.8rem;
    color: #666;
    font-weight: 500;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 1.25rem;
    background: #f8f9fa;
    border-radius: 12px;
    margin-bottom: 1rem;
    border-left: 4px solid #667eea;
    transition: all 0.2s ease;
}

.activity-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.activity-icon {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
    font-size: 1.1rem;
}

.activity-content {
    flex-grow: 1;
}

.activity-title {
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.activity-details {
    font-size: 0.9rem;
    color: #666;
    line-height: 1.4;
}

.badge-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.5rem;
}

.badge-item {
    text-align: center;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 15px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.badge-item.earned {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: #667eea;
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.badge-item:not(.earned):hover {
    border-color: #ccc;
    transform: translateY(-2px);
}

.badge-icon {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    display: block;
}

.badge-title {
    font-weight: 700;
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.badge-count {
    font-size: 0.9rem;
    opacity: 0.85;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #666;
}

.empty-state i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 1.5rem;
}

.empty-state h4 {
    color: #555;
    margin-bottom: 1rem;
    font-weight: 600;
}

.empty-state p {
    color: #777;
    margin-bottom: 2rem;
    font-size: 1rem;
}

.refresh-btn {
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.3);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-size: 0.9rem;
    margin-left: 1rem;
    transition: all 0.2s ease;
}

.refresh-btn:hover {
    background: rgba(255,255,255,0.3);
    color: white;
    transform: translateY(-1px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .progress-page {
        padding: 1rem;
    }
    
    .progress-hero {
        padding: 2rem 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .course-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .course-status {
        text-align: left;
        width: 100%;
    }
    
    .course-stats {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .badge-container {
        grid-template-columns: 1fr;
    }
    
    .activity-item {
        padding: 1rem;
    }
    
    .activity-icon {
        width: 40px;
        height: 40px;
    }
}

@media (max-width: 576px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .course-title {
        font-size: 1.1rem;
    }
    
    .section-card {
        padding: 1.5rem;
    }
}

/* Îmbunătățiri pentru accesibilitate */
.course-progress-item:focus-within {
    outline: 2px solid #667eea;
    outline-offset: 2px;
}

.badge-item.earned {
    animation: badgeEarned 0.6s ease-out;
}

@keyframes badgeEarned {
    0% { transform: scale(1) rotate(0deg); }
    50% { transform: scale(1.1) rotate(5deg); }
    100% { transform: scale(1.02) rotate(0deg); }
}
</style>

<div class="progress-page">
    <div class="container">
        <!-- Hero Section -->
        <div class="progress-hero">
            <h1>
                <i class="fas fa-chart-line me-3"></i>
                Progresul Meu
                <button class="refresh-btn" onclick="forceUpdateProgress()">
                    <i class="fas fa-sync-alt me-1"></i>Actualizează
                </button>
            </h1>
            <p>Urmărește-ți evoluția și realizările în educația financiară</p>
        </div>

        <!-- Statistici Generale -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $statistici['cursuri_inscrise'] ?? 0 ?></div>
                <div class="stat-label">Cursuri Înscrise</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $statistici['quiz_promovate'] ?? 0 ?></div>
                <div class="stat-label">Quiz-uri Promovate</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $statistici['quiz_incercate'] ?? 0 ?></div>
                <div class="stat-label">Quiz-uri Încercate</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $statistici['media_generale'] ? number_format($statistici['media_generale'], 1) . '%' : 'N/A' ?></div>
                <div class="stat-label">Media Generală Reală</div>
            </div>
        </div>

        <!-- Progres Cursuri -->
        <div class="section-card">
            <h2 class="section-title">
                <i class="fas fa-graduation-cap"></i>
                Progresul pe Cursuri (Real-time)
            </h2>
            
            <?php if (!empty($cursuri)): ?>
                <?php foreach ($cursuri as $curs): ?>
                    <div class="course-progress-item" data-course-id="<?= $curs['id'] ?>">
                        <div class="course-header">
                            <div class="course-info">
                                <h3 class="course-title"><?= sanitizeInput($curs['titlu']) ?></h3>
                                <p class="course-description"><?= sanitizeInput($curs['descriere']) ?></p>
                            </div>
                            <div class="course-status">
                                <div class="progress-percentage"><?= $curs['progres_real'] ?>%</div>
                            </div>
                        </div>
                        
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?= $curs['progres_real'] ?>%"></div>
                        </div>
                        
                        <div class="course-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?= $curs['quiz_completate'] ?>/<?= $curs['total_quiz'] ?></div>
                                <div class="stat-text">Quiz-uri</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $curs['videos_completate'] ?>/<?= $curs['total_videos'] ?></div>
                                <div class="stat-text">Video-uri</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $curs['exercitii_completate'] ?>/<?= $curs['total_exercitii'] ?></div>
                                <div class="stat-text">Exerciții</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $curs['activitati_completate'] ?>/<?= $curs['total_activitati'] ?></div>
                                <div class="stat-text">Total</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $curs['media_quiz'] ? number_format($curs['media_quiz'], 1) . '%' : 'N/A' ?></div>
                                <div class="stat-text">Media quiz</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $curs['ultima_activitate'] ? date('d.m.Y', strtotime($curs['ultima_activitate'])) : 'Niciodată' ?></div>
                                <div class="stat-text">Ultima activitate</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-graduation-cap"></i>
                    <h4>Nu ești înscris la niciun curs</h4>
                    <p>Explorează cursurile disponibile pentru a începe!</p>
                    <a href="cursuri.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-search me-2"></i>Explorează Cursurile
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Activitate Recentă -->
        <div class="section-card">
            <h2 class="section-title">
                <i class="fas fa-history"></i>
                Activitatea Recentă
            </h2>
            
            <div class="recent-activity-container">
                <?php if (!empty($activitate_recenta)): ?>
                    <?php foreach ($activitate_recenta as $activitate): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas <?= $activitate['promovat'] ? 'fa-trophy' : 'fa-times' ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <?= sanitizeInput($activitate['quiz_titlu']) ?>
                                </div>
                                <div class="activity-details">
                                    <?php if ($activitate['curs_titlu']): ?>
                                        Din cursul: <?= sanitizeInput($activitate['curs_titlu']) ?> • 
                                    <?php endif; ?>
                                    Scor: <?= number_format($activitate['procentaj'], 1) ?>% • 
                                    <?= $activitate['promovat'] ? 'Promovat' : 'Nepromovat' ?> • 
                                    <?= date('d.m.Y H:i', strtotime($activitate['data_realizare'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h4>Nicio activitate încă</h4>
                        <p>Începe un quiz pentru a vedea activitatea ta aici!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Realizări și Badge-uri -->
        <div class="section-card">
            <h2 class="section-title">
                <i class="fas fa-medal"></i>
                Realizări și Badge-uri
            </h2>
            
            <div class="badge-container">
                <div class="badge-item <?= ($realizari['quiz_perfecte'] ?? 0) > 0 ? 'earned' : '' ?>">
                    <div class="badge-icon">🏆</div>
                    <div class="badge-title">Perfecționist</div>
                    <div class="badge-count"><?= $realizari['quiz_perfecte'] ?? 0 ?> quiz-uri cu 100%</div>
                </div>
                
                <div class="badge-item <?= ($realizari['quiz_excelente'] ?? 0) >= 5 ? 'earned' : '' ?>">
                    <div class="badge-icon">⭐</div>
                    <div class="badge-title">Excelent</div>
                    <div class="badge-count"><?= $realizari['quiz_excelente'] ?? 0 ?> quiz-uri cu 90%+</div>
                </div>
                
                <div class="badge-item <?= ($realizari['quiz_rapide'] ?? 0) >= 3 ? 'earned' : '' ?>">
                    <div class="badge-icon">⚡</div>
                    <div class="badge-title">Rapid</div>
                    <div class="badge-count"><?= $realizari['quiz_rapide'] ?? 0 ?> quiz-uri sub 5 min</div>
                </div>
                
                <div class="badge-item <?= ($statistici['cursuri_inscrise'] ?? 0) >= 3 ? 'earned' : '' ?>">
                    <div class="badge-icon">📚</div>
                    <div class="badge-title">Student Dedicat</div>
                    <div class="badge-count"><?= $statistici['cursuri_inscrise'] ?? 0 ?> cursuri înscrise</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Funcție pentru actualizarea automată a progresului
function updateProgress() {
    fetch('ajax/get-course-progress-realtime.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=refresh_all_progress'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizează statisticile generale
            updateGeneralStats(data.stats);
            
            // Actualizează progresul cursurilor
            updateCourseProgress(data.courses);
            
            console.log('Progres actualizat:', new Date().toLocaleTimeString());
        }
    })
    .catch(error => {
        console.log('Eroare la actualizarea progresului:', error);
    });
}

function updateGeneralStats(stats) {
    const statCards = document.querySelectorAll('.stat-value');
    if (statCards.length >= 4) {
        statCards[0].textContent = stats.total_cursuri || 0;
        statCards[1].textContent = stats.quiz_promovate || 0;
        statCards[2].textContent = stats.quiz_incercate || 0;
        statCards[3].textContent = stats.progres_mediu ? stats.progres_mediu.toFixed(1) + '%' : 'N/A';
    }
}

function updateCourseProgress(courses) {
    courses.forEach(course => {
        const courseElement = document.querySelector(`[data-course-id="${course.id}"]`);
        if (courseElement) {
            // Actualizează bara de progres
            const progressBar = courseElement.querySelector('.progress-bar-fill');
            const progressText = courseElement.querySelector('.progress-percentage');
            
            if (progressBar && progressText) {
                progressBar.style.width = course.progres_real + '%';
                progressText.textContent = course.progres_real.toFixed(1) + '%';
            }
            
            // Actualizează statisticile cursului
            const statNumbers = courseElement.querySelectorAll('.stat-number');
            if (statNumbers.length >= 6) {
                statNumbers[0].textContent = `${course.quiz_completate}/${course.total_quiz}`;
                statNumbers[1].textContent = `${course.videos_completate}/${course.total_videos}`;
                statNumbers[2].textContent = `${course.exercitii_completate}/${course.total_exercitii}`;
                statNumbers[3].textContent = `${course.activitati_completate}/${course.total_activitati}`;
            }
        }
    });
}

// Pornește actualizarea automată
document.addEventListener('DOMContentLoaded', function() {
    // Actualizare la fiecare 30 de secunde
    setInterval(updateProgress, 30000);
    
    // Actualizare la focus pe pagină
    window.addEventListener('focus', updateProgress);
    
    console.log('Actualizare automată a progresului activată în progres.php');
});

// Funcție pentru actualizare manuală
function forceUpdateProgress() {
    const button = event.target;
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Se actualizează...';
    button.disabled = true;
    
    updateProgress();
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 2000);
}
</script>

<?php include 'components/footer.php'; ?>