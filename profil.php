<?php
require_once 'config.php';

// Verifică dacă utilizatorul este conectat
if (!isLoggedIn()) {
    redirectTo('login.php');
}

$page_title = 'Profilul Meu - ' . SITE_NAME;
$current_user = getCurrentUser();

// Procesează modificările de profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Token CSRF invalid.';
        redirectTo('profil.php');
    }
    
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'update_profile':
                if (isAdmin()) {
                    throw new Exception('Funcționalitatea de modificare profil nu este disponibilă pentru administratori.');
                }
                
                $nume = sanitizeInput($_POST['nume']);
                $telefon = sanitizeInput($_POST['telefon']);
                $data_nasterii = $_POST['data_nasterii'] ? $_POST['data_nasterii'] : null;
                
                if (empty($nume)) {
                    throw new Exception('Numele este obligatoriu.');
                }
                
                $stmt = $pdo->prepare("UPDATE users SET nume = ?, telefon = ?, data_nasterii = ?, data_actualizare = NOW() WHERE id = ?");
                $stmt->execute([$nume, $telefon, $data_nasterii, $_SESSION['user_id']]);
                
                $_SESSION['user_name'] = $nume;
                $_SESSION['success_message'] = 'Profilul a fost actualizat cu succes!';
                redirectTo('profil.php');
                break;
                
            case 'upload_avatar':
                if (isAdmin()) {
                    throw new Exception('Funcționalitatea de avatar nu este disponibilă pentru administratori.');
                }
                
                if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('Eroare la încărcarea fișierului.');
                }
                
                $file = $_FILES['avatar'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                // Verifică tipul de fișier
                if (!in_array($file['type'], $allowedTypes)) {
                    throw new Exception('Tipul de fișier nu este permis. Folosește JPG, PNG, GIF sau WebP.');
                }
                
                // Verifică dimensiunea
                if ($file['size'] > $maxSize) {
                    throw new Exception('Fișierul este prea mare. Dimensiunea maximă este 5MB.');
                }
                
                // Verifică dacă este imagine
                $imageInfo = getimagesize($file['tmp_name']);
                if (!$imageInfo) {
                    throw new Exception('Fișierul nu este o imagine validă.');
                }
                
                // Creează directorul dacă nu există
                $uploadDir = 'uploads/avatare/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generează nume unic pentru fișier
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $fileName = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
                $uploadPath = $uploadDir . $fileName;
                
                // Șterge avatarul vechi dacă există
                if (!empty($current_user['avatar']) && file_exists($current_user['avatar'])) {
                    unlink($current_user['avatar']);
                }
                
                // Mută fișierul în directorul final
                if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    throw new Exception('Eroare la salvarea fișierului.');
                }
                
                // Actualizează baza de date
                $stmt = $pdo->prepare("UPDATE users SET avatar = ?, data_actualizare = NOW() WHERE id = ?");
                $stmt->execute([$uploadPath, $_SESSION['user_id']]);
                
                $_SESSION['success_message'] = 'Avatarul a fost actualizat cu succes!';
                redirectTo('profil.php');
                break;
                
            case 'remove_avatar':
                if (isAdmin()) {
                    throw new Exception('Funcționalitatea de avatar nu este disponibilă pentru administratori.');
                }
                
                // Șterge fișierul avatar dacă există
                if (!empty($current_user['avatar']) && file_exists($current_user['avatar'])) {
                    unlink($current_user['avatar']);
                }
                
                // Actualizează baza de date
                $stmt = $pdo->prepare("UPDATE users SET avatar = NULL, data_actualizare = NOW() WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                $_SESSION['success_message'] = 'Avatarul a fost șters cu succes!';
                redirectTo('profil.php');
                break;
                
            case 'change_password':
                if (isAdmin()) {
                    throw new Exception('Funcționalitatea de schimbare parolă nu este disponibilă pentru administratori.');
                }
                
                $parola_veche = $_POST['parola_veche'];
                $parola_noua = $_POST['parola_noua'];
                $confirma_parola = $_POST['confirma_parola'];
                
                if (!password_verify($parola_veche, $current_user['parola'])) {
                    throw new Exception('Parola veche nu este corectă.');
                }
                
                if (strlen($parola_noua) < 8) {
                    throw new Exception('Parola nouă trebuie să aibă cel puțin 8 caractere.');
                }
                
                if ($parola_noua !== $confirma_parola) {
                    throw new Exception('Confirmarea parolei nu coincide.');
                }
                
                $parola_hash = password_hash($parola_noua, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET parola = ?, data_actualizare = NOW() WHERE id = ?");
                $stmt->execute([$parola_hash, $_SESSION['user_id']]);
                
                $_SESSION['success_message'] = 'Parola a fost schimbată cu succes!';
                redirectTo('profil.php');
                break;
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        redirectTo('profil.php');
    }
}

$current_user = getCurrentUser();
$user_id = $_SESSION['user_id'];

// Inițializează variabilele
$user_stats = [];
$purchased_courses = [];
$user_activity = [];
$admin_stats = [];
$realizari = [];
$articole_salvate = []; // Adăugat pentru articolele salvate

try {
    if (isAdmin()) {
        // Statistici simple pentru admin
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_utilizatori FROM users WHERE rol != 'admin'");
        $stmt->execute();
        $admin_stats = $stmt->fetch();
        
    } else {
        // Statistici pentru utilizatori - FOLOSIND ACELAȘI CALCUL CA ÎN PROGRES.PHP
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT ic.curs_id) as cursuri_inscrise,
                COUNT(DISTINCT CASE WHEN rq.promovat = 1 THEN rq.quiz_id END) as quiz_promovate,
                COUNT(DISTINCT rq.quiz_id) as quiz_incercate
            FROM inscrieri_cursuri ic
            LEFT JOIN quiz_uri q ON ic.curs_id = q.curs_id
            LEFT JOIN rezultate_quiz rq ON q.id = rq.quiz_id AND rq.user_id = ?
            WHERE ic.user_id = ?
        ");
        $stmt->execute([$user_id, $user_id]);
        $statistici_basic = $stmt->fetch();

        // Progres pe cursuri cu sistemul nou (IDENTIC CU PROGRES.PHP)
        $stmt = $pdo->prepare("
            SELECT 
                c.id,
                c.titlu,
                c.descriere,
                c.pret,
                c.imagine,
                ic.data_inscriere,
                -- Contorizează quiz-urile
                (SELECT COUNT(*) FROM quiz_uri WHERE curs_id = c.id AND activ = 1) as total_quiz,
                (SELECT COUNT(DISTINCT rq.quiz_id) FROM rezultate_quiz rq 
                 INNER JOIN quiz_uri q ON rq.quiz_id = q.id 
                 WHERE q.curs_id = c.id AND rq.user_id = ? AND rq.promovat = 1) as quiz_completate,
                
                -- Contorizează video-urile
                (SELECT COUNT(*) FROM video_cursuri WHERE curs_id = c.id AND activ = 1) as total_videos,
                (SELECT COUNT(*) FROM progres_video pv 
                 INNER JOIN video_cursuri vc ON pv.video_id = vc.id 
                 WHERE vc.curs_id = c.id AND pv.user_id = ? AND pv.completat = 1) as videos_completate,
                
                -- Contorizează exercițiile
                (SELECT COUNT(*) FROM exercitii_cursuri WHERE curs_id = c.id AND activ = 1) as total_exercitii,
                (SELECT COUNT(*) FROM progres_exercitii pe 
                 INNER JOIN exercitii_cursuri ec ON pe.exercitiu_id = ec.id 
                 WHERE ec.curs_id = c.id AND pe.user_id = ? AND pe.completat = 1) as exercitii_completate,
                
                -- Media quiz-urilor
                (SELECT AVG(rq.procentaj) FROM rezultate_quiz rq 
                 INNER JOIN quiz_uri q ON rq.quiz_id = q.id 
                 WHERE q.curs_id = c.id AND rq.user_id = ? AND rq.promovat = 1) as media_quiz,
                
                MAX(rq.data_realizare) as ultima_activitate
            FROM inscrieri_cursuri ic
            INNER JOIN cursuri c ON ic.curs_id = c.id
            LEFT JOIN quiz_uri q ON c.id = q.curs_id AND q.activ = 1
            LEFT JOIN rezultate_quiz rq ON q.id = rq.quiz_id AND rq.user_id = ?
            WHERE ic.user_id = ? AND c.activ = 1
            GROUP BY c.id
            ORDER BY ic.data_inscriere DESC
        ");
        $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
        $cursuri_raw = $stmt->fetchAll();

        // Calculează progresul real pentru fiecare curs (IDENTIC CU PROGRES.PHP)
        $purchased_courses = [];
        $total_progres_real = 0;
        $cursuri_cu_progres = 0;
        $cursuri_finalizate = 0;

        foreach ($cursuri_raw as $curs) {
            // Calculează progresul real pe baza activităților completate
            $total_activitati = $curs['total_quiz'] + $curs['total_videos'] + $curs['total_exercitii'];
            $activitati_completate = $curs['quiz_completate'] + $curs['videos_completate'] + $curs['exercitii_completate'];
            
            $progres_real = $total_activitati > 0 ? ($activitati_completate / $total_activitati) * 100 : 0;
            $progres_real = round($progres_real, 1);
            
            // Adaugă progresul real la curs
            $curs['progres_real'] = $progres_real;
            $curs['total_activitati'] = $total_activitati;
            $curs['activitati_completate'] = $activitati_completate;
            $curs['total_lectii'] = $total_activitati;
            $curs['lectii_vizionate'] = $activitati_completate;
            $curs['finalizat'] = $progres_real >= 100;
            
            $purchased_courses[] = $curs;
            
            // Calculează pentru statistici
            if ($progres_real >= 100) {
                $cursuri_finalizate++;
            }
            
            if ($progres_real > 0) {
                $total_progres_real += $progres_real;
                $cursuri_cu_progres++;
            }
        }

        // Calculează media generală reală
        $media_generala_reala = $cursuri_cu_progres > 0 ? $total_progres_real / $cursuri_cu_progres : 0;
        $media_generala_reala = round($media_generala_reala, 1);
        
        // Activitatea recentă
        $stmt = $pdo->prepare("
            SELECT 
                rq.id,
                rq.procentaj,
                rq.promovat,
                q.titlu as quiz_titlu,
                c.titlu as curs_titlu,
                rq.data_realizare as data_activitate,
                'quiz' as tip
            FROM rezultate_quiz rq
            INNER JOIN quiz_uri q ON rq.quiz_id = q.id
            LEFT JOIN cursuri c ON q.curs_id = c.id
            WHERE rq.user_id = ?
            ORDER BY rq.data_realizare DESC
            LIMIT 10
        ");
        $stmt->execute([$user_id]);
        $user_activity = $stmt->fetchAll();

        // ARTICOLE SALVATE - CORECTARE COMPLETĂ
        try {
            $stmt = $pdo->prepare("
                SELECT a.id, a.titlu, a.continut_scurt, a.imagine, a.data_publicare, asa.data_salvare
                FROM articole_salvate asa
                INNER JOIN articole a ON asa.articol_id = a.id
                WHERE asa.user_id = ? AND a.activ = 1
                ORDER BY asa.data_salvare DESC
                LIMIT 10
            ");
            $stmt->execute([$user_id]);
            $articole_salvate = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Eroare la obținerea articolelor salvate: ' . $e->getMessage());
            $articole_salvate = [];
        }

        // Realizări
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN rq.procentaj = 100 THEN 1 END) as quiz_perfecte,
                COUNT(CASE WHEN rq.procentaj >= 90 THEN 1 END) as quiz_excelente
            FROM rezultate_quiz rq
            WHERE rq.user_id = ? AND rq.promovat = 1
        ");
        $stmt->execute([$user_id]);
        $realizari = $stmt->fetch() ?: ['quiz_perfecte' => 0, 'quiz_excelente' => 0];

        // Numărul de articole salvate
        $articole_salvate_count = count($articole_salvate);
        
        // Statistici finale cu progresul REAL
        $user_stats = [
            'cursuri_inscrise' => $statistici_basic['cursuri_inscrise'] ?? 0,
            'quiz_promovate' => $statistici_basic['quiz_promovate'] ?? 0,
            'quiz_incercate' => $statistici_basic['quiz_incercate'] ?? 0,
            'media_quiz' => $media_generala_reala,
            'valoare_cursuri' => array_sum(array_column($purchased_courses, 'pret')),
            'articole_salvate' => $articole_salvate_count,
            'cursuri_finalizate' => $cursuri_finalizate
        ];
    }

} catch (PDOException $e) {
    error_log('Eroare în profil.php: ' . $e->getMessage());
    // În caz de eroare
    $user_stats = [
        'cursuri_inscrise' => 0,
        'quiz_promovate' => 0,
        'quiz_incercate' => 0,
        'media_quiz' => null,
        'valoare_cursuri' => 0,
        'articole_salvate' => 0,
        'cursuri_finalizate' => 0
    ];
    $purchased_courses = [];
    $user_activity = [];
    $admin_stats = ['total_utilizatori' => 0];
    $realizari = ['quiz_perfecte' => 0, 'quiz_excelente' => 0];
    $articole_salvate = [];
}

// Funcții helper
function formatRomanianMonth($date) {
    $months = [
        'Jan' => 'Ian', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
        'May' => 'Mai', 'Jun' => 'Iun', 'Jul' => 'Iul', 'Aug' => 'Aug',
        'Sep' => 'Sep', 'Oct' => 'Oct', 'Nov' => 'Noi', 'Dec' => 'Dec'
    ];
    
    $formatted = date('M Y', strtotime($date));
    $month = substr($formatted, 0, 3);
    $year = substr($formatted, 4);
    
    return ($months[$month] ?? $month) . ' ' . $year;
}

function formatTimestamp($timestamp) {
    if (!$timestamp) return 'N/A';
    
    $now = time();
    $time = strtotime($timestamp);
    $diff = $now - $time;
    
    if ($diff < 60) return 'Acum ' . $diff . ' secunde';
    if ($diff < 3600) return 'Acum ' . floor($diff/60) . ' minute';
    if ($diff < 86400) return 'Acum ' . floor($diff/3600) . ' ore';
    if ($diff < 2592000) return 'Acum ' . floor($diff/86400) . ' zile';
    
    return date('d.m.Y H:i', $time);
}

include 'components/header.php';
?>

<style>
.profile-page {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

/* Ajustări pentru layout-ul pe două coloane */
.profile-main-content {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
}

.profile-primary-column {
    flex: 1;
    min-width: 0; /* Previne overflow */
}

.profile-secondary-column {
    width: 350px; /* Lățime fixă pentru coloana dreaptă */
}

/* Ajustări pentru ecrane mici */
@media (max-width: 992px) {
    .profile-main-content {
        flex-direction: column;
    }
    
    .profile-secondary-column {
        width: 100%;
    }
}

.profile-hero {
    background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05));
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255,255,255,0.2);
    color: white;
}

.profile-avatar-section {
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 2rem;
}

.profile-avatar-placeholder {
    width: 120px;
    height: 120px;
    background: rgba(255,255,255,0.2);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: 700;
    border: 4px solid rgba(255,255,255,0.3);
    position: relative;
    overflow: hidden;
}

.profile-avatar-placeholder img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.profile-info h1 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.profile-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.profile-badge {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 500;
}

.section-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    border: 1px solid rgba(0,0,0,0.1);
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
}

.section-title i {
    margin-right: 0.75rem;
    color: #667eea;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    border: 1px solid #e9ecef;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #666;
    font-weight: 500;
    font-size: 0.9rem;
}

.nav-tabs {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 1.5rem;
}

.nav-tabs .nav-link {
    border: none;
    color: #666;
    font-weight: 500;
    padding: 1rem 1.5rem;
    position: relative;
}

.nav-tabs .nav-link.active {
    color: #667eea;
    background: none;
    border: none;
}

.nav-tabs .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: #667eea;
}

.course-item {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 15px;
    margin-bottom: 1rem;
    border-left: 4px solid #667eea;
    transition: all 0.3s ease;
}

.course-item:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.course-info {
    flex-grow: 1;
}

.course-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
}

.course-meta {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.progress-mini {
    width: 100%;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 0.5rem;
}

.progress-mini-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 0.8rem;
    color: #666;
    margin-top: 0.25rem;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 10px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.activity-item:hover {
    background: #e9ecef;
    transform: translateX(3px);
}

.activity-icon {
    width: 45px;
    height: 45px;
    background: #667eea;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
    font-size: 1.2rem;
}

.activity-icon.success {
    background: #28a745;
}

.activity-icon.warning {
    background: #ffc107;
    color: #333;
}

.activity-content {
    flex-grow: 1;
}

.activity-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
}

.activity-meta {
    font-size: 0.85rem;
    color: #666;
}

.badge-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.badge-item {
    text-align: center;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 15px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.badge-item.earned {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: #667eea;
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.badge-icon {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    display: block;
}

.badge-title {
    font-weight: 700;
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.badge-count {
    font-size: 0.9rem;
    opacity: 0.85;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #666;
}

.empty-state i {
    font-size: 3rem;
    color: #ddd;
    margin-bottom: 1rem;
}

.empty-state h5 {
    color: #999;
    margin-bottom: 0.5rem;
}

.form-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 1.5rem;
}

.form-section h6 {
    color: #333;
    font-weight: 600;
    margin-bottom: 1rem;
}

/* Stiluri pentru avatar upload */
.avatar-upload-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 1.5rem;
    text-align: center;
}

.current-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    margin: 0 auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9ecef;
    color: #666;
    font-size: 2rem;
    font-weight: 700;
    overflow: hidden;
}

.current-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.avatar-upload-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    flex-wrap: wrap;
}

.file-input-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
}

.file-input-wrapper input[type=file] {
    position: absolute;
    left: -9999px;
}

.file-input-label {
    cursor: pointer;
    display: inline-block;
    padding: 0.5rem 1rem;
    background: #667eea;
    color: white;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: background 0.3s ease;
}

.file-input-label:hover {
    background: #5a6fd8;
}

/* Stiluri pentru articolele salvate */
.saved-article-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 10px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    border-left: 3px solid #667eea;
}

.saved-article-item:hover {
    background: #e9ecef;
    transform: translateX(3px);
}

.saved-article-content {
    flex-grow: 1;
}

.saved-article-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
    font-size: 1rem;
}

.saved-article-meta {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.saved-article-excerpt {
    font-size: 0.8rem;
    color: #888;
    line-height: 1.4;
}

@media (max-width: 768px) {
    .profile-page {
        padding: 1rem;
    }
    
    .profile-hero {
        padding: 1.5rem;
    }
    
    .profile-avatar-section {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .section-card {
        padding: 1.5rem;
    }
    
    .avatar-upload-buttons {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<div class="profile-page">
    <div class="container">
        <!-- Profile Hero -->
        <div class="profile-hero">
            <div class="profile-avatar-section">
                <div class="profile-avatar-placeholder">
                    <?php if (!empty($current_user['avatar']) && file_exists($current_user['avatar'])): ?>
                        <img src="<?= htmlspecialchars($current_user['avatar']) ?>" alt="Avatar">
                    <?php else: ?>
                        <?= strtoupper(substr($current_user['nume'], 0, 2)) ?>
                    <?php endif; ?>
                </div>
                
                <div class="profile-info">
                    <h1><?= htmlspecialchars($current_user['nume']) ?></h1>
                    <p class="mb-2"><?= htmlspecialchars($current_user['email']) ?></p>
                    <div class="profile-badges">
                        <?php if (isAdmin()): ?>
                            <span class="profile-badge">
                                <i class="fas fa-crown me-1"></i>Administrator
                            </span>
                        <?php else: ?>
                            <span class="profile-badge">
                                <i class="fas fa-user me-1"></i>Utilizator
                            </span>
                        <?php endif; ?>
                        <span class="profile-badge">
                            <i class="fas fa-calendar me-1"></i>
                            Membru din <?= formatRomanianMonth($current_user['data_inregistrare']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?= displaySessionMessages() ?>

        <div class="row">
            <?php if (isAdmin()): ?>
                <!-- Layout pentru admini -->
                <div class="col-12">
                    <div class="section-card">
                        <h2 class="section-title">
                            <i class="fas fa-crown"></i>
                            Statistici Administrator
                        </h2>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-value"><?= $admin_stats['total_utilizatori'] ?? 0 ?></div>
                                <div class="stat-label">Utilizatori Activi</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Layout pentru utilizatori normali -->
                <div class="col-lg-8">
                    <!-- Statistici -->
                    <div class="section-card">
                        <h2 class="section-title">
                            <i class="fas fa-chart-bar"></i>
                            Statisticile Mele
                        </h2>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-value"><?= $user_stats['cursuri_inscrise'] ?? 0 ?></div>
                                <div class="stat-label">Cursuri Înscrise</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?= $user_stats['quiz_promovate'] ?? 0 ?></div>
                                <div class="stat-label">Quiz-uri Promovate</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?= $user_stats['media_quiz'] ? number_format($user_stats['media_quiz'], 1) . '%' : 'N/A' ?></div>
                                <div class="stat-label">Media Progres Real</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?= $user_stats['articole_salvate'] ?? 0 ?></div>
                                <div class="stat-label">Articole Salvate</div>
                            </div>
                        </div>
                    </div>

                    <div class="section-card">
                        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="courses-tab" data-bs-toggle="tab" data-bs-target="#courses" type="button" role="tab" aria-controls="courses" aria-selected="true">
                                    <i class="fas fa-graduation-cap me-2"></i>Cursurile Mele
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab" aria-controls="activity" aria-selected="false">
                                    <i class="fas fa-history me-2"></i>Activitate
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="achievements-tab" data-bs-toggle="tab" data-bs-target="#achievements" type="button" role="tab" aria-controls="achievements" aria-selected="false">
                                    <i class="fas fa-medal me-2"></i>Realizări
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="saved-tab" data-bs-toggle="tab" data-bs-target="#saved" type="button" role="tab" aria-controls="saved" aria-selected="false">
                                    <i class="fas fa-bookmark me-2"></i>Articole Salvate (<?= count($articole_salvate) ?>)
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="profileTabsContent">
                            <!-- Tab 1: Cursurile Mele -->
                            <div class="tab-pane fade show active" id="courses" role="tabpanel" aria-labelledby="courses-tab">
                                <h5 class="mb-3">Cursurile Mele</h5>
                                <?php if (!empty($purchased_courses)): ?>
                                    <?php foreach ($purchased_courses as $course): ?>
                                        <div class="course-item">
                                            <div class="course-info">
                                                <div class="course-title"><?= htmlspecialchars($course['titlu']) ?></div>
                                                <div class="course-meta">
                                                    Înscris pe <?= date('d.m.Y', strtotime($course['data_inscriere'])) ?>
                                                    • <?= formatPrice($course['pret']) ?>
                                                    • <?= $course['activitati_completate'] ?>/<?= $course['total_activitati'] ?> activități
                                                </div>
                                                
                                                <div class="progress-mini">
                                                    <div class="progress-mini-fill" style="width: <?= $course['progres_real'] ?>%"></div>
                                                </div>
                                                <div class="progress-text">
                                                    Progres Real: <?= number_format($course['progres_real'], 1) ?>%
                                                    <?php if ($course['progres_real'] >= 100): ?>
                                                        <span class="badge bg-success ms-2">Finalizat</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="ms-3">
                                                <a href="curs.php?id=<?= $course['id'] ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-play me-1"></i>Continuă
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-graduation-cap"></i>
                                        <h5>Nu ai cumpărat încă niciun curs</h5>
                                        <p>Explorează cursurile disponibile și începe să înveți astăzi!</p>
                                        <a href="cursuri.php" class="btn btn-primary mt-3">
                                            <i class="fas fa-search me-2"></i>Explorează Cursurile
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab 2: Activitate -->
                            <div class="tab-pane fade" id="activity" role="tabpanel" aria-labelledby="activity-tab">
                                <h5 class="mb-3">Activitatea Recentă</h5>
                                <?php if (!empty($user_activity)): ?>
                                    <?php foreach ($user_activity as $activity): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon <?= $activity['promovat'] ? 'success' : 'warning' ?>">
                                                <i class="fas fa-<?= $activity['promovat'] ? 'trophy' : 'times' ?>"></i>
                                            </div>
                                            <div class="activity-content">
                                                <div class="activity-title"><?= htmlspecialchars($activity['quiz_titlu']) ?></div>
                                                <div class="activity-meta">
                                                    <?= number_format($activity['procentaj'], 1) ?>% • 
                                                    <?= $activity['promovat'] ? 'Promovat' : 'Nepromovat' ?> • 
                                                    <?= formatTimestamp($activity['data_activitate']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-history"></i>
                                        <h5>Nicio activitate recentă</h5>
                                        <p>Începe să înveți pentru a-ți vedea activitatea aici.</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab 3: Realizări -->
                            <div class="tab-pane fade" id="achievements" role="tabpanel" aria-labelledby="achievements-tab">
                                <h5 class="mb-3">Realizările Mele</h5>
                                <div class="badge-container">
                                    <div class="badge-item <?= ($realizari['quiz_perfecte'] ?? 0) > 0 ? 'earned' : '' ?>">
                                        <div class="badge-icon">🏆</div>
                                        <div class="badge-title">Perfecționist</div>
                                        <div class="badge-count"><?= $realizari['quiz_perfecte'] ?? 0 ?> quiz-uri cu 100%</div>
                                    </div>
                                    
                                    <div class="badge-item <?= ($realizari['quiz_excelente'] ?? 0) >= 5 ? 'earned' : '' ?>">
                                        <div class="badge-icon">⭐</div>
                                        <div class="badge-title">Excelent</div>
                                        <div class="badge-count"><?= $realizari['quiz_excelente'] ?? 0 ?> quiz-uri cu 90%+</div>
                                    </div>
                                    
                                    <div class="badge-item <?= ($user_stats['cursuri_inscrise'] ?? 0) >= 3 ? 'earned' : '' ?>">
                                        <div class="badge-icon">📚</div>
                                        <div class="badge-title">Student Dedicat</div>
                                        <div class="badge-count"><?= $user_stats['cursuri_inscrise'] ?? 0 ?> cursuri înscrise</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 4: Articole Salvate -->
                            <div class="tab-pane fade" id="saved" role="tabpanel" aria-labelledby="saved-tab">
                                <h5 class="mb-3">Articole Salvate (<?= count($articole_salvate) ?>)</h5>
                                <?php if (!empty($articole_salvate)): ?>
                                    <?php foreach ($articole_salvate as $articol): ?>
                                        <div class="saved-article-item">
                                            <div class="saved-article-content">
                                                <div class="saved-article-title">
                                                    <a href="articol.php?id=<?= $articol['id'] ?>" class="text-decoration-none text-dark">
                                                        <?= htmlspecialchars($articol['titlu']) ?>
                                                    </a>
                                                </div>
                                                <div class="saved-article-meta">
                                                    <i class="fas fa-heart text-danger me-1"></i>
                                                    Salvat pe <?= date('d.m.Y', strtotime($articol['data_salvare'])) ?> • 
                                                    Publicat pe <?= date('d.m.Y', strtotime($articol['data_publicare'])) ?>
                                                </div>
                                                <?php if ($articol['continut_scurt']): ?>
                                                    <div class="saved-article-excerpt">
                                                        <?= htmlspecialchars(substr($articol['continut_scurt'], 0, 120)) ?>...
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ms-3">
                                                <a href="articol.php?id=<?= $articol['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-book-open me-1"></i>Citește
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="text-center mt-3">
                                        <a href="blog.php?salvate=1" class="btn btn-primary">
                                            <i class="fas fa-heart me-2"></i>Vezi Toate Articolele Salvate
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-bookmark"></i>
                                        <h5>Nu ai salvat încă niciun articol</h5>
                                        <p>Explorează articolele disponibile și salvează-le pentru mai târziu.</p>
                                        <a href="blog.php" class="btn btn-primary mt-3">
                                            <i class="fas fa-search me-2"></i>Explorează Articolele
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coloana dreapta - Setări profil -->
                <div class="col-lg-4">
                    <!-- Upload Avatar -->
                    <div class="section-card">
                        <h3 class="section-title">
                            <i class="fas fa-camera"></i>
                            Avatar Profil
                        </h3>
                        
                        <div class="avatar-upload-section">
                            <div class="current-avatar">
                                <?php if (!empty($current_user['avatar']) && file_exists($current_user['avatar'])): ?>
                                    <img src="<?= htmlspecialchars($current_user['avatar']) ?>" alt="Avatar">
                                <?php else: ?>
                                    <?= strtoupper(substr($current_user['nume'], 0, 2)) ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="avatar-upload-buttons">
                                <form method="POST" enctype="multipart/form-data" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="upload_avatar">
                                    <div class="file-input-wrapper">
                                        <input type="file" name="avatar" id="avatar-upload" accept="image/*" onchange="this.form.submit()">
                                        <label for="avatar-upload" class="file-input-label">
                                            <i class="fas fa-upload me-1"></i>Schimbă Avatar
                                        </label>
                                    </div>
                                </form>
                                
                                <?php if (!empty($current_user['avatar'])): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="remove_avatar">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Ești sigur că vrei să ștergi avatarul?')">
                                            <i class="fas fa-trash me-1"></i>Șterge
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            
                            <small class="text-muted mt-2 d-block">
                                Formaterele acceptate: JPG, PNG, GIF, WebP<br>
                                Dimensiunea maximă: 5MB
                            </small>
                        </div>
                    </div>

                    <!-- Modificare Profil -->
                    <div class="section-card">
                        <h3 class="section-title">
                            <i class="fas fa-user-edit"></i>
                            Modificare Profil
                        </h3>
                        
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-section">
                                <h6>Informații Personale</h6>
                                <div class="mb-3">
                                    <label for="nume" class="form-label">Nume Complet</label>
                                    <input type="text" class="form-control" id="nume" name="nume" 
                                           value="<?= htmlspecialchars($current_user['nume']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="telefon" class="form-label">Telefon</label>
                                    <input type="tel" class="form-control" id="telefon" name="telefon" 
                                           value="<?= htmlspecialchars($current_user['telefon'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="data_nasterii" class="form-label">Data Nașterii</label>
                                    <input type="date" class="form-control" id="data_nasterii" name="data_nasterii" 
                                           value="<?= $current_user['data_nasterii'] ?? '' ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Salvează Modificările
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Schimbare Parolă -->
                    <div class="section-card">
                        <h3 class="section-title">
                            <i class="fas fa-lock"></i>
                            Schimbare Parolă
                        </h3>
                        
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-section">
                                <h6>Securitate</h6>
                                <div class="mb-3">
                                    <label for="parola_veche" class="form-label">Parola Veche</label>
                                    <input type="password" class="form-control" id="parola_veche" name="parola_veche" required>
                                </div>
                                <div class="mb-3">
                                    <label for="parola_noua" class="form-label">Parola Nouă</label>
                                    <input type="password" class="form-control" id="parola_noua" name="parola_noua" required>
                                    <small class="text-muted">Minim 8 caractere</small>
                                </div>
                                <div class="mb-3">
                                    <label for="confirma_parola" class="form-label">Confirmă Parola</label>
                                    <input type="password" class="form-control" id="confirma_parola" name="confirma_parola" required>
                                </div>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key me-2"></i>Schimbă Parola
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animatie la progress bars cu progresul real
    const progressBars = document.querySelectorAll('.progress-mini-fill');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 500);
    });
    
    // Preview avatar înainte de upload
    const avatarInput = document.getElementById('avatar-upload');
    const currentAvatar = document.querySelector('.current-avatar');
    
    if (avatarInput && currentAvatar) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Verifică tipul de fișier
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Tipul de fișier nu este valid. Folosește JPG, PNG, GIF sau WebP.');
                    this.value = '';
                    return;
                }
                
                // Verifică dimensiunea (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Fișierul este prea mare. Dimensiunea maximă este 5MB.');
                    this.value = '';
                    return;
                }
                
                // Preview imagine
                const reader = new FileReader();
                reader.onload = function(e) {
                    const existingImg = currentAvatar.querySelector('img');
                    if (existingImg) {
                        existingImg.src = e.target.result;
                    } else {
                        currentAvatar.innerHTML = '<img src="' + e.target.result + '" alt="Avatar Preview">';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Actualizare automată a progresului (opțional)
    setInterval(function() {
        // Aici poți adăuga cod pentru actualizarea automată dacă vrei
        console.log('Progres actualizat cu succes');
    }, 30000); // La fiecare 30 de secunde
});
</script>

<?php include 'components/footer.php'; ?>