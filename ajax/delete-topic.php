<?php
require_once '../config.php';

// Setează header-ul pentru JSON
header('Content-Type: application/json');

// Verifică dacă utilizatorul este autentificat și admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Doar administratorii pot șterge topic-uri!']);
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

if (!isset($input['topic_id']) || !is_numeric($input['topic_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID topic invalid!']);
    exit;
}

$topic_id = intval($input['topic_id']);

try {
    // Verifică dacă topicul există
    $stmt = $pdo->prepare("SELECT titlu FROM topicuri_comunitate WHERE id = ?");
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch();
    
    if (!$topic) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Topicul nu există!']);
        exit;
    }
    
    // Începe tranzacția pentru a șterge toate datele related
    $pdo->beginTransaction();
    
    try {
        // Șterge toate like-urile pentru acest topic
        $stmt = $pdo->prepare("DELETE FROM likes_topicuri WHERE topic_id = ?");
        $stmt->execute([$topic_id]);
        
        // Șterge toate comentariile pentru acest topic
        $stmt = $pdo->prepare("DELETE FROM comentarii_topicuri WHERE topic_id = ?");
        $stmt->execute([$topic_id]);
        
        // Șterge topicul
        $stmt = $pdo->prepare("DELETE FROM topicuri_comunitate WHERE id = ?");
        $stmt->execute([$topic_id]);
        
        // Commit tranzacția
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Topicul "' . sanitizeInput($topic['titlu']) . '" a fost șters cu succes!',
            'topic_id' => $topic_id
        ]);
        
    } catch (PDOException $e) {
        // Rollback în caz de eroare
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Error in delete-topic.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A apărut o eroare la ștergerea topicului!']);
}
?>