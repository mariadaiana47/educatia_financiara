// Submit quiz - FUNCȚIA REPARATĂ
async function submitQuiz() {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
    
    // Calculate time spent
    const endTime = new Date();
    const timeSpent = Math.floor((endTime - startTime) / 1000); // in seconds
    
    // Prepare answers for submission - FORMATUL CORECT
    const answers = [];
    
    // Procesează fiecare întrebare
    quizData.intrebari.forEach(question => {
        if (userAnswers[question.id] !== undefined) {
            if (question.tip === 'adevar_fals') {
                // Pentru adevăr/fals
                answers.push({
                    intrebare_id: question.id,
                    raspuns_id: null,
                    raspuns_text: userAnswers[question.id] // 'true' sau 'false'
                });
            } else {
                // Pentru întrebări cu opțiuni multiple
                answers.push({
                    intrebare_id: question.id,
                    raspuns_id: userAnswers[question.id],
                    raspuns_text: null
                });
            }
        }
    });
    
    // Debug - afișează datele care vor fi trimise
    console.log('Submitting quiz with data:', {
        quiz_id: <?= $quiz_id ?>,
        answers: answers,
        time_spent: timeSpent
    });
    
    try {
        const response = await fetch('ajax/submit-quiz.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                quiz_id: <?= $quiz_id ?>,
                answers: answers,
                time_spent: timeSpent
            })
        });
        
        console.log('Response status:', response.status);
        const result = await response.json();
        console.log('Response data:', result);
        
        if (result.success) {
            showResults(result);
        } else {
            alert(result.message || 'Eroare la trimiterea răspunsurilor');
            console.error('Quiz submission failed:', result);
        }
    } catch (error) {
        console.error('Error submitting quiz:', error);
        alert('Eroare la trimiterea răspunsurilor. Te rugăm să încerci din nou.');
    }
}

// Show results - FUNCȚIA REPARATĂ
function showResults(result) {
    const overlay = document.getElementById('resultsOverlay');
    const modal = document.getElementById('resultsModal');
    const icon = document.getElementById('resultsIcon');
    const score = document.getElementById('resultsScore');
    const message = document.getElementById('resultsMessage');
    const details = document.getElementById('resultsDetails');
    
    // Set score
    score.textContent = result.procentaj.toFixed(1) + '%';
    
    // Set icon and message based on result
    if (result.promovat) {
        icon.className = 'results-icon success';
        icon.innerHTML = '<i class="fas fa-check-circle"></i>';
        message.textContent = 'Felicitări! Ai promovat quiz-ul!';
    } else {
        icon.className = 'results-icon danger';
        icon.innerHTML = '<i class="fas fa-times-circle"></i>';
        message.textContent = 'Din păcate nu ai promovat. Încearcă din nou!';
    }
    
    // Set details
    details.innerHTML = `
        <p>Ai răspuns corect la <strong>${result.corecte}</strong> din <strong>${result.total}</strong> întrebări.</p>
        <p>Timp petrecut: <strong>${formatTime(result.timp_completare)}</strong></p>
        <p>Punctaj obținut: <strong>${result.punctaj_obtinut || result.corecte}</strong> din <strong>${result.punctaj_maxim || result.total}</strong> puncte</p>
    `;
    
    // Show modal
    overlay.style.display = 'block';
    modal.style.display = 'block';
    
    // Adaugă event listener pentru overlay
    overlay.onclick = function() {
        overlay.style.display = 'none';
        modal.style.display = 'none';
    };
}

// Format time helper
function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes} min ${remainingSeconds} sec`;
}

// Retry quiz
function retryQuiz() {
    window.location.reload();
}

// Load quiz data - VERIFICĂ DACĂ EXISTĂ DATELE
async function loadQuiz() {
    try {
        const response = await fetch(`ajax/get-quiz.php?quiz_id=<?= $quiz_id ?>`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Quiz data loaded:', data);
        
        if (data.success) {
            quizData = data;
            initializeQuiz();
        } else {
            alert(data.message || 'Eroare la încărcarea quiz-ului');
            window.location.href = 'quiz.php';
        }
    } catch (error) {
        console.error('Error loading quiz:', error);
        alert('Eroare la încărcarea quiz-ului');
        window.location.href = 'quiz.php';
    }
}