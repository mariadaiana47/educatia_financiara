<?php
require_once '../config.php';

// Verifică dacă utilizatorul este admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = MSG_ERROR_ACCESS_DENIED;
    redirectTo('../login.php');
}

$page_title = 'Progresul Utilizatorilor - Admin - ' . SITE_NAME;

// Filtre
$search_filter = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$course_filter = isset($_GET['course']) ? sanitizeInput($_GET['course']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';

try {
    // Statistici generale
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT u.id) as total_utilizatori,
            COUNT(DISTINCT ic.id) as total_inscrieri,
            COUNT(DISTINCT rq.id) as total_quiz_realizate,
            COUNT(DISTINCT CASE WHEN rq.promovat = 1 THEN rq.id END) as total_quiz_promovate,
            AVG(CASE WHEN rq.promovat = 1 THEN rq.procentaj END) as media_generala
        FROM users u
        LEFT JOIN inscrieri_cursuri ic ON u.id = ic.user_id
        LEFT JOIN quiz_uri q ON ic.curs_id = q.curs_id
        LEFT JOIN rezultate_quiz rq ON q.id = rq.quiz_id AND rq.user_id = u.id
        WHERE u.rol != 'admin'
    ");
    $stmt->execute();
    $statistici_generale = $stmt->fetch();

    // Construiește condițiile pentru filtrare
    $where_conditions = ["u.rol != 'admin'"];
    $params = [];
    
    if ($search_filter) {
        $where_conditions[] = "(u.nume LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search_filter%";
        $params[] = "%$search_filter%";
    }
    
    if ($course_filter) {
        $where_conditions[] = "ic.curs_id = ?";
        $params[] = $course_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);

    // Progresul utilizatorilor cu paginare
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = 12;
    $offset = ($page - 1) * $per_page;

    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.nume,
            u.email,
            u.data_inregistrare,
            COUNT(DISTINCT ic.curs_id) as cursuri_inscrise,
            COUNT(DISTINCT rq.quiz_id) as quiz_incercate,
            COUNT(DISTINCT CASE WHEN rq.promovat = 1 THEN rq.quiz_id END) as quiz_promovate,
            AVG(CASE WHEN rq.promovat = 1 THEN rq.procentaj END) as media_utilizator,
            MAX(rq.data_realizare) as ultima_activitate,
            SUM(c.pret) as valoare_cursuri
        FROM users u
        LEFT JOIN inscrieri_cursuri ic ON u.id = ic.user_id
        LEFT JOIN cursuri c ON ic.curs_id = c.id
        LEFT JOIN quiz_uri q ON c.id = q.curs_id AND q.activ = 1
        LEFT JOIN rezultate_quiz rq ON q.id = rq.quiz_id AND rq.user_id = u.id
        WHERE $where_clause
        GROUP BY u.id
        ORDER BY u.data_inregistrare DESC
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $utilizatori = $stmt->fetchAll();

    // Total utilizatori pentru paginare
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT u.id) FROM users u LEFT JOIN inscrieri_cursuri ic ON u.id = ic.user_id WHERE $where_clause");
    $stmt->execute($params);
    $total_users = $stmt->fetchColumn();
    $total_pages = ceil($total_users / $per_page);

    // Lista cursurilor pentru filtru
    $stmt = $pdo->prepare("SELECT id, titlu FROM cursuri WHERE activ = 1 ORDER BY titlu");
    $stmt->execute();
    $cursuri_lista = $stmt->fetchAll();

    // Top performeri
    $stmt = $pdo->prepare("
        SELECT 
            u.nume,
            u.email,
            COUNT(DISTINCT CASE WHEN rq.promovat = 1 THEN rq.quiz_id END) as quiz_promovate,
            AVG(CASE WHEN rq.promovat = 1 THEN rq.procentaj END) as media
        FROM users u
        INNER JOIN rezultate_quiz rq ON u.id = rq.user_id
        WHERE u.rol != 'admin' AND rq.promovat = 1
        GROUP BY u.id
        HAVING COUNT(DISTINCT CASE WHEN rq.promovat = 1 THEN rq.quiz_id END) >= 1
        ORDER BY media DESC, quiz_promovate DESC
        LIMIT 10
    ");
    $stmt->execute();
    $top_performeri = $stmt->fetchAll();

    // Activitatea recentă
    $stmt = $pdo->prepare("
        SELECT 
            rq.*,
            u.nume as user_nume,
            q.titlu as quiz_titlu,
            c.titlu as curs_titlu
        FROM rezultate_quiz rq
        INNER JOIN users u ON rq.user_id = u.id
        INNER JOIN quiz_uri q ON rq.quiz_id = q.id
        LEFT JOIN cursuri c ON q.curs_id = c.id
        WHERE u.rol != 'admin'
        ORDER BY rq.data_realizare DESC
        LIMIT 20
    ");
    $stmt->execute();
    $activitate_recenta = $stmt->fetchAll();

} catch (PDOException $e) {
    $statistici_generale = [];
    $utilizatori = [];
    $cursuri_lista = [];
    $top_performeri = [];
    $activitate_recenta = [];
    $total_users = 0;
    $total_pages = 0;
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_section = $current_page;
$cart_count = isLoggedIn() ? getCartCount() : 0;
$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitizeInput($page_title) ?></title>
    <meta name="description" content="Educația financiară pentru toți - Progresul utilizatorilor">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/style.css" rel="stylesheet">
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
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold" href="../index.php">
                <img src="../assets/logo.png" alt="<?= SITE_NAME ?>" onerror="this.style.display='none'">
                <span>Educația Financiară</span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home me-2"></i>Acasă
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../cursuri.php">
                            <i class="fas fa-graduation-cap me-2"></i>Cursuri
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../blog.php">
                            <i class="fas fa-blog me-2"></i>Blog
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../instrumente.php">
                            <i class="fas fa-calculator me-2"></i>Instrumente
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../comunitate.php">
                            <i class="fas fa-users me-2"></i>Comunitate
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../despre.php">
                            <i class="fas fa-info-circle me-2"></i>Despre
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <!-- Quiz Button -->
                        <li class="nav-item">
                            <a class="nav-link" href="../quiz.php" title="Quiz-uri">
                                <i class="fas fa-question-circle"></i>
                            </a>
                        </li>

                        <!-- Shopping Cart -->
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="../cos.php" title="Coș de cumpărături">
                                <i class="fas fa-shopping-cart"></i>
                                <?php if ($cart_count > 0): ?>
                                    <span class="cart-badge"><?= $cart_count ?></span>
                                <?php endif; ?>
                            </a>
                        </li>

                        <!-- User Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <?php if ($current_user && $current_user['avatar']): ?>
                                    <img src="<?= UPLOAD_PATH . 'avatare/' . $current_user['avatar'] ?>" alt="Avatar"
                                        class="user-avatar me-2">
                                <?php else: ?>
                                    <i class="fas fa-user-circle me-2 fs-5"></i>
                                <?php endif; ?>
                                <span class="d-none d-md-inline"><?= sanitizeInput($_SESSION['user_name'] ?? 'Utilizator') ?></span>
                            </a>
                            
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="../dashboard.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                    </a></li>
                                <li><a class="dropdown-item" href="../cursurile-mele.php">
                                        <i class="fas fa-graduation-cap me-2"></i>Cursurile Mele
                                    </a></li>
                                <li><a class="dropdown-item" href="../progres.php">
                                        <i class="fas fa-chart-line me-2"></i>Progresul Meu
                                    </a></li>
                                <?php if (isAdmin()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="dropdown-header text-primary">
                                        <i class="fas fa-crown me-2"></i>Administrator
                                    </li>
                                    <li><a class="dropdown-item text-primary active" href="admin-progres.php">
                                            <i class="fas fa-chart-area me-2"></i>Progresul Utilizatorilor
                                        </a></li>
                                    <li><a class="dropdown-item text-primary" href="dashboard-admin.php">
                                            <i class="fas fa-cogs me-2"></i>Admin Panel
                                        </a></li>
                                    <li><a class="dropdown-item text-success" href="content-manager.php">
                                            <i class="fas fa-video me-2"></i>Content Manager
                                        </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../profil.php">
                                        <i class="fas fa-user-edit me-2"></i>Profil
                                    </a></li>
                                <li><a class="dropdown-item text-danger" href="../logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Delogare
                                    </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item me-2">
                            <a class="btn btn-outline-light" href="../login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Conectare
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary" href="../register.php">
                                <i class="fas fa-user-plus me-1"></i>Înregistrare
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
.admin-progress-page {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.admin-hero {
    background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05));
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 3rem 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255,255,255,0.2);
    text-align: center;
    color: white;
}

.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.overview-card {
    background: rgba(255,255,255,0.95);
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    backdrop-filter: blur(10px);
}

.overview-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 0.5rem;
}

.overview-label {
    color: #666;
    font-weight: 500;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.section-panel {
    background: rgba(255,255,255,0.95);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    backdrop-filter: blur(10px);
}

.panel-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
}

.panel-title i {
    margin-right: 0.75rem;
    color: #667eea;
}

.filters-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 2rem;
}

.user-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
}

.user-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
}

.user-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    text-decoration: none;
    color: inherit;
}

.user-header {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.user-avatar {
    width: 50px;
    height: 50px;
    background: #667eea;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-right: 1rem;
    font-size: 1.2rem;
}

.user-info h6 {
    margin: 0;
    font-weight: 600;
    color: #333;
    font-size: 1.1rem;
}

.user-info small {
    color: #666;
}

.user-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-top: 1rem;
}

.stat-item {
    text-align: center;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-number {
    font-size: 1.1rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.25rem;
}

.stat-text {
    font-size: 0.8rem;
    color: #666;
    font-weight: 500;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.top-performers {
    display: grid;
    gap: 1rem;
}

.performer-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 10px;
    border-left: 4px solid #667eea;
}

.performer-rank {
    width: 30px;
    height: 30px;
    background: #667eea;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    margin-right: 1rem;
    flex-shrink: 0;
}

.performer-details {
    flex-grow: 1;
}

.performer-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
}

.performer-stats {
    font-size: 0.9rem;
    color: #666;
}

.activity-feed {
    max-height: 500px;
    overflow-y: auto;
}

.activity-entry {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.activity-entry:last-child {
    border-bottom: none;
}

.activity-time {
    font-size: 0.8rem;
    color: #666;
    white-space: nowrap;
    margin-right: 1rem;
    min-width: 100px;
}

.activity-content {
    flex-grow: 1;
}

.activity-user {
    font-weight: 600;
    color: #333;
}

.activity-action {
    color: #666;
    margin-bottom: 0.25rem;
}

.activity-result {
    font-size: 0.9rem;
}

.result-success {
    color: #28a745;
}

.result-failed {
    color: #dc3545;
}

.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 2rem;
}

.pagination .page-link {
    color: #667eea;
    border-color: #667eea;
}

.pagination .page-item.active .page-link {
    background-color: #667eea;
    border-color: #667eea;
}

@media (max-width: 768px) {
    .admin-progress-page {
        padding: 1rem;
    }
    
    .admin-hero {
        padding: 2rem 1rem;
    }
    
    .stats-overview {
        grid-template-columns: 1fr 1fr;
    }
    
    .user-grid {
        grid-template-columns: 1fr;
    }
    
    .user-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="admin-progress-page">
    <div class="container">
        <!-- Hero Section -->
        <div class="admin-hero">
            <h1>
                <i class="fas fa-chart-area me-3"></i>
                Progresul Utilizatorilor
            </h1>
            <p>Monitorizează progresul și performanța tuturor utilizatorilor platformei</p>
        </div>

        <!-- Statistici Generale -->
        <div class="stats-overview">
            <div class="overview-card">
                <div class="overview-value"><?= $statistici_generale['total_utilizatori'] ?? 0 ?></div>
                <div class="overview-label">Total Utilizatori</div>
            </div>
            <div class="overview-card">
                <div class="overview-value"><?= $statistici_generale['total_inscrieri'] ?? 0 ?></div>
                <div class="overview-label">Total Înscrieri</div>
            </div>
            <div class="overview-card">
                <div class="overview-value"><?= $statistici_generale['total_quiz_promovate'] ?? 0 ?></div>
                <div class="overview-label">Quiz-uri Promovate</div>
            </div>
            <div class="overview-card">
                <div class="overview-value"><?= $statistici_generale['media_generala'] ? number_format($statistici_generale['media_generala'], 1) . '%' : 'N/A' ?></div>
                <div class="overview-label">Media Generală</div>
            </div>
        </div>

        <!-- Filtre -->
        <div class="section-panel">
            <h2 class="panel-title">
                <i class="fas fa-filter"></i>
                Filtrează Utilizatorii
            </h2>
            
            <div class="filters-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Caută Utilizator</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?= htmlspecialchars($search_filter) ?>" 
                               placeholder="Nume sau email...">
                    </div>
                    <div class="col-md-3">
                        <label for="course" class="form-label">Curs</label>
                        <select class="form-select" id="course" name="course">
                            <option value="">Toate cursurile</option>
                            <?php foreach ($cursuri_lista as $curs): ?>
                                <option value="<?= $curs['id'] ?>" <?= $course_filter == $curs['id'] ? 'selected' : '' ?>>
                                    <?= sanitizeInput($curs['titlu']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Toți</option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Activi</option>
                            <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactivi</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Filtrează
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <!-- Lista Utilizatori -->
            <div class="col-lg-8">
                <div class="section-panel">
                    <h2 class="panel-title">
                        <i class="fas fa-users"></i>
                        Lista Utilizatori (<?= $total_users ?>)
                    </h2>
                    
                    <?php if (!empty($utilizatori)): ?>
                        <div class="user-grid">
                            <?php foreach ($utilizatori as $user): ?>
                                <?php 
                                $progres_quiz = $user['quiz_incercate'] > 0 ? ($user['quiz_promovate'] / $user['quiz_incercate']) * 100 : 0;
                                $este_activ = $user['ultima_activitate'] && (strtotime($user['ultima_activitate']) > strtotime('-30 days'));
                                ?>
                                <a href="admin-progres-user.php?user_id=<?= $user['id'] ?>" class="user-card">
                                    <div class="user-header">
                                        <div class="user-avatar">
                                            <?= strtoupper(substr($user['nume'], 0, 2)) ?>
                                        </div>
                                        <div class="user-info">
                                            <h6><?= sanitizeInput($user['nume']) ?></h6>
                                            <small><?= sanitizeInput($user['email']) ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="user-stats">
                                        <div class="stat-item">
                                            <div class="stat-number"><?= $user['cursuri_inscrise'] ?></div>
                                            <div class="stat-text">Cursuri</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-number"><?= $user['quiz_promovate'] ?>/<?= $user['quiz_incercate'] ?></div>
                                            <div class="stat-text">Quiz-uri</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-number"><?= $user['media_utilizator'] ? number_format($user['media_utilizator'], 1) . '%' : 'N/A' ?></div>
                                            <div class="stat-text">Media</div>
                                        </div>
                                        <div class="stat-item">
                                            <span class="status-badge <?= $este_activ ? 'status-active' : 'status-inactive' ?>">
                                                <?= $este_activ ? 'Activ' : 'Inactiv' ?>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Paginare -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination-wrapper">
                                <nav aria-label="Paginare utilizatori">
                                    <ul class="pagination">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                                    Anterior
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                                    Următorul
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h6>Nu s-au găsit utilizatori</h6>
                            <p class="text-muted">Ajustează filtrele pentru a găsi utilizatori</p>
                            <a href="admin-progres.php" class="btn btn-primary">
                                <i class="fas fa-refresh me-2"></i>Vezi toți utilizatorii
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Top Performeri -->
                <div class="section-panel">
                    <h3 class="panel-title">
                        <i class="fas fa-trophy"></i>
                        Top Performeri
                    </h3>
                    
                    <?php if (!empty($top_performeri)): ?>
                        <div class="top-performers">
                            <?php foreach ($top_performeri as $index => $performer): ?>
                                <div class="performer-item">
                                    <div class="performer-rank"><?= $index + 1 ?></div>
                                    <div class="performer-details">
                                        <div class="performer-name"><?= sanitizeInput($performer['nume']) ?></div>
                                        <div class="performer-stats">
                                            <?= $performer['quiz_promovate'] ?> quiz-uri • 
                                            Media: <?= number_format($performer['media'], 1) ?>%
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Nu există performeri încă.</p>
                    <?php endif; ?>
                </div>

                <!-- Activitate Recentă -->
                <div class="section-panel">
                    <h3 class="panel-title">
                        <i class="fas fa-clock"></i>
                        Activitate Recentă
                    </h3>
                    
                    <?php if (!empty($activitate_recenta)): ?>
                        <div class="activity-feed">
                            <?php foreach ($activitate_recenta as $activitate): ?>
                                <div class="activity-entry">
                                    <div class="activity-time">
                                        <?= date('d.m H:i', strtotime($activitate['data_realizare'])) ?>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-user"><?= sanitizeInput($activitate['user_nume']) ?></div>
                                        <div class="activity-action">
                                            a completat quiz-ul "<?= sanitizeInput($activitate['quiz_titlu']) ?>"
                                        </div>
                                        <div class="activity-result <?= $activitate['promovat'] ? 'result-success' : 'result-failed' ?>">
                                            <?= number_format($activitate['procentaj'], 1) ?>% - 
                                            <?= $activitate['promovat'] ? 'Promovat' : 'Nepromovat' ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Nu există activitate recentă.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <!-- About Section -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="d-flex align-items-center">
                        <img src="../assets/logo.png" alt="Logo" class="footer-logo me-2" onerror="this.style.display='none'">
                        Educația Financiară
                    </h5>
                    <p class="text-muted">
                        Misiunea noastră este să aducem educația financiară mai aproape de toată lumea,
                        ajutându-te să îți construiești o viață financiară stabilă și prosperă.
                    </p>
                    <div class="social-links">
                        <a href="#" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" title="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Navigare Rapidă</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="../index.php">
                                <i class="fas fa-home me-2"></i>Acasă
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="../cursuri.php">
                                <i class="fas fa-graduation-cap me-2"></i>Cursuri
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="../blog.php">
                                <i class="fas fa-blog me-2"></i>Blog
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="../instrumente.php">
                                <i class="fas fa-calculator me-2"></i>Instrumente
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="../comunitate.php">
                                <i class="fas fa-users me-2"></i>Comunitate
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="../despre.php">
                                <i class="fas fa-info-circle me-2"></i>Despre Noi
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Learning Resources -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Resurse de Învățare</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="../cursuri.php?nivel=incepator">
                                <i class="fas fa-star me-2"></i>Cursuri pentru Începători
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="../quiz.php">
                                <i class="fas fa-question-circle me-2"></i>Quiz-uri Interactive
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="../instrumente.php#calculator-economii">
                                <i class="fas fa-piggy-bank me-2"></i>Calculator Economii
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="../instrumente.php#calculator-credite">
                                <i class="fas fa-credit-card me-2"></i>Calculator Credite
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="../blog.php?categorie=sfaturi">
                                <i class="fas fa-lightbulb me-2"></i>Sfaturi Financiare
                            </a>
                        </li>
                        <?php if (isLoggedIn()): ?>
                            <li class="mb-2">
                                <a href="../dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard-ul Meu
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Contact & Support -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Contact & Suport</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <a href="mailto:contact@educatie-financiara.ro">
                                contact@educatie-financiara.ro
                            </a>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-phone me-2"></i>
                            <a href="tel:+40721123456">
                                +40 721 123 456
                            </a>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            București, România
                        </li>
                        <li class="mb-2 mt-3">
                            <a href="../contact.php">
                                <i class="fas fa-paper-plane me-2"></i>Formular Contact
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="../ajutor.php">
                                <i class="fas fa-question-circle me-2"></i>Centru de Ajutor
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="../termeni.php">
                                <i class="fas fa-file-contract me-2"></i>Termeni și Condiții
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="../confidentialitate.php">
                                <i class="fas fa-shield-alt me-2"></i>Politica de Confidențialitate
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Statistics Section -->
            <div class="row mt-4 pt-4 border-top border-secondary">
                <div class="col-12">
                    <div class="row text-center">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) as total_cursuri FROM cursuri WHERE activ = TRUE");
                            $total_cursuri = $stmt->fetchColumn();

                            $stmt = $pdo->query("SELECT COUNT(*) as total_utilizatori FROM users WHERE activ = TRUE");
                            $total_utilizatori = $stmt->fetchColumn();

                            $stmt = $pdo->query("SELECT COUNT(*) as total_articole FROM articole WHERE activ = TRUE");
                            $total_articole = $stmt->fetchColumn();

                            $stmt = $pdo->query("SELECT COUNT(*) as total_completari FROM inscrieri_cursuri WHERE finalizat = TRUE");
                            $total_completari = $stmt->fetchColumn();
                        } catch (PDOException $e) {
                            $total_cursuri = 5;
                            $total_utilizatori = 100;
                            $total_articole = 25;
                            $total_completari = 250;
                        }
                        ?>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="stat-number"><?= $total_cursuri ?></div>
                            <div class="stat-label">Cursuri Active</div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="stat-number"><?= number_format($total_utilizatori) ?></div>
                            <div class="stat-label">Studenți Înregistrați</div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="stat-number"><?= $total_articole ?></div>
                            <div class="stat-label">Articole Publicate</div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="stat-number"><?= number_format($total_completari) ?></div>
                            <div class="stat-label">Cursuri Finalizate</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="row mt-4 pt-4 border-top border-secondary">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        &copy; <?= date('Y') ?> Educația Financiară pentru Toți. Toate drepturile rezervate.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-muted">
                        Construit cu <i class="fas fa-heart text-danger"></i> pentru educația financiară
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" class="btn btn-primary position-fixed"
        style="bottom: 20px; right: 20px; display: none; z-index: 1000; border-radius: 50%; width: 50px; height: 50px;">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="../assets/script.js"></script>

    <!-- JavaScript pentru funcționalități comune -->
    <script>
        // Loading spinner functions
        function showLoading() {
            document.getElementById('loadingSpinner').style.display = 'block';
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').style.display = 'none';
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function (alert) {
                setTimeout(function () {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });

            // Back to top button
            const backToTopButton = document.getElementById('backToTop');

            window.addEventListener('scroll', function () {
                if (window.pageYOffset > 300) {
                    backToTopButton.style.display = 'block';
                } else {
                    backToTopButton.style.display = 'none';
                }
            });

            backToTopButton.addEventListener('click', function () {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>