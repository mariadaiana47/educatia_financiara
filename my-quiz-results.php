<?php
require_once 'config.php';

// Verifică dacă utilizatorul este conectat
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    redirectTo('login.php');
}

$page_title = 'Rezultatele Mele - Quiz-uri - ' . SITE_NAME;

// Obține quiz_id din URL dacă există
$quiz_id_filter = isset($_GET['quiz_id']) && is_numeric($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : null;

try {
    // Condiții WHERE pentru filtrare
    $where_conditions = ['rz.user_id = ?'];
    $params = [$_SESSION['user_id']];
    
    if ($quiz_id_filter) {
        $where_conditions[] = 'rz.quiz_id = ?';
        $params[] = $quiz_id_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // 1. Obține toate rezultatele utilizatorului curent
    $stmt = $pdo->prepare("
        SELECT rz.*, q.titlu as quiz_titlu, q.punctaj_minim_promovare, q.dificultate,
               c.titlu as curs_titlu, c.id as curs_id
        FROM rezultate_quiz rz
        INNER JOIN quiz_uri q ON rz.quiz_id = q.id
        LEFT JOIN cursuri c ON q.curs_id = c.id
        WHERE $where_clause
        ORDER BY rz.data_realizare DESC
    ");
    $stmt->execute($params);
    $rezultate = $stmt->fetchAll();
    
    // 2. Statistici generale pentru utilizator
    $stats = [
        'Total încercări' => count($rezultate),
        'Quiz-uri promovate' => count(array_filter($rezultate, fn($r) => $r['promovat'] == 1)),
        'Scor mediu' => $rezultate ? round(array_sum(array_column($rezultate, 'procentaj')) / count($rezultate), 1) : 0,
        'Quiz-uri unice' => count(array_unique(array_column($rezultate, 'quiz_id')))
    ];
    
    // 3. Cel mai bun scor pentru fiecare quiz
    $stmt = $pdo->prepare("
        SELECT rz.quiz_id, q.titlu, MAX(rz.procentaj) as best_score, 
               q.punctaj_minim_promovare, COUNT(*) as attempts,
               (SELECT rz2.id FROM rezultate_quiz rz2 WHERE rz2.quiz_id = rz.quiz_id AND rz2.user_id = rz.user_id ORDER BY rz2.procentaj DESC, rz2.data_realizare DESC LIMIT 1) as best_result_id
        FROM rezultate_quiz rz
        INNER JOIN quiz_uri q ON rz.quiz_id = q.id
        WHERE rz.user_id = ? " . ($quiz_id_filter ? "AND rz.quiz_id = $quiz_id_filter" : "") . "
        GROUP BY rz.quiz_id, q.titlu, q.punctaj_minim_promovare
        ORDER BY best_score DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $best_scores = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $rezultate = [];
    $stats = [];
    $best_scores = [];
}

include 'components/header.php';
?>

<style>
.results-page {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.results-hero {
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
    margin-bottom: 2rem;
}

.stat-card {
    background: rgba(255,255,255,0.95);
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #666;
    font-weight: 500;
    font-size: 0.9rem;
}

.results-table {
    background: rgba(255,255,255,0.95);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.result-row {
    border-bottom: 1px solid #eee;
    padding: 1rem 0;
    transition: background-color 0.2s;
}

.result-row:hover {
    background-color: #f8f9fa;
    border-radius: 8px;
    margin: 0 -1rem;
    padding: 1rem;
}

.result-row:last-child {
    border-bottom: none;
}

.score-badge {
    font-size: 1.25rem;
    font-weight: 700;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    min-width: 80px;
    display: inline-block;
    text-align: center;
}

.score-passed {
    background: #d4edda;
    color: #155724;
}

.score-failed {
    background: #f8d7da;
    color: #721c24;
}

.quiz-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.quiz-badge.usor {
    background: #d4edda;
    color: #155724;
}

.quiz-badge.mediu {
    background: #fff3cd;
    color: #856404;
}

.quiz-badge.greu {
    background: #f8d7da;
    color: #721c24;
}

.best-scores-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.best-score-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.best-score-card:hover {
    transform: translateY(-2px);
}

.empty-state {
    text-align: center;
    padding: 3rem;
    background: rgba(255,255,255,0.9);
    border-radius: 20px;
    color: #666;
}
</style>

<div class="results-page">
    <div class="container">
        <!-- Hero Section -->
        <div class="results-hero">
            <h1>
                <i class="fas fa-chart-line me-3"></i>
                Rezultatele Mele la Quiz-uri
            </h1>
            <p>Urmărește-ți progresul și vezi unde poți să te îmbunătățești</p>
        </div>

        <!-- Back Button -->
        <div class="mb-3">
            <a href="quiz.php" class="btn btn-light">
                <i class="fas fa-arrow-left me-2"></i>Înapoi la Quiz-uri
            </a>
            <?php if ($quiz_id_filter): ?>
                <a href="my-quiz-results.php" class="btn btn-outline-light ms-2">
                    <i class="fas fa-list me-2"></i>Toate Rezultatele
                </a>
            <?php endif; ?>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['Total încercări'] ?></div>
                <div class="stat-label">Total Încercări</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['Quiz-uri promovate'] ?></div>
                <div class="stat-label">Quiz-uri Promovate</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['Scor mediu'] ?>%</div>
                <div class="stat-label">Scor Mediu</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['Quiz-uri unice'] ?></div>
                <div class="stat-label">Quiz-uri Diferite</div>
            </div>
        </div>

        <!-- Best Scores -->
        <?php if (!empty($best_scores)): ?>
        <div class="results-table">
            <h3 class="mb-4">
                <i class="fas fa-trophy me-2 text-warning"></i>
                Cele Mai Bune Scoruri
            </h3>
            <div class="best-scores-grid">
                <?php foreach ($best_scores as $score): ?>
                <div class="best-score-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="mb-0"><?= sanitizeInput($score['titlu']) ?></h6>
                        <span class="score-badge <?= $score['best_score'] >= $score['punctaj_minim_promovare'] ? 'score-passed' : 'score-failed' ?>">
                            <?= $score['best_score'] ?>%
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">
                            <?= $score['attempts'] ?> încercare<?= $score['attempts'] > 1 ? 'i' : '' ?>
                        </small>
                        <small class="text-muted">
                            Nota min: <?= $score['punctaj_minim_promovare'] ?>%
                        </small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="quiz-review.php?rezultat_id=<?= $score['best_result_id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye me-1"></i>Vezi Răspunsurile
                        </a>
                        <a href="quiz.php" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-redo me-1"></i>Încearcă din nou
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detailed Results -->
        <div class="results-table">
            <h3 class="mb-4">
                <i class="fas fa-history me-2"></i>
                Istoricul Complet al Rezultatelor
            </h3>
            
            <?php if (!empty($rezultate)): ?>
                <?php foreach ($rezultate as $index => $rezultat): ?>
                <div class="result-row">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h6 class="mb-1"><?= sanitizeInput($rezultat['quiz_titlu']) ?></h6>
                            <?php if ($rezultat['curs_titlu']): ?>
                                <small class="text-muted">
                                    <i class="fas fa-graduation-cap me-1"></i>
                                    <?= sanitizeInput($rezultat['curs_titlu']) ?>
                                </small>
                            <?php endif; ?>
                            <div class="mt-1">
                                <span class="quiz-badge <?= $rezultat['dificultate'] ?>">
                                    <?= ucfirst($rezultat['dificultate']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <span class="score-badge <?= $rezultat['promovat'] ? 'score-passed' : 'score-failed' ?>">
                                <?= number_format($rezultat['procentaj'], 1) ?>%
                            </span>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="text-muted">
                                <i class="fas fa-check-circle me-1 text-success"></i>
                                <?= $rezultat['punctaj_obtinut'] ?>/<?= $rezultat['punctaj_maxim'] ?>
                            </div>
                            <small class="text-muted">
                                Timp: <?= gmdate("i:s", $rezultat['timp_completare']) ?>
                            </small>
                        </div>
                        
                        <div class="col-md-2">
                            <small class="text-muted">
                                <?= date('d.m.Y H:i', strtotime($rezultat['data_realizare'])) ?>
                            </small>
                        </div>
                        
                        <div class="col-md-2 text-end">
                            <a href="quiz-review.php?rezultat_id=<?= $rezultat['id'] ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>Vezi Detalii
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line fa-3x mb-3 text-muted"></i>
                    <h4>Nu ai rezultate încă</h4>
                    <p>Completează primul tău quiz pentru a vedea rezultatele aici.</p>
                    <a href="quiz.php" class="btn btn-primary">
                        <i class="fas fa-play me-2"></i>Începe un Quiz
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include 'components/footer.php'; ?>