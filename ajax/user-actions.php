<?php
require_once '../config.php';

// Verifică dacă este cerere AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cerere invalidă']);
    exit;
}

// Verifică autentificarea și permisiunile
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acces interzis']);
    exit;
}

// Verifică metoda POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metoda nu este permisă']);
    exit;
}

// Obține datele JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Date invalide']);
    exit;
}

// Verifică token-ul CSRF
if (!isset($input['csrf_token']) || !verifyCSRFToken($input['csrf_token'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalid']);
    exit;
}

$action = $input['action'] ?? '';
$user_id = (int)($input['user_id'] ?? 0);

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID utilizator invalid']);
    exit;
}

try {
    switch ($action) {
        case 'toggle_status':
            // Nu permite dezactivarea propriului cont
            if ($user_id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Nu poți dezactiva propriul cont']);
                exit;
            }

            // Obține statusul actual
            $stmt = $pdo->prepare("SELECT activ, nume FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Utilizatorul nu a fost găsit']);
                exit;
            }

            // Schimbă statusul
            $new_status = !$user['activ'];
            $stmt = $pdo->prepare("UPDATE users SET activ = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);

            $status_text = $new_status ? 'activat' : 'dezactivat';
            $badge_class = $new_status ? 'bg-success' : 'bg-danger';
            $badge_text = $new_status ? 'Activ' : 'Inactiv';
            $button_class = $new_status ? 'btn-outline-warning' : 'btn-outline-success';
            $button_icon = $new_status ? 'fa-ban' : 'fa-check';
            $button_title = $new_status ? 'Dezactivează' : 'Activează';

            echo json_encode([
                'success' => true,
                'message' => "Utilizatorul {$user['nume']} a fost {$status_text}",
                'new_status' => $new_status,
                'badge_html' => "<span class=\"badge {$badge_class}\">{$badge_text}</span>",
                'button_class' => $button_class,
                'button_icon' => $button_icon,
                'button_title' => $button_title
            ]);
            break;

        case 'change_role':
            $new_role = $input['new_role'] ?? '';
            
            if (!in_array($new_role, ['user', 'admin'])) {
                echo json_encode(['success' => false, 'message' => 'Rol invalid']);
                exit;
            }

            // Nu permite schimbarea propriului rol
            if ($user_id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Nu poți schimba propriul rol']);
                exit;
            }

            // Obține datele utilizatorului
            $stmt = $pdo->prepare("SELECT nume, rol FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Utilizatorul nu a fost găsit']);
                exit;
            }

            // Actualizează rolul
            $stmt = $pdo->prepare("UPDATE users SET rol = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);

            $role_text = $new_role === 'admin' ? 'Administrator' : 'Utilizator';

            echo json_encode([
                'success' => true,
                'message' => "Rolul utilizatorului {$user['nume']} a fost schimbat în {$role_text}",
                'new_role' => $new_role
            ]);
            break;

        case 'get_user_details':
            // Obține detaliile complete ale utilizatorului
            $stmt = $pdo->prepare("
                SELECT u.*, 
                       COUNT(DISTINCT ic.curs_id) as cursuri_inscrise,
                       COUNT(DISTINCT cc.curs_id) as cursuri_in_cos,
                       SUM(CASE WHEN ic.finalizat = 1 THEN 1 ELSE 0 END) as cursuri_finalizate,
                       MAX(ic.data_inscriere) as ultima_inscriere
                FROM users u
                LEFT JOIN inscrieri_cursuri ic ON u.id = ic.user_id
                LEFT JOIN cos_cumparaturi cc ON u.id = cc.user_id
                WHERE u.id = ?
                GROUP BY u.id
            ");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Utilizatorul nu a fost găsit']);
                exit;
            }

            // Calculează rata de finalizare
            $completion_rate = $user['cursuri_inscrise'] > 0 ? 
                round(($user['cursuri_finalizate'] / $user['cursuri_inscrise']) * 100, 1) : 0;

            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'nume' => $user['nume'],
                    'email' => $user['email'],
                    'rol' => $user['rol'],
                    'activ' => $user['activ'],
                    'data_inregistrare' => date('d.m.Y H:i', strtotime($user['data_inregistrare'])),
                    'data_actualizare' => date('d.m.Y H:i', strtotime($user['data_actualizare'])),
                    'cursuri_inscrise' => $user['cursuri_inscrise'],
                    'cursuri_finalizate' => $user['cursuri_finalizate'],
                    'cursuri_in_cos' => $user['cursuri_in_cos'],
                    'completion_rate' => $completion_rate,
                    'ultima_inscriere' => $user['ultima_inscriere'] ? date('d.m.Y', strtotime($user['ultima_inscriere'])) : 'Niciodată'
                ]
            ]);
            break;

        case 'delete_user':
            // Nu permite ștergerea propriului cont
            if ($user_id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Nu poți șterge propriul cont']);
                exit;
            }

            // Obține numele utilizatorului
            $stmt = $pdo->prepare("SELECT nume FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Utilizatorul nu a fost găsit']);
                exit;
            }

            // Dezactivează utilizatorul (nu îl șterge complet)
            $stmt = $pdo->prepare("UPDATE users SET activ = FALSE WHERE id = ?");
            $stmt->execute([$user_id]);

            echo json_encode([
                'success' => true,
                'message' => "Utilizatorul {$user['nume']} a fost dezactivat",
                'user_id' => $user_id
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acțiune nerecunoscută']);
            break;
    }

} catch (PDOException $e) {
    error_log("Database error in user-actions.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Eroare de bază de date']);
} catch (Exception $e) {
    error_log("General error in user-actions.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A apărut o eroare neașteptată']);
}
?>