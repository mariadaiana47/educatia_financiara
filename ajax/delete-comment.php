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
    // Obține detaliile comentariului
    $stmt = $pdo->prepare("SELECT user_id, topic_id FROM comentarii_topicuri WHERE id = ? AND activ = 1");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();
    
    if (!$comment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Comentariul nu există!']);
        exit;
    }
    
    // Verifică permisiunile: proprietarul comentariului sau admin
    if ($comment['user_id'] != $user_id && !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Nu ai permisiunea să ștergi acest comentariu!']);
        exit;
    }
    
    // Șterge comentariul (soft delete)
    $stmt = $pdo->prepare("UPDATE comentarii_topicuri SET activ = 0, data_actualizare = NOW() WHERE id = ?");
    $stmt->execute([$comment_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Comentariul a fost șters cu succes!',
            'comment_id' => $comment_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Nu s-a putut șterge comentariul!']);
    }
    
} catch (PDOException $e) {
    error_log("Error in delete-comment.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A apărut o eroare la procesarea cererii!']);
}
?>