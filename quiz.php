<?php
require_once 'config.php';

// Verifică dacă utilizatorul este conectat
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    redirectTo('login.php');
}

// Verifică și șterge mesajul de eroare din sesiune
$error_message = null;
if (isset($_SESSION['error']) && !empty($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}

$page_title = 'Quiz-uri - ' . SITE_NAME;

// Parametrii de filtrare
$nivel_filter = isset($_GET['nivel']) ? sanitizeInput($_GET['nivel']) : 'toate';

try {
    // Construiesc condițiile WHERE pentru filtrare
    $where_conditions = ['q.activ = 1'];
    $params = [];
    
    if ($nivel_filter && $nivel_filter !== 'toate') {
        $where_conditions[] = 'q.dificultate = ?';
        $params[] = $nivel_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Query principal pentru quiz-uri cu verificarea accesului
    if (isAdmin()) {
        // Adminii văd toate quiz-urile
        $stmt = $pdo->prepare("
            SELECT q.*, c.titlu as curs_titlu,
                   COUNT(DISTINCT iq.id) as numar_intrebari_total,
                   (SELECT COUNT(*) FROM rezultate_quiz rz WHERE rz.quiz_id = q.id) as total_incercari,
                   (SELECT COUNT(*) FROM rezultate_quiz rz WHERE rz.quiz_id = q.id AND rz.promovat = 1) as total_promovari
            FROM quiz_uri q
            LEFT JOIN cursuri c ON q.curs_id = c.id
            LEFT JOIN intrebari_quiz iq ON q.id = iq.quiz_id AND iq.activ = 1
            WHERE $where_clause
            GROUP BY q.id
            ORDER BY q.data_creare DESC
        ");
        $stmt->execute($params);
    } else {
        // Utilizatorii normali văd doar quiz-urile la care au acces
        $stmt = $pdo->prepare("
            SELECT q.*, c.titlu as curs_titlu,
                   COUNT(DISTINCT iq.id) as numar_intrebari_total,
                   ic.id as inscriere_id,
                   (SELECT rz.procentaj FROM rezultate_quiz rz WHERE rz.quiz_id = q.id AND rz.user_id = ? ORDER BY rz.data_realizare DESC LIMIT 1) as ultima_nota,
                   (SELECT COUNT(*) FROM rezultate_quiz rz WHERE rz.quiz_id = q.id AND rz.user_id = ?) as incercari_utilizator
            FROM quiz_uri q
            LEFT JOIN cursuri c ON q.curs_id = c.id AND c.activ = 1
            LEFT JOIN intrebari_quiz iq ON q.id = iq.quiz_id AND iq.activ = 1
            LEFT JOIN inscrieri_cursuri ic ON c.id = ic.curs_id AND ic.user_id = ?
            WHERE $where_clause 
            AND (q.curs_id IS NULL OR ic.id IS NOT NULL)
            GROUP BY q.id
            ORDER BY q.data_creare DESC
        ");
        $params = array_merge([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']], $params);
        $stmt->execute($params);
    }
    
    $quiz_uri = $stmt->fetchAll();
    
    // Statistici generale
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_quiz
        FROM quiz_uri q
        LEFT JOIN cursuri c ON q.curs_id = c.id
        LEFT JOIN inscrieri_cursuri ic ON c.id = ic.curs_id AND ic.user_id = ?
        WHERE q.activ = 1 AND (q.curs_id IS NULL OR ic.id IS NOT NULL OR ? = 1)
    ");
    $stmt->execute([$_SESSION['user_id'], isAdmin() ? 1 : 0]);
    $total_quiz = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as quiz_completate
        FROM rezultate_quiz 
        WHERE user_id = ? AND promovat = 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $quiz_completate = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $quiz_uri = [];
    $total_quiz = 0;
    $quiz_completate = 0;
    error_log("Error in quiz.php: " . $e->getMessage());
}

include 'components/header.php';

// Afișează mesajul de eroare dacă există
if (isset($error_message) && !empty($error_message)): ?>
<div class="container mt-3">
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>

<style>
/* QUIZ PAGE STYLES - CARDURI UNIFORME */

.quiz-page {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.quiz-hero {
    background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05));
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 3rem 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255,255,255,0.2);
    text-align: center;
    color: white;
}

.quiz-hero h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.quiz-hero p {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 0;
}

/* Quiz Statistics Cards */
.quiz-stats {
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
    backdrop-filter: blur(10px);
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
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

/* Filter Section */
.quiz-filters {
    background: rgba(255,255,255,0.95);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.filter-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
}

.filter-title i {
    margin-right: 0.5rem;
    color: #667eea;
}

/* Quiz Cards - UNIFORME */
.quiz-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
}

.quiz-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid #f0f0f0;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.quiz-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.quiz-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 1.5rem;
    position: relative;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.quiz-difficulty {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.quiz-difficulty.usor {
    background: rgba(40, 167, 69, 0.9);
}

.quiz-difficulty.mediu {
    background: rgba(255, 193, 7, 0.9);
    color: #333;
}

.quiz-difficulty.greu {
    background: rgba(220, 53, 69, 0.9);
}

.quiz-title {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    line-height: 1.3;
    padding-right: 100px;
    min-height: 2.6rem;
    display: flex;
    align-items: center;
}

.quiz-course {
    font-size: 0.9rem;
    opacity: 0.8;
    margin-bottom: 0;
    min-height: 1.5rem;
    display: flex;
    align-items: center;
}

.quiz-body {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.quiz-description {
    color: #666;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    flex: 1;
    min-height: 3rem;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

/* META INFORMAȚII UNIFORME */
.quiz-meta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    min-height: 80px;
}

.meta-item {
    display: flex;
    align-items: center;
    font-size: 0.9rem;
    color: #555;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    min-height: 35px;
}

.meta-item i {
    margin-right: 0.5rem;
    color: #667eea;
    width: 16px;
    flex-shrink: 0;
}

/* SECȚIUNEA DE PROGRES/STATISTICI UNIFORMĂ */
.quiz-progress {
    margin-bottom: 1.5rem;
    min-height: 60px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.progress-label {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* SCORUL PENTRU UTILIZATORI */
.quiz-score {
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-block;
    text-align: center;
    width: 100%;
}

.quiz-score.passed {
    background: #d4edda;
    color: #155724;
}

.quiz-score.failed {
    background: #f8d7da;
    color: #721c24;
}

/* STATISTICI PENTRU ADMIN */
.admin-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
}

.admin-stat-item {
    text-align: center;
    padding: 0.5rem;
    background: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.admin-stat-number {
    font-size: 1.25rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 0.25rem;
}

.admin-stat-label {
    font-size: 0.75rem;
    color: #666;
    text-transform: uppercase;
    font-weight: 500;
}

/* ACȚIUNI UNIFORME */
.quiz-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: auto;
}

.quiz-btn {
    flex: 1;
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 10px;
    font-weight: 500;
    text-decoration: none;
    text-align: center;
    transition: all 0.2s ease;
    font-size: 0.9rem;
    cursor: pointer;
    min-height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.quiz-btn-primary {
    background: #667eea;
    color: white;
}

.quiz-btn-primary:hover {
    background: #5a67d8;
    color: white;
    transform: translateY(-2px);
}

.quiz-btn-secondary {
    background: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
}

.quiz-btn-secondary:hover {
    background: #e9ecef;
    color: #495057;
}

.quiz-btn-success {
    background: #28a745;
    color: white;
}

.quiz-btn-success:hover {
    background: #218838;
    color: white;
}

/* Access Denied */
.access-denied {
    background: #f8d7da;
    color: #721c24;
    padding: 1.5rem;
    border-radius: 10px;
    text-align: center;
}

.access-denied i {
    font-size: 2rem;
    margin-bottom: 1rem;
    display: block;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: rgba(255,255,255,0.9);
    border-radius: 20px;
    margin-top: 2rem;
}

.empty-state i {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 1.5rem;
}

.empty-state h3 {
    color: #495057;
    margin-bottom: 1rem;
}

.empty-state p {
    color: #6c757d;
    margin-bottom: 2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .quiz-page {
        padding: 1rem 0;
    }
    
    .quiz-hero {
        padding: 2rem 1rem;
        margin-bottom: 1rem;
    }
    
    .quiz-hero h1 {
        font-size: 2rem;
    }
    
    .quiz-stats {
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .quiz-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .quiz-filters {
        padding: 1.5rem;
    }
    
    .quiz-actions {
        flex-direction: column;
    }
    
    .quiz-header {
        min-height: 100px;
    }
    
    .quiz-title {
        font-size: 1.1rem;
        padding-right: 80px;
    }
    
    .quiz-meta {
        grid-template-columns: 1fr;
        min-height: 140px;
    }
}

@media (max-width: 576px) {
    .quiz-stats {
        grid-template-columns: 1fr;
    }
    
    .admin-stats {
        grid-template-columns: 1fr;
    }
    
    .quiz-difficulty {
        font-size: 0.65rem;
        padding: 0.2rem 0.5rem;
    }
}
</style>

<div class="quiz-page">
    <div class="container">
        <!-- Hero Section -->
        <div class="quiz-hero">
            <h1>
                <i class="fas fa-brain me-3"></i>
                Quiz-uri Interactive
            </h1>
            <p>Testează-ți cunoștințele și urmărește-ți progresul în educația financiară</p>
        </div>

        <!-- Statistics Cards -->
        <div class="quiz-stats">
            <div class="stat-card">
                <div class="stat-number"><?= $total_quiz ?></div>
                <div class="stat-label">Quiz-uri Disponibile</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $quiz_completate ?></div>
                <div class="stat-label">Quiz-uri Completate</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_quiz > 0 ? round(($quiz_completate / $total_quiz) * 100) : 0 ?>%</div>
                <div class="stat-label">Rata de Finalizare</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($quiz_uri) ?></div>
                <div class="stat-label">Rezultate Filtrate</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="quiz-filters">
            <h5 class="filter-title">
                <i class="fas fa-filter"></i>
                Filtrează Quiz-urile
            </h5>
            <form method="GET" class="row g-3" id="filterForm">
                <div class="col-md-6">
                    <label for="nivel" class="form-label">Dificultate</label>
                    <select class="form-select" id="nivel" name="nivel" onchange="this.form.submit()">
                        <option value="toate" <?= $nivel_filter === 'toate' ? 'selected' : '' ?>>Toate dificultățile</option>
                        <option value="usor" <?= $nivel_filter === 'usor' ? 'selected' : '' ?>>Ușor</option>
                        <option value="mediu" <?= $nivel_filter === 'mediu' ? 'selected' : '' ?>>Mediu</option>
                        <option value="greu" <?= $nivel_filter === 'greu' ? 'selected' : '' ?>>Dificil</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <a href="quiz.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Reset Filtre
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Quiz Grid -->
        <?php if (!empty($quiz_uri)): ?>
            <div class="quiz-grid">
                <?php foreach ($quiz_uri as $quiz): ?>
                    <div class="quiz-card">
                        <div class="quiz-header">
                            <div class="quiz-difficulty <?= $quiz['dificultate'] ?>">
                                <?= ucfirst($quiz['dificultate']) ?>
                            </div>
                            <h3 class="quiz-title"><?= sanitizeInput($quiz['titlu']) ?></h3>
                            <?php if ($quiz['curs_titlu']): ?>
                                <p class="quiz-course">
                                    <i class="fas fa-graduation-cap me-1"></i>
                                    <?= sanitizeInput($quiz['curs_titlu']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="quiz-body">
                            <p class="quiz-description">
                                <?= sanitizeInput($quiz['descriere']) ?>
                            </p>
                            
                            <div class="quiz-meta">
                                <div class="meta-item">
                                    <i class="fas fa-question-circle"></i>
                                    <span><?= $quiz['numar_intrebari_total'] ?> întrebări</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?= $quiz['timp_limita'] > 0 ? $quiz['timp_limita'] . ' min' : 'Nelimitat' ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-trophy"></i>
                                    <span>Nota min: <?= $quiz['punctaj_minim_promovare'] ?>%</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?= date('d.m.Y', strtotime($quiz['data_creare'])) ?></span>
                                </div>
                            </div>

                            <!-- Progress pentru utilizatori -->
                            <?php if (!isAdmin() && isset($quiz['ultima_nota'])): ?>
                                <div class="quiz-progress">
                                    <div class="progress-label">
                                        <span>Ultima încercare:</span>
                                        <span><?= $quiz['incercari_utilizator'] ?> încercări</span>
                                    </div>
                                    <div class="quiz-score <?= $quiz['ultima_nota'] >= $quiz['punctaj_minim_promovare'] ? 'passed' : 'failed' ?>">
                                        <i class="fas <?= $quiz['ultima_nota'] >= $quiz['punctaj_minim_promovare'] ? 'fa-check-circle' : 'fa-times-circle' ?> me-1"></i>
                                        <?= number_format($quiz['ultima_nota'], 1) ?>%
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Statistici pentru admin -->
                            <?php if (isAdmin()): ?>
                                <div class="quiz-progress">
                                    <div class="progress-label">
                                        <span>Statistici:</span>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">Încercări: <?= $quiz['total_incercari'] ?? 0 ?></small>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Promovări: <?= $quiz['total_promovari'] ?? 0 ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="quiz-actions">
                                <button class="quiz-btn quiz-btn-primary quiz-start-btn" data-quiz-id="<?= $quiz['id'] ?>">
                                    <i class="fas fa-play me-1"></i>
                                    <?php if (!isAdmin() && isset($quiz['ultima_nota'])): ?>
                                        Încearcă din nou
                                    <?php else: ?>
                                        Începe Quiz-ul
                                    <?php endif; ?>
                                </button>
                                
                                <?php if ($quiz['curs_titlu']): ?>
                                    <a href="curs.php?id=<?= $quiz['curs_id'] ?>" class="quiz-btn quiz-btn-secondary">
                                        <i class="fas fa-graduation-cap me-1"></i>
                                        Vezi Cursul
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>Nu s-au găsit quiz-uri</h3>
                <p>
                    <?php if ($nivel_filter !== 'toate'): ?>
                        Încearcă să ajustezi filtrele pentru a găsi quiz-uri disponibile.
                    <?php else: ?>
                        Nu ai acces la niciun quiz momentan. Înscrie-te la cursuri pentru a debloca quiz-urile asociate.
                    <?php endif; ?>
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="quiz.php" class="btn btn-primary">
                        <i class="fas fa-refresh me-1"></i>Resetează Filtrele
                    </a>
                    <a href="cursuri.php" class="btn btn-success">
                        <i class="fas fa-graduation-cap me-1"></i>Explorează Cursurile
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript pentru funcționalitatea quiz-urilor - SIMPLIFICAT -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Quiz page JavaScript loaded');
    
    // Event listeners pentru butoanele de start quiz
    const quizButtons = document.querySelectorAll('.quiz-start-btn');
    console.log('Found quiz buttons:', quizButtons.length);
    
    quizButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Quiz button clicked');
            
            const quizId = this.getAttribute('data-quiz-id');
            console.log('Quiz ID:', quizId);
            
            if (quizId) {
                // Simplificat - redirect direct la o pagină dedicată quiz-ului
                window.location.href = `quiz-start.php?id=${quizId}`;
            } else {
                console.error('Quiz ID not found');
                alert('Eroare: ID quiz nu a fost găsit');
            }
        });
    });
});
</script>

<?php include 'components/footer.php'; ?>