<?php
require_once '../config.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verifică dacă utilizatorul este conectat
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Trebuie să fii conectat pentru a trimite rezultatul.']);
    exit;
}

// Get raw POST data
$raw_input = file_get_contents('php://input');
error_log('Submit quiz raw request: ' . $raw_input);

// Decode JSON
$input = json_decode($raw_input, true);
error_log('Submit quiz decoded input: ' . print_r($input, true));

// Check if JSON decoding failed
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Eroare la decodarea JSON: ' . json_last_error_msg(),
        'raw_data' => $raw_input
    ]);
    exit;
}

// Check required fields
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datele sunt goale.']);
    exit;
}

if (!isset($input['quiz_id']) || !is_numeric($input['quiz_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Quiz ID lipsește sau este invalid.']);
    exit;
}

if (!isset($input['answers']) || !is_array($input['answers'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Răspunsurile lipsesc sau sunt în format greșit.']);
    exit;
}

if (!isset($input['time_spent']) || !is_numeric($input['time_spent'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Timpul petrecut lipsește sau este invalid.']);
    exit;
}

$quiz_id = (int)$input['quiz_id'];
$answers = $input['answers'];
$time_spent = (int)$input['time_spent'];

error_log("Processing quiz submission - Quiz ID: $quiz_id, Answers count: " . count($answers) . ", Time: $time_spent");

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
    
    error_log("Quiz found: " . $quiz['titlu']);
    
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
    
    error_log("Questions found: " . count($questions));
    
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
    
    // Procesează răspunsurile utilizatorului
    foreach ($answers as $answer) {
        if (!isset($answer['intrebare_id'])) {
            error_log("Answer missing intrebare_id: " . print_r($answer, true));
            continue;
        }
        
        $question_id = $answer['intrebare_id'];
        
        // Verifică dacă există întrebarea
        if (!isset($questions_map[$question_id])) {
            error_log("Question not found: $question_id");
            continue;
        }
        
        $question = $questions_map[$question_id];
        $is_correct = false;
        
        error_log("Processing answer for question $question_id (type: {$question['tip']})");
        
        if ($question['tip'] === 'adevar_fals') {
            // Pentru adevăr/fals
            $user_answer = $answer['raspuns_text'] ?? null;
            
            if (!$user_answer) {
                error_log("Missing raspuns_text for true/false question $question_id");
                continue;
            }
            
            // Obține răspunsul corect
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
                if (($user_answer === 'true' && stripos($correct_answer['raspuns'], 'adevăr') !== false) ||
                    ($user_answer === 'false' && stripos($correct_answer['raspuns'], 'fals') !== false)) {
                    $is_correct = true;
                    error_log("True/false answer correct for question $question_id");
                }
                
                $user_answers_for_db[] = [
                    'question_id' => $question_id,
                    'answer_id' => $correct_answer['id'],
                    'answer_text' => $user_answer,
                    'is_correct' => $is_correct,
                    'points' => $is_correct ? $question['punctaj'] : 0
                ];
            }
        } else {
            // Pentru întrebări cu opțiuni multiple
            $user_answer_id = $answer['raspuns_id'] ?? null;
            
            if (!$user_answer_id) {
                error_log("Missing raspuns_id for multiple choice question $question_id");
                continue;
            }
            
            // Verifică dacă răspunsul ales este corect
            $stmt = $pdo->prepare("
                SELECT corect FROM raspunsuri_quiz 
                WHERE id = ? AND intrebare_id = ?
            ");
            $stmt->execute([$user_answer_id, $question_id]);
            $answer_info = $stmt->fetch();
            
            if ($answer_info && $answer_info['corect'] == 1) {
                $is_correct = true;
                error_log("Multiple choice answer correct for question $question_id");
            }
            
            $user_answers_for_db[] = [
                'question_id' => $question_id,
                'answer_id' => $user_answer_id,
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
    
    error_log("Score calculated: $correct_count/$total_count correct, $earned_points/$total_points points");
    
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
        $_SESSION['user_id'],
        $quiz_id,
        $correct_count,
        $total_count,
        $percentage,
        $promovat ? 1 : 0,
        $time_spent
    ]);
    
    $rezultat_id = $pdo->lastInsertId();
    error_log("Main result saved with ID: $rezultat_id");
    
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
    
    error_log("Individual answers saved");
    
    // Commit tranzacția
    $pdo->commit();
    
    // Returnează rezultatul complet
    $response = [
        'success' => true,
        'message' => 'Quiz completat cu succes!',
        'promovat' => $promovat,
        'procentaj' => $percentage,
        'corecte' => $correct_count,
        'total' => $total_count,
        'punctaj_obtinut' => $earned_points,
        'punctaj_maxim' => $total_points,
        'timp_completare' => $time_spent,
        'rezultat_id' => $rezultat_id
    ];
    
    error_log("Success response: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Error in submit-quiz.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Eroare la salvarea rezultatului: ' . $e->getMessage()
    ]);
}
?>