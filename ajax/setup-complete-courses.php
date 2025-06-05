<?php
// setup-complete-courses.php - Script pentru setup complet al cursurilor
require_once 'config.php';

// Verifică dacă utilizatorul este admin
if (!isLoggedIn() || !isAdmin()) {
    die('Access denied');
}

echo "<h2>Setup Complet Cursuri cu Conținut Standard</h2>";

try {
    // 1. ACTUALIZEZ CURSURILE CU IMAGINI ȘI VIDEO-URI STANDARD
    
    $cursuri_update = [
        1 => [
            'imagine' => 'bugetare-personal.jpg',
            'video_intro_url' => 'https://www.youtube.com/watch?v=QZuNmqXLCwA', // Video despre bugetare
            'video_principal' => [
                'titlu' => 'Fundamente de Bugetare: Cum să îți Creezi Primul Buget',
                'url' => 'https://www.youtube.com/watch?v=QZuNmqXLCwA',
                'durata' => 1200, // 20 minute
                'descriere' => 'Învață pas cu pas cum să îți creezi primul buget personal folosind regula 50/30/20 și alte tehnici dovedite.'
            ]
        ],
        2 => [
            'imagine' => 'economisire-inteligenta.jpg', 
            'video_intro_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'video_principal' => [
                'titlu' => 'Economisirea Inteligentă și Puterea Dobânzii Compuse',
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'durata' => 1440, // 24 minute
                'descriere' => 'Descoperă secretele economisirii eficiente și cum dobânda compusă poate să îți transforme viitorul financiar.'
            ]
        ],
        3 => [
            'imagine' => 'investitii-incepatori.jpg',
            'video_intro_url' => 'https://www.youtube.com/watch?v=oHg5SJYRHA0',
            'video_principal' => [
                'titlu' => 'Introducere în Investiții: De la Zero la Primul Portofoliu',
                'url' => 'https://www.youtube.com/watch?v=oHg5SJYRHA0',
                'durata' => 1800, // 30 minute
                'descriere' => 'Primul tău pas în lumea investițiilor: înțelege riscurile, diversificarea și cum să îți construiești primul portofoliu.'
            ]
        ],
        4 => [
            'imagine' => 'gestionare-datorii.jpg',
            'video_intro_url' => 'https://www.youtube.com/watch?v=RgKAFK5djSk',
            'video_principal' => [
                'titlu' => 'Gestionarea Datoriilor: Strategii Inteligente de Eliminare',
                'url' => 'https://www.youtube.com/watch?v=RgKAFK5djSk',
                'durata' => 1320, // 22 minute
                'descriere' => 'Învață strategii dovedite pentru eliminarea datoriilor și cum să negociezi cu băncile pentru condiții mai bune.'
            ]
        ],
        5 => [
            'imagine' => 'planificare-pensie.jpg',
            'video_intro_url' => 'https://www.youtube.com/watch?v=hFDcoX7s6rE',
            'video_principal' => [
                'titlu' => 'Planificarea Pensiei: Începe de Azi pentru Mâine',
                'url' => 'https://www.youtube.com/watch?v=hFDcoX7s6rE',
                'durata' => 1560, // 26 minute
                'descriere' => 'Planifică-ți pensia de pe acum. Învață despre pensiile private, calculele necesare și strategiile pe termen lung.'
            ]
        ]
    ];

    // Actualizez cursurile cu imaginile
    foreach ($cursuri_update as $curs_id => $data) {
        $stmt = $pdo->prepare("
            UPDATE cursuri 
            SET imagine = ?, video_intro_url = ?
            WHERE id = ?
        ");
        $stmt->execute([$data['imagine'], $data['video_intro_url'], $curs_id]);
        
        echo "<p style='color: green;'>✓ Actualizat cursul ID $curs_id cu imagine și video intro</p>";
        
        // Adaug video-ul principal pentru fiecare curs
        $video_data = $data['video_principal'];
        
        // Verific dacă video-ul există deja
        $stmt = $pdo->prepare("SELECT id FROM video_cursuri WHERE curs_id = ? AND titlu = ?");
        $stmt->execute([$curs_id, $video_data['titlu']]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO video_cursuri (curs_id, titlu, descriere, url_video, durata_secunde, ordine, activ) 
                VALUES (?, ?, ?, ?, ?, 1, 1)
            ");
            $stmt->execute([
                $curs_id,
                $video_data['titlu'],
                $video_data['descriere'],
                $video_data['url'],
                $video_data['durata']
            ]);
            
            echo "<p style='color: green; margin-left: 20px;'>  ✓ Adăugat video: {$video_data['titlu']}</p>";
        } else {
            echo "<p style='color: blue; margin-left: 20px;'>  → Video-ul '{$video_data['titlu']}' există deja</p>";
        }
    }

    // 2. QUIZ-URI COMPLETE PENTRU FIECARE CURS
    
    $quiz_uri_complete = [
        1 => [
            [
                'titlu' => 'Test Evaluare: Fundamente de Bugetare',
                'descriere' => 'Evaluează-ți cunoștințele despre conceptele fundamentale ale bugetului personal și planificării financiare.',
                'timp_limita' => 20,
                'dificultate' => 'usor',
                'tip_quiz' => 'evaluare',
                'intrebari' => [
                    [
                        'intrebare' => 'Ce reprezintă regula 50/30/20 în bugetare?',
                        'tip' => 'multipla',
                        'raspunsuri' => [
                            ['raspuns' => '50% economii, 30% necesități, 20% dorințe', 'corect' => 0],
                            ['raspuns' => '50% necesități, 30% dorințe, 20% economii', 'corect' => 1],
                            ['raspuns' => '50% dorințe, 30% economii, 20% necesități', 'corect' => 0],
                            ['raspuns' => '50% investiții, 30% cheltuieli, 20% economii', 'corect' => 0]
                        ],
                        'explicatie' => 'Regula 50/30/20 alocă 50% din venit pentru necesități (chirie, mâncare), 30% pentru dorințe (entertainment) și 20% pentru economii și investiții.'
                    ],
                    [
                        'intrebare' => 'Care este primul pas în crearea unui buget?',
                        'tip' => 'multipla',
                        'raspunsuri' => [
                            ['raspuns' => 'Calcularea cheltuielilor', 'corect' => 0],
                            ['raspuns' => 'Stabilirea obiectivelor financiare', 'corect' => 0],
                            ['raspuns' => 'Calcularea veniturilor totale', 'corect' => 1],
                            ['raspuns' => 'Deschiderea unui cont de economii', 'corect' => 0]
                        ],
                        'explicatie' => 'Primul pas este să știi exact câți bani intră în buzunarul tău lunar. Fără această informație, nu poți plani eficient.'
                    ],
                    [
                        'intrebare' => 'Un buget lunar ar trebui revizuit și actualizat periodic.',
                        'tip' => 'adevar_fals',
                        'raspunsuri' => [
                            ['raspuns' => 'Adevărat', 'corect' => 1],
                            ['raspuns' => 'Fals', 'corect' => 0]
                        ],
                        'explicatie' => 'Da, bugetul este un document dinamic care trebuie ajustat în funcție de schimbările din viața ta.'
                    ],
                    [
                        'intrebare' => 'Cheltuielile variabile sunt aceleași în fiecare lună.',
                        'tip' => 'adevar_fals',
                        'raspunsuri' => [
                            ['raspuns' => 'Adevărat', 'corect' => 0],
                            ['raspuns' => 'Fals', 'corect' => 1]
                        ],
                        'explicatie' => 'Cheltuielile variabile se schimbă de la lună la lună (ex: factura la electricitate, mâncare, entertainment).'
                    ],
                    [
                        'intrebare' => 'Ce înseamnă să "plătești pe tine primul"?',
                        'tip' => 'multipla',
                        'raspunsuri' => [
                            ['raspuns' => 'Să îți cumperi lucruri scumpe', 'corect' => 0],
                            ['raspuns' => 'Să economisești înainte de a cheltui pe altceva', 'corect' => 1],
                            ['raspuns' => 'Să îți plătești datoriile întâi', 'corect' => 0],
                            ['raspuns' => 'Să îți dai salariul', 'corect' => 0]
                        ],
                        'explicatie' => 'Acest principiu înseamnă că economisirea trebuie tratată ca o factură obligatorie, prima cheltuială din buget.'
                    ]
                ]
            ],
            [
                'titlu' => 'Quiz Practic: Instrumente de Bugetare',
                'descriere' => 'Testează cunoștințele practice despre instrumentele și metodele de bugetare în situații reale.',
                'timp_limita' => 15,
                'dificultate' => 'usor',
                'tip_quiz' => 'practica',
                'intrebari' => [
                    [
                        'intrebare' => 'Care aplicație NU este recomandată pentru bugetare?',
                        'tip' => 'multipla',
                        'raspunsuri' => [
                            ['raspuns' => 'Excel', 'corect' => 0],
                            ['raspuns' => 'YNAB (You Need A Budget)', 'corect' => 0],
                            ['raspuns' => 'Instagram', 'corect' => 1],
                            ['raspuns' => 'Mint', 'corect' => 0]
                        ],
                        'explicatie' => 'Instagram este o platformă socială, nu un instrument de bugetare. Celelalte sunt instrumente financiare legitimate.'
                    ],
                    [
                        'intrebare' => 'Cât de des ar trebui să îți verifici bugetul?',
                        'tip' => 'multipla',
                        'raspunsuri' => [
                            ['raspuns' => 'O dată pe an', 'corect' => 0],
                            ['raspuns' => 'O dată pe lună', 'corect' => 0],
                            ['raspuns' => 'Săptămânal sau bi-săptămânal', 'corect' => 1],
                            ['raspuns' => 'Niciodată după ce l-ai făcut', 'corect' => 0]
                        ],
                        'explicatie' => 'Verificarea regulată (săptămânală) te ajută să rămâi pe drumul cel bun și să faci ajustări rapide.'
                    ],
                    [
                        'intrebare' => 'Bugetul pe bază zero înseamnă că cheltuiești toți banii.',
                        'tip' => 'adevar_fals',
                        'raspunsuri' => [
                            ['raspuns' => 'Adevărat', 'corect' => 0],
                            ['raspuns' => 'Fals', 'corect' => 1]
                        ],
                        'explicatie' => 'Bugetul pe bază zero înseamnă că fiecare leu are o destinație clară, inclusiv economiile. Nu înseamnă să cheltuiești tot.'
                    ]
                ]
            ]
        ],
        2 => [
            [
                'titlu' => 'Test Evaluare: Economisirea Inteligentă',
                'descriere' => 'Evaluează cunoștințele despre strategii de economisire și puterea dobânzii compuse.',
                'timp_limita' => 25,
                'dificultate' => 'mediu',
                'tip_quiz' => 'evaluare',
                'intrebari' => [
                    [
                        'intrebare' => 'Ce înseamnă dobânda compusă?',
                        'tip' => 'multipla',
                        'raspunsuri' => [
                            ['raspuns' => 'Dobânda calculată doar pe suma inițială', 'corect' => 0],
                            ['raspuns' => 'Dobânda calculată pe suma inițială plus dobânzile anterioare', 'corect' => 1],
                            ['raspuns' => 'O taxă bancară aplicată la credite', 'corect' => 0],
                            ['raspuns' => 'Dobânda care se plătește lunar', 'corect' => 0]
                        ],
                        'explicatie' => 'Dobânda compusă este "dobânda la dobândă" - se calculează pe suma inițială plus dobânzile deja câștigate.'
                    ],
                    [
                        'intrebare' => 'Un fond de urgență ar trebui să acopere cheltuielile pentru câte luni?',
                        'tip' => 'multipla',
                        'raspunsuri' => [
                            ['raspuns' => '1-2 luni', 'corect' => 0],
                            ['raspuns' => '3-6 luni', 'corect' => 1],
                            ['raspuns' => '12 luni', 'corect' => 0],
                            ['raspuns' => '24 luni', 'corect' => 0]
                        ],
                        'explicatie' => 'Experții recomandă un fond de urgență de 3-6 luni de cheltuieli pentru a acoperi situații neașteptate.'
                    ],
                    [
                        'intrebare' => 'Care este avantajul principal al economisirii automate?',
                        'tip' => 'multipla',
                        'raspunsuri' => [
                            ['raspuns' => 'Economisești mai mulți bani', 'corect' => 0],
                            ['raspuns' => 'Nu uiți să economisești', 'corect' => 1],
                            ['raspuns' => 'Primești dobândă mai mare', 'corect' => 0],
                            ['raspuns' => 'Eviți taxele bancare', 'corect' => 0]
                        ],
                        'explicatie' => 'Automatizarea elimină factorul uman și emoțional din economisire, făcând procesul consistent.'
                    ],
                    [
                        'intrebare' => 'Inflația poate eroda puterea de cumpărare a economiilor tale.',
                        'tip' => 'adevar_fals',
                        'raspunsuri' => [
                            ['raspuns' => 'Adevărat', 'corect' => 1],
                            ['raspuns' => 'Fals', 'corect' => 0]
                        ],
                        'explicatie' => 'Da, inflația face ca banii să piardă din valoare în timp, de aceea e important să investești economiile.'
                    ]
                ]
            ]
        ],
        3 => [
            [
                'titlu' => 'Test Evaluare: Bazele Investițiilor',
                'descriere' => 'Evaluează cunoștințele despre conceptele fundamentale ale investițiilor și gestionarea riscurilor.',
                'timp_limita' => 30,
                'dificultate' => 'mediu',
                'tip_quiz' => 'evaluare',
                'intrebari' => [
                    [
                        'intrebare' => 'Care este prima regulă a investițiilor conform lui Warren Buffett?',
                        'tip' => 'multipla',
                        'raspunsuri' => [
                            ['raspuns' => 'Să câștigi cât mai mult și cât mai repede', 'corect' => 0],
                            ['raspuns' => 'Să nu pierzi banii', 'corect' => 1],
                            ['raspuns' => 'Să investești tot ce ai', 'corect' => 0],
                            ['raspuns' => 'Să urmezi tendințele pieței', 'corect' => 0]
                        ],
                        'explicatie' => 'Regula #1: Nu pierde banii. Regula #2: Nu uita niciodată regula #1. Protecția capitalului vine înaintea profitului.'
                    ],
                    [
                        'intrebare' => 'Ce înseamnă diversificarea portofoliului?',
                        'tip' => 'multipla',
                        'raspunsuri' => [
                            ['raspuns' => 'Să investești doar în acțiuni', 'corect' => 0],
                            ['raspuns' => 'Să împarți investițiile pe mai multe tipuri de active', 'corect' => 1],
                            ['raspuns' => 'Să investești doar în imobiliare', 'corect' => 0],
                            ['raspuns' => 'Să ții banii doar la bancă', 'corect' => 0]
                        ],
                        'explicatie' => 'Diversificarea înseamnă să nu pui "toate ouăle în același coș" - împarți riscul pe mai multe investiții.'
                    ],
                    [
                        'intrebare' => 'Investițiile pe termen lung au istoricul de a bate inflația.',
                        'tip' => 'adevar_fals',
                        'raspunsuri' => [
                            ['raspuns' => 'Adevărat', 'corect' => 1],
                            ['raspuns' => 'Fals', 'corect' => 0]
                        ],
                        'explicatie' => 'Pe termen lung (10+ ani), investițiile diversificate au tenința să depășească rata inflației.'
                    ],
                    [
                        'intrebare' => 'Care tip de investiție este considerat cel mai riscant?',
                        'tip' => 'multipla',
                        'raspunsuri' => [
                            ['raspuns' => 'Obligațiuni de stat', 'corect' => 0],
                            ['raspuns' => 'Fonduri mutuale', 'corect' => 0],
                            ['raspuns' => 'Acțiuni individuale', 'corect' => 1],
                            ['raspuns' => 'Conturi de economii', 'corect' => 0]
                        ],
                        'explicatie' => 'Acțiunile individuale au cel mai mare risc fiindcă soarta ta depinde de o singură companie.'
                    ]
                ]
            ]
        ],
        4 => [
            [
                'titlu' => 'Test Evaluare: Gestionarea Datoriilor',
                'descriere' => 'Evaluează strategiile de gestionare și eliminare a datoriilor în mod eficient.',
                'timp_limita' => 20,
                'dificultate' => 'mediu',
                'tip_quiz' => 'evaluare',
                'intrebari' => [
                    [
                        'intrebare' => 'Care metodă de plată a datoriilor se concentrează pe dobânda cea mai mare?',
                        'tip' => 'multipla',
                        'raspunsuri' => [
                            ['raspuns' => 'Metoda bulgărelui de zăpadă', 'corect' => 0],
                            ['raspuns' => 'Metoda avalanșei', 'corect' => 1],
                            ['raspuns' => 'Metoda minimului', 'corect' => 0],
                            ['raspuns' => 'Metoda echilibrată', 'corect' => 0]
                        ],
                        'explicatie' => 'Metoda avalanșei vizează întâi datoriile cu dobânda cea mai mare, economisind bani pe termen lung.'
                    ],
                    [
                        'intrebare' => 'Este o idee bună să plătești doar minimul pe cardurile de credit.',
                        'tip' => 'adevar_fals',
                        'raspunsuri' => [
                            ['raspuns' => 'Adevărat', 'corect' => 0],
                            ['raspuns' => 'Fals', 'corect' => 1]
                        ],
                        'explicatie' => 'Plata doar a minimului înseamnă că vei plăti dobânzi uriașe și va dura foarte mult să scapi de datorie.'
                    ],
                    [
                        'intrebare' => 'Ce înseamnă consolidarea datoriilor?',
                        'tip' => 'multipla',
                        'raspunsuri' => [
                            ['raspuns' => 'Să faci mai multe datorii', 'corect' => 0],
                            ['raspuns' => 'Să combini toate datoriile într-una singură', 'corect' => 1],
                            ['raspuns' => 'Să plătești doar dobânzile', 'corect' => 0],
                            ['raspuns' => 'Să declari faliment', 'corect' => 0]
                        ],
                        'explicatie' => 'Consolidarea combină multiple datorii într-una singură, de obicei cu o dobândă mai mică și o rată simplificată.'
                    ]
                ]
            ]
        ],
        5 => [
            [
                'titlu' => 'Test Evaluare: Planificarea Pensiei',
                'descriere' => 'Evaluează cunoștințele despre planificarea pe termen lung și pregătirea pentru pensie.',
                'timp_limita' => 25,
                'dificultate' => 'mediu',
                'tip_quiz' => 'evaluare',
                'intrebari' => [
                    [
                        'intrebare' => 'La ce vârstă este ideal să începi să economisești pentru pensie?',
                        'tip' => 'multipla',
                        'raspunsuri' => [
                            ['raspuns' => 'La 40 de ani', 'corect' => 0],
                            ['raspuns' => 'Cât mai devreme posibil', 'corect' => 1],
                            ['raspuns' => 'La 30 de ani', 'corect' => 0],
                            ['raspuns' => 'Cu 10 ani înainte de pensie', 'corect' => 0]
                        ],
                        'explicatie' => 'Cu cât începi mai devreme, cu atât dobânda compusă lucrează mai mult în favoarea ta.'
                    ],
                    [
                        'intrebare' => 'Regula 4% în planificarea pensiei se referă la?',
                        'tip' => 'multipla',
                        'raspunsuri' => [
                            ['raspuns' => 'Cât să economisești anual', 'corect' => 0],
                            ['raspuns' => 'Cât poți retrage anual din economii la pensie', 'corect' => 1],
                            ['raspuns' => 'Dobânda minimă necesară', 'corect' => 0],
                            ['raspuns' => 'Taxa de administrare', 'corect' => 0]
                        ],
                        'explicatie' => 'Regula 4% sugerează că poți retrage în siguranță 4% din economiile de pensie anual, fără să rămâi fără bani.'
                    ],
                    [
                        'intrebare' => 'Pensia de stat va fi suficientă pentru majoritatea oamenilor.',
                        'tip' => 'adevar_fals',
                        'raspunsuri' => [
                            ['raspuns' => 'Adevărat', 'corect' => 0],
                            ['raspuns' => 'Fals', 'corect' => 1]
                        ],
                        'explicatie' => 'Pensia de stat acoperă de obicei doar nevoile de bază. Pentru un trai decent la pensie, trebuie să economisești suplimentar.'
                    ]
                ]
            ]
        ]
    ];

    // 3. EXERCIȚII PRACTICE PENTRU FIECARE CURS
    
    $exercitii_practice = [
        1 => [
            [
                'titlu' => 'Calculator Buget 50/30/20',
                'descriere' => 'Folosește calculatorul nostru pentru a-ți crea primul buget folosind regula 50/30/20. Introdu venitul și vezi cum se împarte automat.',
                'tip' => 'external_link',
                'link_extern' => 'instrumente.php#planificator-buget',
                'ordine' => 1
            ],
            [
                'titlu' => 'Template Excel Buget Personal',
                'descriere' => 'Descarcă template-ul nostru de buget în Excel și completează-l cu propriile tale date financiare.',
                'tip' => 'document',
                'fisier_descarcare' => 'template-buget-personal.xlsx',
                'ordine' => 2
            ],
            [
                'titlu' => 'Exercițiu: Analizează-ți Cheltuielile',
                'descriere' => 'Urmărește-ți cheltuielile timp de o săptămână și folosește calculatorul pentru a identifica zonele problematice.',
                'tip' => 'calculator',
                'ordine' => 3
            ],
            [
                'titlu' => 'Simulare Buget Familie',
                'descriere' => 'Folosește acest simulator pentru a vedea cum ar arăta bugetul unei familii tip și învață din exemplu.',
                'tip' => 'calculator',
                'ordine' => 4
            ]
        ],
        2 => [
            [
                'titlu' => 'Calculator Dobândă Compusă',
                'descriere' => 'Vezi magic-ul dobânzii compuse! Calculează cum cresc economiile tale în timp cu diferite rate de dobândă.',
                'tip' => 'external_link',
                'link_extern' => 'instrumente.php#calculator-economii',
                'ordine' => 1
            ],
            [
                'titlu' => 'Plan Economisire pe 10 ani',
                'descriere' => 'Creează-ți strategia personalizată de economisire pe 10 ani folosind principiile învățate în curs.',
                'tip' => 'calculator',
                'ordine' => 2
            ],
            [
                'titlu' => 'Ghid: 30 Trucuri de Economisire',
                'descriere' => 'Descarcă ghidul complet cu 30 de trucuri practice și imediate pentru a economisi bani în fiecare zi.',
                'tip' => 'document',
                'fisier_descarcare' => 'ghid-30-trucuri-economisire.pdf',
                'ordine' => 3
            ],
            [
                'titlu' => 'Calculator Fond de Urgență',
                'descriere' => 'Calculează exact cât ai nevoie pentru fondul de urgență bazat pe cheltuielile tale reale.',
                'tip' => 'calculator',
                'ordine' => 4
            ]
        ],
        3 => [
            [
                'titlu' => 'Simulator Portofoliu Virtual',
                'descriere' => 'Creează-ți primul portofoliu virtual pe Yahoo Finance și învață cum funcționează piața fără să riști bani reali.',
                'tip' => 'external_link',
                'link_extern' => 'https://finance.yahoo.com/portfolios',
                'ordine' => 1
            ],
            [
                'titlu' => 'Test Profil de Risc',
                'descriere' => 'Evaluează-ți toleranța la risc și află ce tip de investiții ți se potrivesc temperamentului.',
                'tip' => 'calculator',
                'ordine' => 2
            ],
            [
                'titlu' => 'Analiză Comparativă: Apple vs Microsoft',
                'descriere' => 'Învață să analizezi acțiunile comparând două gigante tech folosind instrumentele noastre.',
                'tip' => 'calculator',
                'ordine' => 3
            ],
            [
                'titlu' => 'Ghid: Primul Portofoliu în 5 Pași',
                'descriere' => 'Descarcă ghidul pas cu pas pentru construirea primului tău portofoliu de investiții.',
                'tip' => 'document',
                'fisier_descarcare' => 'ghid-primul-portofoliu.pdf',
                'ordine' => 4
            ]
        ],
        4 => [
            [
                'titlu' => 'Calculator Rate Credit și Dobânzi',
                'descriere' => 'Calculează ratele pentru diferite tipuri de credite și compară ofertele băncilor.',
                'tip' => 'external_link',
                'link_extern' => 'instrumente.php#calculator-credite',
                'ordine' => 1
            ],
            [
                'titlu' => 'Plan Personal Eliminare Datorii',
                'descriere' => 'Creează strategia ta personală pentru eliminarea datoriilor folosind metoda avalanșei sau bulgărelui.',
                'tip' => 'calculator',
                'ordine' => 2
            ],
            [
                'titlu' => 'Ghid: Negocierea cu Băncile',
                'descriere' => 'Descarcă ghidul pentru negocierea condițiilor creditelor și refinanțarea datoriilor.',
                'tip' => 'document',
                'fisier_descarcare' => 'ghid-negociere-banci.pdf',
                'ordine' => 3
            ],
            [
                'titlu' => 'Calculator Consolidare Datorii',
                'descriere' => 'Vezi dacă consolidarea datoriilor este benefică în cazul tău și cât poți economisi.',
                'tip' => 'calculator',
                'ordine' => 4
            ]
        ],
        5 => [
            [
                'titlu' => 'Calculator Pensie Privată România',
                'descriere' => 'Calculează cât trebuie să economisești lunar pentru pensia dorită folosind sistemul românesc.',
                'tip' => 'calculator',
                'ordine' => 1
            ],
            [
                'titlu' => 'Simulator FIRE (Financial Independence)',
                'descriere' => 'Calculează când poți deveni independent financiar și să te retragi timpuriu.',
                'tip' => 'calculator',
                'ordine' => 2
            ],
            [
                'titlu' => 'Ghid: Pensii Private în România',
                'descriere' => 'Tot ce trebuie să știi despre sistemul de pensii private din România și cum să alegi cel mai bun.',
                'tip' => 'document',
                'fisier_descarcare' => 'ghid-pensii-private-romania.pdf',
                'ordine' => 3
            ],
            [
                'titlu' => 'Calculator Regula 4% pentru Pensie',
                'descriere' => 'Calculează de câți bani ai nevoie pentru a te putea retrage folosind regula 4%.',
                'tip' => 'calculator',
                'ordine' => 4
            ]
        ]
    ];

    // 4. INSERARE ÎN BAZA DE DATE

    // Inserez quiz-urile complete
    foreach ($quiz_uri_complete as $curs_id => $quiz_uri) {
        echo "<h3>Procesez quiz-urile pentru cursul ID: $curs_id</h3>";
        
        foreach ($quiz_uri as $quiz_data) {
            // Verific dacă quiz-ul există deja
            $stmt = $pdo->prepare("SELECT id FROM quiz_uri WHERE titlu = ? AND curs_id = ?");
            $stmt->execute([$quiz_data['titlu'], $curs_id]);
            
            if ($stmt->fetch()) {
                echo "<p style='color: blue;'>Quiz '{$quiz_data['titlu']}' există deja - sărim peste el.</p>";
                continue;
            }
            
            // Inserez quiz-ul
            $stmt = $pdo->prepare("
                INSERT INTO quiz_uri (titlu, descriere, curs_id, timp_limita, numar_intrebari, dificultate, tip_quiz, punctaj_minim_promovare, activ) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 70, 1)
            ");
            $stmt->execute([
                $quiz_data['titlu'],
                $quiz_data['descriere'],
                $curs_id,
                $quiz_data['timp_limita'],
                count($quiz_data['intrebari']),
                $quiz_data['dificultate'],
                $quiz_data['tip_quiz']
            ]);
            
            $quiz_id = $pdo->lastInsertId();
            echo "<p style='color: green;'>✓ Quiz adăugat: {$quiz_data['titlu']} (ID: $quiz_id)</p>";
            
            // Inserez întrebările
            foreach ($quiz_data['intrebari'] as $ordine => $intrebare_data) {
                $stmt = $pdo->prepare("
                    INSERT INTO intrebari_quiz (quiz_id, intrebare, tip, punctaj, explicatie, ordine, activ) 
                    VALUES (?, ?, ?, 1, ?, ?, 1)
                ");
                $stmt->execute([
                    $quiz_id,
                    $intrebare_data['intrebare'],
                    $intrebare_data['tip'],
                    $intrebare_data['explicatie'] ?? null,
                    $ordine + 1
                ]);
                
                $intrebare_id = $pdo->lastInsertId();
                
                // Inserez răspunsurile
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
    }

    // Inserez exercițiile practice
    foreach ($exercitii_practice as $curs_id => $exercitii) {
        echo "<h3>Procesez exercițiile pentru cursul ID: $curs_id</h3>";
        
        foreach ($exercitii as $exercitiu_data) {
            // Verific dacă exercițiul există deja
            $stmt = $pdo->prepare("SELECT id FROM exercitii_cursuri WHERE titlu = ? AND curs_id = ?");
            $stmt->execute([$exercitiu_data['titlu'], $curs_id]);
            
            if ($stmt->fetch()) {
                echo "<p style='color: blue;'>Exercițiul '{$exercitiu_data['titlu']}' există deja - sărim peste el.</p>";
                continue;
            }
            
            // Inserez exercițiul
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
    }

    echo "<h2 style='color: green;'>✓ Setup complet finalizat!</h2>";
    echo "<h3>Ce s-a adăugat:</h3>";
    echo "<ul>";
    echo "<li>✓ Imagini ilustrative pentru toate cursurile</li>";
    echo "<li>✓ Video-uri principale pentru fiecare curs</li>";
    echo "<li>✓ Quiz-uri complete cu explicații detaliate</li>";
    echo "<li>✓ Exerciții practice și calculatoare</li>";
    echo "<li>✓ Materiale descărcabile (PDF, Excel)</li>";
    echo "</ul>";
    
    echo "<h3>Următorii pași:</h3>";
    echo "<ol>";
    echo "<li>📸 Adaugă imaginile în folderul <code>uploads/cursuri/</code>:</li>";
    echo "<ul>";
    echo "<li>bugetare-personal.jpg</li>";
    echo "<li>economisire-inteligenta.jpg</li>";
    echo "<li>investitii-incepatori.jpg</li>";
    echo "<li>gestionare-datorii.jpg</li>";
    echo "<li>planificare-pensie.jpg</li>";
    echo "</ul>";
    echo "<li>📄 Creează fișierele PDF și Excel pentru descărcare în <code>uploads/exercitii/</code></li>";
    echo "<li>🔧 Testează filtrarea pe pagina de cursuri</li>";
    echo "</ol>";

    echo "<p><a href='cursuri.php' class='btn btn-primary'>← Vezi cursurile actualizate</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Eroare: " . $e->getMessage() . "</p>";
}
?>