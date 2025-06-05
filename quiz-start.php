<?php
require_once 'config.php';

// Verifică dacă utilizatorul este conectat
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    redirectTo('login.php');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectTo('quiz.php');
}

$quiz_id = (int)$_GET['id'];

try {
    // Obține informațiile quiz-ului
    $stmt = $pdo->prepare("
        SELECT q.*, c.titlu as curs_titlu
        FROM quiz_uri q
        LEFT JOIN cursuri c ON q.curs_id = c.id
        WHERE q.id = ? AND q.activ = 1
    ");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        $_SESSION['error_message'] = 'Quiz-ul nu a fost găsit.';
        redirectTo('quiz.php');
    }
    
    // Obține întrebările quiz-ului
    $stmt = $pdo->prepare("
        SELECT iq.*, 
               GROUP_CONCAT(
                   JSON_OBJECT(
                       'id', rq.id,
                       'raspuns', rq.raspuns,
                       'corect', rq.corect,
                       'ordine', rq.ordine
                   ) ORDER BY rq.ordine
               ) as raspunsuri_json
        FROM intrebari_quiz iq
        LEFT JOIN raspunsuri_quiz rq ON iq.id = rq.intrebare_id
        WHERE iq.quiz_id = ? AND iq.activ = 1
        GROUP BY iq.id
        ORDER BY iq.ordine, iq.id
        LIMIT ?
    ");
    $stmt->execute([$quiz_id, $quiz['numar_intrebari']]);
    $intrebari_raw = $stmt->fetchAll();
    
    // Procesează răspunsurile JSON
    $intrebari = [];
    foreach ($intrebari_raw as $intrebare) {
        $intrebare['raspunsuri'] = [];
        if ($intrebare['raspunsuri_json']) {
            $raspunsuri_str = '[' . $intrebare['raspunsuri_json'] . ']';
            $raspunsuri_array = json_decode($raspunsuri_str, true);
            
            if ($raspunsuri_array && is_array($raspunsuri_array)) {
                foreach ($raspunsuri_array as $raspuns) {
                    if ($raspuns && is_array($raspuns)) {
                        $intrebare['raspunsuri'][] = $raspuns;
                    }
                }
            }
        }
        unset($intrebare['raspunsuri_json']);
        $intrebari[] = $intrebare;
    }
    
    if (empty($intrebari)) {
        $_SESSION['error_message'] = 'Nu există întrebări pentru acest quiz.';
        redirectTo('quiz.php');
    }
    
    // Verifică rezultatele anterioare
    $stmt = $pdo->prepare("
        SELECT * FROM rezultate_quiz 
        WHERE user_id = ? AND quiz_id = ? 
        ORDER BY data_realizare DESC 
        LIMIT 3
    ");
    $stmt->execute([$_SESSION['user_id'], $quiz_id]);
    $rezultate_anterioare = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Eroare la încărcarea quiz-ului.';
    redirectTo('quiz.php');
}

$page_title = $quiz['titlu'] . ' - Quiz - ' . SITE_NAME;

include 'components/header.php';
?>

<style>
/* Quiz Review Styles */
.quiz-review-screen {
    padding: 2rem 0;
}

.review-header {
    text-align: center;
    margin-bottom: 3rem;
    padding: 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    color: white;
}

.review-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.review-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 2rem;
}

.review-summary {
    display: flex;
    justify-content: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.summary-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    backdrop-filter: blur(10px);
    min-width: 100px;
}

.summary-item.correct .summary-number {
    color: #10b981;
}

.summary-item.incorrect .summary-number {
    color: #ef4444;
}

.summary-item.unanswered .summary-number {
    color: #f59e0b;
}

.summary-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.summary-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.questions-review {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.review-question-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border-left: 4px solid #e5e7eb;
    transition: all 0.3s ease;
}

.review-question-card.correct {
    border-left-color: #10b981;
    background: linear-gradient(to right, #ecfdf5, #ffffff);
}

.review-question-card.incorrect {
    border-left-color: #ef4444;
    background: linear-gradient(to right, #fef2f2, #ffffff);
}

.review-question-card.unanswered {
    border-left-color: #f59e0b;
    background: linear-gradient(to right, #fffbeb, #ffffff);
}

.review-question-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.question-number-review {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.question-index {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #f3f4f6;
    border-radius: 50%;
    font-weight: 700;
    font-size: 1.1rem;
}

.question-status-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: white;
}

.correct .question-status-icon {
    background: #10b981;
}

.incorrect .question-status-icon {
    background: #ef4444;
}

.unanswered .question-status-icon {
    background: #f59e0b;
}

.review-question-text {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    line-height: 1.4;
}

.review-answers {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.answer-comparison {
    display: grid;
    gap: 1rem;
    grid-template-columns: 1fr;
}

@media (min-width: 768px) {
    .answer-comparison {
        grid-template-columns: 1fr 1fr;
    }
}

.answer-section {
    padding: 1rem;
    border-radius: 12px;
    border: 2px solid transparent;
}

.answer-section.correct {
    background: #ecfdf5;
    border-color: #10b981;
}

.answer-section.incorrect {
    background: #fef2f2;
    border-color: #ef4444;
}

.answer-section.unanswered {
    background: #fffbeb;
    border-color: #f59e0b;
}

.answer-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.answer-label i {
    font-size: 0.8rem;
}

.answer-text {
    font-size: 1rem;
    line-height: 1.4;
    color: #374151;
}

.answer-explanation {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
}

.explanation-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #4f46e5;
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
}

.explanation-text {
    color: #475569;
    line-height: 1.6;
}

.review-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 2rem;
    background: #f8fafc;
    border-radius: 16px;
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .review-summary {
        gap: 1rem;
    }
    
    .summary-item {
        min-width: 80px;
        padding: 0.75rem;
    }
    
    .summary-number {
        font-size: 1.5rem;
    }
    
    .review-question-header {
        flex-direction: column;
        gap: 0.75rem;
        text-align: center;
    }
    
    .review-actions {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
    }
}
</style>

<div class="quiz-page">
    <div class="container">
        <!-- Quiz Start Screen -->
        <div id="quizStartScreen" class="quiz-start-screen">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    
                    <!-- Quiz Header -->
                    <div class="quiz-hero">
                        <div class="quiz-icon">
                            <i class="fas fa-brain"></i>
                        </div>
                        <h1 class="quiz-title"><?= sanitizeInput($quiz['titlu']) ?></h1>
                        <?php if ($quiz['curs_titlu']): ?>
                            <p class="quiz-course">
                                <i class="fas fa-graduation-cap me-2"></i>
                                <?= sanitizeInput($quiz['curs_titlu']) ?>
                            </p>
                        <?php endif; ?>
                        <p class="quiz-description">
                            <?= sanitizeInput($quiz['descriere']) ?>
                        </p>
                        
                        <div class="quiz-difficulty-tag">
                            <span class="difficulty-badge <?= $quiz['dificultate'] ?>">
                                <?php
                                $difficulty_labels = [
                                    'usor' => 'Ușor',
                                    'mediu' => 'Mediu', 
                                    'greu' => 'Dificil'
                                ];
                                echo $difficulty_labels[$quiz['dificultate']] ?? 'Mediu';
                                ?>
                            </span>
                        </div>
                    </div>

                    <!-- Quiz Stats -->
                    <div class="quiz-stats">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?= count($intrebari) ?></div>
                                <div class="stat-label">Întrebări</div>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number">
                                    <?= $quiz['timp_limita'] > 0 ? $quiz['timp_limita'] . ' min' : '∞' ?>
                                </div>
                                <div class="stat-label">Timp</div>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?= $quiz['punctaj_minim_promovare'] ?>%</div>
                                <div class="stat-label">Nota de trecere</div>
                            </div>
                        </div>
                    </div>

                    <!-- Previous Results -->
                    <?php if (!empty($rezultate_anterioare)): ?>
                        <div class="previous-results">
                            <h5 class="previous-results-title">
                                <i class="fas fa-history me-2"></i>Rezultatele tale anterioare
                            </h5>
                            <div class="results-grid">
                                <?php foreach ($rezultate_anterioare as $rezultat): ?>
                                    <div class="result-badge <?= $rezultat['promovat'] ? 'passed' : 'failed' ?>">
                                        <div class="result-score"><?= number_format($rezultat['procentaj'], 0) ?>%</div>
                                        <div class="result-date"><?= date('d.m.Y', strtotime($rezultat['data_realizare'])) ?></div>
                                        <?php if ($rezultat['promovat']): ?>
                                            <i class="fas fa-check-circle result-icon"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Quiz Instructions -->
                    <div class="quiz-instructions">
                        <h5>
                            <i class="fas fa-info-circle me-2"></i>Cum funcționează
                        </h5>
                        <div class="instructions-grid">
                            <div class="instruction-item">
                                <span class="instruction-number">1</span>
                                <span>Citește cu atenție fiecare întrebare</span>
                            </div>
                            <div class="instruction-item">
                                <span class="instruction-number">2</span>
                                <span>Selectează răspunsul pe care îl consideri corect</span>
                            </div>
                            <div class="instruction-item">
                                <span class="instruction-number">3</span>
                                <span>Poți naviga înapoi pentru a schimba răspunsurile</span>
                            </div>
                            <div class="instruction-item">
                                <span class="instruction-number">4</span>
                                <span>La final vei vedea scorul și explicațiile</span>
                            </div>
                        </div>
                    </div>

                    <!-- Start Button -->
                    <div class="quiz-start-action">
                        <button onclick="startQuiz()" class="start-quiz-btn">
                            <span class="btn-text">
                                <?= !empty($rezultate_anterioare) ? 'Reluează Quiz-ul' : 'Începe Quiz-ul' ?>
                            </span>
                            <i class="fas fa-play btn-icon"></i>
                        </button>
                        
                        <div class="start-note">
                            <?php if ($quiz['timp_limita'] > 0): ?>
                                ⏱️ Timer-ul va începe automat când pornești quiz-ul
                            <?php else: ?>
                                💡 Poți lua timpul necesar pentru a răspunde
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quiz Questions Screen -->
        <div id="quizQuestionsScreen" class="quiz-questions-screen" style="display: none;">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    
                    <!-- Quiz Header -->
                    <div class="quiz-header">
                        <div class="quiz-progress-info">
                            <span class="question-counter">
                                Întrebarea <span id="currentQuestionNumber">1</span> din <?= count($intrebari) ?>
                            </span>
                            <?php if ($quiz['timp_limita'] > 0): ?>
                                <div class="quiz-timer" id="quizTimer">
                                    <i class="fas fa-clock"></i>
                                    <span id="timeDisplay"><?= $quiz['timp_limita'] ?>:00</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="progress-bar-container">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressFill"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Question Card -->
                    <div class="question-card">
                        <h3 class="question-text" id="questionText"></h3>
                        <div class="answers-container" id="answersContainer"></div>
                    </div>

                    <!-- Navigation -->
                    <div class="quiz-navigation">
                        <button class="nav-btn secondary" id="prevButton" onclick="previousQuestion()" disabled>
                            <i class="fas fa-arrow-left"></i>
                            <span>Anterior</span>
                        </button>
                        
                        <div class="question-dots" id="questionDots"></div>
                        
                        <button class="nav-btn primary" id="nextButton" onclick="nextQuestion()">
                            <span>Următorul</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quiz Results Screen -->
        <div id="quizResultsScreen" class="quiz-results-screen" style="display: none;">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    
                    <div class="results-container">
                        <!-- Score Display -->
                        <div class="score-display">
                            <div class="score-circle" id="scoreCircle">
                                <div class="score-percentage" id="scorePercentage">0%</div>
                                <div class="score-label">Scorul tău</div>
                            </div>
                            
                            <h2 class="result-message" id="resultMessage">Calculez rezultatele...</h2>
                            
                            <div class="result-status" id="resultStatus"></div>
                        </div>

                        <!-- Results Details -->
                        <div class="results-details">
                            <div class="detail-item">
                                <span class="detail-label">Răspunsuri corecte</span>
                                <span class="detail-value" id="correctCount">0</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Total întrebări</span>
                                <span class="detail-value"><?= count($intrebari) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Timp utilizat</span>
                                <span class="detail-value" id="timeUsed">-</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="results-actions">
                            <button class="action-btn primary" onclick="retakeQuiz()">
                                <i class="fas fa-redo"></i>
                                <span>Încearcă din nou</span>
                            </button>
                            
                            <button class="action-btn secondary" onclick="reviewAnswers()">
                                <i class="fas fa-eye"></i>
                                <span>Revizuiește răspunsurile</span>
                            </button>
                            
                            <?php if ($quiz['curs_id']): ?>
                                <a href="curs.php?id=<?= $quiz['curs_id'] ?>" class="action-btn outline">
                                    <i class="fas fa-arrow-left"></i>
                                    <span>Înapoi la curs</span>
                                </a>
                            <?php else: ?>
                                <a href="quiz.php" class="action-btn outline">
                                    <i class="fas fa-list"></i>
                                    <span>Alte quiz-uri</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quiz Review Screen -->
        <div id="quizReviewScreen" class="quiz-review-screen" style="display: none;">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">
                    
                    <!-- Review Header -->
                    <div class="review-header">
                        <h2 class="review-title">
                            <i class="fas fa-eye me-2"></i>Revizuirea răspunsurilor
                        </h2>
                        <p class="review-subtitle">Vezi răspunsurile tale și explicațiile pentru fiecare întrebare</p>
                        
                        <div class="review-summary">
                            <div class="summary-item correct">
                                <span class="summary-number" id="reviewCorrectCount">0</span>
                                <span class="summary-label">Corecte</span>
                            </div>
                            <div class="summary-item incorrect">
                                <span class="summary-number" id="reviewIncorrectCount">0</span>
                                <span class="summary-label">Greșite</span>
                            </div>
                            <div class="summary-item unanswered">
                                <span class="summary-number" id="reviewUnansweredCount">0</span>
                                <span class="summary-label">Necompletate</span>
                            </div>
                        </div>
                    </div>

                    <!-- Questions Review -->
                    <div class="questions-review" id="questionsReview">
                        <!-- Questions will be loaded here -->
                    </div>

                    <!-- Review Actions -->
                    <div class="review-actions">
                        <button class="action-btn secondary" onclick="backToResults()">
                            <i class="fas fa-arrow-left"></i>
                            <span>Înapoi la rezultate</span>
                        </button>
                        
                        <button class="action-btn primary" onclick="retakeQuiz()">
                            <i class="fas fa-redo"></i>
                            <span>Încearcă din nou</span>
                        </button>
                        
                        <?php if ($quiz['curs_id']): ?>
                            <a href="curs.php?id=<?= $quiz['curs_id'] ?>" class="action-btn outline">
                                <i class="fas fa-graduation-cap"></i>
                                <span>Înapoi la curs</span>
                            </a>
                        <?php else: ?>
                            <a href="quiz.php" class="action-btn outline">
                                <i class="fas fa-list"></i>
                                <span>Alte quiz-uri</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Quiz Data
const quizData = {
    id: <?= $quiz_id ?>,
    questions: <?= json_encode($intrebari) ?>,
    timeLimit: <?= $quiz['timp_limita'] ?>,
    passingScore: <?= $quiz['punctaj_minim_promovare'] ?>
};

let currentQuestionIndex = 0;
let userAnswers = {};
let quizStartTime = null;
let quizTimer = null;
let timeRemaining = quizData.timeLimit * 60;

function startQuiz() {
    document.getElementById('quizStartScreen').style.display = 'none';
    document.getElementById('quizQuestionsScreen').style.display = 'block';
    
    quizStartTime = Date.now();
    currentQuestionIndex = 0;
    userAnswers = {};
    
    if (quizData.timeLimit > 0) {
        startTimer();
    }
    
    initializeQuestionDots();
    loadQuestion(0);
    updateProgress();
}

function startTimer() {
    const timerDisplay = document.getElementById('timeDisplay');
    
    quizTimer = setInterval(() => {
        timeRemaining--;
        
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        
        timerDisplay.textContent = minutes + ':' + seconds.toString().padStart(2, '0');
        
        const timerElement = document.getElementById('quizTimer');
        if (timeRemaining <= 300) { // ultimele 5 minute
            timerElement.classList.add('timer-warning');
        }
        
        if (timeRemaining <= 0) {
            clearInterval(quizTimer);
            finishQuiz();
        }
    }, 1000);
}

function initializeQuestionDots() {
    const dotsContainer = document.getElementById('questionDots');
    dotsContainer.innerHTML = '';
    
    for (let i = 0; i < quizData.questions.length; i++) {
        const dot = document.createElement('div');
        dot.className = 'question-dot';
        dot.onclick = () => goToQuestion(i);
        dotsContainer.appendChild(dot);
    }
}

function loadQuestion(index) {
    const question = quizData.questions[index];
    const questionText = document.getElementById('questionText');
    const answersContainer = document.getElementById('answersContainer');
    
    questionText.textContent = question.intrebare;
    
    let answersHTML = '';
    
    if (question.tip === 'adevar_fals') {
        answersHTML = `
            <div class="answer-option" data-answer="1" onclick="selectAnswer(1)">
                <div class="answer-radio"></div>
                <div class="answer-text">Adevărat</div>
            </div>
            <div class="answer-option" data-answer="0" onclick="selectAnswer(0)">
                <div class="answer-radio"></div>
                <div class="answer-text">Fals</div>
            </div>
        `;
    } else {
        question.raspunsuri.forEach((raspuns, idx) => {
            answersHTML += `
                <div class="answer-option" data-answer="${raspuns.id}" onclick="selectAnswer(${raspuns.id})">
                    <div class="answer-radio"></div>
                    <div class="answer-text">${raspuns.raspuns}</div>
                </div>
            `;
        });
    }
    
    answersContainer.innerHTML = answersHTML;
    
    // Restore previous answer
    if (userAnswers[index] !== undefined) {
        const selectedOption = document.querySelector(`[data-answer="${userAnswers[index]}"]`);
        if (selectedOption) {
            selectedOption.classList.add('selected');
        }
    }
    
    // Update navigation
    document.getElementById('prevButton').disabled = index === 0;
    const nextButton = document.getElementById('nextButton');
    nextButton.querySelector('span').textContent = 
        index === quizData.questions.length - 1 ? 'Finalizează' : 'Următorul';
    
    document.getElementById('currentQuestionNumber').textContent = index + 1;
    
    // Update question dots
    updateQuestionDots();
}

function selectAnswer(answerId) {
    // Remove previous selection
    document.querySelectorAll('.answer-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Add new selection
    const selectedOption = document.querySelector(`[data-answer="${answerId}"]`);
    selectedOption.classList.add('selected');
    
    // Save answer
    userAnswers[currentQuestionIndex] = answerId;
    
    // Update question dot
    updateQuestionDots();
}

function nextQuestion() {
    if (currentQuestionIndex < quizData.questions.length - 1) {
        currentQuestionIndex++;
        loadQuestion(currentQuestionIndex);
        updateProgress();
    } else {
        finishQuiz();
    }
}

function previousQuestion() {
    if (currentQuestionIndex > 0) {
        currentQuestionIndex--;
        loadQuestion(currentQuestionIndex);
        updateProgress();
    }
}

function goToQuestion(index) {
    currentQuestionIndex = index;
    loadQuestion(currentQuestionIndex);
    updateProgress();
}

function updateProgress() {
    const progress = ((currentQuestionIndex + 1) / quizData.questions.length) * 100;
    document.getElementById('progressFill').style.width = progress + '%';
}

function updateQuestionDots() {
    const dots = document.querySelectorAll('.question-dot');
    dots.forEach((dot, index) => {
        dot.classList.remove('current', 'answered');
        
        if (index === currentQuestionIndex) {
            dot.classList.add('current');
        }
        
        if (userAnswers[index] !== undefined) {
            dot.classList.add('answered');
        }
    });
}

function finishQuiz() {
    if (quizTimer) {
        clearInterval(quizTimer);
    }
    
    // Calculate results
    let correctAnswers = 0;
    const totalQuestions = quizData.questions.length;
    
    for (let i = 0; i < totalQuestions; i++) {
        const question = quizData.questions[i];
        const userAnswer = userAnswers[i];
        
        if (question.tip === 'adevar_fals') {
            const correctAnswer = question.raspunsuri.find(r => r.corect == 1);
            if (correctAnswer && userAnswer == correctAnswer.corect) {
                correctAnswers++;
            }
        } else {
            const selectedAnswer = question.raspunsuri.find(r => r.id == userAnswer);
            if (selectedAnswer && selectedAnswer.corect == 1) {
                correctAnswers++;
            }
        }
    }
    
    const percentage = (correctAnswers / totalQuestions) * 100;
    const passed = percentage >= quizData.passingScore;
    
    const timeUsed = quizData.timeLimit > 0 ? 
        Math.ceil((Date.now() - quizStartTime) / 1000 / 60) : 0;
    
    // Save results
    saveQuizResults(correctAnswers, totalQuestions, percentage, timeUsed, passed);
    
    // Show results
    showResults(correctAnswers, totalQuestions, percentage, timeUsed, passed);
}

function saveQuizResults(correct, total, percentage, timeUsed, passed) {
    fetch('ajax/save-quiz-result.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            quiz_id: quizData.id,
            punctaj_obtinut: correct,
            punctaj_maxim: total,
            procentaj: percentage,
            timp_completare: timeUsed,
            promovat: passed,
            raspunsuri: userAnswers
        })
    })
    .catch(error => console.error('Error saving results:', error));
}

function showResults(correct, total, percentage, timeUsed, passed) {
    document.getElementById('quizQuestionsScreen').style.display = 'none';
    document.getElementById('quizResultsScreen').style.display = 'block';
    
    // Animate score
    const scoreElement = document.getElementById('scorePercentage');
    const scoreCircle = document.getElementById('scoreCircle');
    
    let currentScore = 0;
    const targetScore = Math.round(percentage);
    
    const scoreInterval = setInterval(() => {
        currentScore += 2;
        scoreElement.textContent = Math.min(currentScore, targetScore) + '%';
        
        if (currentScore >= targetScore) {
            clearInterval(scoreInterval);
        }
    }, 30);
    
    // Set circle color based on result
    if (passed) {
        scoreCircle.classList.add('passed');
    } else {
        scoreCircle.classList.add('failed');
    }
    
    // Update other elements
    document.getElementById('correctCount').textContent = correct + '/' + total;
    document.getElementById('timeUsed').textContent = timeUsed > 0 ? timeUsed + ' min' : 'N/A';
    
    const resultMessage = document.getElementById('resultMessage');
    const resultStatus = document.getElementById('resultStatus');
    
    if (passed) {
        resultMessage.textContent = 'Felicitări! Ai trecut testul!';
        resultStatus.innerHTML = '<i class="fas fa-trophy"></i> Ai obținut nota de trecere';
        resultStatus.className = 'result-status passed';
    } else {
        resultMessage.textContent = 'Mai încearcă o dată!';
        resultStatus.innerHTML = '<i class="fas fa-redo"></i> Nu ai atins scorul minim de ' + quizData.passingScore + '%';
        resultStatus.className = 'result-status failed';
    }
}

function retakeQuiz() {
    location.reload();
}

function reviewAnswers() {
    document.getElementById('quizResultsScreen').style.display = 'none';
    document.getElementById('quizReviewScreen').style.display = 'block';
    
    generateReviewContent();
}

function backToResults() {
    document.getElementById('quizReviewScreen').style.display = 'none';
    document.getElementById('quizResultsScreen').style.display = 'block';
}

function generateReviewContent() {
    const reviewContainer = document.getElementById('questionsReview');
    let reviewHTML = '';
    
    let correctCount = 0;
    let incorrectCount = 0;
    let unansweredCount = 0;
    
    quizData.questions.forEach((question, index) => {
        const userAnswer = userAnswers[index];
        let isCorrect = false;
        let correctAnswerText = '';
        let userAnswerText = '';
        let questionStatus = 'unanswered';
        
        if (userAnswer !== undefined) {
            if (question.tip === 'adevar_fals') {
                // Pentru întrebări adevăr/fals
                const correctAnswer = question.raspunsuri.find(r => r.corect == 1);
                isCorrect = userAnswer == correctAnswer.id;
                correctAnswerText = userAnswer == 1 ? 'Adevărat' : 'Fals';
                userAnswerText = userAnswer == 1 ? 'Adevărat' : 'Fals';
            } else {
                // Pentru întrebări cu variante multiple
                const selectedAnswer = question.raspunsuri.find(r => r.id == userAnswer);
                const correctAnswer = question.raspunsuri.find(r => r.corect == 1);
                
                isCorrect = selectedAnswer && selectedAnswer.corect == 1;
                correctAnswerText = correctAnswer ? correctAnswer.raspuns : 'N/A';
                userAnswerText = selectedAnswer ? selectedAnswer.raspuns : 'N/A';
            }
            
            if (isCorrect) {
                correctCount++;
                questionStatus = 'correct';
            } else {
                incorrectCount++;
                questionStatus = 'incorrect';
            }
        } else {
            unansweredCount++;
            if (question.tip === 'adevar_fals') {
                const correctAnswer = question.raspunsuri.find(r => r.corect == 1);
                correctAnswerText = correctAnswer.id == 1 ? 'Adevărat' : 'Fals';
            } else {
                const correctAnswer = question.raspunsuri.find(r => r.corect == 1);
                correctAnswerText = correctAnswer ? correctAnswer.raspuns : 'N/A';
            }
            userAnswerText = 'Nu ai răspuns';
        }
        
        reviewHTML += `
            <div class="review-question-card ${questionStatus}">
                <div class="review-question-header">
                    <div class="question-number-review">
                        <span class="question-index">${index + 1}</span>
                        <div class="question-status-icon">
                            ${questionStatus === 'correct' ? '<i class="fas fa-check"></i>' : 
                              questionStatus === 'incorrect' ? '<i class="fas fa-times"></i>' : 
                              '<i class="fas fa-minus"></i>'}
                        </div>
                    </div>
                    <h4 class="review-question-text">${question.intrebare}</h4>
                </div>
                
                <div class="review-answers">
                    <div class="answer-comparison">
                        <div class="answer-section ${questionStatus === 'unanswered' ? 'unanswered' : (isCorrect ? 'correct' : 'incorrect')}">
                            <div class="answer-label">
                                <i class="fas fa-user"></i>
                                Răspunsul tău
                            </div>
                            <div class="answer-text">${userAnswerText}</div>
                        </div>
                        
                        ${!isCorrect || questionStatus === 'unanswered' ? `
                            <div class="answer-section correct">
                                <div class="answer-label">
                                    <i class="fas fa-check-circle"></i>
                                    Răspunsul corect
                                </div>
                                <div class="answer-text">${correctAnswerText}</div>
                            </div>
                        ` : ''}
                    </div>
                    
                    ${question.explicatie ? `
                        <div class="answer-explanation">
                            <div class="explanation-label">
                                <i class="fas fa-lightbulb"></i>
                                Explicație
                            </div>
                            <div class="explanation-text">${question.explicatie}</div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    reviewContainer.innerHTML = reviewHTML;
    
    // Update summary counts
    document.getElementById('reviewCorrectCount').textContent = correctCount;
    document.getElementById('reviewIncorrectCount').textContent = incorrectCount;
    document.getElementById('reviewUnansweredCount').textContent = unansweredCount;
}
</script>

<?php include 'components/footer.php'; ?>