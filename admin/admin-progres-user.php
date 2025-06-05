<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = MSG_ERROR_ACCESS_DENIED;
    redirectTo('../login.php');
}

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    $_SESSION['error_message'] = 'ID utilizator invalid.';
    redirectTo('admin-progres.php');
}

$user_id = (int)$_GET['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND rol != 'admin'");
    $stmt->execute([$user_id]);
    $utilizator = $stmt->fetch();
    
    if (!$utilizator) {
        $_SESSION['error_message'] = 'Utilizatorul nu a fost găsit.';
        redirectTo('admin-progres.php');
    }

    $page_title = 'Progresul lui ' . $utilizator['nume'] . ' - Admin - ' . SITE_NAME;

    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT ic.curs_id) as cursuri_inscrise,
            COUNT(DISTINCT CASE WHEN rq.promovat = 1 THEN rq.quiz_id END) as quiz_promovate,
            COUNT(DISTINCT rq.quiz_id) as quiz_incercate,
            AVG(CASE WHEN rq.promovat = 1 THEN rq.procentaj END) as media_generale,
            SUM(c.pret) as valoare_totala_cursuri
        FROM inscrieri_cursuri ic
        LEFT JOIN cursuri c ON ic.curs_id = c.id
        LEFT JOIN quiz_uri q ON ic.curs_id = q.curs_id
        LEFT JOIN rezultate_quiz rq ON q.id = rq.quiz_id AND rq.user_id = ?
        WHERE ic.user_id = ?
    ");
    $stmt->execute([$user_id, $user_id]);
    $statistici = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.titlu,
            c.descriere,
            c.pret,
            ic.data_inscriere,
            COUNT(DISTINCT q.id) as total_quiz,
            COUNT(DISTINCT CASE WHEN rq.promovat = 1 THEN rq.quiz_id END) as quiz_completate,
            AVG(CASE WHEN rq.promovat = 1 THEN rq.procentaj END) as media_curs,
            MAX(rq.data_realizare) as ultima_activitate,
            COUNT(DISTINCT rq.quiz_id) as quiz_incercate
        FROM inscrieri_cursuri ic
        INNER JOIN cursuri c ON ic.curs_id = c.id
        LEFT JOIN quiz_uri q ON c.id = q.curs_id AND q.activ = 1
        LEFT JOIN rezultate_quiz rq ON q.id = rq.quiz_id AND rq.user_id = ?
        WHERE ic.user_id = ? AND c.activ = 1
        GROUP BY c.id
        ORDER BY ic.data_inscriere DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    $cursuri = $stmt->fetchAll();

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
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $activitate_recenta = $stmt->fetchAll();

    // Corectare: calculul badge-urilor trebuie să fie corelat cu datele reale
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN rq.procentaj = 100 THEN 1 END) as quiz_perfecte,
            COUNT(CASE WHEN rq.procentaj >= 90 THEN 1 END) as quiz_excelente,
            COUNT(CASE WHEN rq.timp_completare <= 300 THEN 1 END) as quiz_rapide,
            MIN(rq.data_realizare) as primul_quiz,
            MAX(rq.data_realizare) as ultimul_quiz,
            COUNT(DISTINCT rq.id) as total_quiz_realizate
        FROM rezultate_quiz rq
        WHERE rq.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $realizari = $stmt->fetch();

} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Eroare la încărcarea datelor utilizatorului.';
    redirectTo('admin-progres.php');
}

include '../components/header.php';
?>

<style>
.user-progress-page {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 1rem 0;
}

.user-hero {
    background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05));
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(255,255,255,0.2);
    text-align: center;
    color: white;
}

.user-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.user-avatar-large {
    width: 60px;
    height: 60px;
    background: rgba(255,255,255,0.2);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    border: 2px solid rgba(255,255,255,0.3);
    flex-shrink: 0;
}

.user-details h1 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1.2;
}

.user-details p {
    margin: 0.25rem 0;
    opacity: 0.9;
    font-size: 0.95rem;
}

.back-button {
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.3);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.back-button:hover {
    background: rgba(255,255,255,0.3);
    color: white;
    transform: translateY(-1px);
    text-decoration: none;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1rem;
    text-align: center;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border: 1px solid rgba(0,0,0,0.1);
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 0.25rem;
    line-height: 1;
}

.stat-label {
    color: #444;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    line-height: 1.2;
}

.section-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border: 1px solid rgba(0,0,0,0.1);
}

.section-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
}

.section-title i {
    margin-right: 0.5rem;
    color: #667eea;
    font-size: 1.1rem;
}

.course-progress-item {
    background: white;
    border-radius: 15px;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.course-progress-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    border-color: #667eea;
}

.course-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 1rem;
}

.course-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.25rem;
    line-height: 1.3;
}

.course-description {
    color: #666;
    font-size: 0.85rem;
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
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
    display: inline-block;
    min-width: 60px;
}

.progress-bar-container {
    background: #e9ecef;
    border-radius: 10px;
    height: 8px;
    margin-bottom: 1rem;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 10px;
    transition: width 0.6s ease;
}

.course-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 0.75rem;
    margin-top: 0.75rem;
}

.stat-item {
    text-align: center;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.stat-number {
    font-size: 1rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.1rem;
    line-height: 1;
}

.stat-text {
    font-size: 0.7rem;
    color: #666;
    font-weight: 500;
    line-height: 1.2;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 12px;
    margin-bottom: 0.75rem;
    border-left: 4px solid #667eea;
    transition: all 0.2s ease;
}

.activity-item:hover {
    background: #e9ecef;
    transform: translateX(3px);
}

.activity-icon {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    flex-shrink: 0;
    font-size: 0.9rem;
}

.activity-content {
    flex-grow: 1;
    min-width: 0;
}

.activity-title {
    font-weight: 700;
    color: #333;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
    word-break: break-word;
}

.activity-details {
    font-size: 0.8rem;
    color: #666;
    line-height: 1.4;
    word-break: break-word;
}

.badge-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
}

.badge-item {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 15px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
    position: relative;
}

/* Badge conditions - CORECTATE */
.badge-item.perfectionist.earned {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    color: #333;
    border-color: #ffd700;
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
}

.badge-item.excellent.earned {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border-color: #28a745;
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
}

.badge-item.rapid.earned {
    background: linear-gradient(135deg, #17a2b8, #6f42c1);
    color: white;
    border-color: #17a2b8;
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(23, 162, 184, 0.3);
}

.badge-item.dedicated.earned {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: #667eea;
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.badge-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: block;
}

.badge-title {
    font-weight: 700;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.badge-count {
    font-size: 0.8rem;
    opacity: 0.85;
}

.empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: #666;
}

.empty-state i {
    font-size: 3rem;
    color: #ddd;
    margin-bottom: 1rem;
}

.empty-state h4 {
    color: #555;
    margin-bottom: 0.75rem;
    font-weight: 600;
    font-size: 1.1rem;
}

.empty-state p {
    color: #777;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}

/* Media Queries Responsive */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .course-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 992px) {
    .user-progress-page {
        padding: 0.5rem;
    }
    
    .user-hero {
        padding: 1.5rem;
    }
    
    .section-card {
        padding: 1.25rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .badge-container {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .user-hero {
        padding: 1rem;
    }
    
    .user-info {
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }
    
    .user-details h1 {
        font-size: 1.5rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .course-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .course-status {
        text-align: left;
        width: 100%;
    }
    
    .course-stats {
        grid-template-columns: 1fr 1fr;
    }
    
    .activity-item {
        padding: 0.75rem;
    }
    
    .badge-container {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .user-progress-page {
        padding: 0.25rem;
    }
    
    .user-hero {
        padding: 0.75rem;
        margin-bottom: 1rem;
    }
    
    .section-card {
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }
    
    .stat-card {
        padding: 0.75rem;
    }
    
    .stat-value {
        font-size: 1.25rem;
    }
    
    .stat-label {
        font-size: 0.7rem;
    }
    
    .course-stats {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .activity-item {
        padding: 0.5rem;
    }
    
    .activity-icon {
        width: 30px;
        height: 30px;
        font-size: 0.8rem;
    }
    
    .activity-title {
        font-size: 0.85rem;
    }
    
    .activity-details {
        font-size: 0.75rem;
    }
}

@media (min-width: 1400px) {
    .container {
        max-width: 1200px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}
</style>

<div class="user-progress-page">
    <div class="container">
        <!-- Hero Section -->
        <div class="user-hero">
            <a href="admin-progres.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Înapoi la Lista Utilizatori
            </a>
            
            <div class="user-info">
                <div class="user-avatar-large">
                    <?= strtoupper(substr($utilizator['nume'], 0, 2)) ?>
                </div>
                <div class="user-details">
                    <h1><?= sanitizeInput($utilizator['nume']) ?></h1>
                    <p><?= sanitizeInput($utilizator['email']) ?></p>
                    <p><small>Înregistrat: <?= date('d.m.Y', strtotime($utilizator['data_inregistrare'])) ?></small></p>
                </div>
            </div>
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
                <div class="stat-label">Media Generală</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $statistici['valoare_totala_cursuri'] ? number_format($statistici['valoare_totala_cursuri'], 0) . ' RON' : '0 RON' ?></div>
                <div class="stat-label">Valoare Cursuri</div>
            </div>
        </div>

        <!-- Progres Cursuri -->
        <div class="section-card">
            <h2 class="section-title">
                <i class="fas fa-graduation-cap"></i>
                Progresul pe Cursuri
            </h2>
            
            <?php if (!empty($cursuri)): ?>
                <?php foreach ($cursuri as $curs): ?>
                    <?php 
                    $progres = $curs['total_quiz'] > 0 ? ($curs['quiz_completate'] / $curs['total_quiz']) * 100 : 0;
                    ?>
                    <div class="course-progress-item">
                        <div class="course-header">
                            <div class="course-info">
                                <h3 class="course-title"><?= sanitizeInput($curs['titlu']) ?></h3>
                                <p class="course-description"><?= sanitizeInput($curs['descriere']) ?></p>
                            </div>
                            <div class="course-status">
                                <div class="progress-percentage"><?= number_format($progres, 0) ?>%</div>
                            </div>
                        </div>
                        
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?= $progres ?>%"></div>
                        </div>
                        
                        <div class="course-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?= $curs['quiz_completate'] ?>/<?= $curs['total_quiz'] ?></div>
                                <div class="stat-text">Quiz-uri completate</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $curs['quiz_incercate'] ?></div>
                                <div class="stat-text">Quiz-uri încercate</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $curs['media_curs'] ? number_format($curs['media_curs'], 1) . '%' : 'N/A' ?></div>
                                <div class="stat-text">Media cursului</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $curs['ultima_activitate'] ? date('d.m.Y', strtotime($curs['ultima_activitate'])) : 'Niciodată' ?></div>
                                <div class="stat-text">Ultima activitate</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= date('d.m.Y', strtotime($curs['data_inscriere'])) ?></div>
                                <div class="stat-text">Data înscrierii</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= number_format($curs['pret'], 0) ?> RON</div>
                                <div class="stat-text">Preț curs</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-graduation-cap"></i>
                    <h4>Nu este înscris la niciun curs</h4>
                    <p>Acest utilizator nu s-a înscris încă la cursuri.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Activitate Recentă -->
        <div class="section-card">
            <h2 class="section-title">
                <i class="fas fa-history"></i>
                Activitatea Recentă
            </h2>
            
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
                                Timp: <?= gmdate("i:s", $activitate['timp_completare']) ?> • 
                                <?= date('d.m.Y H:i', strtotime($activitate['data_realizare'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h4>Nicio activitate încă</h4>
                    <p>Acest utilizator nu a completat încă niciun quiz.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Realizări și Badge-uri - CORECTATE -->
        <div class="section-card">
            <h2 class="section-title">
                <i class="fas fa-medal"></i>
                Realizări și Badge-uri
            </h2>
            
            <div class="badge-container">
                <div class="badge-item perfectionist <?= ($realizari['quiz_perfecte'] ?? 0) > 0 ? 'earned' : '' ?>">
                    <div class="badge-icon">🏆</div>
                    <div class="badge-title">Perfecționist</div>
                    <div class="badge-count"><?= $realizari['quiz_perfecte'] ?? 0 ?> quiz-uri cu 100%</div>
                </div>
                
                <div class="badge-item excellent <?= ($realizari['quiz_excelente'] ?? 0) >= 5 ? 'earned' : '' ?>">
                    <div class="badge-icon">⭐</div>
                    <div class="badge-title">Excelent</div>
                    <div class="badge-count"><?= $realizari['quiz_excelente'] ?? 0 ?> quiz-uri cu 90%+</div>
                </div>
                
                <div class="badge-item rapid <?= ($realizari['quiz_rapide'] ?? 0) >= 3 ? 'earned' : '' ?>">
                    <div class="badge-icon">⚡</div>
                    <div class="badge-title">Rapid</div>
                    <div class="badge-count"><?= $realizari['quiz_rapide'] ?? 0 ?> quiz-uri sub 5 min</div>
                </div>
                
                <div class="badge-item dedicated <?= ($statistici['cursuri_inscrise'] ?? 0) >= 3 ? 'earned' : '' ?>">
                    <div class="badge-icon">📚</div>
                    <div class="badge-title">Student Dedicat</div>
                    <div class="badge-count"><?= $statistici['cursuri_inscrise'] ?? 0 ?> cursuri înscrise</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../components/footer.php'; ?>