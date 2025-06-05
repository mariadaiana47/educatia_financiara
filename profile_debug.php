<?php
// Test simplu pentru a vedea unde este problema
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Start profil.php debug<br>";

try {
    require_once 'config.php';
    echo "Config loaded<br>";
} catch (Exception $e) {
    die("Error loading config: " . $e->getMessage());
}

// Verifică dacă utilizatorul este conectat
if (!isLoggedIn()) {
    echo "User not logged in, redirecting...<br>";
    redirectTo('login.php');
}

echo "User is logged in<br>";

$page_title = 'Profilul Meu - ' . SITE_NAME;
$current_user = getCurrentUser();

echo "Current user: " . htmlspecialchars($current_user['nume']) . "<br>";

// Test simplu pentru cursuri
try {
    $user_id = $_SESSION['user_id'];
    echo "User ID: $user_id<br>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM inscrieri_cursuri WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $count = $stmt->fetchColumn();
    
    echo "Total cursuri inscrise: $count<br>";
    
    if ($count > 0) {
        $stmt = $pdo->prepare("
            SELECT c.id, c.titlu, ic.data_inscriere, ic.progress 
            FROM inscrieri_cursuri ic 
            JOIN cursuri c ON ic.curs_id = c.id 
            WHERE ic.user_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $curs = $stmt->fetch();
        
        if ($curs) {
            echo "Primul curs: " . htmlspecialchars($curs['titlu']) . " - Progres: " . $curs['progress'] . "%<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

echo "Debug completed successfully!<br>";

// Încarcă header-ul doar dacă totul merge bine până aici
echo "<h1>Pagina profil funcționează!</h1>";
echo "<p>Următorul pas este să adaug înapoi funcționalitatea completă.</p>";
?>