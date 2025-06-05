<?php
require_once '../config.php';

header('Content-Type: application/json');

// Verifică dacă utilizatorul este conectat
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Obține datele din request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['quiz_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Quiz ID missing']);
    exit;
}

$quiz_id = (int)$input['quiz_id'];

try {
    // Verifică dacă quiz-ul există și utilizatorul are acces
    $stmt = $pdo->prepare("
        SELECT q.*, c.titlu as curs_titlu
        FROM quiz_uri q
        LEFT JOIN cursuri c ON q.curs_id = c.id
        WHERE q.id = ? AND q.activ = 1
    ");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Quiz not found']);
        exit;
    }
    
    // Verifică accesul
    $has_access = false;
    
    if ($quiz['curs_id']) {
        if (isAdmin()) {
            $has_access = true;
        } else {
            $stmt = $pdo->prepare("SELECT id FROM inscrieri_cursuri WHERE user_id = ? AND curs_id = ?");
            $stmt->execute([$_SESSION['user_id'], $quiz['curs_id']]);
            $has_access = $stmt->fetch() !== false;
        }
    } else {
        $has_access = true; // Quiz-uri independente
    }
    
    if (!$has_access) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Obține răspunsurile corecte
    $stmt = $pdo->prepare("
        SELECT iq.id as question_id, rq.id as answer_id
        FROM intrebari_quiz iq
        INNER JOIN raspunsuri_quiz rq ON iq.id = rq.intrebare_id
        WHERE iq.quiz_id = ? AND iq.activ = 1 AND rq.corect = 1
    ");
    $stmt->execute([$quiz_id]);
    $correct_answers_raw = $stmt->fetchAll();
    
    // Formatează răspunsurile
    $correct_answers = [];
    foreach ($correct_answers_raw as $answer) {
        $correct_answers[$answer['question_id']] = $answer['answer_id'];
    }
    
    echo json_encode([
        'success' => true,
        'answers' => $correct_answers
    ]);
    
} catch (PDOException $e) {
    error_log('Error in get-quiz-answers.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
?>