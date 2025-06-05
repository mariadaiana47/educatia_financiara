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
                        <li class="nav-item nav-item-compact">
                            <a class="nav-link nav-action-btn" href="<?= isAdminPage() ? '../quiz.php' : 'quiz.php' ?>" title="Quiz-uri">
                                <i class="fas fa-question-circle"></i>
                                <span class="nav-text">Quiz</span>
                            </a>
                        </li>

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

<style>
/* === NAVBAR LAYOUT - CENTERED DESIGN === */

/* Navbar brand section */
.navbar-brand-section {
    flex: 0 0 auto;
    min-width: 250px;
}

/* Center navigation menu */
.navbar-nav-center {
    flex: 1;
    justify-content: center;
}

/* Right navigation - compact */
.navbar-nav-right {
    flex: 0 0 auto;
    align-items: center;
    gap: 0;
    min-width: 250px;
    justify-content: flex-end;
}

/* Compact spacing for right items */
.nav-item-compact {
    margin-left: 0.5rem;
}

.nav-item-compact:first-child {
    margin-left: 0;
}

/* Action buttons styling - Ultra compact */
.nav-action-btn {
    padding: 0.4rem 0.6rem !important;
    border-radius: 6px;
    transition: all 0.3s ease;
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    min-width: 70px;
    justify-content: center;
    text-decoration: none !important;
}

.nav-action-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
    color: inherit !important;
}

.nav-action-btn i {
    font-size: 0.85rem;
    margin-right: 0.3rem !important;
    margin-bottom: 0 !important;
}

.nav-text {
    font-size: 0.8rem;
    font-weight: 500;
    white-space: nowrap;
    display: inline !important;
}

/* User button specific styling - Ultra compact */
.nav-user-btn {
    padding: 0.4rem 0.6rem !important;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    min-width: 100px;
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    text-decoration: none !important;
}

.nav-user-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
    color: inherit !important;
}

.nav-username {
    font-size: 0.8rem;
    font-weight: 500;
    max-width: 80px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: inline !important;
}

/* Compact login buttons */
.btn-nav-compact {
    padding: 0.4rem 0.6rem !important;
    font-size: 0.8rem !important;
    font-weight: 500 !important;
    border-radius: 6px !important;
    transition: all 0.3s ease !important;
    border-width: 2px !important;
    min-width: 70px !important;
}

.btn-nav-compact:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
}

.btn-outline-light.btn-nav-compact:hover {
    background: rgba(255, 255, 255, 0.9) !important;
    color: #333 !important;
}

/* Navigation buttons (Login/Register) - Balanced size */
.btn-nav {
    padding: 0.5rem 1rem !important;
    font-size: 0.85rem !important;
    font-weight: 500 !important;
    border-radius: 8px !important;
    transition: all 0.3s ease !important;
    border-width: 2px !important;
    min-width: 100px !important;
}

.btn-nav:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.btn-outline-light.btn-nav:hover {
    background: rgba(255, 255, 255, 0.9) !important;
    color: #333 !important;
}

/* === DROPDOWN STYLING === */
.dropdown-menu-custom {
    border-radius: 12px !important;
    border: none !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
    padding: 0.5rem 0 !important;
    min-width: 220px !important;
    margin-top: 0.5rem !important;
}

.dropdown-item-custom {
    padding: 0.75rem 1.25rem !important;
    font-size: 0.9rem !important;
    transition: all 0.2s ease !important;
    display: flex !important;
    align-items: center !important;
}

.dropdown-item-custom:hover {
    background: rgba(0, 0, 0, 0.05) !important;
    transform: translateX(5px) !important;
}

.dropdown-header {
    font-weight: 700 !important;
    font-size: 0.8rem !important;
    letter-spacing: 0.5px !important;
    text-transform: uppercase !important;
    padding: 0.5rem 1.25rem !important;
    margin-bottom: 0.25rem !important;
}

/* Dropdown item color hovers */
.dropdown-item-custom.text-success:hover {
    background: rgba(40, 167, 69, 0.1) !important;
    color: #28a745 !important;
}

.dropdown-item-custom.text-info:hover {
    background: rgba(23, 162, 184, 0.1) !important;
    color: #17a2b8 !important;
}

.dropdown-item-custom.text-secondary:hover {
    background: rgba(108, 117, 125, 0.1) !important;
    color: #6c757d !important;
}

.dropdown-item-custom.text-danger:hover {
    background: rgba(220, 53, 69, 0.1) !important;
    color: #dc3545 !important;
}

/* Override Bootstrap default nav-link flex behavior */
.navbar-nav .nav-link {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
}

.navbar-nav .nav-link i {
    margin-right: 0.5rem !important;
    margin-bottom: 0 !important;
}

/* Ensure text stays inline */
.navbar-nav .nav-link span {
    display: inline !important;
    white-space: nowrap !important;
}
.cart-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    border-radius: 50%;
    padding: 0.25rem;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* === USER AVATAR === */
.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
}

.nav-user-btn:hover .user-avatar {
    border-color: rgba(255, 255, 255, 0.6);
    transform: scale(1.05);
}

/* === RESPONSIVE DESIGN === */
@media (max-width: 991px) {
    .navbar-nav-center,
    .navbar-nav-right {
        margin-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 1rem;
        gap: 0.5rem;
        justify-content: flex-start;
    }
    
    .nav-item-compact {
        margin-left: 0;
        width: 100%;
    }
    
    .nav-action-btn,
    .nav-user-btn {
        width: 100%;
        justify-content: flex-start;
        padding: 0.75rem 1rem !important;
        margin-bottom: 0.5rem;
        min-width: auto;
    }
    
    .btn-nav-compact {
        width: 100%;
        margin-bottom: 0.5rem !important;
        min-width: auto;
    }
    
    .nav-username {
        display: inline !important;
        max-width: none;
    }
    
    .navbar-brand-section {
        min-width: auto;
    }
}

@media (max-width: 768px) {
    .dropdown-menu-custom {
        border-radius: 15px !important;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2) !important;
        min-width: 250px !important;
    }
    
    .dropdown-item-custom {
        padding: 1rem 1.5rem !important;
        font-size: 1rem !important;
    }
    
    .dropdown-header {
        padding: 0.75rem 1.5rem !important;
        font-size: 0.85rem !important;
    }
    
    .nav-text {
        font-size: 1rem;
    }
}

/* === LOADING SPINNER === */
.loading-spinner {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.loading-spinner.show {
    opacity: 1;
    visibility: visible;
}

/* === ACCESSIBILITY IMPROVEMENTS === */
@media (prefers-reduced-motion: reduce) {
    .nav-action-btn,
    .nav-user-btn,
    .btn-nav,
    .dropdown-item-custom,
    .user-avatar {
        transition: none !important;
    }
    
    .cart-badge {
        animation: none !important;
    }
}

/* Focus states for accessibility */
.nav-action-btn:focus,
.nav-user-btn:focus,
.btn-nav:focus {
    outline: 2px solid rgba(255, 255, 255, 0.8);
    outline-offset: 2px;
}

.dropdown-item-custom:focus {
    outline: 2px solid #007bff;
    outline-offset: -2px;
}
</style>