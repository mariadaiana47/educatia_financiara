<?php
// fix-images.php - Script rapid pentru a repara imaginile
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    die('Access denied');
}

echo "<h2>Reparare Rapidă Imagini</h2>";

try {
    // Definește imaginile pentru fiecare curs
    $imagini_cursuri = [
        1 => 'bugetare-personal.jpg',
        2 => 'economisire-inteligenta.jpg', 
        3 => 'investitii-incepatori.jpg',
        4 => 'gestionare-datorii.jpg',
        5 => 'planificare-pensie.jpg'
    ];

    // Verifică și creează folderul dacă nu există
    $uploads_dir = 'uploads/cursuri/';
    if (!is_dir($uploads_dir)) {
        if (!file_exists('uploads')) {
            mkdir('uploads', 0755, true);
        }
        mkdir($uploads_dir, 0755, true);
        echo "<p style='color: green;'>✓ Folderul '$uploads_dir' a fost creat</p>";
    }

    // Actualizează fiecare curs
    foreach ($imagini_cursuri as $curs_id => $imagine) {
        $image_path = $uploads_dir . $imagine;
        
        // Verifică dacă fișierul există
        if (file_exists($image_path)) {
            // Actualizează în baza de date
            $stmt = $pdo->prepare("UPDATE cursuri SET imagine = ? WHERE id = ?");
            $stmt->execute([$imagine, $curs_id]);
            
            echo "<p style='color: green;'>✓ Cursul $curs_id - imaginea '$imagine' a fost setată</p>";
        } else {
            echo "<p style='color: red;'>✗ Cursul $curs_id - imaginea '$imagine' nu există la calea: $image_path</p>";
        }
    }

    echo "<hr>";
    echo "<h3>Verificare Finală</h3>";
    
    // Verifică rezultatul
    $stmt = $pdo->query("SELECT id, titlu, imagine FROM cursuri ORDER BY id");
    $cursuri = $stmt->fetchAll();
    
    foreach ($cursuri as $curs) {
        $image_path = $uploads_dir . $curs['imagine'];
        $status = file_exists($image_path) ? 'OK' : 'LIPSEȘTE';
        $color = file_exists($image_path) ? 'green' : 'red';
        
        echo "<p style='color: $color;'>Curs: " . sanitizeInput($curs['titlu']) . " - Imagine: " . ($curs['imagine'] ?: 'NONE') . " - Status: $status</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>Eroare: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Gata!</strong> <a href='cursuri.php'>Verifică cursurile acum</a></p>";
?>