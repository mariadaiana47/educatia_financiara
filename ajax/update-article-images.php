<?php
// update-article-images-correct.php
// Rulează acest script o singură dată pentru a actualiza imaginile articolelor cu numele corecte

require_once 'config.php';

try {
    // Array cu maparea articolelor către imaginile REALE
    $article_images = [
        '10 Greșeli Financiare pe care să le Eviți' => 'financial-mistakes.jpg',
        'Cum să Economisești pentru Pensie de la 20 de Ani' => 'pension-savings.jpg',
        'Diferența dintre Active și Pasive' => 'assets-vs-liabilities.jpg',
        'Dobânda Compusă: A 8-a Minune a Lumii' => 'compound-interest.jpg'
    ];
    
    echo "<h2>Actualizare imagini articole cu numele corecte</h2>";
    
    foreach ($article_images as $article_title => $image_name) {
        $stmt = $pdo->prepare("UPDATE articole SET imagine = ? WHERE titlu = ?");
        $result = $stmt->execute([$image_name, $article_title]);
        
        if ($result) {
            echo "✅ Actualizat: $article_title -> $image_name<br>";
        } else {
            echo "❌ Eroare la actualizarea: $article_title<br>";
        }
    }
    
    // Verifică rezultatele
    echo "<br><h3>Articole cu imagini:</h3>";
    $stmt = $pdo->query("SELECT id, titlu, imagine FROM articole WHERE imagine IS NOT NULL");
    $articole = $stmt->fetchAll();
    
    foreach ($articole as $articol) {
        $image_path = "assets/images/articles/" . $articol['imagine'];
        $exists = file_exists($image_path) ? "✅ EXISTĂ" : "❌ NU EXISTĂ";
        echo "<div style='margin: 10px 0; padding: 10px; background: " . ($exists == "✅ EXISTĂ" ? "#d4edda" : "#f8d7da") . ";'>";
        echo "$exists ID: {$articol['id']} - {$articol['titlu']} - {$articol['imagine']}<br>";
        echo "Căutând în: $image_path";
        echo "</div>";
    }
    
    echo "<br><h3>Verificare fișiere:</h3>";
    $files_to_check = [
        'financial-mistakes.jpg',
        'pension-savings.jpg', 
        'assets-vs-liabilities.jpg',
        'compound-interest.jpg'
    ];
    
    foreach ($files_to_check as $file) {
        $path = "assets/images/articles/" . $file;
        $exists = file_exists($path);
        echo ($exists ? "✅" : "❌") . " $path<br>";
    }
    
} catch (PDOException $e) {
    echo "Eroare: " . $e->getMessage();
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; margin-top: 20px; }
code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
</style>