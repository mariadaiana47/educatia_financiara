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
    echo json_encode(['success' => false, 'message' => 'Metodă invalildă!']);
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
$user_id = $_SESSION['user_id'];

try {
    // Verifică dacă topicul există și este activ
    $stmt = $pdo->prepare("SELECT id FROM topicuri_comunitate WHERE id = ? AND activ = 1");
    $stmt->execute([$topic_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Topicul nu există!']);
        exit;
    }
    
    // Verifică dacă utilizatorul a dat deja like
    $stmt = $pdo->prepare("SELECT id FROM likes_topicuri WHERE user_id = ? AND topic_id = ?");
    $stmt->execute([$user_id, $topic_id]);
    $existingLike = $stmt->fetch();
    
    if ($existingLike) {
        // Elimină like-ul (unlike)
        $stmt = $pdo->prepare("DELETE FROM likes_topicuri WHERE user_id = ? AND topic_id = ?");
        $stmt->execute([$user_id, $topic_id]);
        $action = 'removed';
    } else {
        // Adaugă like
        $stmt = $pdo->prepare("INSERT INTO likes_topicuri (user_id, topic_id, data_like) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $topic_id]);
        $action = 'added';
    }
    
    // Obține numărul total de like-uri pentru topic
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_likes FROM likes_topicuri WHERE topic_id = ?");
    $stmt->execute([$topic_id]);
    $result = $stmt->fetch();
    $totalLikes = $result['total_likes'];
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'likes' => $totalLikes,
        'message' => $action === 'added' ? 'Like adăugat!' : 'Like eliminat!'
    ]);
    
} catch (PDOException $e) {
    error_log("Error in like-topic.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A apărut o eroare la procesarea cererii!']);
}
?>