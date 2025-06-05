<?php
require_once 'config.php';

$exercitiu_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

echo "<h3>Debug Exercițiu ID: $exercitiu_id</h3>";

try {
    // Query simplu pentru a verifica exercițiul
    $stmt = $pdo->prepare("SELECT * FROM exercitii_cursuri WHERE id = ?");
    $stmt->execute([$exercitiu_id]);
    $exercitiu = $stmt->fetch();
    
    echo "<h4>Exercițiu găsit:</h4>";
    if ($exercitiu) {
        echo "<pre>";
        print_r($exercitiu);
        echo "</pre>";
    } else {
        echo "<p>NU s-a găsit exercițiul cu ID $exercitiu_id</p>";
    }
    
    // Verifică cursul
    if ($exercitiu) {
        $stmt = $pdo->prepare("SELECT * FROM cursuri WHERE id = ?");
        $stmt->execute([$exercitiu['curs_id']]);
        $curs = $stmt->fetch();
        
        echo "<h4>Curs găsit:</h4>";
        if ($curs) {
            echo "<p>Titlu curs: " . $curs['titlu'] . "</p>";
            echo "<p>Activ: " . ($curs['activ'] ? 'DA' : 'NU') . "</p>";
        } else {
            echo "<p>NU s-a găsit cursul</p>";
        }
    }
    
    // Verifică înscrierea utilizatorului
    if (isLoggedIn() && $exercitiu) {
        $stmt = $pdo->prepare("SELECT * FROM inscrieri_cursuri WHERE user_id = ? AND curs_id = ?");
        $stmt->execute([$_SESSION['user_id'], $exercitiu['curs_id']]);
        $inscriere = $stmt->fetch();
        
        echo "<h4>Înscriere găsită:</h4>";
        if ($inscriere) {
            echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
            echo "<p>Curs ID: " . $exercitiu['curs_id'] . "</p>";
            echo "<p>Data înscrierii: " . $inscriere['data_inscriere'] . "</p>";
        } else {
            echo "<p>NU ești înscris la acest curs!</p>";
            echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
            echo "<p>Curs ID: " . $exercitiu['curs_id'] . "</p>";
        }
    } else {
        echo "<h4>Nu ești logat sau nu există exercițiul</h4>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Eroare PDO: " . $e->getMessage() . "</p>";
}
?>