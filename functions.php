<?php
// Adaugă aceste funcții în includes/functions.php dacă nu există

/**
 * Generează token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifică dacă utilizatorul este autentificat
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verifică dacă utilizatorul este admin
 */
function isAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

/**
 * Obține numărul de elemente din coș
 */
function getCartCount($userId = null) {
    global $pdo;
    
    if (!$userId && isLoggedIn()) {
        $userId = $_SESSION['user_id'];
    }
    
    if (!$userId) {
        return 0;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM cos_cumparaturi cc
            JOIN cursuri c ON cc.curs_id = c.id
            WHERE cc.user_id = ? AND c.activ = 1
        ");
        $stmt->execute([$userId]);
        
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
        
    } catch (PDOException $e) {
        error_log("Error getting cart count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Formatează prețul
 */
function formatPrice($price) {
    return number_format($price, 2, ',', '.') . ' RON';
}

/**
 * Sanitizează input-ul
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Truncate text
 */
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Obține utilizator după ID
 */
function getUserById($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM utilizatori WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting user: " . $e->getMessage());
        return false;
    }
}
?>