<?php
require_once '../config.php';

header('Content-Type: application/json');

// Verifică dacă utilizatorul este conectat
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Trebuie să fii conectat']);
    exit;
}

// Verifică parametrii
if (!isset($_GET['quiz_id']) || !is_numeric($_GET['quiz_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID quiz invalid']);
    exit;
}

$quiz_id = (int)$_GET['quiz_id'];
$user_id = $_SESSION['user_id'];

try {
    // Verifică dacă quiz-ul există și este activ
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
        echo json_encode(['success' => false, 'message' => 'Quiz-ul nu a fost găsit']);
        exit;
    }
    
    // Verifică accesul la quiz
    $has_access = false;
    
    if ($quiz['curs_id']) {
        // Quiz asociat unui curs - verifică înscrierea
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
        // Quiz independent - accesibil tuturor utilizatorilor logați
        $has_access = true;
    }
    
    // Obține întrebările cu răspunsurile
    $stmt = $pdo->prepare("
        SELECT iq.id, iq.intrebare, iq.tip, iq.punctaj, iq.explicatie, iq.ordine
        FROM intrebari_quiz iq
        WHERE iq.quiz_id = ? AND iq.activ = 1
        ORDER BY iq.ordine, iq.id
        LIMIT ?
    ");
    $stmt->execute([$quiz_id, $quiz['numar_intrebari']]);
    $intrebari_raw = $stmt->fetchAll();
    
    if (empty($intrebari_raw)) {
        echo json_encode(['success' => false, 'message' => 'Nu există întrebări pentru acest quiz']);
        exit;
    }
    
    // Pentru fiecare întrebare, obține răspunsurile
    $intrebari = [];
    foreach ($intrebari_raw as $intrebare) {
        $stmt = $pdo->prepare("
            SELECT id, raspuns, ordine
            FROM raspunsuri_quiz
            WHERE intrebare_id = ?
            ORDER BY ordine
        ");
        $stmt->execute([$intrebare['id']]);
        $raspunsuri = $stmt->fetchAll();
        
        // Nu include informația despre răspunsul corect în frontend
        $raspunsuri_clean = [];
        foreach ($raspunsuri as $raspuns) {
            $raspunsuri_clean[] = [
                'id' => $raspuns['id'],
                'raspuns' => $raspuns['raspuns'],
                'ordine' => $raspuns['ordine']
            ];
        }
        
        $intrebari[] = [
            'id' => $intrebare['id'],
            'intrebare' => $intrebare['intrebare'],
            'tip' => $intrebare['tip'],
            'punctaj' => $intrebare['punctaj'],
            'ordine' => $intrebare['ordine'],
            'raspunsuri' => $raspunsuri_clean
        ];
    }
    
    // Obține rezultatele anterioare
    $stmt = $pdo->prepare("
        SELECT procentaj, promovat, data_realizare, timp_completare
        FROM rezultate_quiz 
        WHERE user_id = ? AND quiz_id = ? 
        ORDER BY data_realizare DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id, $quiz_id]);
    $rezultate_anterioare = $stmt->fetchAll();
    
    // Formatează rezultatele pentru frontend
    $rezultate_formatted = [];
    foreach ($rezultate_anterioare as $rezultat) {
        $rezultate_formatted[] = [
            'procentaj' => round($rezultat['procentaj'], 1),
            'promovat' => $rezultat['promovat'],
            'data_realizare' => date('d.m.Y H:i', strtotime($rezultat['data_realizare'])),
            'timp_completare' => $rezultat['timp_completare']
        ];
    }
    
    // Returnează toate datele necesare
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
            'curs_titlu' => $quiz['curs_titlu'],
            'tip_quiz' => $quiz['tip_quiz']
        ],
        'intrebari' => $intrebari,
        'rezultate_anterioare' => $rezultate_formatted,
        'debug_info' => [
            'user_id' => $user_id,
            'quiz_id' => $quiz_id,
            'has_access' => $has_access,
            'total_questions' => count($intrebari)
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Error in get-quiz.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Eroare la încărcarea quiz-ului: ' . $e->getMessage()
    ]);
}
?>