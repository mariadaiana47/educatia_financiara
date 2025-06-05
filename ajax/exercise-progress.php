<?php
require_once '../config.php';

// Verifică dacă este cerere AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cerere invalidă']);
    exit;
}

// Verifică autentificarea
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nu ești conectat']);
    exit;
}

// Verifică metoda POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metoda nu este permisă']);
    exit;
}

// Verifică token-ul CSRF
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalid']);
    exit;
}

$action = $_POST['action'] ?? '';
$exercise_id = (int)($_POST['exercise_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($exercise_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID exercițiu invalid']);
    exit;
}

try {
    // Verifică dacă utilizatorul are acces la exercițiu
    $stmt = $pdo->prepare("
        SELECT e.id, e.curs_id, ic.id as inscriere_id
        FROM exercitii_cursuri e
        INNER JOIN cursuri c ON e.curs_id = c.id
        INNER JOIN inscrieri_cursuri ic ON c.id = ic.curs_id AND ic.user_id = ?
        WHERE e.id = ? AND e.activ = 1 AND c.activ = 1
    ");
    $stmt->execute([$user_id, $exercise_id]);
    $exercise = $stmt->fetch();

    if (!$exercise) {
        echo json_encode(['success' => false, 'message' => 'Nu ai acces la acest exercițiu']);
        exit;
    }

    switch ($action) {
        case 'update_progress':
            $completed = (int)($_POST['completed'] ?? 0);
            
            // Verifică dacă există deja progres
            $stmt = $pdo->prepare("
                SELECT id FROM progres_exercitii 
                WHERE user_id = ? AND exercitiu_id = ?
            ");
            $stmt->execute([$user_id, $exercise_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Actualizează progresul existent
                $stmt = $pdo->prepare("
                    UPDATE progres_exercitii 
                    SET completat = ?, data_completare = ?
                    WHERE user_id = ? AND exercitiu_id = ?
                ");
                $data_completare = $completed ? date('Y-m-d H:i:s') : null;
                $stmt->execute([$completed, $data_completare, $user_id, $exercise_id]);
            } else {
                // Creează progres nou
                $stmt = $pdo->prepare("
                    INSERT INTO progres_exercitii (user_id, exercitiu_id, completat, data_completare)
                    VALUES (?, ?, ?, ?)
                ");
                $data_completare = $completed ? date('Y-m-d H:i:s') : null;
                $stmt->execute([$user_id, $exercise_id, $completed, $data_completare]);
            }

            echo json_encode([
                'success' => true,
                'message' => $completed ? 'Exercițiu marcat ca completat!' : 'Exercițiu marcat ca necompletat!'
            ]);
            break;

        case 'save_progress':
            $progress_data = $_POST['progress_data'] ?? '{}';
            
            // Validează JSON
            $decoded = json_decode($progress_data, true);
            if ($decoded === null) {
                echo json_encode(['success' => false, 'message' => 'Date de progres invalide']);
                exit;
            }

            // Verifică dacă există progres
            $stmt = $pdo->prepare("
                SELECT id FROM progres_exercitii 
                WHERE user_id = ? AND exercitiu_id = ?
            ");
            $stmt->execute([$user_id, $exercise_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Actualizează progresul cu datele salvate
                $stmt = $pdo->prepare("
                    UPDATE progres_exercitii 
                    SET data_progres = ?
                    WHERE user_id = ? AND exercitiu_id = ?
                ");
                $stmt->execute([$progress_data, $user_id, $exercise_id]);
            } else {
                // Creează progres nou cu datele
                $stmt = $pdo->prepare("
                    INSERT INTO progres_exercitii (user_id, exercitiu_id, completat, data_progres)
                    VALUES (?, ?, 0, ?)
                ");
                $stmt->execute([$user_id, $exercise_id, $progress_data]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Progresul a fost salvat!'
            ]);
            break;

        case 'get_progress':
            // Returnează progresul salvat
            $stmt = $pdo->prepare("
                SELECT completat, data_completare, data_progres
                FROM progres_exercitii 
                WHERE user_id = ? AND exercitiu_id = ?
            ");
            $stmt->execute([$user_id, $exercise_id]);
            $progress = $stmt->fetch();

            if ($progress) {
                $progress_data = $progress['data_progres'] ? json_decode($progress['data_progres'], true) : [];
                echo json_encode([
                    'success' => true,
                    'progress' => [
                        'completed' => (bool)$progress['completat'],
                        'completion_date' => $progress['data_completare'],
                        'data' => $progress_data
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'progress' => [
                        'completed' => false,
                        'completion_date' => null,
                        'data' => []
                    ]
                ]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acțiune nerecunoscută']);
            break;
    }

} catch (PDOException $e) {
    error_log("Database error in exercise-progress.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Eroare de bază de date']);
} catch (Exception $e) {
    error_log("General error in exercise-progress.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A apărut o eroare neașteptată']);
}
?>