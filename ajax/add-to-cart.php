<?php
// ajax/add-to-cart.php - Versiunea reparată
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Funcție pentru debugging
function debug_log($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'debug.log');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodă invalidă', 'debug' => 'Method: ' . $_SERVER['REQUEST_METHOD']]);
    exit;
}

try {
    require_once '../config.php';
    
    // Debug toate datele primite
    debug_log('POST data: ' . print_r($_POST, true));
    debug_log('Raw input: ' . file_get_contents('php://input'));
    
    // Verifică autentificarea
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Trebuie să fii autentificat']);
        exit;
    }
    
    $courseId = null;
    
    // Încearcă să obții course_id din mai multe surse
    if (isset($_POST['course_id'])) {
        $courseId = (int)$_POST['course_id'];
        debug_log('Course ID din POST: ' . $courseId);
    } else {
        // Încearcă să citești din JSON
        $json_input = file_get_contents('php://input');
        if (!empty($json_input)) {
            $json_data = json_decode($json_input, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($json_data['course_id'])) {
                $courseId = (int)$json_data['course_id'];
                debug_log('Course ID din JSON: ' . $courseId);
            }
        }
        
        // Încearcă să obții din query string
        if (!$courseId && isset($_GET['course_id'])) {
            $courseId = (int)$_GET['course_id'];
            debug_log('Course ID din GET: ' . $courseId);
        }
    }
    
    if (!$courseId || $courseId <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'ID curs lipsă sau invalid',
            'debug' => [
                'post' => $_POST,
                'get' => $_GET,
                'raw_input' => file_get_contents('php://input'),
                'course_id' => $courseId
            ]
        ]);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    debug_log("Processing: User ID = $userId, Course ID = $courseId");
    
    // Verifică dacă cursul există și este activ
    $stmt = $pdo->prepare("SELECT id, titlu, pret FROM cursuri WHERE id = ? AND activ = 1");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();
    
    if (!$course) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cursul nu există sau nu este activ',
            'debug' => "Course ID: $courseId"
        ]);
        exit;
    }
    
    debug_log('Found course: ' . $course['titlu']);
    
    // Verifică dacă utilizatorul este deja înscris
    $stmt = $pdo->prepare("SELECT id FROM inscrieri_cursuri WHERE user_id = ? AND curs_id = ?");
    $stmt->execute([$userId, $courseId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ești deja înscris la acest curs']);
        exit;
    }
    
    // Verifică dacă cursul este deja în coș
    $stmt = $pdo->prepare("SELECT id FROM cos_cumparaturi WHERE user_id = ? AND curs_id = ?");
    $stmt->execute([$userId, $courseId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cursul este deja în coș']);
        exit;
    }
    
    // Adaugă cursul în coș
    $stmt = $pdo->prepare("INSERT INTO cos_cumparaturi (user_id, curs_id, data_adaugare) VALUES (?, ?, NOW())");
    $success = $stmt->execute([$userId, $courseId]);
    
    if (!$success) {
        echo json_encode([
            'success' => false, 
            'message' => 'Eroare la adăugarea în coș',
            'debug' => $stmt->errorInfo()
        ]);
        exit;
    }
    
    debug_log('Successfully added to cart');
    
    // Calculează noul număr de elemente din coș
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cos_cumparaturi WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cartCount = $stmt->fetch()['count'];
    
    debug_log("New cart count: $cartCount");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cursul "' . $course['titlu'] . '" a fost adăugat în coș!',
        'cart_count' => $cartCount
    ]);
    
} catch (PDOException $e) {
    debug_log('PDO Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Eroare la baza de date: ' . $e->getMessage()]);
} catch (Exception $e) {
    debug_log('General Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Eroare: ' . $e->getMessage()]);
}
?>