<?php
require_once '../config.php';

// Verifică dacă utilizatorul este admin
if (!isLoggedIn() || !isAdmin()) {
    die('Access denied - doar adminii pot șterge quiz-uri');
}

echo "<h2>🗑️ Ștergere Quiz-uri Dublicate</h2>";

try {
    // Identifică quiz-urile dublicate
    $quizzes_to_delete = [
        'Quiz Bugetare',
        'Quiz Investiții pentru Începători'
    ];
    
    echo "<h3>Quiz-uri care vor fi șterse:</h3>";
    echo "<ul>";
    
    foreach ($quizzes_to_delete as $quiz_title) {
        // Găsește quiz-ul
        $stmt = $pdo->prepare("
            SELECT q.id, q.titlu, q.curs_id, c.titlu as curs_titlu,
                   (SELECT COUNT(*) FROM intrebari_quiz WHERE quiz_id = q.id) as nr_intrebari
            FROM quiz_uri q
            LEFT JOIN cursuri c ON q.curs_id = c.id
            WHERE q.titlu = ?
        ");
        $stmt->execute([$quiz_title]);
        $quiz = $stmt->fetch();
        
        if ($quiz) {
            echo "<li><strong>ID {$quiz['id']}: {$quiz['titlu']}</strong>";
            echo " - {$quiz['nr_intrebari']} întrebări";
            if ($quiz['curs_titlu']) {
                echo " (din cursul: {$quiz['curs_titlu']})";
            }
            echo "</li>";
        } else {
            echo "<li style='color: orange;'>⚠️ Quiz '{$quiz_title}' nu a fost găsit</li>";
        }
    }
    echo "</ul>";
    
    // Confirmă ștergerea
    if (isset($_POST['confirm_delete'])) {
        echo "<h3>🔥 Procesez ștergerea...</h3>";
        
        foreach ($quizzes_to_delete as $quiz_title) {
            $stmt = $pdo->prepare("SELECT id FROM quiz_uri WHERE titlu = ?");
            $stmt->execute([$quiz_title]);
            $quiz = $stmt->fetch();
            
            if ($quiz) {
                $quiz_id = $quiz['id'];
                
                // Începe tranzacția
                $pdo->beginTransaction();
                
                try {
                    // 1. Șterge răspunsurile utilizatorilor
                    $stmt = $pdo->prepare("
                        DELETE ru FROM raspunsuri_utilizatori ru
                        INNER JOIN rezultate_quiz rz ON ru.rezultat_id = rz.id
                        WHERE rz.quiz_id = ?
                    ");
                    $stmt->execute([$quiz_id]);
                    $deleted_user_answers = $stmt->rowCount();
                    
                    // 2. Șterge rezultatele quiz-urilor
                    $stmt = $pdo->prepare("DELETE FROM rezultate_quiz WHERE quiz_id = ?");
                    $stmt->execute([$quiz_id]);
                    $deleted_results = $stmt->rowCount();
                    
                    // 3. Șterge răspunsurile întrebărilor
                    $stmt = $pdo->prepare("
                        DELETE rq FROM raspunsuri_quiz rq
                        INNER JOIN intrebari_quiz iq ON rq.intrebare_id = iq.id
                        WHERE iq.quiz_id = ?
                    ");
                    $stmt->execute([$quiz_id]);
                    $deleted_answers = $stmt->rowCount();
                    
                    // 4. Șterge întrebările
                    $stmt = $pdo->prepare("DELETE FROM intrebari_quiz WHERE quiz_id = ?");
                    $stmt->execute([$quiz_id]);
                    $deleted_questions = $stmt->rowCount();
                    
                    // 5. Șterge quiz-ul
                    $stmt = $pdo->prepare("DELETE FROM quiz_uri WHERE id = ?");
                    $stmt->execute([$quiz_id]);
                    
                    // Commit tranzacția
                    $pdo->commit();
                    
                    echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
                    echo "✅ <strong>Quiz '$quiz_title' (ID: $quiz_id) șters cu succes!</strong><br>";
                    echo "📊 Statistici ștergere:<br>";
                    echo "- Răspunsuri utilizatori: $deleted_user_answers<br>";
                    echo "- Rezultate quiz: $deleted_results<br>";
                    echo "- Răspunsuri întrebări: $deleted_answers<br>";
                    echo "- Întrebări: $deleted_questions<br>";
                    echo "</div>";
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
                    echo "❌ <strong>Eroare la ștergerea quiz-ului '$quiz_title':</strong><br>";
                    echo $e->getMessage();
                    echo "</div>";
                }
            } else {
                echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
                echo "⚠️ Quiz '$quiz_title' nu a fost găsit în baza de date";
                echo "</div>";
            }
        }
        
        echo "<hr>";
        echo "<h3>🎉 Operațiune finalizată!</h3>";
        echo "<p><a href='quiz.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🎯 Vezi Quiz-urile Actualizate</a></p>";
        echo "<p><a href='test-quizes.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔍 Verifică din Nou</a></p>";
        
    } else {
        // Formular de confirmare
        echo "<hr>";
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>⚠️ ATENȚIE!</h4>";
        echo "<p>Această operațiune va șterge <strong>DEFINITIV</strong> quiz-urile și toate datele asociate:</p>";
        echo "<ul>";
        echo "<li>Quiz-urile selectate</li>";
        echo "<li>Toate întrebările lor</li>";
        echo "<li>Toate răspunsurile</li>";
        echo "<li>Rezultatele utilizatorilor pentru aceste quiz-uri</li>";
        echo "</ul>";
        echo "<p><strong>Această operațiune NU poate fi anulată!</strong></p>";
        echo "</div>";
        
        echo "<form method='post' style='margin: 20px 0;'>";
        echo "<button type='submit' name='confirm_delete' value='1' ";
        echo "style='background: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;' ";
        echo "onclick='return confirm(\"Ești absolut sigur că vrei să ștergi aceste quiz-uri? Această acțiune NU poate fi anulată!\");'>";
        echo "🗑️ DA, Șterge Quiz-urile Dublicate";
        echo "</button>";
        echo "</form>";
        
        echo "<p><a href='quiz.php' style='color: #6c757d;'>← Anulează și înapoi la quiz-uri</a></p>";
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ Eroare bază de date:</strong><br>";
    echo $e->getMessage();
    echo "</div>";
}
?>