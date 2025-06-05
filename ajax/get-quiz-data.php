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

if (!$input || !isset($input['quiz_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Quiz ID lipsește']);
    exit;
}

$quiz_id = (int)$input['quiz_id'];
$user_id = $_SESSION['user_id'];

try {
    // Verifică dacă quiz-ul există
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
        echo json_encode(['success' => false, 'message' => 'Quiz-ul nu a fost găsit.']);
        exit;
    }
    
    // Verifică accesul
    $has_access = false;
    
    if ($quiz['curs_id']) {
        if (isAdmin()) {
            $has_access = true;
        } else {
            $stmt = $pdo->prepare("
                SELECT id FROM inscrieri_cursuri 
                WHERE user_id = ? AND curs_id = ?
            ");
            $stmt->execute([$user_id, $quiz['curs_id']]);
            if ($stmt->fetch()) {
                $has_access = true;
            }
        }
        
        if (!$has_access) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'Nu ai acces la acest quiz. Trebuie să fii înscris la cursul asociat.'
            ]);
            exit;
        }
    } else {
        $has_access = true;
    }
    
    // Obține întrebările - FĂRĂ LIMIT sau cu LIMIT mare
    $stmt = $pdo->prepare("
        SELECT iq.id, iq.intrebare, iq.tip, iq.punctaj, iq.ordine
        FROM intrebari_quiz iq
        WHERE iq.quiz_id = ? AND iq.activ = 1
        ORDER BY iq.ordine, iq.id
    ");
    $stmt->execute([$quiz_id]);
    $intrebari_raw = $stmt->fetchAll();
    
    // Debug: verifică câte întrebări am găsit
    error_log("Found " . count($intrebari_raw) . " questions for quiz_id $quiz_id");
    
    if (empty($intrebari_raw)) {
        // Verifică dacă există întrebări dezactivate
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM intrebari_quiz WHERE quiz_id = ?
        ");
        $stmt->execute([$quiz_id]);
        $total_questions = $stmt->fetchColumn();
        
        error_log("Total questions (including inactive): $total_questions");
        
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Nu există întrebări active pentru acest quiz. Total întrebări în DB: ' . $total_questions
        ]);
        exit;
    }
    
    // Pentru fiecare întrebare, obține răspunsurile
    $questions = [];
    foreach ($intrebari_raw as $intrebare) {
        $stmt = $pdo->prepare("
            SELECT id, raspuns, ordine
            FROM raspunsuri_quiz
            WHERE intrebare_id = ?
            ORDER BY ordine
        ");
        $stmt->execute([$intrebare['id']]);
        $raspunsuri = $stmt->fetchAll();
        
        $questions[] = [
            'id' => $intrebare['id'],
            'intrebare' => $intrebare['intrebare'],
            'tip' => $intrebare['tip'],
            'punctaj' => $intrebare['punctaj'],
            'ordine' => $intrebare['ordine'],
            'raspunsuri' => $raspunsuri
        ];
    }
    
    // Aplică limita după ce am obținut întrebările (dacă e setată)
    if ($quiz['numar_intrebari'] && $quiz['numar_intrebari'] > 0 && $quiz['numar_intrebari'] < count($questions)) {
        $questions = array_slice($questions, 0, $quiz['numar_intrebari']);
    }
    
    // Returnează datele
    echo json_encode([
        'success' => true,
        'quiz' => [
            'id' => $quiz['id'],
            'titlu' => $quiz['titlu'],
            'descriere' => $quiz['descriere'],
            'timp_limita' => $quiz['timp_limita'],
            'punctaj_minim_promovare' => $quiz['punctaj_minim_promovare'],
            'curs_titlu' => $quiz['curs_titlu'],
            'numar_intrebari' => count($questions)
        ],
        'questions' => $questions,
        'debug' => [
            'quiz_id' => $quiz_id,
            'total_found' => count($intrebari_raw),
            'returned' => count($questions),
            'limit_setting' => $quiz['numar_intrebari']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Error in get-quiz-data.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Eroare la încărcarea quiz-ului: ' . $e->getMessage()
    ]);
}
?>