<?php
// debug-images.php - Script pentru a verifica imaginile cursurilor
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    die('Access denied');
}

echo "<h2>Debug Imagini Cursuri</h2>";

// 1. Verifică dacă folderul uploads/cursuri există
$uploads_dir = 'uploads/cursuri/';
echo "<h3>1. Verificare Folder</h3>";
if (is_dir($uploads_dir)) {
    echo "<p style='color: green;'>✓ Folderul '$uploads_dir' există</p>";
    
    // Verifică permisiunile
    if (is_writable($uploads_dir)) {
        echo "<p style='color: green;'>✓ Folderul are permisiuni de scriere</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Folderul nu are permisiuni de scriere</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Folderul '$uploads_dir' nu există!</p>";
    echo "<p>Creez folderul...</p>";
    
    if (!file_exists('uploads')) {
        mkdir('uploads', 0755, true);
    }
    mkdir($uploads_dir, 0755, true);
    
    if (is_dir($uploads_dir)) {
        echo "<p style='color: green;'>✓ Folderul a fost creat cu succes!</p>";
    }
}

// 2. Listează fișierele din folder
echo "<h3>2. Fișiere în Folder</h3>";
if (is_dir($uploads_dir)) {
    $files = scandir($uploads_dir);
    $image_files = array_filter($files, function($file) {
        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    });
    
    if (!empty($image_files)) {
        echo "<ul>";
        foreach ($image_files as $file) {
            $file_path = $uploads_dir . $file;
            $file_size = filesize($file_path);
            echo "<li><strong>$file</strong> - " . number_format($file_size / 1024, 2) . " KB</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠ Nu există imagini în folder</p>";
    }
} else {
    echo "<p style='color: red;'>Nu se poate lista folderul</p>";
}

// 3. Verifică cursurile din baza de date
echo "<h3>3. Cursuri din Baza de Date</h3>";
try {
    $stmt = $pdo->query("SELECT id, titlu, imagine FROM cursuri ORDER BY id");
    $cursuri = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Titlu</th><th>Imagine în BD</th><th>Fișier Există</th><th>Status</th></tr>";
    
    foreach ($cursuri as $curs) {
        $image_path = $uploads_dir . $curs['imagine'];
        $file_exists = $curs['imagine'] && file_exists($image_path);
        
        echo "<tr>";
        echo "<td>{$curs['id']}</td>";
        echo "<td>" . sanitizeInput($curs['titlu']) . "</td>";
        echo "<td>" . ($curs['imagine'] ?: '<em>NULL</em>') . "</td>";
        echo "<td>" . ($file_exists ? '✓ DA' : '✗ NU') . "</td>";
        
        if (!$curs['imagine']) {
            echo "<td style='color: orange;'>Lipsește numele imaginii în BD</td>";
        } elseif (!$file_exists) {
            echo "<td style='color: red;'>Fișierul nu există</td>";
        } else {
            echo "<td style='color: green;'>OK</td>";
        }
        
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Eroare BD: " . $e->getMessage() . "</p>";
}

// 4. Actualizează imaginile în baza de date
echo "<h3>4. Actualizare Automată</h3>";
echo "<p>Vrei să actualizez automat imaginile în baza de date?</p>";

if (isset($_POST['update_images'])) {
    $imagini_cursuri = [
        1 => 'bugetare-personal.jpg',
        2 => 'economisire-inteligenta.jpg', 
        3 => 'investitii-incepatori.jpg',
        4 => 'gestionare-datorii.jpg',
        5 => 'planificare-pensie.jpg'
    ];
    
    foreach ($imagini_cursuri as $curs_id => $imagine) {
        $image_path = $uploads_dir . $imagine;
        
        if (file_exists($image_path)) {
            try {
                $stmt = $pdo->prepare("UPDATE cursuri SET imagine = ? WHERE id = ?");
                $stmt->execute([$imagine, $curs_id]);
                echo "<p style='color: green;'>✓ Actualizat cursul $curs_id cu imaginea $imagine</p>";
            } catch (PDOException $e) {
                echo "<p style='color: red;'>✗ Eroare la actualizarea cursului $curs_id: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ Imaginea $imagine nu există pentru cursul $curs_id</p>";
        }
    }
    
    echo "<p><strong>Actualizare completă!</strong> <a href='cursuri.php'>Vezi cursurile</a></p>";
} else {
    echo "<form method='POST'>";
    echo "<button type='submit' name='update_images' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Actualizează Imaginile</button>";
    echo "</form>";
}

// 5. Test de încărcare imagine
echo "<h3>5. Test Încărcare Imagine</h3>";
if (isset($_POST['test_image'])) {
    $test_image = $uploads_dir . 'bugetare-personal.jpg';
    if (file_exists($test_image)) {
        echo "<p>Imaginea de test:</p>";
        echo "<img src='$test_image' style='max-width: 200px; border: 2px solid #ddd; border-radius: 8px;' alt='Test image'>";
        echo "<p><strong>Calea completă:</strong> " . realpath($test_image) . "</p>";
        echo "<p><strong>URL relativ:</strong> $test_image</p>";
    } else {
        echo "<p style='color: red;'>Imaginea de test nu există!</p>";
    }
} else {
    echo "<form method='POST'>";
    echo "<button type='submit' name='test_image' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Testează Imaginea</button>";
    echo "</form>";
}

echo "<hr>";
echo "<p><a href='cursuri.php'>← Înapoi la cursuri</a> | <a href='setup-complete-courses.php'>Setup cursuri</a></p>";
?>