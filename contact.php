<?php
// Start session dacă nu e pornită
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$page_title = 'Contact - ' . SITE_NAME;

// Inițializare variabile
$success_message = null;
$error_message = null;

// Procesează formularul de contact
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifică dacă funcția există, altfel folosește o verificare simplă
    $csrf_valid = true;
    if (function_exists('verifyCSRFToken')) {
        $csrf_valid = verifyCSRFToken($_POST['csrf_token'] ?? '');
    }
    
    if (!$csrf_valid) {
        $error_message = 'Token de securitate invalid!';
    } else {
        // Funcție simplă de sanitizare dacă nu există
        if (!function_exists('sanitizeInput')) {
            function sanitizeInput($data) {
                return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
            }
        }
        
        $nume = sanitizeInput($_POST['nume'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $subiect = sanitizeInput($_POST['subiect'] ?? '');
        $mesaj = sanitizeInput($_POST['mesaj'] ?? '');
        
        $errors = [];
        
        // Validări
        if (empty($nume)) {
            $errors[] = 'Numele este obligatoriu!';
        } elseif (strlen($nume) < 2) {
            $errors[] = 'Numele trebuie să aibă minim 2 caractere!';
        }
        
        if (empty($email)) {
            $errors[] = 'Email-ul este obligatoriu!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresa de email nu este validă!';
        }
        
        if (empty($subiect)) {
            $errors[] = 'Subiectul este obligatoriu!';
        } elseif (strlen($subiect) < 5) {
            $errors[] = 'Subiectul trebuie să aibă minim 5 caractere!';
        }
        
        if (empty($mesaj)) {
            $errors[] = 'Mesajul este obligatoriu!';
        } elseif (strlen($mesaj) < 10) {
            $errors[] = 'Mesajul trebuie să aibă minim 10 caractere!';
        }
        
        if (empty($errors)) {
            try {
                // Conectează la baza de date dacă nu e deja conectată
                if (!isset($db)) {
                    // Încearcă să folosești variabilele de conexiune din config
                    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
                        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                        $db = new PDO($dsn, DB_USER, DB_PASS, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                        ]);
                    } else {
                        // Fallback pentru alte tipuri de conexiune
                        global $db, $pdo, $connection, $conn;
                        if (isset($pdo)) $db = $pdo;
                        elseif (isset($connection)) $db = $connection;
                        elseif (isset($conn)) $db = $conn;
                    }
                }
                
                // Verifică din nou dacă avem conexiunea
                if (isset($db) && ($db instanceof PDO || $db instanceof mysqli)) {
                    
                    if ($db instanceof PDO) {
                        // Folosește PDO
                        $stmt = $db->prepare("
                            INSERT INTO contact_messages (nume, email, subiect, mesaj, ip_address, user_agent, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                        $current_time = date('Y-m-d H:i:s');
                        
                        $result = $stmt->execute([
                            $nume,
                            $email, 
                            $subiect,
                            $mesaj,
                            $ip_address,
                            $user_agent,
                            $current_time
                        ]);
                        
                        if ($result) {
                            $message_id = $db->lastInsertId();
                            $success_message = "Mesajul tău a fost trimis cu succes! Îți vom răspunde în curând. Numărul de referință: #{$message_id}";
                            $_POST = [];
                        } else {
                            $error_info = $stmt->errorInfo();
                            $error_message = 'Eroare la salvarea mesajului: ' . $error_info[2];
                        }
                    } else {
                        // Folosește MySQLi
                        $stmt = $db->prepare("INSERT INTO contact_messages (nume, email, subiect, mesaj, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                        $current_time = date('Y-m-d H:i:s');
                        
                        $stmt->bind_param('sssssss', $nume, $email, $subiect, $mesaj, $ip_address, $user_agent, $current_time);
                        
                        if ($stmt->execute()) {
                            $message_id = $db->insert_id;
                            $success_message = "Mesajul tău a fost trimis cu succes! Îți vom răspunde în curând. Numărul de referință: #{$message_id}";
                            $_POST = [];
                        } else {
                            $error_message = 'Eroare la salvarea mesajului: ' . $stmt->error;
                        }
                    }
                } else {
                    $error_message = 'Nu s-a putut conecta la baza de date. Te rugăm să încerci din nou.';
                }
                
            } catch (Exception $e) {
                $error_message = 'Eroare la trimiterea mesajului: ' . $e->getMessage();
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
}

// Funcție pentru CSRF token dacă nu există
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

include 'components/header.php';
?>

<div class="container-fluid">
    <!-- Hero Section -->
    <section class="contact-hero bg-gradient-primary text-white py-5 mb-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">
                        Ia legătura cu <span class="text-warning">noi</span>
                    </h1>
                    <p class="lead mb-4">
                        Ai întrebări despre educația financiară? Vrei să ne sugerezi un subiect? 
                        Sau pur și simplu vrei să ne spui ce părere ai despre platformă?
                    </p>
                    <div class="contact-stats d-flex flex-wrap gap-3 mb-4">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <span class="stat-text">Răspundem în 24h</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="stat-content">
                                <span class="stat-text">100% Confidențial</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="stat-content">
                                <span class="stat-text">Mereu aici pentru tine</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="contact-illustration">
                        <i class="fas fa-envelope display-1 text-warning mb-3"></i>
                        <div class="floating-elements">
                            <i class="fas fa-comment-dots floating-1"></i>
                            <i class="fas fa-question-circle floating-2"></i>
                            <i class="fas fa-lightbulb floating-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Formular Contact și Info -->
    <section class="py-5 mb-5">
        <div class="container">
            <!-- Toast Notifications -->
            <?php if (!empty($success_message)): ?>
            <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
                <div class="toast show align-items-center text-white bg-success border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
                <div class="toast show align-items-center text-white bg-danger border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-exclamation-circle me-2"></i><?= $error_message ?>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Success/Error Alerts în pagină -->
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Succes!</strong> <?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Eroare!</strong> <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="row g-5">
                <!-- Formular Contact -->
                <div class="col-lg-8">
                    <div class="contact-form-card">
                        <div class="form-header mb-4">
                            <h2 class="h3 mb-2">
                                <i class="fas fa-paper-plane text-primary me-3"></i>Trimite-ne un mesaj
                            </h2>
                            <p class="text-muted">
                                Completează formularul de mai jos și îți vom răspunde cât mai curând posibil.
                            </p>
                        </div>
                        
                        <form method="POST" id="contactForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" 
                                               class="form-control" 
                                               id="nume" 
                                               name="nume" 
                                               placeholder="Numele tău"
                                               value="<?= isset($_POST['nume']) && empty($success_message) ? sanitizeInput($_POST['nume']) : '' ?>" 
                                               required>
                                        <label for="nume">
                                            <i class="fas fa-user me-2"></i>Numele tău *
                                        </label>
                                        <div class="invalid-feedback">
                                            Te rugăm să introduci numele tău.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" 
                                               class="form-control" 
                                               id="email" 
                                               name="email" 
                                               placeholder="Email-ul tău"
                                               value="<?= isset($_POST['email']) && empty($success_message) ? sanitizeInput($_POST['email']) : '' ?>" 
                                               required>
                                        <label for="email">
                                            <i class="fas fa-envelope me-2"></i>Email-ul tău *
                                        </label>
                                        <div class="invalid-feedback">
                                            Te rugăm să introduci o adresă de email validă.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-floating">
                                    <select class="form-select" id="subiect" name="subiect" required>
                                        <option value="">Selectează subiectul...</option>
                                        <option value="Întrebare generală" <?= (isset($_POST['subiect']) && $_POST['subiect'] === 'Întrebare generală' && empty($success_message)) ? 'selected' : '' ?>>
                                            Întrebare generală
                                        </option>
                                        <option value="Probleme cu cursurile" <?= (isset($_POST['subiect']) && $_POST['subiect'] === 'Probleme cu cursurile' && empty($success_message)) ? 'selected' : '' ?>>
                                            Probleme cu cursurile
                                        </option>
                                        <option value="Probleme tehnice" <?= (isset($_POST['subiect']) && $_POST['subiect'] === 'Probleme tehnice' && empty($success_message)) ? 'selected' : '' ?>>
                                            Probleme tehnice
                                        </option>
                                        <option value="Sugestii îmbunătățiri" <?= (isset($_POST['subiect']) && $_POST['subiect'] === 'Sugestii îmbunătățiri' && empty($success_message)) ? 'selected' : '' ?>>
                                            Sugestii și îmbunătățiri
                                        </option>
                                        <option value="Colaborare" <?= (isset($_POST['subiect']) && $_POST['subiect'] === 'Colaborare' && empty($success_message)) ? 'selected' : '' ?>>
                                            Colaborare
                                        </option>
                                        <option value="Altceva" <?= (isset($_POST['subiect']) && $_POST['subiect'] === 'Altceva' && empty($success_message)) ? 'selected' : '' ?>>
                                            Altceva
                                        </option>
                                    </select>
                                    <label for="subiect">
                                        <i class="fas fa-tag me-2"></i>Subiectul mesajului *
                                    </label>
                                    <div class="invalid-feedback">
                                        Te rugăm să selectezi un subiect.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-floating">
                                    <textarea class="form-control" 
                                              id="mesaj" 
                                              name="mesaj" 
                                              placeholder="Mesajul tău"
                                              style="height: 150px" 
                                              required><?= isset($_POST['mesaj']) && empty($success_message) ? sanitizeInput($_POST['mesaj']) : '' ?></textarea>
                                    <label for="mesaj">
                                        <i class="fas fa-comment me-2"></i>Mesajul tău *
                                    </label>
                                    <div class="invalid-feedback">
                                        Te rugăm să introduci mesajul tău.
                                    </div>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Minimum 10 caractere. Fii cât mai specific pentru a-ți putea răspunde mai bine.
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Trimite Mesajul
                                </button>
                                <small class="text-muted text-center">
                                    <i class="fas fa-lock me-1"></i>
                                    Informațiile tale sunt protejate și nu vor fi partajate cu terți.
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Informații Contact -->
                <div class="col-lg-4">
                    <div class="contact-info-card mb-4">
                        <h3 class="h4 mb-4">
                            <i class="fas fa-info-circle text-primary me-2"></i>Informații Contact
                        </h3>
                        
                        <div class="contact-item mb-4">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h5>Email</h5>
                                <p class="text-muted mb-0">contact@educatie-financiara.ro</p>
                                <small class="text-success">
                                    <i class="fas fa-check-circle me-1"></i>Răspundem în 24h
                                </small>
                            </div>
                        </div>
                        
                        <div class="contact-item mb-4">
                            <div class="contact-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="contact-details">
                                <h5>Comunitate</h5>
                                <p class="text-muted mb-2">Alătură-te discuțiilor din comunitate</p>
                                <a href="comunitate.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-users me-1"></i>Vezi Comunitatea
                                </a>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <div class="contact-details">
                                <h5>Întrebări Frecvente</h5>
                                <p class="text-muted mb-2">Poate găsești răspunsul aici</p>
                                <a href="#faq" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-search me-1"></i>Vezi FAQ
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Program de lucru -->
                    <div class="schedule-card">
                        <h4 class="h5 mb-3">
                            <i class="fas fa-clock text-warning me-2"></i>Program Răspunsuri
                        </h4>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-circle text-success me-2" style="font-size: 0.5rem;"></i>
                                <strong>Luni - Vineri:</strong> 9:00 - 18:00
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-circle text-warning me-2" style="font-size: 0.5rem;"></i>
                                <strong>Sâmbătă:</strong> 10:00 - 14:00
                            </li>
                            <li>
                                <i class="fas fa-circle text-danger me-2" style="font-size: 0.5rem;"></i>
                                <strong>Duminică:</strong> Închis
                            </li>
                        </ul>
                        <small class="text-muted mt-3 d-block">
                            <i class="fas fa-info-circle me-1"></i>
                            În weekend răspundem doar la urgențe.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-5 mb-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="h1 mb-4">
                    <i class="fas fa-question-circle text-primary me-3"></i>Întrebări Frecvente
                </h2>
                <p class="lead text-muted">
                    Răspunsuri la cele mai comune întrebări
                </p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Cum pot să îmi creez un cont?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Pentru a-ți crea un cont, apasă pe butonul "Înregistrare" din dreapta sus a paginii. 
                                    Completează formularul cu datele tale și vei primi un email de confirmare. 
                                    După confirmarea email-ului, poți să te conectezi și să accesezi toate cursurile.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Cursurile sunt gratuite?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Oferim atât cursuri gratuite, cât și cursuri premium. Cursurile de bază și instrumentele 
                                    financiare sunt gratuite pentru toți utilizatorii. Cursurile avansate și personalizate 
                                    au o taxă pentru a acoperi costurile de dezvoltare și menținere.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Cum funcționează cursurile?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Cursurile sunt structurate în module cu video-uri, exerciții practice și teste de evaluare. 
                                    Poți învăța în ritmul tău și îți salvăm progresul. La sfârșitul fiecărui curs primești 
                                    un certificat de participare dacă obții nota minimă.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    Pot să îmi anulez contul?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Da, poți să îți ștergi contul oricând din secțiunea "Setări cont" din dashboard. 
                                    Toate datele tale vor fi șterse permanent după 30 de zile. Dacă ai cursuri plătite, 
                                    îți vei păstra accesul până la sfârșitul perioadei pentru care ai plătit.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    Cum pot să contribui la comunitate?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Poți contribui prin participarea în discuțiile din forum, prin crearea de conținut util 
                                    pentru alți membrii, sau prin raportarea eventualelor probleme. Dacă ai expertiză în 
                                    domeniul financiar, ne poți contacta pentru colaborări.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 bg-gradient-success text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="h2 mb-3">Nu ai găsit răspunsul la întrebarea ta?</h3>
                    <p class="lead mb-0">
                        Nu ezita să ne contactezi! Suntem aici să te ajutăm cu orice ai nevoie.
                    </p>
                </div>
                <div class="col-lg-4 text-end">
                    <a href="#contactForm" class="btn btn-light btn-lg me-3">
                        <i class="fas fa-envelope me-2"></i>Scrie-ne
                    </a>
                    <a href="comunitate.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-users me-2"></i>Comunitate
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
/* Contact Hero Section */
.contact-hero {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
    position: relative;
    overflow: hidden;
}

.contact-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 70% 30%, rgba(248, 193, 70, 0.1) 0%, transparent 50%);
}

.contact-stats .stat-item {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 1rem 1.5rem;
    border: 2px solid rgba(248, 193, 70, 0.3);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
    min-width: 200px;
}

.contact-stats .stat-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    border-color: #f8c146;
    background: white;
}

.stat-icon {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #f8c146 0%, #ffa726 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(248, 193, 70, 0.3);
}

.stat-icon i {
    color: white;
    font-size: 1.1rem;
}

.stat-content {
    flex: 1;
}

.stat-text {
    color: #2c5aa0;
    font-weight: 600;
    font-size: 0.95rem;
}

/* Contact Illustration */
.contact-illustration {
    position: relative;
}

.floating-elements {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
}

.floating-elements i {
    position: absolute;
    color: rgba(248, 193, 70, 0.6);
    animation: floatContact 4s ease-in-out infinite;
}

.floating-1 {
    top: 20%;
    left: 20%;
    animation-delay: 0s;
}

.floating-2 {
    top: 60%;
    right: 30%;
    animation-delay: 1s;
    font-size: 1.5rem;
}

.floating-3 {
    bottom: 30%;
    left: 30%;
    animation-delay: 2s;
    font-size: 1.2rem;
}

@keyframes floatContact {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    33% { transform: translateY(-15px) rotate(5deg); }
    66% { transform: translateY(-5px) rotate(-5deg); }
}

/* Contact Form Card */
.contact-form-card {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    border: 1px solid #f0f0f0;
}

.form-floating > label {
    color: #6c757d;
}

.form-control:focus,
.form-select:focus {
    border-color: #2c5aa0;
    box-shadow: 0 0 0 0.2rem rgba(44, 90, 160, 0.25);
}

.form-control:focus + label,
.form-select:focus + label {
    color: #2c5aa0;
}

/* Contact Info Card */
.contact-info-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    border: 1px solid #f0f0f0;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.contact-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.contact-icon i {
    color: white;
    font-size: 1.2rem;
}

.contact-details h5 {
    color: #2c5aa0;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

/* Schedule Card */
.schedule-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid #dee2e6;
}

/* FAQ Accordion */
.accordion-item {
    border: none;
    margin-bottom: 1rem;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.accordion-button {
    background: white;
    border: none;
    font-weight: 600;
    color: #2c5aa0;
    padding: 1.25rem 1.5rem;
}

.accordion-button:not(.collapsed) {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
    color: white;
    box-shadow: none;
}

.accordion-button:focus {
    border-color: transparent;
    box-shadow: 0 0 0 0.2rem rgba(44, 90, 160, 0.25);
}

.accordion-body {
    padding: 1.5rem;
    background: #f8f9fa;
    color: #495057;
    line-height: 1.6;
}

/* Button Animations */
.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
}

/* Gradient Backgrounds */
.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

/* Toast Notifications */
.toast-container {
    z-index: 9999;
}

.toast {
    min-width: 350px;
    backdrop-filter: blur(10px);
}

.toast .toast-body {
    font-weight: 500;
}

/* Alert Animations */
.alert {
    animation: slideDown 0.3s ease-out;
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.alert-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.alert-danger {
    background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%);
    color: white;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .contact-hero .display-4 {
        font-size: 2rem;
    }
    
    .contact-form-card,
    .contact-info-card {
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .contact-stats {
        flex-direction: column;
        gap: 0.5rem !important;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .floating-elements {
        display: none;
    }
}

/* Smooth Scrolling */
html {
    scroll-behavior: smooth;
}
</style>

<script>
// Validare formular în timp real
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contactForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    // Validare pentru fiecare input
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });
    
    // Validare la submit
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            e.stopPropagation();
            
            // Scroll la primul câmp invalid
            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
            }
        }
        
        form.classList.add('was-validated');
    });
    
    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        
        // Reset classes
        field.classList.remove('is-valid', 'is-invalid');
        
        if (field.hasAttribute('required') && !value) {
            isValid = false;
        } else if (field.type === 'email' && value && !isValidEmail(value)) {
            isValid = false;
        } else if (field.name === 'nume' && value && value.length < 2) {
            isValid = false;
        } else if (field.name === 'mesaj' && value && value.length < 10) {
            isValid = false;
        }
        
        if (isValid && value) {
            field.classList.add('is-valid');
        } else if (!isValid) {
            field.classList.add('is-invalid');
        }
        
        return isValid;
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Auto-resize pentru textarea
    const textarea = document.getElementById('mesaj');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    }
    
    // Smooth scroll pentru linkurile interne
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Animație pentru statistici în hero
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'slideInUp 0.6s ease forwards';
            }
        });
    }, observerOptions);
    
    // Observă elementele care trebuie animate
    document.querySelectorAll('.contact-stats .stat-item').forEach(item => {
        observer.observe(item);
    });
    
    // Auto-dismiss toast după 5 secunde
    setTimeout(() => {
        const toasts = document.querySelectorAll('.toast');
        toasts.forEach(toast => {
            toast.style.opacity = '0';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 300);
        });
    }, 5000);
    
    // Auto-dismiss alert după 7 secunde
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 300);
        });
    }, 7000);
    
    // Loading state pentru butonul de submit
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    form.addEventListener('submit', function() {
        if (form.checkValidity()) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Se trimite...';
            
            // Restore după 5 secunde dacă nu se redirecționează
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 5000);
        }
    });
});

// Funcție pentru afișarea notificărilor (dacă nu există deja)
function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 'alert-info';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove după 5 secunde
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Animație CSS pentru elementele care apar
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .contact-stats .stat-item {
        opacity: 0;
        transform: translateY(30px);
    }
`;
document.head.appendChild(style);
</script>

<?php include 'components/footer.php'; ?>