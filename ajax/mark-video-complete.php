<?php
require_once '../config.php';

// Verifică dacă este cerere AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cerere invalidă']);
    exit;
}

// Verifică autentificarea
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nu ești conectat']);
    exit;
}

// Verifică metoda POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metoda nu este permisă']);
    exit;
}

$video_id = (int)($_POST['video_id'] ?? 0);
$course_id = (int)($_POST['course_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($video_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID video invalid']);
    exit;
}

try {
    // Verifică dacă utilizatorul are acces la acest video
    $stmt = $pdo->prepare("
        SELECT v.id, v.curs_id, v.durata_secunde, ic.id as inscriere_id
        FROM video_cursuri v
        INNER JOIN cursuri c ON v.curs_id = c.id
        INNER JOIN inscrieri_cursuri ic ON c.id = ic.curs_id AND ic.user_id = ?
        WHERE v.id = ? AND v.activ = 1 AND c.activ = 1
    ");
    $stmt->execute([$user_id, $video_id]);
    $video = $stmt->fetch();

    if (!$video) {
        echo json_encode(['success' => false, 'message' => 'Nu ai acces la acest video']);
        exit;
    }

    // Verifică dacă există deja progres pentru acest video
    $stmt = $pdo->prepare("
        SELECT id, completat, timp_vizionat 
        FROM progres_video 
        WHERE user_id = ? AND video_id = ?
    ");
    $stmt->execute([$user_id, $video_id]);
    $progres_existent = $stmt->fetch();

    if ($progres_existent) {
        // Actualizează progresul existent
        $stmt = $pdo->prepare("
            UPDATE progres_video 
            SET completat = 1, 
                data_completare = NOW(),
                timp_vizionat = ?,
                ultima_pozitie = ?
            WHERE user_id = ? AND video_id = ?
        ");
        $stmt->execute([
            $video['durata_secunde'], // Setează timp_vizionat la durata completă
            $video['durata_secunde'], // Setează ultima_pozitie la sfârșitul video-ului
            $user_id, 
            $video_id
        ]);
    } else {
        // Creează progres nou
        $stmt = $pdo->prepare("
            INSERT INTO progres_video (user_id, video_id, timp_vizionat, completat, data_completare, ultima_pozitie)
            VALUES (?, ?, ?, 1, NOW(), ?)
        ");
        $stmt->execute([
            $user_id, 
            $video_id, 
            $video['durata_secunde'],
            $video['durata_secunde']
        ]);
    }

    // Calculează progresul cursului actualizat
    $stmt = $pdo->prepare("
        SELECT 
            -- Contorizează quiz-urile
            (SELECT COUNT(*) FROM quiz_uri WHERE curs_id = ? AND activ = 1) as total_quiz,
            (SELECT COUNT(DISTINCT rq.quiz_id) FROM rezultate_quiz rq 
             INNER JOIN quiz_uri q ON rq.quiz_id = q.id 
             WHERE q.curs_id = ? AND rq.user_id = ? AND rq.promovat = 1) as quiz_completate,
            
            -- Contorizează video-urile
            (SELECT COUNT(*) FROM video_cursuri WHERE curs_id = ? AND activ = 1) as total_videos,
            (SELECT COUNT(*) FROM progres_video pv 
             INNER JOIN video_cursuri vc ON pv.video_id = vc.id 
             WHERE vc.curs_id = ? AND pv.user_id = ? AND pv.completat = 1) as videos_completate,
            
            -- Contorizează exercițiile
            (SELECT COUNT(*) FROM exercitii_cursuri WHERE curs_id = ? AND activ = 1) as total_exercitii,
            (SELECT COUNT(*) FROM progres_exercitii pe 
             INNER JOIN exercitii_cursuri ec ON pe.exercitiu_id = ec.id 
             WHERE ec.curs_id = ? AND pe.user_id = ? AND pe.completat = 1) as exercitii_completate
    ");
    $stmt->execute([
        $video['curs_id'], $video['curs_id'], $user_id,
        $video['curs_id'], $video['curs_id'], $user_id,
        $video['curs_id'], $video['curs_id'], $user_id
    ]);
    $progres_curs = $stmt->fetch();

    // Calculează progresul real
    $total_activitati = $progres_curs['total_quiz'] + $progres_curs['total_videos'] + $progres_curs['total_exercitii'];
    $activitati_completate = $progres_curs['quiz_completate'] + $progres_curs['videos_completate'] + $progres_curs['exercitii_completate'];
    
    $progres_real = $total_activitati > 0 ? ($activitati_completate / $total_activitati) * 100 : 0;
    $progres_real = round($progres_real, 1);

    // Actualizează progresul în tabela inscrieri_cursuri
    $finalizat = $progres_real >= 100 ? 1 : 0;
    $stmt = $pdo->prepare("
        UPDATE inscrieri_cursuri 
        SET progress = ?, finalizat = ?
        WHERE user_id = ? AND curs_id = ?
    ");
    $stmt->execute([$progres_real, $finalizat, $user_id, $video['curs_id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Video marcat ca finalizat cu succes!',
        'course_progress' => [
            'progres_real' => $progres_real,
            'total_quiz' => $progres_curs['total_quiz'],
            'quiz_completate' => $progres_curs['quiz_completate'],
            'total_videos' => $progres_curs['total_videos'],
            'videos_completate' => $progres_curs['videos_completate'],
            'total_exercitii' => $progres_curs['total_exercitii'],
            'exercitii_completate' => $progres_curs['exercitii_completate'],
            'finalizat' => $finalizat
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database error in mark-video-complete.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Eroare de bază de date']);
} catch (Exception $e) {
    error_log("General error in mark-video-complete.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A apărut o eroare neașteptată']);
}
?>