<?php
// setup-course-content.php - Script pentru a popula cursurile cu quiz-uri și exerciții predefinite
require_once 'config.php';

// Verifică dacă utilizatorul este admin
if (!isLoggedIn() || !isAdmin()) {
    die('Access denied');
}

echo "<h2>Setup Quiz-uri și Exerciții Predefinite pentru Cursuri</h2>";

try {
    // 1. QUIZ-URI PREDEFINITE PENTRU FIECARE CURS
    
    // Curs 1: Fundamente de Bugetare
    $curs1_quizuri = [
        [
            'titlu' => 'Quiz: Bazele Bugetării',
            'descriere' => 'Testează cunoștințele despre conceptele fundamentale ale bugetului personal.',
            'timp_limita' => 15,
            'dificultate' => 'usor',
            'intrebari' => [
                [
                    'intrebare' => 'Ce reprezintă regula 50/30/20 în bugetare?',
                    'tip' => 'multipla',
                    'raspunsuri' => [
                        ['raspuns' => '50% economii, 30% necesități, 20% dorințe', 'corect' => 0],
                        ['raspuns' => '50% necesități, 30% dorințe, 20% economii', 'corect' => 1],
                        ['raspuns' => '50% dorințe, 30% economii, 20% necesități', 'corect' => 0],
                        ['raspuns' => '50% investiții, 30% cheltuieli, 20% economii', 'corect' => 0]
                    ]
                ],
                [
                    'intrebare' => 'Un buget lunar ar trebui revizuit și actualizat periodic.',
                    'tip' => 'adevar_fals',
                    'raspunsuri' => [
                        ['raspuns' => 'Adevărat', 'corect' => 1],
                        ['raspuns' => 'Fals', 'corect' => 0]
                    ]
                ],
                [
                    'intrebare' => 'Care este primul pas în crearea unui buget?',
                    'tip' => 'multipla',
                    'raspunsuri' => [
                        ['raspuns' => 'Calcularea cheltuielilor', 'corect' => 0],
                        ['raspuns' => 'Stabilirea obiectivelor financiare', 'corect' => 0],
                        ['raspuns' => 'Calcularea veniturilor totale', 'corect' => 1],
                        ['raspuns' => 'Deschiderea unui cont de economii', 'corect' => 0]
                    ]
                ],
                [
                    'intrebare' => 'Cheltuielile variabile sunt aceleași în fiecare lună.',
                    'tip' => 'adevar_fals',
                    'raspunsuri' => [
                        ['raspuns' => 'Adevărat', 'corect' => 0],
                        ['raspuns' => 'Fals', 'corect' => 1]
                    ]
                ],
                [
                    'intrebare' => 'Ce înseamnă să "plătești pe tine primul"?',
                    'tip' => 'multipla',
                    'raspunsuri' => [
                        ['raspuns' => 'Să îți cumperi lucruri scumpe', 'corect' => 0],
                        ['raspuns' => 'Să economisești înainte de a cheltui pe altceva', 'corect' => 1],
                        ['raspuns' => 'Să îți plătești datoriile întâi', 'corect' => 0],
                        ['raspuns' => 'Să îți dai salariul', 'corect' => 0]
                    ]
                ]
            ]
        ],
        [
            'titlu' => 'Quiz: Instrumente de Bugetare',
            'descriere' => 'Testează cunoștințele despre instrumentele și metodele de bugetare.',
            'timp_limita' => 10,
            'dificultate' => 'usor',
            'intrebari' => [
                [
                    'intrebare' => 'Excel sau aplicațiile mobile sunt instrumente utile pentru bugetare.',
                    'tip' => 'adevar_fals',
                    'raspunsuri' => [
                        ['raspuns' => 'Adevărat', 'corect' => 1],
                        ['raspuns' => 'Fals', 'corect' => 0]
                    ]
                ],
                [
                    'intrebare' => 'Ce tip de buget este recomandat pentru începători?',
                    'tip' => 'multipla',
                    'raspunsuri' => [
                        ['raspuns' => 'Buget pe bază zero', 'corect' => 0],
                        ['raspuns' => 'Buget 50/30/20', 'corect' => 1],
                        ['raspuns' => 'Buget flexibil', 'corect' => 0],
                        ['raspuns' => 'Buget săptămânal', 'corect' => 0]
                    ]
                ],
                [
                    'intrebare' => 'Cât de des ar trebui să îți verifici bugetul?',
                    'tip' => 'multipla',
                    'raspunsuri' => [
                        ['raspuns' => 'O dată pe an', 'corect' => 0],
                        ['raspuns' => 'O dată pe lună', 'corect' => 0],
                        ['raspuns' => 'Săptămânal', 'corect' => 1],
                        ['raspuns' => 'Niciodată', 'corect' => 0]
                    ]
                ]
            ]
        ]
    ];

    // Curs 2: Economisirea Inteligentă
    $curs2_quizuri = [
        [
            'titlu' => 'Quiz: Strategii de Economisire',
            'descriere' => 'Testează cunoștințele despre strategii eficiente de economisire.',
            'timp_limita' => 15,
            'dificultate' => 'usor',
            'intrebari' => [
                [
                    'intrebare' => 'Ce înseamnă dobânda compusă?',
                    'tip' => 'multipla',
                    'raspunsuri' => [
                        ['raspuns' => 'Dobânda calculată doar pe suma inițială', 'corect' => 0],
                        ['raspuns' => 'Dobânda calculată pe suma inițială plus dobânzile anterioare', 'corect' => 1],
                        ['raspuns' => 'O taxă bancară aplicată la credite', 'corect' => 0],
                        ['raspuns' => 'Dobânda care se plătește lunar', 'corect' => 0]
                    ]
                ],
                [
                    'intrebare' => 'Un fond de urgență ar trebui să acopere cheltuielile pentru 3-6 luni.',
                    'tip' => 'adevar_fals',
                    'raspunsuri' => [
                        ['raspuns' => 'Adevărat', 'corect' => 1],
                        ['raspuns' => 'Fals', 'corect' => 0]
                    ]
                ],
                [
                    'intrebare' => 'Care este avantajul principal al economisirii automate?',
                    'tip' => 'multipla',
                    'raspunsuri' => [
                        ['raspuns' => 'Economisești mai mulți bani', 'corect' => 0],
                        ['raspuns' => 'Nu uiți să economisești', 'corect' => 1],
                        ['raspuns' => 'Primești dobândă mai mare', 'corect' => 0],
                        ['raspuns' => 'Eviți taxele bancare', 'corect' => 0]
                    ]
                ]
            ]
        ]
    ];

    // Curs 3: Introducere în Investiții
    $curs3_quizuri = [
        [
            'titlu' => 'Quiz: Bazele Investițiilor',
            'descriere' => 'Testează cunoștințele despre conceptele de bază ale investițiilor.',
            'timp_limita' => 20,
            'dificultate' => 'mediu',
            'intrebari' => [
                [
                    'intrebare' => 'Care este prima regulă a investițiilor?',
                    'tip' => 'multipla',
                    'raspunsuri' => [
                        ['raspuns' => 'Să câștigi cât mai mult și cât mai repede', 'corect' => 0],
                        ['raspuns' => 'Să nu pierzi banii', 'corect' => 1],
                        ['raspuns' => 'Să investești tot ce ai', 'corect' => 0],
                        ['raspuns' => 'Să urmezi sfaturile altora', 'corect' => 0]
                    ]
                ],
                [
                    'intrebare' => 'Diversificarea portofoliului reduce riscul investițional.',
                    'tip' => 'adevar_fals',
                    'raspunsuri' => [
                        ['raspuns' => 'Adevărat', 'corect' => 1],
                        ['raspuns' => 'Fals', 'corect' => 0]
                    ]
                ],
                [
                    'intrebare' => 'Ce înseamnă diversificarea portofoliului?',
                    'tip' => 'multipla',
                    'raspunsuri' => [
                        ['raspuns' => 'Să investești doar în acțiuni', 'corect' => 0],
                        ['raspuns' => 'Să împarți investițiile pe mai multe tipuri de active', 'corect' => 1],
                        ['raspuns' => 'Să investești doar în imobiliare', 'corect' => 0],
                        ['raspuns' => 'Să ții banii doar la bancă', 'corect' => 0]
                    ]
                ]
            ]
        ]
    ];

    // 2. EXERCIȚII PREDEFINITE PENTRU FIECARE CURS

    // Exerciții pentru Curs 1: Fundamente de Bugetare
    $curs1_exercitii = [
        [
            'titlu' => 'Calculator Buget Personal 50/30/20',
            'descriere' => 'Folosește calculatorul nostru pentru a-ți crea primul buget folosind regula 50/30/20',
            'tip' => 'external_link',
            'link_extern' => 'instrumente.php#planificator-buget',
            'ordine' => 1
        ],
        [
            'titlu' => 'Template Excel pentru Buget Lunar',
            'descriere' => 'Descarcă și completează template-ul nostru de buget lunar pentru a-ți organiza finanțele',
            'tip' => 'document',
            'fisier_descarcare' => 'template-buget-lunar.xlsx',
            'ordine' => 2
        ],
        [
            'titlu' => 'Exercițiu: Analiza Cheltuielilor Tale',
            'descriere' => 'Analizează-ți cheltuielile din ultima lună și identifică unde poți economisi folosind calculatorul nostru',
            'tip' => 'calculator',
            'ordine' => 3
        ]
    ];

    // Exerciții pentru Curs 2: Economisirea Inteligentă
    $curs2_exercitii = [
        [
            'titlu' => 'Calculator Dobândă Compusă',
            'descriere' => 'Calculează cum cresc economiile tale în timp cu puterea dobânzii compuse',
            'tip' => 'external_link',
            'link_extern' => 'instrumente.php#calculator-economii',
            'ordine' => 1
        ],
        [
            'titlu' => 'Plan Personal de Economisire pe 5 ani',
            'descriere' => 'Creează-ți planul personalizat de economisire pe 5 ani folosind strategiile învățate',
            'tip' => 'calculator',
            'ordine' => 2
        ],
        [
            'titlu' => 'Ghid: 25 Strategii Dovedite de Economisire',
            'descriere' => 'Descarcă ghidul complet cu 25 de strategii practice și dovedite de economisire',
            'tip' => 'document',
            'fisier_descarcare' => 'ghid-strategii-economisire.pdf',
            'ordine' => 3
        ]
    ];

    // Exerciții pentru Curs 3: Introducere în Investiții
    $curs3_exercitii = [
        [
            'titlu' => 'Simulator Portofoliu Virtual',
            'descriere' => 'Creează-ți primul portofoliu virtual și învață cum funcționează piața de capital',
            'tip' => 'external_link',
            'link_extern' => 'https://finance.yahoo.com/portfolios',
            'ordine' => 1
        ],
        [
            'titlu' => 'Calculator Profilul de Risc',
            'descriere' => 'Evaluează-ți profilul de risc și află ce tip de investiții ți se potrivesc',
            'tip' => 'calculator',
            'ordine' => 2
        ],
        [
            'titlu' => 'Analiză Practică: Tesla vs Apple',
            'descriere' => 'Compară două acțiuni populare și învață să analizezi datele financiare ale companiilor',
            'tip' => 'calculator',
            'ordine' => 3
        ]
    ];

    // 3. INSERARE ÎN BAZA DE DATE

    $cursuri_content = [
        1 => ['quizuri' => $curs1_quizuri, 'exercitii' => $curs1_exercitii],
        2 => ['quizuri' => $curs2_quizuri, 'exercitii' => $curs2_exercitii],
        3 => ['quizuri' => $curs3_quizuri, 'exercitii' => $curs3_exercitii]
    ];

    foreach ($cursuri_content as $curs_id => $content) {
        echo "<h3>Procesez cursul ID: $curs_id</h3>";
        
        // Verifică dacă cursul există
        $stmt = $pdo->prepare("SELECT titlu FROM cursuri WHERE id = ?");
        $stmt->execute([$curs_id]);
        $curs = $stmt->fetch();
        
        if (!$curs) {
            echo "<p style='color: orange;'>Cursul cu ID $curs_id nu există în baza de date.</p>";
            continue;
        }
        
        echo "<p><strong>Curs:</strong> " . $curs['titlu'] . "</p>";
        
        // Inserează quiz-urile
        foreach ($content['quizuri'] as $quiz_data) {
            // Verifică dacă quiz-ul există deja
            $stmt = $pdo->prepare("SELECT id FROM quiz_uri WHERE titlu = ? AND curs_id = ?");
            $stmt->execute([$quiz_data['titlu'], $curs_id]);
            
            if ($stmt->fetch()) {
                echo "<p style='color: blue;'>Quiz '{$quiz_data['titlu']}' există deja - sărim peste el.</p>";
                continue;
            }
            
            // Inserează quiz-ul
            $stmt = $pdo->prepare("
                INSERT INTO quiz_uri (titlu, descriere, curs_id, timp_limita, dificultate, numar_intrebari, activ) 
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $quiz_data['titlu'],
                $quiz_data['descriere'],
                $curs_id,
                $quiz_data['timp_limita'],
                $quiz_data['dificultate'],
                count($quiz_data['intrebari'])
            ]);
            
            $quiz_id = $pdo->lastInsertId();
            echo "<p style='color: green;'>✓ Quiz adăugat: {$quiz_data['titlu']} (ID: $quiz_id)</p>";
            
            // Inserează întrebările
            foreach ($quiz_data['intrebari'] as $ordine => $intrebare_data) {
                $stmt = $pdo->prepare("
                    INSERT INTO intrebari_quiz (quiz_id, intrebare, tip, punctaj, ordine, activ) 
                    VALUES (?, ?, ?, 1, ?, 1)
                ");
                $stmt->execute([
                    $quiz_id,
                    $intrebare_data['intrebare'],
                    $intrebare_data['tip'],
                    $ordine
                ]);
                
                $intrebare_id = $pdo->lastInsertId();
                
                // Inserează răspunsurile
                foreach ($intrebare_data['raspunsuri'] as $rasp_ordine => $raspuns_data) {
                    $stmt = $pdo->prepare("
                        INSERT INTO raspunsuri_quiz (intrebare_id, raspuns, corect, ordine) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $intrebare_id,
                        $raspuns_data['raspuns'],
                        $raspuns_data['corect'],
                        $rasp_ordine + 1
                    ]);
                }
                
                echo "<p style='margin-left: 20px; color: green;'>  ✓ Întrebare adăugată: " . substr($intrebare_data['intrebare'], 0, 50) . "...</p>";
            }
        }
        
        // Inserează exercițiile
        foreach ($content['exercitii'] as $exercitiu_data) {
            // Verifică dacă exercițiul există deja
            $stmt = $pdo->prepare("SELECT id FROM exercitii_cursuri WHERE titlu = ? AND curs_id = ?");
            $stmt->execute([$exercitiu_data['titlu'], $curs_id]);
            
            if ($stmt->fetch()) {
                echo "<p style='color: blue;'>Exercițiul '{$exercitiu_data['titlu']}' există deja - sărim peste el.</p>";
                continue;
            }
            
            // Inserează exercițiul
            $stmt = $pdo->prepare("
                INSERT INTO exercitii_cursuri (curs_id, titlu, descriere, tip, link_extern, fisier_descarcare, ordine, activ) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $curs_id,
                $exercitiu_data['titlu'],
                $exercitiu_data['descriere'],
                $exercitiu_data['tip'],
                $exercitiu_data['link_extern'] ?? null,
                $exercitiu_data['fisier_descarcare'] ?? null,
                $exercitiu_data['ordine']
            ]);
            
            echo "<p style='color: green;'>✓ Exercițiu adăugat: {$exercitiu_data['titlu']}</p>";
        }
        
        echo "<hr>";
    }

    echo "<h2 style='color: green;'>✓ Setup complet! Toate quiz-urile și exercițiile au fost adăugate.</h2>";
    echo "<p><a href='cursuri.php'>← Înapoi la cursuri</a> | <a href='admin/dashboard.php'>Admin Dashboard</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Eroare: " . $e->getMessage() . "</p>";
}
?>