<?php
require_once '../config.php';

header('Content-Type: application/json');

// Verifică dacă utilizatorul este conectat
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Trebuie să fii conectat pentru a salva articole.']);
    exit;
}

// Verifică method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Obține datele din request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['articol_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID articol lipsește']);
    exit;
}

$articol_id = (int)$input['articol_id'];
$user_id = $_SESSION['user_id'];

// Validare ID articol
if ($articol_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID articol invalid']);
    exit;
}

try {
    // Verifică dacă articolul există și este activ
    $stmt = $pdo->prepare("SELECT id, titlu FROM articole WHERE id = ? AND activ = 1");
    $stmt->execute([$articol_id]);
    $articol = $stmt->fetch();
    
    if (!$articol) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Articolul nu a fost găsit.']);
        exit;
    }
    
    // Verifică dacă articolul este deja salvat
    $stmt = $pdo->prepare("SELECT id FROM articole_salvate WHERE user_id = ? AND articol_id = ?");
    $stmt->execute([$user_id, $articol_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Articolul este deja salvat - îl șterge (unsave)
        $stmt = $pdo->prepare("DELETE FROM articole_salvate WHERE user_id = ? AND articol_id = ?");
        $stmt->execute([$user_id, $articol_id]);
        
        $action = 'removed';
        $message = 'Articolul "' . htmlspecialchars($articol['titlu']) . '" a fost eliminat din salvate.';
    } else {
        // Salvează articolul
        $stmt = $pdo->prepare("INSERT INTO articole_salvate (user_id, articol_id, data_salvare) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $articol_id]);
        
        $action = 'saved';
        $message = 'Articolul "' . htmlspecialchars($articol['titlu']) . '" a fost salvat cu succes!';
    }
    
    // Obține numărul total de articole salvate de utilizator
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM articole_salvate WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_saved = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'message' => $message,
        'articol_id' => $articol_id,
        'articol_titlu' => $articol['titlu'],
        'total_saved' => (int)$total_saved,
        'is_saved' => $action === 'saved'
    ]);
    
} catch (PDOException $e) {
    error_log('Error in save-article.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Eroare la salvarea articolului. Te rugăm să încerci din nou.'
    ]);
}
?>