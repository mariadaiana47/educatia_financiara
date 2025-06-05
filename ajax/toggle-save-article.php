<?php
require_once '../config.php';

header('Content-Type: application/json');

// Verifică dacă utilizatorul este conectat
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Trebuie să fii conectat.']);
    exit;
}

// Verifică method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Obține datele din request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['article_ids']) || !is_array($input['article_ids'])) {
    echo json_encode(['success' => false, 'message' => 'Date invalide']);
    exit;
}

$article_ids = array_filter(array_map('intval', $input['article_ids']));
$user_id = $_SESSION['user_id'];

if (empty($article_ids)) {
    echo json_encode([
        'success' => true,
        'saved_articles' => []
    ]);
    exit;
}

try {
    // Creează placeholders pentru query
    $placeholders = implode(',', array_fill(0, count($article_ids), '?'));
    
    // Verifică care articole sunt salvate de utilizator
    $params = array_merge([$user_id], $article_ids);
    
    $stmt = $pdo->prepare("
        SELECT articol_id 
        FROM articole_salvate 
        WHERE user_id = ? AND articol_id IN ($placeholders)
    ");
    $stmt->execute($params);
    
    $saved_articles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Convertește la numere întregi
    $saved_articles = array_map('intval', $saved_articles);
    
    echo json_encode([
        'success' => true,
        'saved_articles' => $saved_articles
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Eroare la verificarea articolelor salvate.'
    ]);
}
?>