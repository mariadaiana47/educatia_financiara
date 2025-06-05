<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = MSG_ERROR_ACCESS_DENIED;
    redirectTo('../login.php');
}

$curs_id = isset($_GET['curs_id']) ? (int)$_GET['curs_id'] : 0;

// Validare curs
$stmt = $pdo->prepare("SELECT * FROM cursuri WHERE id = ?");
$stmt->execute([$curs_id]);
$curs = $stmt->fetch();
if (!$curs) {
    $_SESSION['error_message'] = 'Cursul nu a fost găsit.';
    redirectTo('content-manager.php');
}

// Adaugă quiz-uri predefinite dacă nu există deja pentru cursul curent
$check = $pdo->prepare("SELECT COUNT(*) FROM quiz_uri WHERE curs_id = ?");
$check->execute([$curs_id]);
if ($check->fetchColumn() == 0) {
    $stmt = $pdo->prepare("
        INSERT INTO quiz_uri (titlu, descriere, curs_id, timp_limita, dificultate, tip_quiz, numar_intrebari)
        VALUES 
        ('Quiz: Bazele bugetării', 'Testează conceptele fundamentale ale bugetului personal.', ?, 10, 'usor', 'evaluare', 5),
        ('Quiz: Regula 50/30/20', 'Înțelegi cum se aplică regula 50/30/20? Află acum.', ?, 8, 'usor', 'evaluare', 4)
    ");
    $stmt->execute([$curs_id, $curs_id]);
    $_SESSION['success_message'] = 'S-au adăugat quiz-uri predefinite pentru acest curs.';
    redirectTo("quiz-manager.php?curs_id=$curs_id");
}

// Procesare acțiuni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Token CSRF invalid.';
        redirectTo("quiz-manager.php?curs_id=$curs_id");
    }

    $action = $_POST['action'];

    if ($action === 'add_quiz') {
        $titlu = sanitizeInput($_POST['titlu']);
        $descriere = sanitizeInput($_POST['descriere']);
        $timp = (int)$_POST['timp_limita'];
        $dificultate = $_POST['dificultate'];

        $stmt = $pdo->prepare("INSERT INTO quiz_uri (titlu, descriere, curs_id, timp_limita, dificultate) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$titlu, $descriere, $curs_id, $timp, $dificultate]);
        $_SESSION['success_message'] = 'Quiz adăugat.';
    }

    if ($action === 'delete_quiz') {
        $quiz_id = (int)$_POST['quiz_id'];
        $stmt = $pdo->prepare("DELETE FROM quiz_uri WHERE id = ? AND curs_id = ?");
        $stmt->execute([$quiz_id, $curs_id]);
        $_SESSION['success_message'] = 'Quiz șters.';
    }

    redirectTo("quiz-manager.php?curs_id=$curs_id");
}

// Afișare quizuri
$stmt = $pdo->prepare("SELECT * FROM quiz_uri WHERE curs_id = ? ORDER BY data_creare DESC");
$stmt->execute([$curs_id]);
$quizuri = $stmt->fetchAll();

include '../components/header.php';
?>

<div class="container py-4">
    <h2 class="mb-4">Quizuri pentru cursul: <?= sanitizeInput($curs['titlu']) ?></h2>

    <?= displaySessionMessages() ?>

    <form method="post" class="card p-3 mb-4">
        <h5>Adaugă quiz nou</h5>
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="action" value="add_quiz">
        <div class="mb-2">
            <label>Titlu</label>
            <input type="text" name="titlu" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Descriere</label>
            <textarea name="descriere" class="form-control" required></textarea>
        </div>
        <div class="mb-2">
            <label>Timp limită (minute)</label>
            <input type="number" name="timp_limita" class="form-control" value="10" required>
        </div>
        <div class="mb-3">
            <label>Dificultate</label>
            <select name="dificultate" class="form-select">
                <option value="usor">Ușor</option>
                <option value="mediu">Mediu</option>
                <option value="greu">Greu</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Adaugă Quiz</button>
    </form>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Quizuri existente</h5>
            <?php if (count($quizuri) > 0): ?>
                <ul class="list-group">
                    <?php foreach ($quizuri as $quiz): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= sanitizeInput($quiz['titlu']) ?></strong><br>
                                <small class="text-muted"><?= sanitizeInput($quiz['descriere']) ?></small>
                            </div>
                            <form method="post" onsubmit="return confirm('Sigur vrei să ștergi quizul?')">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="action" value="delete_quiz">
                                <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Șterge</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">Nu există quizuri pentru acest curs.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../components/footer.php'; ?>
