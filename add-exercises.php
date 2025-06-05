<?php
require_once 'config.php';

// Verifică dacă utilizatorul este admin
if (!isLoggedIn() || !isAdmin()) {
    die('Acces interzis. Doar administratorii pot rula acest script.');
}

$page_title = 'Adăugare Exerciții - ' . SITE_NAME;

$messages = [];
$errors = [];

// Procesează adăugarea exercițiilor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_exercises'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Token CSRF invalid.';
    } else {
        try {
            // Definirea exercițiilor pentru fiecare curs
            $exercises_data = [
                // CURS 2: Economisirea Inteligentă
                2 => [
                    ['Calculator Dobândă Compusă Personal', 'Calculează câți bani vei avea după 10, 20 sau 30 de ani economisind o sumă fixă lunar. Experimentează cu diferite sume și perioade.', 'calculator', 1],
                    ['Simulare: Economisire 100 lei/lună', 'Calculează impactul economisirii de doar 100 lei pe lună pe diferite perioade de timp. Vei fi surprins de rezultate!', 'calculator', 2],
                    ['Challenge: Găsește 50 lei să economisești', 'O provocare practică să identifici cheltuieli care pot fi eliminate pentru a economisi 50 lei lunar. Analizează-ți cheltuielile actuale.', 'calculator', 3],
                    ['Comparație Randamente: Bancă vs Investiții', 'Compară randamentul bancar (2-3%) cu investițiile moderate (7-8%) pe termen lung. Vezi diferența dramatică!', 'calculator', 4],
                    ['Planul Personal de Economisire pe 5 ani', 'Creează un plan structurat de economisire cu obiective concrete și etape pentru următorii 5 ani.', 'calculator', 5],
                    ['Calculatorul Regulii 72', 'Află în câți ani îți dublezi banii la diferite rate ale dobânzii folosind formula magică a regulii 72.', 'calculator', 6],
                    ['Tracking Cheltuieli: Challenge 30 de zile', 'Monitorizează și categorisează toate cheltuielile timp de o lună întreagă. Identifică zonele de economisire.', 'calculator', 7],
                    ['Simulare Fond de Urgență Personalizat', 'Calculează cât timp îți ia să creezi un fond de urgență de 3-6 luni de cheltuieli bazat pe situația ta reală.', 'calculator', 8],
                    ['Optimizare Cheltuieli Lunare', 'Analizează și optimizează cheltuielile lunare pentru a găsi cel puțin 200 lei extra de economisit fără să îți afectezi calitatea vieții.', 'calculator', 9],
                    ['Calculul Inflației: Impactul asupra Economiilor', 'Înțelege cum inflația îți afectează economiile și calculează puterea de cumpărare reală în timp.', 'calculator', 10],
                ],
                
                // CURS 3: Introducere în Investiții
                3 => [
                    ['Test Profil de Risc Investițional', 'Descoperă ce tip de investitor ești: conservator, moderat sau agresiv. Cunoaște-ți toleranța la risc.', 'calculator', 1],
                    ['Simulare Portofoliu Virtual 10.000 lei', 'Creează primul tău portofoliu virtual cu 10.000 lei și urmărește performanța pe 6 luni.', 'calculator', 2],
                    ['Analiza Comparativă: Acțiuni vs Obligațiuni', 'Compară randamentul și riscul acțiunilor față de obligațiuni pe perioade de 5, 10 și 20 de ani.', 'calculator', 3],
                    ['Calculul Diversificării Portofoliului', 'Învață să distribuii investițiile între acțiuni, obligațiuni și fonduri pentru a minimiza riscul.', 'calculator', 4],
                    ['Simulare Dollar Cost Averaging (DCA)', 'Calculează efectul investiției regulate de 200 lei lunar într-un fond de acțiuni pe 10 ani.', 'calculator', 5],
                    ['Analiza Acțiunilor: BRD vs Banca Transilvania', 'Compară două bănci românești și învață să analizezi indicatorii financiari de bază (P/E, ROE, dividende).', 'calculator', 6],
                    ['Construire ETF Portfolio Global', 'Creează un portofoliu diversificat international folosind ETF-uri pentru SUA, Europa, Asia și mercatele emergente.', 'calculator', 7],
                    ['Calculul Randamentului Real vs Nominal', 'Înțelege diferența crucială dintre randamentul nominal și cel real (ajustat cu inflația).', 'calculator', 8],
                    ['Simulare Investiție pe Termen Lung', 'Modelează o investiție de 500 lei/lună pe 20 de ani în diferite active și vezi puterea timpului.', 'calculator', 9],
                    ['Analiză Fonduri Mutuale BVB', 'Compară fondurile mutuale disponibile în România și alege cele mai potrivite pentru profilul tău.', 'calculator', 10],
                    ['Gestionarea Riscului: Stop-Loss și Take-Profit', 'Învață să îți protejezi investițiile folosind ordine de stop-loss și să îți realizezi profiturile strategic.', 'calculator', 11],
                    ['Evaluarea unei Companii: Analiza Fundamentală', 'Analizează o companie listată la BVB folosind bilanțul, contul de profit și pierderi și fluxurile de numerar.', 'calculator', 12],
                ],
                
                // CURS 5: Planificarea Pensiei
                5 => [
                    ['Calculator Necesar Pensie Personalizat', 'Calculează exact de câți bani vei avea nevoie la pensie pentru a îți menține stilul de viață actual.', 'calculator', 1],
                    ['Simulare Pilonul II vs Pilonul III', 'Compară randamentele pensiei private obligatorii (Pilonul II) cu cea facultativă (Pilonul III) pe 30 de ani.', 'calculator', 2],
                    ['Planul de Pensionare Timpurie (FIRE)', 'Calculează cât trebuie să economisești pentru a te putea pensiona la 50 de ani în loc de 65.', 'calculator', 3],
                    ['Optimizare Contribuții Deductibile Fiscal', 'Calculează beneficiul fiscal maxim al contribuțiilor la pensia privată facultativă (până la 400 EUR/an).', 'calculator', 4],
                    ['Simularea Regulii 4% pentru Pensie', 'Calculează cât capital îți trebuie pentru a retrage 4% anual și a trăi confortabil din investiții.', 'calculator', 5],
                    ['Comparația Dramatică: Start la 25 vs 35 ani', 'Vezi diferența uriașă între a începe economisirea pentru pensie la 25 de ani față de 35 de ani.', 'calculator', 6],
                    ['Strategia de Alocare pe Vârste', 'Învață cum să îți ajustezi portofoliul de pensie pe măsură ce îmbătrânești (de la agresiv la conservator).', 'calculator', 7],
                    ['Calculul Pensiei de Stat vs Private', 'Compară pensia de stat estimată cu contribuțiile tale la pilonul privat și vezi diferențele.', 'calculator', 8],
                    ['Simulare Moștenire vs Consum', 'Calculează cât poți cheltui lunar la pensie fără să lași/să lași moștenire copiilor.', 'calculator', 9],
                    ['Planificarea Fiscală pentru Pensionari', 'Înțelege implicațiile fiscale ale diferitelor surse de venit la pensie și optimizează-le.', 'calculator', 10],
                    ['Gestionarea Inflației la Pensie', 'Calculează cum să îți protejezi puterea de cumpărare pe parcursul unei pensii de 20-30 de ani.', 'calculator', 11],
                    ['Scenarii Multiple: Pensionare în Siguranță', 'Analizează diferite scenarii (criză economică, inflație mare, randamente mici) și pregătește-te pentru toate.', 'calculator', 12],
                ]
            ];

            $total_added = 0;
            $pdo->beginTransaction();

            foreach ($exercises_data as $curs_id => $exercises) {
                // Verifică dacă cursul există
                $stmt = $pdo->prepare("SELECT titlu FROM cursuri WHERE id = ? AND activ = 1");
                $stmt->execute([$curs_id]);
                $curs = $stmt->fetch();
                
                if (!$curs) {
                    $errors[] = "Cursul cu ID $curs_id nu a fost găsit sau nu este activ.";
                    continue;
                }

                // Verifică dacă exercițiile există deja
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM exercitii_cursuri WHERE curs_id = ?");
                $stmt->execute([$curs_id]);
                $existing_count = $stmt->fetchColumn();

                if ($existing_count > 0) {
                    $messages[] = "Cursul '{$curs['titlu']}' are deja $existing_count exerciții. Se omite.";
                    continue;
                }

                // Adaugă exercițiile
                $stmt = $pdo->prepare("
                    INSERT INTO exercitii_cursuri (curs_id, titlu, descriere, tip, ordine, activ, data_creare) 
                    VALUES (?, ?, ?, ?, ?, 1, NOW())
                ");

                foreach ($exercises as $exercise) {
                    list($titlu, $descriere, $tip, $ordine) = $exercise;
                    $stmt->execute([$curs_id, $titlu, $descriere, $tip, $ordine]);
                    $total_added++;
                }

                $messages[] = "S-au adăugat " . count($exercises) . " exerciții pentru cursul '{$curs['titlu']}'.";
            }

            $pdo->commit();
            $messages[] = "<strong>Total: $total_added exerciții adăugate cu succes!</strong>";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Eroare la adăugarea exercițiilor: ' . $e->getMessage();
        }
    }
}

// Verifică statusul exercițiilor existente
try {
    $stmt = $pdo->query("
        SELECT 
            c.id,
            c.titlu as curs_nume,
            COUNT(e.id) as numar_exercitii
        FROM cursuri c
        LEFT JOIN exercitii_cursuri e ON c.id = e.curs_id 
        WHERE c.id IN (2, 3, 5) AND c.activ = 1
        GROUP BY c.id, c.titlu
        ORDER BY c.id
    ");
    $current_status = $stmt->fetchAll();
} catch (PDOException $e) {
    $current_status = [];
    $errors[] = 'Eroare la verificarea statusului: ' . $e->getMessage();
}

include 'components/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-tasks me-2"></i>Adăugare Exerciții pentru Cursuri
                    </h1>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        Acest script va adăuga exerciții practice pentru următoarele cursuri:
                        <strong>Economisirea Inteligentă</strong>, 
                        <strong>Introducere în Investiții</strong> și 
                        <strong>Planificarea Pensiei</strong>.
                    </p>
                </div>
            </div>

            <!-- Messages -->
            <?php if (!empty($messages)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Succes!</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($messages as $message): ?>
                            <li><?= $message ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Erori:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Status actual -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Status Actual Exerciții
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($current_status)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID Curs</th>
                                        <th>Nume Curs</th>
                                        <th>Exerciții Existente</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($current_status as $status): ?>
                                        <tr>
                                            <td><?= $status['id'] ?></td>
                                            <td><?= sanitizeInput($status['curs_nume']) ?></td>
                                            <td>
                                                <span class="badge <?= $status['numar_exercitii'] > 0 ? 'bg-success' : 'bg-warning' ?>">
                                                    <?= $status['numar_exercitii'] ?> exerciții
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($status['numar_exercitii'] > 0): ?>
                                                    <span class="text-success">
                                                        <i class="fas fa-check-circle me-1"></i>Completat
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-warning">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>Fără exerciții
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Nu s-au putut încărca informațiile despre cursuri.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formularul de adăugare -->
            <?php 
            $need_exercises = false;
            foreach ($current_status as $status) {
                if ($status['numar_exercitii'] == 0) {
                    $need_exercises = true;
                    break;
                }
            }
            ?>

            <?php if ($need_exercises): ?>
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle me-2"></i>Adaugă Exercițiile
                        </h5>
                    </div>
                    <div class="card-body">
                        <p>Se vor adăuga următoarele exerciții:</p>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <h6><i class="fas fa-piggy-bank me-2"></i>Economisirea Inteligentă</h6>
                                <ul class="small">
                                    <li>Calculator Dobândă Compusă</li>
                                    <li>Simulare Economisire</li>
                                    <li>Challenge Economisire</li>
                                    <li>Planul de Economisire</li>
                                    <li>Regula 72</li>
                                    <li>+5 exerciții suplimentare</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6><i class="fas fa-chart-line me-2"></i>Introducere în Investiții</h6>
                                <ul class="small">
                                    <li>Test Profil de Risc</li>
                                    <li>Portofoliu Virtual</li>
                                    <li>Analiza Acțiuni vs Obligațiuni</li>
                                    <li>Diversificare Portofoliu</li>
                                    <li>Dollar Cost Averaging</li>
                                    <li>+7 exerciții suplimentare</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6><i class="fas fa-user-clock me-2"></i>Planificarea Pensiei</h6>
                                <ul class="small">
                                    <li>Calculator Necesar Pensie</li>
                                    <li>Pilonul II vs III</li>
                                    <li>Pensionare Timpurie (FIRE)</li>
                                    <li>Regula 4%</li>
                                    <li>Comparația 25 vs 35 ani</li>
                                    <li>+7 exerciții suplimentare</li>
                                </ul>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Importante:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Se vor adăuga <strong>34 exerciții în total</strong> (10 + 12 + 12)</li>
                                <li>Exercițiile vor fi adăugate doar pentru cursurile care nu au exerciții existente</li>
                                <li>Toate exercițiile vor fi de tip "calculator" pentru interactivitate</li>
                                <li>Ordinea exercițiilor va fi setată automat (1, 2, 3, etc.)</li>
                            </ul>
                        </div>

                        <form method="POST" onsubmit="return confirm('Ești sigur că vrei să adaugi exercițiile? Această acțiune nu poate fi anulată.');">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <div class="d-grid gap-2">
                                <button type="submit" name="add_exercises" class="btn btn-success btn-lg">
                                    <i class="fas fa-plus-circle me-2"></i>
                                    Adaugă Toate Exercițiile
                                </button>
                                <a href="admin/content-manager.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Înapoi la Content Manager
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>Toate Cursurile Au Exerciții
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <i class="fas fa-trophy fa-3x text-success mb-3"></i>
                        <h4>Felicitări!</h4>
                        <p class="text-muted">
                            Toate cursurile specificate au deja exerciții adăugate. 
                            Poți gestiona exercițiile existente din Content Manager.
                        </p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="admin/content-manager.php" class="btn btn-primary">
                                <i class="fas fa-cogs me-2"></i>Content Manager
                            </a>
                            <a href="cursuri.php" class="btn btn-outline-secondary">
                                <i class="fas fa-graduation-cap me-2"></i>Vezi Cursurile
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Exerciții detaliate (doar pentru informare) -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Lista Completă a Exercițiilor care vor fi Adăugate
                    </h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="exercisesAccordion">
                        
                        <!-- Economisirea Inteligentă -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingEconomisire">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapseEconomisire" aria-expanded="false" aria-controls="collapseEconomisire">
                                    <i class="fas fa-piggy-bank me-2"></i>
                                    Economisirea Inteligentă (10 exerciții)
                                </button>
                            </h2>
                            <div id="collapseEconomisire" class="accordion-collapse collapse" 
                                 aria-labelledby="headingEconomisire" data-bs-parent="#exercisesAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li><strong>Calculator Dobândă Compusă Personal</strong> - Calculează câți bani vei avea după 10, 20 sau 30 de ani</li>
                                        <li><strong>Simulare: Economisire 100 lei/lună</strong> - Calculează impactul economisirii regulate</li>
                                        <li><strong>Challenge: Găsește 50 lei să economisești</strong> - Identifică cheltuieli de eliminat</li>
                                        <li><strong>Comparație Randamente: Bancă vs Investiții</strong> - Compară randamentele pe termen lung</li>
                                        <li><strong>Planul Personal de Economisire pe 5 ani</strong> - Creează un plan structurat</li>
                                        <li><strong>Calculatorul Regulii 72</strong> - Află în câți ani îți dublezi banii</li>
                                        <li><strong>Tracking Cheltuieli: Challenge 30 de zile</strong> - Monitorizează cheltuielile</li>
                                        <li><strong>Simulare Fond de Urgență Personalizat</strong> - Calculează fondul de urgență</li>
                                        <li><strong>Optimizare Cheltuieli Lunare</strong> - Găsește 200 lei extra de economisit</li>
                                        <li><strong>Calculul Inflației: Impactul asupra Economiilor</strong> - Înțelege efectul inflației</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- Introducere în Investiții -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingInvestitii">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapseInvestitii" aria-expanded="false" aria-controls="collapseInvestitii">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Introducere în Investiții (12 exerciții)
                                </button>
                            </h2>
                            <div id="collapseInvestitii" class="accordion-collapse collapse" 
                                 aria-labelledby="headingInvestitii" data-bs-parent="#exercisesAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li><strong>Test Profil de Risc Investițional</strong> - Descoperă tipul tău de investitor</li>
                                        <li><strong>Simulare Portofoliu Virtual 10.000 lei</strong> - Creează primul portofoliu</li>
                                        <li><strong>Analiza Comparativă: Acțiuni vs Obligațiuni</strong> - Compară randamentele și riscurile</li>
                                        <li><strong>Calculul Diversificării Portofoliului</strong> - Minimizează riscul prin diversificare</li>
                                        <li><strong>Simulare Dollar Cost Averaging (DCA)</strong> - Investiția regulată 200 lei/lună</li>
                                        <li><strong>Analiza Acțiunilor: BRD vs Banca Transilvania</strong> - Compară indicatorii financiari</li>
                                        <li><strong>Construire ETF Portfolio Global</strong> - Portofoliu diversificat international</li>
                                        <li><strong>Calculul Randamentului Real vs Nominal</strong> - Diferența crucială cu inflația</li>
                                        <li><strong>Simulare Investiție pe Termen Lung</strong> - 500 lei/lună pe 20 de ani</li>
                                        <li><strong>Analiză Fonduri Mutuale BVB</strong> - Compară fondurile din România</li>
                                        <li><strong>Gestionarea Riscului: Stop-Loss și Take-Profit</strong> - Protejează investițiile</li>
                                        <li><strong>Evaluarea unei Companii: Analiza Fundamentală</strong> - Analizează o companie de la BVB</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- Planificarea Pensiei -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingPensie">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapsePensie" aria-expanded="false" aria-controls="collapsePensie">
                                    <i class="fas fa-user-clock me-2"></i>
                                    Planificarea Pensiei (12 exerciții)
                                </button>
                            </h2>
                            <div id="collapsePensie" class="accordion-collapse collapse" 
                                 aria-labelledby="headingPensie" data-bs-parent="#exercisesAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li><strong>Calculator Necesar Pensie Personalizat</strong> - Calculează necesarul pentru pensie</li>
                                        <li><strong>Simulare Pilonul II vs Pilonul III</strong> - Compară pensiile private</li>
                                        <li><strong>Planul de Pensionare Timpurie (FIRE)</strong> - Pensionează-te la 50 de ani</li>
                                        <li><strong>Optimizare Contribuții Deductibile Fiscal</strong> - Beneficiul fiscal maxim</li>
                                        <li><strong>Simularea Regulii 4% pentru Pensie</strong> - Calculul capitalului necesar</li>
                                        <li><strong>Comparația Dramatică: Start la 25 vs 35 ani</strong> - Diferența uriașă de timp</li>
                                        <li><strong>Strategia de Alocare pe Vârste</strong> - Ajustarea portofoliului în timp</li>
                                        <li><strong>Calculul Pensiei de Stat vs Private</strong> - Compară toate sursele</li>
                                        <li><strong>Simulare Moștenire vs Consum</strong> - Optimizează cheltuielile la pensie</li>
                                        <li><strong>Planificarea Fiscală pentru Pensionari</strong> - Optimizează taxele</li>
                                        <li><strong>Gestionarea Inflației la Pensie</strong> - Protejează puterea de cumpărare</li>
                                        <li><strong>Scenarii Multiple: Pensionare în Siguranță</strong> - Pregătește-te pentru toate scenariile</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.accordion-button:not(.collapsed) {
    background-color: var(--bs-primary);
    color: white;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: var(--bs-primary);
}

.card-header.bg-primary, .card-header.bg-success, .card-header.bg-info {
    border-bottom: none;
}

.btn-lg {
    font-size: 1.1rem;
    padding: 0.75rem 1.5rem;
}

ol li {
    margin-bottom: 0.5rem;
}

ol li strong {
    color: var(--bs-primary);
}
</style>

<?php include 'components/footer.php'; ?>