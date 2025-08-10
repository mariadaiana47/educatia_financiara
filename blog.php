<?php
require_once 'config.php';

$page_title = 'Blog - Educație Financiară - ' . SITE_NAME;

// Filtre
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$salvate = isset($_GET['salvate']) ? (bool)$_GET['salvate'] : false;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 6;

$where_conditions = ['a.activ = 1'];
$params = [];

// Dacă utilizatorul vrea doar articolele salvate
if ($salvate && isLoggedIn()) {
    $where_conditions[] = 'asa.user_id = ?';
    $params[] = $_SESSION['user_id'];
}

// Căutare strict după titlu
if ($search) {
    $where_conditions[] = 'a.titlu LIKE ?';
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
}

try {
    $where_clause = implode(' AND ', $where_conditions);
    
    // Query pentru articole
    if ($salvate && isLoggedIn()) {
        $query = "
            SELECT a.*, u.nume as autor_nume, asa.data_salvare
            FROM articole a
            JOIN users u ON a.autor_id = u.id
            JOIN articole_salvate asa ON a.id = asa.articol_id
            WHERE $where_clause
            ORDER BY asa.data_salvare DESC
        ";
    } else {
        $query = "
            SELECT a.*, u.nume as autor_nume
            FROM articole a
            JOIN users u ON a.autor_id = u.id
            WHERE $where_clause
            ORDER BY a.data_publicare DESC
        ";
    }
    
    // Numărul total de articole
    $count_query = str_replace('SELECT a.*, u.nume as autor_nume' . ($salvate ? ', asa.data_salvare' : ''), 'SELECT COUNT(*)', $query);
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_articles = $stmt->fetchColumn();
    
    // Articolele pentru pagina curentă
    $offset = ($page - 1) * $per_page;
    $stmt = $pdo->prepare($query . " LIMIT $per_page OFFSET $offset");
    $stmt->execute($params);
    $articole = $stmt->fetchAll();
    
    $total_pages = ceil($total_articles / $per_page);
    
    // Articole populare (pentru sidebar)
    $stmt = $pdo->query("
        SELECT a.*, u.nume as autor_nume
        FROM articole a
        JOIN users u ON a.autor_id = u.id
        WHERE a.activ = 1
        ORDER BY a.vizualizari DESC
        LIMIT 5
    ");
    $articole_populare = $stmt->fetchAll();
    
    // Articole recente (pentru sidebar)
    $stmt = $pdo->query("
        SELECT a.*, u.nume as autor_nume
        FROM articole a
        JOIN users u ON a.autor_id = u.id
        WHERE a.activ = 1
        ORDER BY a.data_publicare DESC
        LIMIT 5
    ");
    $articole_recente = $stmt->fetchAll();
    
    // Numărul de articole salvate de utilizatorul curent
    $total_saved = 0;
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM articole_salvate WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $total_saved = $stmt->fetchColumn();
    }
    
} catch (PDOException $e) {
    $articole = [];
    $articole_populare = [];
    $articole_recente = [];
    $total_articles = 0;
    $total_pages = 0;
    $total_saved = 0;
}

include 'components/header.php';
?>

<style>
/* =============================================================================
   CSS COMPLET PENTRU BLOG RESPONSIVE - FUNDAL ALB CU TEXT NEGRU
   ========================================================================== */

/* Reset și stiluri de bază pentru toate butoanele */
.btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    border: 1px solid transparent;
}

/* Dimensiuni consistente pentru butoanele din card-uri */
.card .btn-sm {
    height: 36px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    line-height: 1;
    min-width: auto;
}

/* Butonul de salvare - dimensiuni fixe */
.save-article-btn {
    width: 36px !important;
    height: 36px !important;
    padding: 0 !important;
    min-width: 36px !important;
    border-radius: 6px;
    transition: all 0.2s ease;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0;
}

.save-article-btn i {
    font-size: 1rem;
    margin: 0 !important;
}

.save-article-btn:hover {
    transform: scale(1.05);
}

.save-article-btn.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

.save-article-btn.btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
    color: white;
    transform: scale(1.05);
}

.save-article-btn.btn-outline-secondary {
    background-color: white;
    border-color: #6c757d;
    color: #6c757d;
}

.save-article-btn.btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
    transform: scale(1.05);
}

/* Container pentru butoanele din card - COMPLET RESPONSIVE */
.card-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    margin-top: 1rem;
}

.card-date {
    flex: 1;
    min-width: 0;
    font-size: 0.8125rem;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.card-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-shrink: 0;
}

/* Butonul principal "Citește" */
.btn-read-more {
    height: 36px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    line-height: 1;
    border-radius: 6px;
    white-space: nowrap;
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

.btn-read-more:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
    color: white;
}

/* Card styling - FUNDAL ALB */
.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid rgba(0,0,0,0.125);
    border-radius: 8px;
    background-color: white;
    color: #212529;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
}

.card-img-top {
    border-radius: 8px 8px 0 0;
}

.card-body {
    background-color: white;
    color: #212529;
}

.card-title a {
    color: #212529 !important;
}

.card-title a:hover {
    color: #0d6efd !important;
}

.card-text {
    color: #6c757d !important;
}

/* Meta informații - RESPONSIVE */
.card-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 0.8125rem;
    color: #6c757d;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.card-meta > div {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    white-space: nowrap;
}

/* Loading state pentru butonul de salvare */
.save-article-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

.save-article-btn .fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Notificări responsive */
.notification-custom {
    animation: slideInRight 0.3s ease;
    background-color: white;
    color: #212529;
    border: 1px solid rgba(0,0,0,0.125);
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Badge uniform */
.badge {
    font-size: 0.75rem;
    padding: 0.375em 0.5em;
}

/* Paginare responsive */
.pagination {
    margin-bottom: 0;
}

.page-link {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    background-color: white;
    color: #0d6efd;
    border-color: #dee2e6;
}

/* Saved count animation */
.saved-articles-count {
    transition: all 0.3s ease;
}

/* Stiluri pentru filtrare îmbunătățită */
.filter-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(0,0,0,0.1);
}

.filter-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
}

.filter-btn {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    border: 2px solid transparent;
    background-color: white;
    color: #6c757d;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.875rem;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.filter-btn:hover {
    color: #0d6efd;
    border-color: #0d6efd;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
}

.filter-btn.active {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

.filter-btn .badge {
    font-size: 0.7rem;
    margin-left: 0.5rem;
    padding: 0.25em 0.5em;
}

.filter-btn.active .badge {
    background-color: rgba(255,255,255,0.2) !important;
    color: white;
}

.search-container {
    background-color: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid rgba(0,0,0,0.1);
}

.search-input-group {
    position: relative;
}

.search-input {
    border: 2px solid #e9ecef;
    border-radius: 25px;
    padding: 0.75rem 3rem 0.75rem 1.5rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
}

.search-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.1);
    background-color: white;
    outline: none;
}

.search-btn {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background-color: #0d6efd;
    color: white;
    border-radius: 20px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.search-btn:hover {
    background-color: #0b5ed7;
    transform: translateY(-50%) scale(1.05);
}

.clear-filters-btn {
    background-color: #6c757d;
    color: white;
    border: none;
    border-radius: 20px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.clear-filters-btn:hover {
    background-color: #5a6268;
    transform: translateY(-2px);
}

/* Results info styling */
.results-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,0.1);
}

.results-count {
    font-weight: 600;
    color: #495057;
}

.results-sort {
    font-size: 0.875rem;
    color: #6c757d;
}

/* RESPONSIVE BREAKPOINTS */
@media (max-width: 1200px) {
    .card-img-top {
        height: 180px !important;
    }
    
    .card-title {
        font-size: 1.1rem;
    }
}

@media (max-width: 992px) {
    .card .btn-sm,
    .btn-read-more {
        height: 36px;
        font-size: 0.875rem;
    }
    
    .save-article-btn {
        width: 36px !important;
        height: 36px !important;
    }
    
    .card-img-top {
        height: 200px !important;
    }
    
    .col-lg-4 {
        margin-top: 2rem;
    }
    
    .card-meta {
        font-size: 0.8rem;
        gap: 0.25rem;
    }
    
    .card-date {
        font-size: 0.8rem;
    }
    
    .filter-buttons {
        justify-content: center;
    }
    
    .results-info {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
}

@media (max-width: 768px) {
    .card .btn-sm,
    .btn-read-more {
        height: 34px;
        padding: 0.4rem 0.875rem;
        font-size: 0.8125rem;
    }
    
    .save-article-btn {
        width: 34px !important;
        height: 34px !important;
    }
    
    .save-article-btn i {
        font-size: 0.9375rem;
    }
    
    .card-img-top {
        height: 160px !important;
    }
    
    .card-body {
        padding: 1rem 0.875rem;
    }
    
    .card-title {
        font-size: 1.0625rem;
        line-height: 1.3;
        margin-bottom: 0.75rem;
    }
    
    .card-text {
        font-size: 0.875rem;
        line-height: 1.4;
    }
    
    .card-meta {
        font-size: 0.75rem;
        margin-bottom: 0.75rem;
        flex-direction: row;
        justify-content: space-between;
    }
    
    .card-meta > div {
        font-size: 0.75rem;
    }
    
    .card-date {
        font-size: 0.75rem;
        flex: 0 1 auto;
        min-width: fit-content;
    }
    
    .col-md-8 h1 {
        font-size: 1.5rem;
    }
    
    .col-md-4.text-md-end {
        text-align: center !important;
        margin-top: 1rem;
    }
    
    .d-none.d-md-inline {
        display: none !important;
    }
    
    .d-inline.d-md-none {
        display: inline !important;
    }
    
    .filter-section {
        padding: 1rem;
    }
    
    .filter-buttons {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-btn {
        text-align: center;
        justify-content: center;
    }
    
    .search-container {
        padding: 1rem;
    }
}

@media (max-width: 576px) {
    .card .btn-sm,
    .btn-read-more {
        height: 32px;
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
    }
    
    .save-article-btn {
        width: 32px !important;
        height: 32px !important;
    }
    
    .save-article-btn i {
        font-size: 0.875rem;
    }
    
    .card-img-top {
        height: 140px !important;
    }
    
    .card-body {
        padding: 0.875rem;
    }
    
    .card-title {
        font-size: 1rem;
        line-height: 1.25;
    }
    
    .card-text {
        font-size: 0.8125rem;
        line-height: 1.3;
    }
    
    .card-meta {
        font-size: 0.7rem;
        margin-bottom: 0.5rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .card-meta > div {
        font-size: 0.7rem;
    }
    
    .card-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }
    
    .card-date {
        text-align: center;
        order: 2;
        font-size: 0.7rem;
        flex: none;
        min-width: auto;
        white-space: normal;
        overflow: visible;
        text-overflow: initial;
    }
    
    .card-buttons {
        order: 1;
        justify-content: space-between;
        width: 100%;
        gap: 0.5rem;
    }
    
    .btn-read-more {
        flex: 1;
        min-width: 0;
    }
    
    .container {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .notification-custom {
        left: 1rem !important;
        right: 1rem !important;
        top: 1rem !important;
        min-width: auto !important;
        max-width: none !important;
        font-size: 0.875rem;
    }
    
    .page-link {
        padding: 0.375rem 0.5rem;
        font-size: 0.8125rem;
    }
    
    .filter-btn {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }
}

@media (max-width: 400px) {
    .card-body {
        padding: 0.75rem;
    }
    
    .card-title {
        font-size: 0.95rem;
    }
    
    .card-text {
        font-size: 0.8rem;
    }
    
    .card-meta {
        font-size: 0.65rem;
    }
    
    .card-meta > div {
        font-size: 0.65rem;
    }
    
    .card-date {
        font-size: 0.65rem;
    }
    
    .btn-read-more {
        font-size: 0.7rem;
        height: 30px;
    }
    
    .save-article-btn {
        width: 30px !important;
        height: 30px !important;
    }
}

@media (min-width: 1400px) {
    .card-img-top {
        height: 220px !important;
    }
    
    .card-title {
        font-size: 1.25rem;
    }
    
    .card-text {
        font-size: 0.9rem;
    }
}

@media (min-width: 768px) {
    .d-none.d-md-inline {
        display: inline !important;
    }
    
    .d-inline.d-md-none {
        display: none !important;
    }
}

.save-article-btn:focus,
.btn-read-more:focus,
.filter-btn:focus,
.search-btn:focus {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
}

.card,
.card-body,
.notification-custom,
.filter-section,
.search-container {
    background-color: white !important;
    color: #212529 !important;
}

.card-title a {
    color: #212529 !important;
}

.card-text {
    color: #6c757d !important;
}

.card-meta,
.card-date {
    color: #6c757d !important;
}

@media print {
    .save-article-btn,
    .card-buttons,
    .filter-section,
    .search-container {
        display: none !important;
    }
    
    .card {
        break-inside: avoid;
        box-shadow: none !important;
        border: 1px solid #000;
        background-color: white !important;
    }
    
    .card:hover {
        transform: none;
    }
}
</style>

<div class="container py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 mb-2">
                <i class="fas fa-blog me-2"></i>
                <?php echo $salvate ? 'Articolele Mele Salvate' : 'Blog - Educație Financiară'; ?>
            </h1>
            <p class="text-muted mb-0">
                <?php echo $salvate ? 'Articolele pe care le-ai salvat pentru mai târziu' : 'Sfaturi practice și strategii financiare pentru dezvoltarea ta personală'; ?>
            </p>
        </div>
        <div class="col-md-4 d-none d-md-block"></div>
    </div>

    <!-- Secțiunea de Filtrare Îmbunătățită -->
    <div class="filter-section">
        <div class="row g-3">
            <!-- Butoane de Filtrare -->
            <div class="col-12">
                <h6 class="mb-3">
                    <i class="fas fa-filter me-2"></i>Filtrează articolele
                </h6>
                <div class="filter-buttons">
                    <a href="blog.php" class="filter-btn <?php echo (!$salvate && !$search) ? 'active' : ''; ?>">
                        <i class="fas fa-list me-2"></i>
                        Toate articolele
                        <span class="badge bg-secondary"><?php echo $total_articles; ?></span>
                    </a>
                    
                    <?php if ($search || $salvate): ?>
                        <a href="blog.php" class="clear-filters-btn">
                            <i class="fas fa-times me-2"></i>
                            Șterge filtrele
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Căutare Îmbunătățită -->
            <div class="col-12">
                <div class="search-container">
                    <form method="GET" class="d-flex align-items-center">
                        <?php if ($salvate): ?>
                            <input type="hidden" name="salvate" value="1">
                        <?php endif; ?>
                        
                        <div class="search-input-group flex-grow-1">
                            <input type="text" 
                                   class="form-control search-input" 
                                   name="search" 
                                   value="<?php echo sanitizeInput($search); ?>" 
                                   placeholder="Caută după titlul articolului...">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Informații despre rezultate -->
    <?php if ($search || $salvate || $total_articles > 0): ?>
        <div class="results-info">
            <div class="results-count">
                <i class="fas fa-info-circle me-2"></i>
                <?php if ($search): ?>
                    Găsite <strong><?php echo $total_articles; ?></strong> articole pentru "<?php echo sanitizeInput($search); ?>"
                <?php elseif ($salvate): ?>
                    Ai <strong><?php echo $total_articles; ?></strong> articole salvate
                <?php else: ?>
                    <strong><?php echo $total_articles; ?></strong> articole disponibile
                <?php endif; ?>
            </div>
            <div class="results-sort">
                Sortate după 
                <?php echo $salvate ? 'data salvării' : 'data publicării'; ?> 
                (cel mai recent)
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Articole principale -->
        <div class="col-lg-8">
            <?php if (!empty($articole)): ?>
                <!-- Card-uri pentru articole -->
                <div class="row">
                    <?php foreach ($articole as $articol): ?>
                        <div class="col-lg-6 col-md-6 col-12 mb-4">
                            <div class="card h-100 shadow-sm">
                                <!-- Imaginea articolului -->
                                <div class="position-relative">
                                    <?php if ($articol['imagine']): ?>
                                        <img src="assets/images/articles/<?php echo $articol['imagine']; ?>" 
                                             class="card-img-top" 
                                             alt="<?php echo sanitizeInput($articol['titlu']); ?>"
                                             style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top d-flex align-items-center justify-content-center bg-light" 
                                             style="height: 200px;">
                                            <i class="fas fa-newspaper fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Badge pentru articole featured -->
                                    <?php if (isset($articol['featured']) && $articol['featured']): ?>
                                        <span class="position-absolute top-0 end-0 badge bg-warning m-2">
                                            <i class="fas fa-star me-1"></i>Popular
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">
                                        <a href="articol.php?id=<?php echo $articol['id']; ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo sanitizeInput($articol['titlu']); ?>
                                        </a>
                                    </h5>
                                    
                                    <p class="card-text text-muted flex-grow-1">
                                        <?php 
                                        $excerpt = $articol['continut_scurt'] ?: strip_tags($articol['continut']);
                                        echo sanitizeInput(strlen($excerpt) > 120 ? substr($excerpt, 0, 120) . '...' : $excerpt);
                                        ?>
                                    </p>
                                    
                                    <div class="mt-auto">
                                        <!-- Meta informații -->
                                        <div class="card-meta">
                                            <div>
                                                <i class="fas fa-user me-1"></i>
                                                <span class="d-none d-md-inline"><?php echo sanitizeInput($articol['autor_nume']); ?></span>
                                                <span class="d-inline d-md-none"><?php echo sanitizeInput(explode(' ', $articol['autor_nume'])[0]); ?></span>
                                            </div>
                                            <div>
                                                <i class="fas fa-eye me-1"></i>
                                                <span><?php echo number_format($articol['vizualizari']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <!-- Acțiuni card -->
                                        <div class="card-actions">
                                            <small class="text-muted card-date">
                                                <i class="fas fa-calendar me-1"></i>
                                                <span class="d-none d-md-inline">
                                                    <?php echo $salvate && isset($articol['data_salvare']) ? 
                                                        'Salvat: ' . date('d.m.Y', strtotime($articol['data_salvare'])) : 
                                                        date('d.m.Y', strtotime($articol['data_publicare'])); ?>
                                                </span>
                                                <span class="d-inline d-md-none">
                                                    <?php echo date('d.m', strtotime($salvate && isset($articol['data_salvare']) ? $articol['data_salvare'] : $articol['data_publicare'])); ?>
                                                </span>
                                            </small>
                                            
                                            <div class="card-buttons">
                                                <a href="articol.php?id=<?php echo $articol['id']; ?>" 
                                                   class="btn btn-primary btn-sm btn-read-more">
                                                    <i class="fas fa-arrow-right me-1"></i>
                                                    <span class="d-none d-md-inline">Citește mai mult</span>
                                                    <span class="d-inline d-md-none">Citește</span>
                                                </a>
                                                
                                                <?php if (isLoggedIn()): ?>
                                                    <button class="btn btn-outline-secondary save-article-btn" 
                                                            data-article-id="<?php echo $articol['id']; ?>"
                                                            title="Salvează articolul">
                                                        <i class="far fa-heart"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Paginare -->
                <?php if ($total_pages > 1): ?>
                    <div class="row">
                        <div class="col-12">
                            <nav aria-label="Paginare articole">
                                <ul class="pagination justify-content-center flex-wrap">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="fas fa-chevron-left d-none d-sm-inline"></i>
                                                <span class="d-none d-sm-inline"> Anterior</span>
                                                <span class="d-inline d-sm-none">‹</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                <span class="d-none d-sm-inline">Următorul </span>
                                                <span class="d-inline d-sm-none">›</span>
                                                <i class="fas fa-chevron-right d-none d-sm-inline"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- Stare goală -->
                <div class="text-center py-5">
                    <i class="fas fa-<?php echo $salvate ? 'heart' : 'search'; ?> fa-3x text-muted mb-3"></i>
                    <h4>
                        <?php echo $salvate ? 'Nu ai articole salvate' : 'Nu s-au găsit articole'; ?>
                    </h4>
                    <p class="text-muted">
                        <?php if ($salvate): ?>
                            Explorează articolele noastre și salvează cele care te interesează.
                        <?php elseif ($search): ?>
                            Încearcă să cauți cu alți termeni sau explorează toate articolele.
                        <?php else: ?>
                            Articolele vor fi adăugate în curând. Revino pentru conținut nou!
                        <?php endif; ?>
                    </p>
                    <?php if ($salvate): ?>
                        <a href="blog.php" class="btn btn-primary">
                            <i class="fas fa-blog me-2"></i>Explorează Articolele
                        </a>
                    <?php elseif ($search): ?>
                        <a href="blog.php" class="btn btn-primary">
                            <i class="fas fa-list me-2"></i>Vezi Toate Articolele
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Statistici rapide -->
            <?php if (isLoggedIn()): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Statisticile Tale
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="mb-2">
                                    <i class="fas fa-heart text-danger fa-2x"></i>
                                </div>
                                <h5 class="mb-1 saved-articles-count"><?php echo $total_saved; ?></h5>
                                <small class="text-muted">Articole salvate</small>
                            </div>
                            <div class="col-6">
                                <div class="mb-2">
                                    <i class="fas fa-eye text-primary fa-2x"></i>
                                </div>
                                <h5 class="mb-1"><?php echo count($articole_populare); ?></h5>
                                <small class="text-muted">Populare disponibile</small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Articole populare -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-fire me-2"></i>Articole Populare
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($articole_populare)): ?>
                        <?php foreach ($articole_populare as $index => $articol): ?>
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <span class="badge bg-primary rounded-pill"><?php echo $index + 1; ?></span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1 small">
                                        <a href="articol.php?id=<?php echo $articol['id']; ?>" class="text-decoration-none">
                                            <?php echo sanitizeInput($articol['titlu']); ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fas fa-eye me-1"></i><?php echo number_format($articol['vizualizari']); ?> vizualizări
                                    </small>
                                </div>
                            </div>
                            <?php if ($index < count($articole_populare) - 1): ?>
                                <hr class="my-2">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small">Nu există articole populare încă.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Articole recente -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Articole Recente
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($articole_recente)): ?>
                        <?php foreach ($articole_recente as $index => $articol): ?>
                            <div class="mb-3">
                                <h6 class="mb-1 small">
                                    <a href="articol.php?id=<?php echo $articol['id']; ?>" class="text-decoration-none">
                                        <?php echo sanitizeInput($articol['titlu']); ?>
                                    </a>
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('d.m.Y', strtotime($articol['data_publicare'])); ?>
                                </small>
                            </div>
                            <?php if ($index < count($articole_recente) - 1): ?>
                                <hr class="my-2">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small">Nu există articole recente.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tags populare -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-tags me-2"></i>Subiecte Populare
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <!-- Butoanele au fost eliminate conform cererii -->
                        <p class="text-muted mb-0">Folosește căutarea de mai sus pentru a găsi articole pe subiecte specifice.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript cu debugging pentru a vedea ce se întâmplă
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== STARTING SAVE ARTICLES DEBUG ===');
    initializeSaveButtons();
    
    // Adaugă o întârziere mai mare pentru a fi sigur că totul s-a încărcat
    setTimeout(() => {
        console.log('Checking saved articles after delay...');
        checkSavedArticles();
    }, 1000);
});

function initializeSaveButtons() {
    const saveButtons = document.querySelectorAll('.save-article-btn');
    console.log('Initialize buttons - Found:', saveButtons.length);
    
    saveButtons.forEach((button, index) => {
        console.log(`Button ${index}:`, {
            articleId: button.getAttribute('data-article-id'),
            currentClass: button.className,
            iconClass: button.querySelector('i')?.className
        });
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const articleId = this.getAttribute('data-article-id');
            console.log('Button clicked for article:', articleId);
            
            if (articleId) {
                toggleSaveArticle(articleId, this);
            }
        });
    });
}

function checkSavedArticles() {
    console.log('=== CHECKING SAVED ARTICLES ===');
    const saveButtons = document.querySelectorAll('.save-article-btn');
    console.log('Found buttons to check:', saveButtons.length);
    
    const articleIds = [];
    
    saveButtons.forEach((button, index) => {
        const articleId = button.getAttribute('data-article-id');
        console.log(`Button ${index} - Article ID:`, articleId);
        if (articleId) {
            articleIds.push(parseInt(articleId));
        }
    });
    
    console.log('Article IDs to check:', articleIds);
    
    if (articleIds.length === 0) {
        console.log('No article IDs found, exiting...');
        return;
    }
    
    console.log('Making request to check-saved-articles.php...');
    
    fetch('ajax/check-saved-articles.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            article_ids: articleIds
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text(); // Folosim text() mai întâi pentru debugging
    })
    .then(text => {
        console.log('Raw response:', text);
        try {
            const data = JSON.parse(text);
            console.log('Parsed response:', data);
            
            if (data.success) {
                console.log('SUCCESS! Saved articles:', data.saved_articles);
                
                saveButtons.forEach((button, index) => {
                    const articleId = parseInt(button.getAttribute('data-article-id'));
                    const isSaved = data.saved_articles.includes(articleId);
                    console.log(`Article ${articleId} - Is saved: ${isSaved}`);
                    console.log(`Before update - Button class: ${button.className}`);
                    
                    updateSaveButton(button, isSaved);
                    
                    console.log(`After update - Button class: ${button.className}`);
                });
            } else {
                console.error('Response indicates failure:', data.message);
            }
        } catch (e) {
            console.error('Failed to parse JSON:', e);
            console.error('Raw text was:', text);
        }
    })
    .catch(error => {
        console.error('Network error:', error);
    });
}

function updateSaveButton(button, isSaved) {
    console.log(`Updating button - isSaved: ${isSaved}`);
    const icon = button.querySelector('i');
    console.log('Current icon class:', icon?.className);
    
    // Resetează toate clasele
    button.classList.remove('btn-outline-secondary', 'btn-danger', 'btn-outline-danger');
    
    if (isSaved) {
        // Articol salvat - buton roșu
        console.log('Setting as SAVED (red button)');
        icon.className = 'fas fa-heart';
        button.classList.add('btn-danger');
        button.title = 'Elimină din salvate';
    } else {
        // Articol nesalvat - buton outline
        console.log('Setting as NOT SAVED (outline button)');
        icon.className = 'far fa-heart';
        button.classList.add('btn-outline-secondary');
        button.title = 'Salvează articolul';
    }
    
    console.log('Final button class:', button.className);
    console.log('Final icon class:', icon?.className);
}

function toggleSaveArticle(articleId, button) {
    console.log('Toggle save for article:', articleId);
    button.disabled = true;
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    fetch('ajax/save-article.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            articol_id: parseInt(articleId)
        })
    })
    .then(response => {
        console.log('Save response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Save response data:', data);
        
        if (data.success) {
            updateSaveButton(button, data.is_saved);
            showNotification(data.message, 'success');
            updateSavedCount(data.total_saved);
            
            // Dacă suntem pe pagina articolelor salvate și articolul a fost șters
            if (data.action === 'removed' && window.location.search.includes('salvate=1')) {
                removeArticleFromPage(articleId);
            }
        } else {
            showNotification(data.message, 'error');
            button.innerHTML = originalContent;
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        showNotification('A apărut o eroare. Te rugăm să încerci din nou.', 'error');
        button.innerHTML = originalContent;
    })
    .finally(() => {
        button.disabled = false;
    });
}

function updateSavedCount(count) {
    const countElements = document.querySelectorAll('.saved-articles-count');
    countElements.forEach(element => {
        element.textContent = count;
    });
}

function removeArticleFromPage(articleId) {
    const button = document.querySelector(`[data-article-id="${articleId}"]`);
    const articleCard = button.closest('.col-lg-6');
    
    if (articleCard) {
        articleCard.style.transition = 'opacity 0.3s ease';
        articleCard.style.opacity = '0';
        
        setTimeout(() => {
            articleCard.remove();
            
            const remainingArticles = document.querySelectorAll('.save-article-btn');
            if (remainingArticles.length === 0) {
                showEmptyState();
            }
        }, 300);
    }
}

function showEmptyState() {
    const articlesContainer = document.querySelector('.col-lg-8 .row');
    if (articlesContainer) {
        articlesContainer.innerHTML = `
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                    <h4>Nu mai ai articole salvate</h4>
                    <p class="text-muted">
                        Explorează articolele noastre și salvează cele care te interesează.
                    </p>
                    <a href="blog.php" class="btn btn-primary">
                        <i class="fas fa-blog me-2"></i>Explorează Articolele
                    </a>
                </div>
            </div>
        `;
    }
}

function showNotification(message, type = 'info') {
    const existingNotifications = document.querySelectorAll('.notification-custom');
    existingNotifications.forEach(notif => notif.remove());
    
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed notification-custom`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle';
    
    notification.innerHTML = `
        <i class="fas fa-${icon} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Funcție de test pentru debugging manual
window.testCheckSaved = function() {
    console.log('=== MANUAL TEST ===');
    checkSavedArticles();
};

window.testButtonUpdate = function(articleId, isSaved) {
    const button = document.querySelector(`[data-article-id="${articleId}"]`);
    if (button) {
        console.log('Testing button update for article:', articleId, 'isSaved:', isSaved);
        updateSaveButton(button, isSaved);
    } else {
        console.log('Button not found for article:', articleId);
    }
};
</script>

<?php include 'components/footer.php'; ?>