<?php
// Verifică dacă sesiunea nu este deja activă înainte de a porni o nouă sesiune
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'educatie_financiara');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_URL', 'http://localhost/educatia-financiara/');
define('SITE_NAME', 'Educația Financiară pentru Toți');
define('UPLOAD_PATH', 'uploads/');

define('HASH_COST', 10);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Eroare conectare bază de date: " . $e->getMessage());
}


function redirectTo($url) {
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = SITE_URL . $url;
    }
    
    if (!headers_sent()) {
        header("Location: " . $url);
        exit();
    } else {
        echo "<script>window.location.href='" . $url . "';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . $url . "'></noscript>";
        exit();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND activ = TRUE");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function sanitizeInput($input) {
    if ($input === null || $input === '') {
        return '';
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatPrice($price) {
    return number_format($price, 2, ',', '.') . ' RON';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Acum ' . $time . ' secunde';
    if ($time < 3600) return 'Acum ' . floor($time/60) . ' minute';
    if ($time < 86400) return 'Acum ' . floor($time/3600) . ' ore';
    if ($time < 2592000) return 'Acum ' . floor($time/86400) . ' zile';
    if ($time < 31536000) return 'Acum ' . floor($time/2592000) . ' luni';
    return 'Acum ' . floor($time/31536000) . ' ani';
}

function checkSession() {
    if (isLoggedIn()) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT id, rol FROM users WHERE id = ? AND activ = TRUE");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                session_destroy();
                return false;
            }
            
            $_SESSION['user_role'] = $user['rol'];
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    return false;
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getCartCount() {
    if (!isLoggedIn() || isAdmin()) return 0;
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cos_cumparaturi WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function getCartItems() {
    if (!isLoggedIn() || isAdmin()) return [];
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.user_id, c.curs_id, c.data_adaugare,
                   curs.titlu, curs.pret, curs.imagine, curs.descriere_scurta,
                   curs.durata_minute, curs.nivel, curs.descriere
            FROM cos_cumparaturi c
            JOIN cursuri curs ON c.curs_id = curs.id
            WHERE c.user_id = ? AND curs.activ = 1
            ORDER BY c.data_adaugare DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getCartTotal() {
    if (isAdmin()) return 0;
    
    $items = getCartItems();
    $total = 0;
    foreach ($items as $item) {
        $total += $item['pret'];
    }
    return $total;
}


function isEnrolledInCourse($user_id, $course_id) {
    if (!$user_id || !$course_id) return false;
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM inscrieri_cursuri WHERE user_id = ? AND curs_id = ?");
        $stmt->execute([$user_id, $course_id]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

function isInCart($user_id, $course_id) {
    if (!$user_id || !$course_id || isAdmin()) return false;
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM cos_cumparaturi WHERE user_id = ? AND curs_id = ?");
        $stmt->execute([$user_id, $course_id]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

function showMessage($type, $message) {
    $alertClass = '';
    $icon = '';
    
    switch ($type) {
        case 'success':
            $alertClass = 'alert-success';
            $icon = 'fas fa-check-circle';
            break;
        case 'error':
            $alertClass = 'alert-danger';
            $icon = 'fas fa-exclamation-circle';
            break;
        case 'warning':
            $alertClass = 'alert-warning';
            $icon = 'fas fa-exclamation-triangle';
            break;
        case 'info':
            $alertClass = 'alert-info';
            $icon = 'fas fa-info-circle';
            break;
        default:
            $alertClass = 'alert-primary';
            $icon = 'fas fa-info-circle';
    }
    
    return "
    <div class='alert {$alertClass} alert-dismissible fade show' role='alert'>
        <i class='{$icon} me-2'></i>{$message}
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";
}

function displaySessionMessages() {
    $output = '';
    
    if (isset($_SESSION['success_message'])) {
        $output .= showMessage('success', $_SESSION['success_message']);
        unset($_SESSION['success_message']);
    }
    
    if (isset($_SESSION['error_message'])) {
        $output .= showMessage('error', $_SESSION['error_message']);
        unset($_SESSION['error_message']);
    }
    
    if (isset($_SESSION['warning_message'])) {
        $output .= showMessage('warning', $_SESSION['warning_message']);
        unset($_SESSION['warning_message']);
    }
    
    if (isset($_SESSION['info_message'])) {
        $output .= showMessage('info', $_SESSION['info_message']);
        unset($_SESSION['info_message']);
    }
    
    return $output;
}

function truncateText($text, $limit = 150) {
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit) . '...';
}

function createSlug($text) {
    $replacements = [
        'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ț' => 't',
        'Ă' => 'A', 'Â' => 'A', 'Î' => 'I', 'Ș' => 'S', 'Ț' => 'T'
    ];
    
    $text = strtr($text, $replacements);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isStrongPassword($password) {
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

function logActivity($user_id, $action, $details = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (PDOException $e) {
        error_log("Eroare logging activitate: " . $e->getMessage());
    }
}

function updateCourseDuration($course_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT SUM(durata_secunde) as total_secunde
            FROM video_cursuri 
            WHERE curs_id = ? AND activ = 1
        ");
        $stmt->execute([$course_id]);
        $result = $stmt->fetch();
        
        $total_secunde = $result['total_secunde'] ?? 0;
        $total_minute = ceil($total_secunde / 60);
        
        $stmt = $pdo->prepare("
            UPDATE cursuri 
            SET durata_minute = ?, data_actualizare = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$total_minute, $course_id]);
        
        return $total_minute;
        
    } catch (PDOException $e) {
        error_log("Error updating course duration: " . $e->getMessage());
        return false;
    }
}

checkSession();

define('MSG_SUCCESS_REGISTER', 'Contul a fost creat cu succes! Te poți conecta acum.');
define('MSG_SUCCESS_LOGIN', 'Te-ai conectat cu succes!');
define('MSG_SUCCESS_LOGOUT', 'Te-ai deconectat cu succes!');
define('MSG_ERROR_LOGIN', 'Email sau parolă incorectă!');
define('MSG_ERROR_EMAIL_EXISTS', 'Există deja un cont cu această adresă de email!');
define('MSG_ERROR_WEAK_PASSWORD', 'Parola trebuie să aibă minimum 8 caractere, o literă mare, o literă mică și o cifră!');
define('MSG_ERROR_ACCESS_DENIED', 'Nu aveți permisiunea să accesați această pagină!');
define('MSG_SUCCESS_ADDED_TO_CART', 'Cursul a fost adăugat în coș!');
define('MSG_ERROR_ALREADY_IN_CART', 'Cursul este deja în coș!');
define('MSG_ERROR_ALREADY_ENROLLED', 'Ești deja înscris la acest curs!');
?>