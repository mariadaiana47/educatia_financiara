<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Trebuie să fii conectat pentru a accesa quiz-ul.'
    ]);
    exit;
}

if (!isset($_GET['quiz_id']) || !is_numeric($_GET['quiz_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID-ul quiz-ului este invalid.'
    ]);
    exit;
}

$quiz_id = (int)$_GET['quiz_id'];
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT q.*, c.titlu as curs_titlu, c.pret as curs_pret
        FROM quiz_uri q
        LEFT JOIN cursuri c ON q.curs_id = c.id
        WHERE q.id = ? AND q.activ = 1
    ");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Quiz-ul nu a fost găsit.'
        ]);
        exit;
    }
    
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
            $is_enrolled = $stmt->fetch();
            
            if ($is_enrolled) {
                $has_access = true;
            }
        }
        
        if (!$has_access) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Nu ai acces la acest quiz. Trebuie să fii înscris la cursul "' . $quiz['curs_titlu'] . '".',
                'requires_enrollment' => true,
                'course_id' => $quiz['curs_id'],
                'course_title' => $quiz['curs_titlu'],
                'course_price' => $quiz['curs_pret']
            ]);
            exit;
        }
    } else {
        $has_access = true;
    }
    
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
    
    $intrebari = [];
    foreach ($intrebari_raw as $intrebare) {
        $intrebare['raspunsuri'] = [];
        if ($intrebare['raspunsuri_json']) {
            $raspunsuri_str = '[' . $intrebare['raspunsuri_json'] . ']';
            $raspunsuri_array = json_decode($raspunsuri_str, true);
            
            if ($raspunsuri_array && is_array($raspunsuri_array)) {
                foreach ($raspunsuri_array as $raspuns) {
                    if ($raspuns && is_array($raspuns)) {
                        unset($raspuns['corect']);
                        $intrebare['raspunsuri'][] = $raspuns;
                    }
                }
            }
        }
        unset($intrebare['raspunsuri_json']);
        $intrebari[] = $intrebare;
    }
    
    if (empty($intrebari)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Nu există întrebări pentru acest quiz.'
        ]);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT procentaj, promovat, data_realizare 
        FROM rezultate_quiz 
        WHERE user_id = ? AND quiz_id = ? 
        ORDER BY data_realizare DESC 
        LIMIT 3
    ");
    $stmt->execute([$user_id, $quiz_id]);
    $rezultate_anterioare = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'quiz' => [
            'id' => $quiz['id'],
            'titlu' => $quiz['titlu'],
            'descriere' => $quiz['descriere'],
            'timp_limita' => $quiz['timp_limita'],
            'numar_intrebari' => count($intrebari),
            'dificultate' => $quiz['dificultate'],
            'punctaj_minim_promovare' => $quiz['punctaj_minim_promovare'],
            'curs_titlu' => $quiz['curs_titlu']
        ],
        'intrebari' => $intrebari,
        'rezultate_anterioare' => $rezultate_anterioare
    ]);
    
} catch (PDOException $e) {
    error_log('Error in start-quiz.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Eroare la încărcarea quiz-ului. Te rugăm să încerci din nou.'
    ]);
}
?>