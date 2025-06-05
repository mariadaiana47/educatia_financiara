<?php
require_once '../config.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Verifică dacă utilizatorul este conectat
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Trebuie să fii conectat.']);
    exit;
}

// Verifică metoda POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Obține datele din request
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

if (!$input || !isset($input['quiz_id']) || !isset($input['answers']) || !isset($input['time_spent'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datele sunt incomplete.']);
    exit;
}

$quiz_id = (int)$input['quiz_id'];
$answers = $input['answers'];
$time_spent = (int)$input['time_spent'];
$user_id = $_SESSION['user_id'];

try {
    // Verifică dacă quiz-ul există
    $stmt = $pdo->prepare("SELECT * FROM quiz_uri WHERE id = ? AND activ = 1");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Quiz-ul nu a fost găsit.']);
        exit;
    }
    
    // Începe tranzacția
    $pdo->beginTransaction();
    
    // Obține toate întrebările pentru acest quiz
    $stmt = $pdo->prepare("
        SELECT iq.id as question_id, iq.tip, iq.punctaj
        FROM intrebari_quiz iq
        WHERE iq.quiz_id = ? AND iq.activ = 1
        ORDER BY iq.ordine
    ");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll();
    
    if (empty($questions)) {
        throw new Exception('Nu există întrebări pentru acest quiz.');
    }
    
    // Mapează întrebările pentru acces rapid
    $questions_map = [];
    $total_points = 0;
    foreach ($questions as $q) {
        $questions_map[$q['question_id']] = $q;
        $total_points += $q['punctaj'];
    }
    
    // Calculează scorul
    $correct_count = 0;
    $total_count = count($questions);
    $earned_points = 0;
    $user_answers_for_db = [];
    
    // Procesează răspunsurile utilizatorului - structura simplă
    foreach ($answers as $question_id => $answer_id) {
        if (!isset($questions_map[$question_id])) {
            continue;
        }
        
        $question = $questions_map[$question_id];
        $is_correct = false;
        
        if ($question['tip'] === 'adevar_fals') {
            // Pentru adevăr/fals - answer_id este 'true' sau 'false'
            $stmt = $pdo->prepare("
                SELECT rq.id, rq.raspuns, rq.corect
                FROM raspunsuri_quiz rq
                WHERE rq.intrebare_id = ? AND rq.corect = 1
                LIMIT 1
            ");
            $stmt->execute([$question_id]);
            $correct_answer = $stmt->fetch();
            
            if ($correct_answer) {
                // Verifică dacă răspunsul utilizatorului e corect
                if (($answer_id === 'true' && stripos($correct_answer['raspuns'], 'adevăr') !== false) ||
                    ($answer_id === 'false' && stripos($correct_answer['raspuns'], 'fals') !== false)) {
                    $is_correct = true;
                }
                
                $user_answers_for_db[] = [
                    'question_id' => $question_id,
                    'answer_id' => $correct_answer['id'],
                    'answer_text' => $answer_id,
                    'is_correct' => $is_correct,
                    'points' => $is_correct ? $question['punctaj'] : 0
                ];
            }
        } else {
            // Pentru întrebări cu opțiuni multiple
            if (!is_numeric($answer_id)) {
                continue;
            }
            
            // Verifică dacă răspunsul ales este corect
            $stmt = $pdo->prepare("
                SELECT corect FROM raspunsuri_quiz 
                WHERE id = ? AND intrebare_id = ?
            ");
            $stmt->execute([$answer_id, $question_id]);
            $answer_info = $stmt->fetch();
            
            if ($answer_info && $answer_info['corect'] == 1) {
                $is_correct = true;
            }
            
            $user_answers_for_db[] = [
                'question_id' => $question_id,
                'answer_id' => $answer_id,
                'answer_text' => null,
                'is_correct' => $is_correct,
                'points' => $is_correct ? $question['punctaj'] : 0
            ];
        }
        
        if ($is_correct) {
            $correct_count++;
            $earned_points += $question['punctaj'];
        }
    }
    
    // Calculează procentajul
    $percentage = $total_count > 0 ? round(($correct_count / $total_count) * 100, 2) : 0;
    $promovat = $percentage >= $quiz['punctaj_minim_promovare'];
    
    // Salvează rezultatul principal
    $stmt = $pdo->prepare("
        INSERT INTO rezultate_quiz 
        (user_id, quiz_id, punctaj_obtinut, punctaj_maxim, procentaj, promovat, timp_completare, data_realizare)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $user_id,
        $quiz_id,
        $correct_count,
        $total_count,
        $percentage,
        $promovat ? 1 : 0,
        $time_spent
    ]);
    
    $rezultat_id = $pdo->lastInsertId();
    
    // Salvează răspunsurile individuale
    foreach ($user_answers_for_db as $ua) {
        $stmt = $pdo->prepare("
            INSERT INTO raspunsuri_utilizatori 
            (rezultat_id, intrebare_id, raspuns_ales_id, raspuns_text, corect, punctaj_obtinut)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $rezultat_id,
            $ua['question_id'],
            $ua['answer_id'],
            $ua['answer_text'],
            $ua['is_correct'] ? 1 : 0,
            $ua['points']
        ]);
    }
    
    // Commit tranzacția
    $pdo->commit();
    
    // Returnează rezultatul complet
    echo json_encode([
        'success' => true,
        'message' => 'Quiz completat cu succes!',
        'data' => [
            'promovat' => $promovat,
            'score' => $percentage,
            'corecte' => $correct_count,
            'total' => $total_count,
            'punctaj_obtinut' => $earned_points,
            'punctaj_maxim' => $total_points,
            'timp_completare' => $time_spent,
            'rezultat_id' => $rezultat_id
        ]
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Error in save-quiz-results.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Eroare la salvarea rezultatului: ' . $e->getMessage()
    ]);
}
?>