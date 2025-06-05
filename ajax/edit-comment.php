<?php
require_once '../config.php';

// Setează header-ul pentru JSON
header('Content-Type: application/json');

// Verifică dacă utilizatorul este autentificat
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Trebuie să fii autentificat!']);
    exit;
}

// Verifică metoda de request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodă invalidă!']);
    exit;
}

// Citește JSON din request
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['comment_id']) || !is_numeric($input['comment_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID comentariu invalid!']);
    exit;
}

$comment_id = intval($input['comment_id']);
$user_id = $_SESSION['user_id'];

try {
    if (isset($input['action']) && $input['action'] === 'get') {
        // Obține conținutul comentariului pentru editare
        $stmt = $pdo->prepare("SELECT continut, user_id FROM comentarii_topicuri WHERE id = ? AND activ = 1");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch();
        
        if (!$comment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Comentariul nu există!']);
            exit;
        }
        
        // Verifică dacă utilizatorul poate edita comentariul
        if ($comment['user_id'] != $user_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Nu poți edita acest comentariu!']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'content' => $comment['continut']
        ]);
        
    } elseif (isset($input['action']) && $input['action'] === 'save') {
        // Salvează comentariul editat
        if (!isset($input['content']) || empty(trim($input['content']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Conținutul comentariului nu poate fi gol!']);
            exit;
        }
        
        $newContent = sanitizeInput(trim($input['content']));
        
        if (strlen($newContent) < 5) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Comentariul trebuie să aibă minim 5 caractere!']);
            exit;
        }
        
        // Verifică dacă comentariul există și aparține utilizatorului
        $stmt = $pdo->prepare("SELECT user_id FROM comentarii_topicuri WHERE id = ? AND activ = 1");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch();
        
        if (!$comment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Comentariul nu există!']);
            exit;
        }
        
        if ($comment['user_id'] != $user_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Nu poți edita acest comentariu!']);
            exit;
        }
        
        // Actualizează comentariul
        $stmt = $pdo->prepare("UPDATE comentarii_topicuri SET continut = ?, data_actualizare = NOW() WHERE id = ?");
        $stmt->execute([$newContent, $comment_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Comentariul a fost actualizat cu succes!',
                'content' => nl2br(sanitizeInput($newContent))
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Nu s-a putut actualiza comentariul!']);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acțiune invalidă!']);
    }
    
} catch (PDOException $e) {
    error_log("Error in edit-comment.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A apărut o eroare la procesarea cererii!']);
}
?>