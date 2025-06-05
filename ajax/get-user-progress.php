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

$user_id = $_SESSION['user_id'];

try {
    // Obține toate cursurile utilizatorului cu progres real-time
    $stmt = $pdo->prepare("
        SELECT c.id, c.titlu,
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
               
               -- Ultima activitate pentru fiecare curs
               (SELECT MAX(rq.data_realizare) FROM rezultate_quiz rq 
                INNER JOIN quiz_uri q ON rq.quiz_id = q.id 
                WHERE q.curs_id = c.id AND rq.user_id = ?) as ultima_activitate
        FROM inscrieri_cursuri ic
        JOIN cursuri c ON ic.curs_id = c.id
        WHERE ic.user_id = ? AND c.activ = 1
        ORDER BY ic.data_inscriere DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
    $cursuri = $stmt->fetchAll();
    
    // Statistici pentru quiz-uri
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT CASE WHEN rq.promovat = 1 THEN rq.quiz_id END) as quiz_promovate,
            COUNT(DISTINCT rq.quiz_id) as quiz_incercate
        FROM inscrieri_cursuri ic
        LEFT JOIN quiz_uri q ON ic.curs_id = q.curs_id
        LEFT JOIN rezultate_quiz rq ON q.id = rq.quiz_id AND rq.user_id = ?
        WHERE ic.user_id = ?
    ");
    $stmt->execute([$user_id, $user_id]);
    $quiz_stats = $stmt->fetch();
    
    // Activitate recentă
    $stmt = $pdo->prepare("
        SELECT 
            rq.*,
            q.titlu as quiz_titlu,
            c.titlu as curs_titlu
        FROM rezultate_quiz rq
        INNER JOIN quiz_uri q ON rq.quiz_id = q.id
        LEFT JOIN cursuri c ON q.curs_id = c.id
        WHERE rq.user_id = ?
        ORDER BY rq.data_realizare DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $activitate_recenta = $stmt->fetchAll();
    
    // Calculează progresul real pentru fiecare curs
    $cursuri_cu_progres = [];
    $total_progres = 0;
    $cursuri_cu_activitate = 0;
    $cursuri_finalizate = 0;
    
    foreach ($cursuri as $curs) {
        // Calculează progresul real pe baza activităților completate
        $total_activitati = $curs['total_quiz'] + $curs['total_videos'] + $curs['total_exercitii'];
        $activitati_completate = $curs['quiz_completate'] + $curs['videos_completate'] + $curs['exercitii_completate'];
        
        $progres_real = $total_activitati > 0 ? ($activitati_completate / $total_activitati) * 100 : 0;
        $progres_real = round($progres_real, 1);
        
        $cursuri_cu_progres[] = [
            'id' => $curs['id'],
            'titlu' => $curs['titlu'],
            'progres_real' => $progres_real,
            'total_quiz' => $curs['total_quiz'],
            'quiz_completate' => $curs['quiz_completate'],
            'total_videos' => $curs['total_videos'],
            'videos_completate' => $curs['videos_completate'],
            'total_exercitii' => $curs['total_exercitii'],
            'exercitii_completate' => $curs['exercitii_completate'],
            'total_activitati' => $total_activitati,
            'activitati_completate' => $activitati_completate,
            'ultima_activitate' => $curs['ultima_activitate']
        ];
        
        // Calculează pentru statistici
        if ($progres_real >= 100) {
            $cursuri_finalizate++;
        }
        
        if ($progres_real > 0) {
            $total_progres += $progres_real;
            $cursuri_cu_activitate++;
        }
    }
    
    // Statistici generale
    $total_cursuri = count($cursuri);
    $progres_mediu = $cursuri_cu_activitate > 0 ? $total_progres / $cursuri_cu_activitate : 0;
    $progres_mediu = round($progres_mediu, 1);
    
    // Returnează toate datele pentru actualizarea paginilor
    echo json_encode([
        'success' => true,
        'stats' => [
            'cursuri_inscrise' => $total_cursuri,
            'quiz_promovate' => $quiz_stats['quiz_promovate'] ?? 0,
            'quiz_incercate' => $quiz_stats['quiz_incercate'] ?? 0,
            'media_generale' => $progres_mediu,
            'cursuri_finalizate' => $cursuri_finalizate,
            'cursuri_cu_activitate' => $cursuri_cu_activitate,
            'total_cursuri' => $total_cursuri,
            'progres_mediu' => $progres_mediu
        ],
        'courses' => $cursuri_cu_progres,
        'recent_activity' => array_map(function($activity) {
            return [
                'quiz_titlu' => $activity['quiz_titlu'],
                'curs_titlu' => $activity['curs_titlu'],
                'procentaj' => $activity['procentaj'],
                'promovat' => $activity['promovat'],
                'data_realizare' => $activity['data_realizare']
            ];
        }, $activitate_recenta),
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    error_log("Database error in get-user-progress.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Eroare de bază de date',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in get-user-progress.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'A apărut o eroare neașteptată'
    ]);
}
?>