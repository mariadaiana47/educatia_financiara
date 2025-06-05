<?php
require_once '../config.php';

// Setează header-ul pentru JSON
header('Content-Type: application/json');

// Verifică dacă utilizatorul este autentificat și admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Doar administratorii pot gestiona pin-urile!']);
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
    // Verifică dacă topicul există și obține statusul curent de pin
    $stmt = $pdo->prepare("SELECT titlu, pinned FROM topicuri_comunitate WHERE id = ? AND activ = 1");
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch();
    
    if (!$topic) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Topicul nu există!']);
        exit;
    }
    
    // Toggle pin status
    $newPinStatus = $topic['pinned'] ? 0 : 1;
    
    $stmt = $pdo->prepare("UPDATE topicuri_comunitate SET pinned = ?, data_actualizare = NOW() WHERE id = ?");
    $stmt->execute([$newPinStatus, $topic_id]);
    
    if ($stmt->rowCount() > 0) {
        $action = $newPinStatus ? 'pinned' : 'unpinned';
        $message = $newPinStatus ? 
                   'Topicul "' . sanitizeInput($topic['titlu']) . '" a fost fixat cu succes!' :
                   'Topicul "' . sanitizeInput($topic['titlu']) . '" a fost dezlipet cu succes!';
        
        echo json_encode([
            'success' => true,
            'action' => $action,
            'pinned' => (bool)$newPinStatus,
            'message' => $message,
            'topic_id' => $topic_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Nu s-a putut actualiza statusul topic-ului!']);
    }
    
} catch (PDOException $e) {
    error_log("Error in pin-topic.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A apărut o eroare la procesarea cererii!']);
}
?>