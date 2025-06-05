<?php
// ajax/add-to-cart.php
session_start();

// Ascunde erorile PHP pentru a nu strica JSON-ul
ini_set('display_errors', 0);
error_reporting(0);

// Setează header-ul pentru JSON ÎNAINTE de orice altceva
header('Content-Type: application/json');

try {
    // Path-uri corecte pentru structura ta - UN NIVEL SUS cu ../
    require_once '../config.php';
    require_once '../includes/functions.php';
    require_once '../includes/cart-functions.php';
    
    // Verifică dacă este o cerere POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Metodă invalidă']);
        exit;
    }
    
    // Verifică autentificarea
    if (!function_exists('isLoggedIn') || !isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Trebuie să fii autentificat', 'redirect' => 'login.php']);
        exit;
    }
    
    // Obține ID-ul cursului
    $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    
    if ($courseId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID curs invalid']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Verifică dacă funcția addToCart există
    if (!function_exists('addToCart')) {
        echo json_encode(['success' => false, 'message' => 'Funcția addToCart nu există']);
        exit;
    }
    
    // Adaugă cursul în coș
    $result = addToCart($userId, $courseId);
    
    // Adaugă și numărul actualizat de elemente din coș
    if ($result['success'] && function_exists('getCartCount')) {
        $result['cart_count'] = getCartCount($userId);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Eroare: ' . $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['success' => false, 'message' => 'Eroare fatală: ' . $e->getMessage()]);
}
?>