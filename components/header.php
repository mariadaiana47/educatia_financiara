<?php
// Determină pagina curentă și secțiunea
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_section = $current_page;

// Obține numărul de items din coș (0 pentru admin)
$cart_count = isLoggedIn() && !isAdmin() ? getCartCount() : 0;

// Obține datele utilizatorului curent
$current_user = getCurrentUser();

// Funcție helper pentru a detecta dacă suntem pe o pagină admin
function isAdminPage() {
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
    return $current_dir === 'admin';
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitizeInput($page_title) ?></title>
    <meta name="description" content="Educația financiară pentru toți - Învață să îți gestionezi banii inteligent">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= isAdminPage() ? '../assets/style.css' : 'assets/style.css' ?>" rel="stylesheet">
    
    <?php if (isAdminPage()): ?>
        <!-- CSS specific pentru paginile admin -->
        <link href="../assets/admin-responsive.css" rel="stylesheet">
    <?php endif; ?>
</head>

<body>
    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Se încarcă...</span>
            </div>
        </div>
    </div>

    <!-- Main Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark main-navbar sticky-top">
        <div class="container-fluid">
            <!-- Left Side - Logo -->
            <div class="navbar-brand-section">
                <a class="navbar-brand d-flex align-items-center fw-bold" href="<?= isAdminPage() ? '../index.php' : 'index.php' ?>">
                    <img src="<?= isAdminPage() ? '../assets/logo.png' : 'assets/logo.png' ?>" alt="<?= SITE_NAME ?>" onerror="this.style.display='none'">
                    <span>Educația Financiară</span>
                </a>
            </div>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Center Navigation Menu -->
                <ul class="navbar-nav navbar-nav-center mx-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_section === 'index' ? 'active' : '' ?>" href="<?= isAdminPage() ? '../index.php' : 'index.php' ?>">
                            <i class="fas fa-home me-2"></i>Acasă
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_section === 'cursuri' ? 'active' : '' ?>" href="<?= isAdminPage() ? '../cursuri.php' : 'cursuri.php' ?>">
                            <i class="fas fa-graduation-cap me-2"></i>Cursuri
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_section === 'blog' ? 'active' : '' ?>" href="<?= isAdminPage() ? '../blog.php' : 'blog.php' ?>">
                            <i class="fas fa-blog me-2"></i>Blog
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_section === 'instrumente' ? 'active' : '' ?>" href="<?= isAdminPage() ? '../instrumente.php' : 'instrumente.php' ?>">
                            <i class="fas fa-calculator me-2"></i>Instrumente
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_section === 'comunitate' ? 'active' : '' ?>" href="<?= isAdminPage() ? '../comunitate.php' : 'comunitate.php' ?>">
                            <i class="fas fa-users me-2"></i>Comunitate
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_section === 'despre' ? 'active' : '' ?>" href="<?= isAdminPage() ? '../despre.php' : 'despre.php' ?>">
                            <i class="fas fa-info-circle me-2"></i>Despre
                        </a>
                    </li>
                </ul>

                <!-- Right Side Navigation - Compact -->
                <ul class="navbar-nav navbar-nav-right">
                    <?php if (isLoggedIn()): ?>
                        <!-- Quiz Button -->
                        <?php if (isLoggedIn() && !isAdmin()): ?>
                            <li class="nav-item nav-item-compact">
                                <a class="nav-link nav-action-btn" href="<?= isAdminPage() ? '../quiz.php' : 'quiz.php' ?>" title="Quiz-uri">
                                    <i class="fas fa-question-circle"></i>
                                    <span class="nav-text">Quiz</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Shopping Cart - DOAR pentru utilizatorii normali -->
                        <?php if (!isAdmin()): ?>
                            <li class="nav-item nav-item-compact">
                                <a class="nav-link nav-action-btn position-relative" href="<?= isAdminPage() ? '../cos.php' : 'cos.php' ?>" title="Coș de cumpărături">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span class="nav-text">Coș</span>
                                    <?php if ($cart_count > 0): ?>
                                        <span class="cart-badge"><?= $cart_count ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- User Dropdown -->
                        <li class="nav-item dropdown nav-item-compact">
                            <a class="nav-link dropdown-toggle nav-user-btn d-flex align-items-center" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <?php 
                                // Pentru administratori - mereu iconița default
                                if (isAdmin()) {
                                    echo '<i class="fas fa-user-circle me-2 fs-5"></i>';
                                } else {
                                    // Pentru utilizatorii normali - încearcă să afișeze avatar-ul
                                    if ($current_user && !empty($current_user['avatar'])) {
                                        // Verificăm dacă suntem pe o pagină admin sau nu
                                        $base_path = isAdminPage() ? '../' : '';
                                        $avatar_full_path = $base_path . 'uploads/avatare/' . $current_user['avatar'];
                                        
                                        // Verificăm dacă fișierul există
                                        if (file_exists($avatar_full_path)) {
                                            echo '<img src="' . $avatar_full_path . '" alt="Avatar" class="user-avatar me-2">';
                                        } else {
                                            echo '<i class="fas fa-user-circle me-2 fs-5"></i>';
                                        }
                                    } else {
                                        echo '<i class="fas fa-user-circle me-2 fs-5"></i>';
                                    }
                                }
                                ?>
                                <span class="nav-username d-none d-lg-inline"><?= sanitizeInput($_SESSION['user_name'] ?? 'Utilizator') ?></span>
                            </a>
                            
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom">
                                <?php if (isAdmin()): ?>
                                    <!-- Meniu specific pentru admin - SIMPLIFICAT -->
                                    <li class="dropdown-header text-primary">
                                        <i class="fas fa-crown me-2"></i>Administrator
                                    </li>
                                    <li><a class="dropdown-item dropdown-item-custom text-success" href="<?= isAdminPage() ? 'content-manager.php' : 'admin/content-manager.php' ?>">
                                            <i class="fas fa-video me-3"></i>Content Manager
                                        </a></li>
                                    <li><a class="dropdown-item dropdown-item-custom text-info" href="<?= isAdminPage() ? 'utilizatori.php' : 'admin/utilizatori.php' ?>">
                                            <i class="fas fa-users me-3"></i>Gestionare Utilizatori
                                        </a></li>
                                    <li><a class="dropdown-item dropdown-item-custom text-info" href="<?= isAdminPage() ? '../admin-contact.php' : 'admin-contact.php' ?>">
                                            <i class="fa-solid fa-message me-3"></i> Mesaje Contact 
                                        </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="dropdown-header">
                                        <i class="fas fa-user me-2"></i>Cont Personal
                                    </li>
                                    <li><a class="dropdown-item dropdown-item-custom" href="<?= isAdminPage() ? 'admin-profile.php' : 'admin/admin-profile.php' ?>">
                                            <i class="fas fa-user-edit me-3"></i>Profil
                                        </a></li>
                                    <!-- Vedere ca utilizator -->
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item dropdown-item-custom text-secondary" href="<?= isAdminPage() ? '../cursuri.php' : 'cursuri.php' ?>">
                                            <i class="fas fa-eye me-3"></i>Vedere Utilizator
                                        </a></li>
                                <?php else: ?>
                                    <!-- Meniu standard pentru utilizatori -->
                                    <li><a class="dropdown-item dropdown-item-custom" href="<?= isAdminPage() ? '../profil.php' : 'profil.php' ?>">
                                            <i class="fas fa-user-edit me-3"></i>Profil
                                        </a></li>
                                    <li><a class="dropdown-item dropdown-item-custom" href="<?= isAdminPage() ? '../dashboard.php' : 'dashboard.php' ?>">
                                            <i class="fas fa-chart-line me-3"></i>Progresul meu
                                        </a></li>
                                <?php endif; ?>
                                
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item dropdown-item-custom text-danger" href="<?= isAdminPage() ? '../logout.php' : 'logout.php' ?>">
                                        <i class="fas fa-sign-out-alt me-3"></i>Delogare
                                    </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Login/Register Buttons - Compact -->
                        <li class="nav-item nav-item-compact">
                            <a class="btn btn-outline-light btn-nav-compact" href="<?= isAdminPage() ? '../login.php' : 'login.php' ?>">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item nav-item-compact">
                            <a class="btn btn-primary btn-nav-compact" href="<?= isAdminPage() ? '../register.php' : 'register.php' ?>">
                                <i class="fas fa-user-plus me-1"></i>Signup
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="main-content">
        <!-- Display Session Messages -->
        <?php echo displaySessionMessages(); ?>