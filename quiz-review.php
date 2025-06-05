<?php
require_once 'config.php';

// Verifică dacă utilizatorul este conectat
if (!isLoggedIn()) {
    redirectTo('login.php');
}

// Verifică parametrul rezultat_id
if (!isset($_GET['rezultat_id']) || !is_numeric($_GET['rezultat_id'])) {
    $_SESSION['error'] = 'ID rezultat invalid.';
    redirectTo('quiz.php');
}

$rezultat_id = (int)$_GET['rezultat_id'];

try {
    // Obține rezultatul și verifică dacă aparține utilizatorului
    $stmt = $pdo->prepare("
        SELECT rz.*, q.titlu as quiz_titlu, q.descriere as quiz_descriere,
               q.punctaj_minim_promovare, c.titlu as curs_titlu
        FROM rezultate_quiz rz
        INNER JOIN quiz_uri q ON rz.quiz_id = q.id
        LEFT JOIN cursuri c ON q.curs_id = c.id
        WHERE rz.id = ? AND rz.user_id = ?
    ");
    $stmt->execute([$rezultat_id, $_SESSION['user_id']]);
    $rezultat = $stmt->fetch();
    
    if (!$rezultat) {
        $_SESSION['error'] = 'Rezultatul nu a fost găsit sau nu îți aparține.';
        redirectTo('quiz.php');
    }
    
    // Obține răspunsurile utilizatorului cu detaliile întrebărilor
    $stmt = $pdo->prepare("
        SELECT ru.*, iq.intrebare, iq.explicatie, iq.tip,
               ra_ales.raspuns as raspuns_ales,
               ra_corect.raspuns as raspuns_corect,
               ra_corect.id as raspuns_corect_id
        FROM raspunsuri_utilizatori ru
        INNER JOIN intrebari_quiz iq ON ru.intrebare_id = iq.id
        LEFT JOIN raspunsuri_quiz ra_ales ON ru.raspuns_ales_id = ra_ales.id
        LEFT JOIN raspunsuri_quiz ra_corect ON iq.id = ra_corect.intrebare_id AND ra_corect.corect = 1
        WHERE ru.rezultat_id = ?
        ORDER BY iq.ordine, iq.id
    ");
    $stmt->execute([$rezultat_id]);
    $raspunsuri = $stmt->fetchAll();
    
    // Obține toate opțiunile de răspuns pentru fiecare întrebare
    $stmt = $pdo->prepare("
        SELECT rq.*, iq.id as intrebare_id
        FROM raspunsuri_quiz rq
        INNER JOIN intrebari_quiz iq ON rq.intrebare_id = iq.id
        INNER JOIN raspunsuri_utilizatori ru ON iq.id = ru.intrebare_id
        WHERE ru.rezultat_id = ?
        ORDER BY iq.ordine, rq.ordine
    ");
    $stmt->execute([$rezultat_id]);
    $toate_raspunsurile = $stmt->fetchAll();
    
    // Grupează răspunsurile pe întrebări
    $raspunsuri_pe_intrebari = [];
    foreach ($toate_raspunsurile as $rasp) {
        $raspunsuri_pe_intrebari[$rasp['intrebare_id']][] = $rasp;
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Eroare la încărcarea rezultatelor.';
    redirectTo('quiz.php');
}

$page_title = 'Revizuire: ' . $rezultat['quiz_titlu'] . ' - ' . SITE_NAME;
include 'components/header.php';
?>

<style>
.review-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.review-header {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    text-align: center;
}

.review-score {
    display: inline-block;
    padding: 1rem 2rem;
    border-radius: 15px;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.review-score.passed {
    background: #d4edda;
    color: #155724;
    border: 2px solid #c3e6cb;
}

.review-score.failed {
    background: #f8d7da;
    color: #721c24;
    border: 2px solid #f5c6cb;
}

.review-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1.5rem;
}

.stat-item {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
}

.stat-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
}

.question-review {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.question-header {
    display: flex;
    justify-content: between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.question-number {
    background: #667eea;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    margin-right: 1rem;
    flex-shrink: 0;
}

.question-text {
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
    line-height: 1.5;
    flex-grow: 1;
}

.question-result {
    text-align: right;
    flex-shrink: 0;
    margin-left: 1rem;
}

.result-icon {
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
}

.result-icon.correct {
    color: #28a745;
}

.result-icon.incorrect {
    color: #dc3545;
}

.answers-list {
    margin-bottom: 1.5rem;
}

.answer-option {
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    border-radius: 10px;
    border: 2px solid transparent;
    transition: all 0.2s ease;
}

.answer-option.correct {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.answer-option.incorrect {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.answer-option.neutral {
    background: #f8f9fa;
    color: #495057;
    border-color: #e9ecef;
}

.answer-option.user-selected {
    border-left: 4px solid #667eea;
    font-weight: 600;
}

.answer-icon {
    margin-right: 0.5rem;
    font-weight: 700;
}

.explanation {
    background: #e8f4fd;
    border-left: 4px solid #667eea;
    padding: 1rem;
    border-radius: 0 8px 8px 0;
    font-style: italic;
    color: #0c5460;
}

.explanation-title {
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-style: normal;
}

.back-actions {
    text-align: center;
    margin-top: 2rem;
}

.back-btn {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    margin: 0 0.5rem;
    transition: all 0.2s ease;
}

.back-btn:hover {
    background: #667eea;
    color: white;
}

@media (max-width: 768px) {
    .review-container {
        padding: 1rem;
    }
    
    .question-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .question-result {
        text-align: left;
        margin-left: 0;
    }
    
    .review-stats {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<div class="review-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                
                <!-- Review Header -->
                <div class="review-header">
                    <h1 class="mb-3">
                        <i class="fas fa-search me-2"></i>
                        Revizuire Răspunsuri
                    </h1>
                    <h2 class="h4 text-muted mb-3"><?= sanitizeInput($rezultat['quiz_titlu']) ?></h2>
                    
                    <?php if ($rezultat['curs_titlu']): ?>
                        <p class="text-muted mb-3">
                            <i class="fas fa-graduation-cap me-1"></i>
                            Din cursul: <?= sanitizeInput($rezultat['curs_titlu']) ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="review-score <?= $rezultat['promovat'] ? 'passed' : 'failed' ?>">
                        <?php if ($rezultat['promovat']): ?>
                            <i class="fas fa-check-circle me-2"></i>PROMOVAT
                        <?php else: ?>
                            <i class="fas fa-times-circle me-2"></i>NEPROMOVAT
                        <?php endif; ?>
                        - <?= number_format($rezultat['procentaj'], 1) ?>%
                    </div>
                    
                    <div class="review-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?= $rezultat['punctaj_obtinut'] ?></div>
                            <div class="stat-label">Răspunsuri Corecte</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $rezultat['punctaj_maxim'] ?></div>
                            <div class="stat-label">Total Întrebări</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= gmdate("i:s", $rezultat['timp_completare']) ?></div>
                            <div class="stat-label">Timp Utilizat</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $rezultat['punctaj_minim_promovare'] ?>%</div>
                            <div class="stat-label">Nota de Trecere</div>
                        </div>
                    </div>
                </div>

                <!-- Questions Review -->
                <?php foreach ($raspunsuri as $index => $raspuns): ?>
                    <div class="question-review">
                        <div class="question-header">
                            <div class="question-number"><?= $index + 1 ?></div>
                            <div class="question-text"><?= sanitizeInput($raspuns['intrebare']) ?></div>
                            <div class="question-result">
                                <div class="result-icon <?= $raspuns['corect'] ? 'correct' : 'incorrect' ?>">
                                    <i class="fas <?= $raspuns['corect'] ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                </div>
                                <div class="small">
                                    <?= $raspuns['corect'] ? 'Corect' : 'Incorect' ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="answers-list">
                            <?php 
                            $intrebare_id = $raspuns['intrebare_id'];
                            if (isset($raspunsuri_pe_intrebari[$intrebare_id])):
                                foreach ($raspunsuri_pe_intrebari[$intrebare_id] as $optiune):
                                    $is_correct = $optiune['corect'] == 1;
                                    $is_user_choice = $optiune['id'] == $raspuns['raspuns_ales_id'];
                                    
                                    $css_class = '';
                                    $icon = '';
                                    
                                    if ($is_correct) {
                                        $css_class = 'answer-option correct';
                                        $icon = '<i class="fas fa-check answer-icon"></i>';
                                    } elseif ($is_user_choice && !$is_correct) {
                                        $css_class = 'answer-option incorrect';
                                        $icon = '<i class="fas fa-times answer-icon"></i>';
                                    } else {
                                        $css_class = 'answer-option neutral';
                                        $icon = '<i class="fas fa-circle answer-icon" style="opacity: 0.3;"></i>';
                                    }
                                    
                                    if ($is_user_choice) {
                                        $css_class .= ' user-selected';
                                    }
                            ?>
                                    <div class="<?= $css_class ?>">
                                        <?= $icon ?>
                                        <?= sanitizeInput($optiune['raspuns']) ?>
                                        <?php if ($is_user_choice): ?>
                                            <span class="small ms-2">(răspunsul tău)</span>
                                        <?php endif; ?>
                                        <?php if ($is_correct): ?>
                                            <span class="small ms-2">(răspuns corect)</span>
                                        <?php endif; ?>
                                    </div>
                            <?php 
                                endforeach;
                            endif; 
                            ?>
                        </div>
                        
                        <?php if ($raspuns['explicatie']): ?>
                            <div class="explanation">
                                <div class="explanation-title">
                                    <i class="fas fa-lightbulb me-1"></i>Explicație:
                                </div>
                                <?= sanitizeInput($raspuns['explicatie']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <!-- Back Actions -->
                <div class="back-actions">
                    <a href="quiz.php" class="back-btn">
                        <i class="fas fa-list me-2"></i>Înapoi la Quiz-uri
                    </a>
                    
                    <a href="quiz.php" class="back-btn">
                        <i class="fas fa-redo me-2"></i>Încearcă din Nou
                    </a>
                    
                    <?php if (isLoggedIn()): ?>
                        <a href="dashboard.php" class="back-btn">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>