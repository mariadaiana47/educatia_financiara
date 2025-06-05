<?php
require_once 'config.php';

$page_title = 'Cursuri - ' . SITE_NAME;

// Parametrii de filtrare și sortare
$nivel_filter = isset($_GET['nivel']) ? sanitizeInput($_GET['nivel']) : 'toate';
$pret_filter = isset($_GET['pret']) ? sanitizeInput($_GET['pret']) : 'toate';
$search_filter = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'populare';

// Construiesc condițiile WHERE
$where_conditions = ['c.activ = TRUE'];
$params = [];

if ($nivel_filter && $nivel_filter !== 'toate') {
    $where_conditions[] = 'c.nivel = ?';
    $params[] = $nivel_filter;
}

if ($pret_filter && $pret_filter !== 'toate') {
    switch ($pret_filter) {
        case '0-100':
            $where_conditions[] = 'c.pret BETWEEN 0 AND 100';
            break;
        case '100-200':
            $where_conditions[] = 'c.pret BETWEEN 100 AND 200';
            break;
        case '200-300':
            $where_conditions[] = 'c.pret BETWEEN 200 AND 300';
            break;
        case '300+':
            $where_conditions[] = 'c.pret > 300';
            break;
    }
}

if ($search_filter) {
    $where_conditions[] = '(c.titlu LIKE ? OR c.descriere LIKE ? OR c.descriere_scurta LIKE ?)';
    $search_term = '%' . $search_filter . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Sortare
$order_by = 'c.data_creare DESC';
switch ($sort_by) {
    case 'populare':
        $order_by = 'enrolled_count DESC, c.data_creare DESC';
        break;
    case 'pret_asc':
        $order_by = 'c.pret ASC';
        break;
    case 'pret_desc':
        $order_by = 'c.pret DESC';
        break;
    case 'alfabetic':
        $order_by = 'c.titlu ASC';
        break;
    case 'noi':
        $order_by = 'c.data_creare DESC';
        break;
}

try {
    $where_clause = implode(' AND ', $where_conditions);
    
    if (isAdmin()) {
        // Query pentru admin cu statistici detaliate
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   COUNT(DISTINCT ic.user_id) as enrolled_count,
                   COUNT(DISTINCT rq.user_id) as quiz_attempts,
                   AVG(CASE WHEN ic.finalizat = 1 THEN ic.progress ELSE NULL END) as avg_completion,
                   SUM(CASE WHEN ic.finalizat = 1 THEN 1 ELSE 0 END) as completed_count,
                   COUNT(DISTINCT vc.id) as video_count,
                   COUNT(DISTINCT ec.id) as exercise_count,
                   COUNT(DISTINCT qz.id) as quiz_count,
                   SUM(ic.timp_petrecut) as total_time_spent,
                   AVG(CASE WHEN rq.promovat = 1 THEN rq.procentaj ELSE NULL END) as avg_quiz_score
            FROM cursuri c
            LEFT JOIN inscrieri_cursuri ic ON c.id = ic.curs_id
            LEFT JOIN rezultate_quiz rq ON c.id = rq.quiz_id
            LEFT JOIN video_cursuri vc ON c.id = vc.curs_id AND vc.activ = 1
            LEFT JOIN exercitii_cursuri ec ON c.id = ec.curs_id AND ec.activ = 1
            LEFT JOIN quiz_uri qz ON c.id = qz.curs_id AND qz.activ = 1
            WHERE $where_clause
            GROUP BY c.id
            ORDER BY $order_by
        ");
    } else {
        // Query pentru utilizatori normali
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   COUNT(DISTINCT ic.user_id) as enrolled_count,
                   COUNT(DISTINCT vc.id) as video_count,
                   COUNT(DISTINCT ec.id) as exercise_count,
                   COUNT(DISTINCT qz.id) as quiz_count
            FROM cursuri c
            LEFT JOIN inscrieri_cursuri ic ON c.id = ic.curs_id
            LEFT JOIN video_cursuri vc ON c.id = vc.curs_id AND vc.activ = 1
            LEFT JOIN exercitii_cursuri ec ON c.id = ec.curs_id AND ec.activ = 1
            LEFT JOIN quiz_uri qz ON c.id = qz.curs_id AND qz.activ = 1
            WHERE $where_clause
            GROUP BY c.id
            ORDER BY $order_by
        ");
    }
    
    $stmt->execute($params);
    $cursuri = $stmt->fetchAll();
    
    // Statistici generale pentru admin
    if (isAdmin()) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM cursuri WHERE activ = TRUE");
        $total_cursuri = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM inscrieri_cursuri");
        $total_studenti = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT SUM(pret * enrolled_count) as total_revenue FROM (
            SELECT c.pret, COUNT(ic.user_id) as enrolled_count 
            FROM cursuri c 
            LEFT JOIN inscrieri_cursuri ic ON c.id = ic.curs_id 
            GROUP BY c.id, c.pret
        ) as revenue_calc");
        $total_revenue = $stmt->fetchColumn() ?: 0;
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM cursuri WHERE activ = FALSE");
        $cursuri_inactive = $stmt->fetchColumn();
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM cursuri WHERE activ = TRUE");
        $total_cursuri = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM inscrieri_cursuri");
        $total_studenti = $stmt->fetchColumn();
    }
    
} catch (PDOException $e) {
    $cursuri = [];
    $total_cursuri = 0;
    $total_studenti = 0;
    if (isAdmin()) {
        $total_revenue = 0;
        $cursuri_inactive = 0;
    }
}

include 'components/header.php';
?>

<meta name="csrf-token" content="<?= generateCSRFToken() ?>">

<style>
/* =================================
   COURSE CARDS STYLES (PENTRU UTILIZATORI)
   ================================= */

/* Course Card Main Container */
.course-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    border: 1px solid #f0f0f0;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.course-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    border-color: var(--primary-color);
}

/* Course Image Section */
.course-image-container {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: #f8f9fa;
}

.course-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.course-image-placeholder {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
}

.course-card:hover .course-image {
    transform: scale(1.05);
}

/* Course Badges */
.course-level-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 2;
}

.course-featured-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 2;
}

/* Course Content Section */
.course-content {
    padding: 24px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.course-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 12px;
    line-height: 1.4;
    min-height: 3rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.course-description {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 16px;
    height: 4.5rem;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

/* Course Stats Grid */
.course-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 20px;
}

.stat-item {
    display: flex;
    align-items: center;
    font-size: 0.85rem;
    color: #666;
    white-space: nowrap;
}

.stat-item i {
    width: 16px;
    margin-right: 6px;
    flex-shrink: 0;
}

/* Course Price Section */
.course-price {
    margin-bottom: 20px;
    text-align: center;
}

.price-current {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
}

.price-original {
    font-size: 1rem;
    color: #999;
    text-decoration: line-through;
    margin-left: 8px;
}

/* Course Actions */
.course-actions {
    margin-top: auto;
}

.course-actions .btn {
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.course-actions .btn:hover {
    transform: translateY(-2px);
}

/* Badge Styles */
.badge-level {
    font-size: 0.75rem;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-level.incepator {
    background-color: #28a745;
    color: white;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.badge-level.intermediar {
    background-color: #ffc107;
    color: #212529;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
}

.badge-level.avansat {
    background-color: #dc3545;
    color: white;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

/* Course Item Animation */
.course-item {
    transition: all 0.5s ease;
    opacity: 1;
    transform: scale(1);
}

.course-item.hidden {
    opacity: 0;
    transform: scale(0.8);
    pointer-events: none;
}

/* Loading States */
.add-to-cart-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.add-to-cart-btn .fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .course-stats {
        grid-template-columns: 1fr;
        gap: 6px;
    }
    
    .course-content {
        padding: 20px;
    }
    
    .course-title {
        font-size: 1.1rem;
        min-height: 2.5rem;
    }
}

@media (max-width: 576px) {
    .course-image-container {
        height: 160px;
    }
    
    .course-content {
        padding: 16px;
    }
}
</style>

<?php if (isAdmin()): ?>
    <!-- ========================================
         INTERFAȚA ADMIN - DOAR TABEL
         ======================================== -->
    <div class="container py-4">
        <!-- Header Admin -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="h2 mb-2">
                    <i class="fas fa-cogs me-2"></i>Gestionare Cursuri
                </h1>
                <p class="text-muted">
                    Administrează cursurile, vezi statistici și gestionează conținutul educațional
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="cursuri.php?view=user" class="btn btn-outline-primary">
                    <i class="fas fa-eye me-2"></i>Vedere Utilizator
                </a>
            </div>
        </div>

        <!-- Statistici Admin -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3><?= $total_cursuri ?></h3>
                        <p class="mb-0">Cursuri Active</p>
                        <small class="opacity-75"><?= $cursuri_inactive ?> inactive</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3><?= $total_studenti ?></h3>
                        <p class="mb-0">Studenți Înscriși</p>
                        <small class="opacity-75">Total unic</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h3><?= formatPrice($total_revenue) ?></h3>
                        <p class="mb-0">Venit Total</p>
                        <small class="opacity-75">Din înscrieri</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3><?= count($cursuri) ?></h3>
                        <p class="mb-0">Cursuri Filtrate</p>
                        <small class="opacity-75">Rezultate actuale</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtre Admin -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3" id="filterForm">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Căutare</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?= sanitizeInput($search_filter) ?>" 
                               placeholder="Caută cursuri...">
                    </div>
                    <div class="col-md-2">
                        <label for="nivel" class="form-label">Nivel</label>
                        <select class="form-select" id="nivel" name="nivel">
                            <option value="toate" <?= $nivel_filter === 'toate' ? 'selected' : '' ?>>Toate</option>
                            <option value="incepator" <?= $nivel_filter === 'incepator' ? 'selected' : '' ?>>Începător</option>
                            <option value="intermediar" <?= $nivel_filter === 'intermediar' ? 'selected' : '' ?>>Intermediar</option>
                            <option value="avansat" <?= $nivel_filter === 'avansat' ? 'selected' : '' ?>>Avansat</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="pret" class="form-label">Preț</label>
                        <select class="form-select" id="pret" name="pret">
                            <option value="toate" <?= $pret_filter === 'toate' ? 'selected' : '' ?>>Toate</option>
                            <option value="0-100" <?= $pret_filter === '0-100' ? 'selected' : '' ?>>0-100 RON</option>
                            <option value="100-200" <?= $pret_filter === '100-200' ? 'selected' : '' ?>>100-200 RON</option>
                            <option value="200-300" <?= $pret_filter === '200-300' ? 'selected' : '' ?>>200-300 RON</option>
                            <option value="300+" <?= $pret_filter === '300+' ? 'selected' : '' ?>>300+ RON</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="sort" class="form-label">Sortare</label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="populare" <?= $sort_by === 'populare' ? 'selected' : '' ?>>Populare</option>
                            <option value="noi" <?= $sort_by === 'noi' ? 'selected' : '' ?>>Noi</option>
                            <option value="alfabetic" <?= $sort_by === 'alfabetic' ? 'selected' : '' ?>>Alfabetic</option>
                            <option value="pret_asc" <?= $sort_by === 'pret_asc' ? 'selected' : '' ?>>Preț Crescător</option>
                            <option value="pret_desc" <?= $sort_by === 'pret_desc' ? 'selected' : '' ?>>Preț Descrescător</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Filtrează
                            </button>
                            <a href="cursuri.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Cursuri Admin -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Lista Cursurilor</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Curs</th>
                                <th>Nivel</th>
                                <th>Preț</th>
                                <th>Conținut</th>
                                <th>Înscriși</th>
                                <th>Finalizat</th>
                                <th>Performanță</th>
                                <th>Status</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cursuri)): ?>
                                <?php foreach ($cursuri as $curs): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($curs['imagine']): ?>
                                                <img src="uploads/cursuri/<?= sanitizeInput($curs['imagine']) ?>" 
                                                     alt="<?= sanitizeInput($curs['titlu']) ?>" 
                                                     class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;"
                                                     onerror="this.src='assets/placeholder-course.jpg'">
                                            <?php else: ?>
                                                <div class="bg-primary rounded me-3 d-flex align-items-center justify-content-center" 
                                                     style="width: 50px; height: 50px;">
                                                    <i class="fas fa-graduation-cap text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0"><?= sanitizeInput($curs['titlu']) ?></h6>
                                                <small class="text-muted">
                                                    Creat: <?= date('d.m.Y', strtotime($curs['data_creare'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-level <?= $curs['nivel'] ?>">
                                            <?= ucfirst($curs['nivel']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-primary"><?= formatPrice($curs['pret']) ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <span class="badge bg-info" title="Video-uri">
                                                <i class="fas fa-video"></i> <?= $curs['video_count'] ?>
                                            </span>
                                            <span class="badge bg-warning" title="Quiz-uri">
                                                <i class="fas fa-question"></i> <?= $curs['quiz_count'] ?>
                                            </span>
                                            <span class="badge bg-secondary" title="Exerciții">
                                                <i class="fas fa-tasks"></i> <?= $curs['exercise_count'] ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?= $curs['enrolled_count'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $curs['completed_count'] ?? 0 ?></span>
                                        <?php if ($curs['enrolled_count'] > 0): ?>
                                            <br><small class="text-muted">
                                                <?= round(($curs['completed_count'] ?? 0) / $curs['enrolled_count'] * 100, 1) ?>%
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($curs['avg_quiz_score']): ?>
                                            <div class="text-center">
                                                <strong class="text-success"><?= round($curs['avg_quiz_score'], 1) ?>%</strong>
                                                <br><small class="text-muted">Quiz mediu</small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($curs['activ']): ?>
                                            <span class="badge bg-success">Activ</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactiv</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="curs.php?id=<?= $curs['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Vezi cursul">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="admin/content-manager.php" 
                                               class="btn btn-sm btn-outline-success" title="Gestionează conținut">
                                                <i class="fas fa-cogs"></i>
                                            </a>
                                            <a href="admin/admin-progres.php" 
                                               class="btn btn-sm btn-outline-info" title="Vezi progresul tuturor">
                                                <i class="fas fa-chart-line"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="toggleCourseStatus(<?= $curs['id'] ?>, <?= $curs['activ'] ? 'false' : 'true' ?>)"
                                                    title="<?= $curs['activ'] ? 'Dezactivează' : 'Activează' ?>">
                                                <i class="fas fa-<?= $curs['activ'] ? 'ban' : 'check' ?>"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                        <h6>Nu s-au găsit cursuri</h6>
                                        <p class="text-muted">Ajustează filtrele pentru a găsi cursuri</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ========================================
         INTERFAȚA UTILIZATOR - DOAR CARDURI
         ======================================== -->
    <div class="container py-4">
        <!-- Header Utilizator -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="h2 mb-2">
                    <i class="fas fa-graduation-cap me-2"></i>Cursuri de Educație Financiară
                </h1>
                <p class="text-muted">
                    Investește în educația ta financiară și construiește-ți un viitor prosper
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="d-flex align-items-center justify-content-end">
                    <span class="text-muted me-3">
                        <span id="resultsCount"><?= count($cursuri) ?> cursuri găsite</span>
                    </span>
                    <?php if (isLoggedIn()): ?>
                        <a href="cos.php" class="btn btn-outline-primary">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Coș (<?= getCartCount() ?>)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Filtre Utilizator -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3" id="filterForm">
                    <div class="col-md-4">
                        <label for="searchFilter" class="form-label">Căutare</label>
                        <input type="text" class="form-control" id="searchFilter" name="search" 
                               value="<?= sanitizeInput($search_filter) ?>" 
                               placeholder="Caută cursuri...">
                    </div>
                    <div class="col-md-2">
                        <label for="levelFilter" class="form-label">Nivel</label>
                        <select class="form-select" id="levelFilter" name="nivel">
                            <option value="toate">Toate nivelurile</option>
                            <option value="incepator" <?= $nivel_filter === 'incepator' ? 'selected' : '' ?>>Începător</option>
                            <option value="intermediar" <?= $nivel_filter === 'intermediar' ? 'selected' : '' ?>>Intermediar</option>
                            <option value="avansat" <?= $nivel_filter === 'avansat' ? 'selected' : '' ?>>Avansat</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="priceFilter" class="form-label">Preț</label>
                        <select class="form-select" id="priceFilter" name="pret">
                            <option value="toate">Toate prețurile</option>
                            <option value="0-100" <?= $pret_filter === '0-100' ? 'selected' : '' ?>>0-100 RON</option>
                            <option value="100-200" <?= $pret_filter === '100-200' ? 'selected' : '' ?>>100-200 RON</option>
                            <option value="200-300" <?= $pret_filter === '200-300' ? 'selected' : '' ?>>200-300 RON</option>
                            <option value="300+" <?= $pret_filter === '300+' ? 'selected' : '' ?>>300+ RON</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="sortFilter" class="form-label">Sortare</label>
                        <select class="form-select" id="sortFilter" name="sort">
                            <option value="populare" <?= $sort_by === 'populare' ? 'selected' : '' ?>>Cele mai populare</option>
                            <option value="noi" <?= $sort_by === 'noi' ? 'selected' : '' ?>>Cele mai noi</option>
                            <option value="alfabetic" <?= $sort_by === 'alfabetic' ? 'selected' : '' ?>>Alfabetic</option>
                            <option value="pret_asc" <?= $sort_by === 'pret_asc' ? 'selected' : '' ?>>Preț crescător</option>
                            <option value="pret_desc" <?= $sort_by === 'pret_desc' ? 'selected' : '' ?>>Preț descrescător</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <a href="cursuri.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Reset filtre
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Grid Cursuri cu Design Îmbunătățit - DOAR PENTRU UTILIZATORI -->
        <div class="row" id="coursesGrid">
            <?php if (!empty($cursuri)): ?>
                <?php foreach ($cursuri as $curs): ?>
                <div class="col-lg-4 col-md-6 mb-4 course-item" 
                     data-level="<?= $curs['nivel'] ?>" 
                     data-price="<?= $curs['pret'] ?>"
                     data-title="<?= strtolower($curs['titlu']) ?>"
                     data-description="<?= strtolower($curs['descriere_scurta'] ?? $curs['descriere']) ?>">
                    
                    <div class="course-card h-100">
                        <!-- Course Image -->
                        <div class="course-image-container">
                            <?php if ($curs['imagine']): ?>
                                <img src="uploads/cursuri/<?= sanitizeInput($curs['imagine']) ?>" 
                                     alt="<?= sanitizeInput($curs['titlu']) ?>" 
                                     class="course-image"
                                     onerror="this.src='assets/placeholder-course.jpg'">
                            <?php else: ?>
                                <div class="course-image course-image-placeholder">
                                    <i class="fas fa-graduation-cap fa-3x"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Course Level Badge -->
                            <div class="course-level-badge">
                                <span class="badge badge-level <?= $curs['nivel'] ?>">
                                    <?= ucfirst($curs['nivel']) ?>
                                </span>
                            </div>
                            
                            <!-- Featured Badge -->
                            <?php if ($curs['featured']): ?>
                                <div class="course-featured-badge">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-star me-1"></i>Recomandat
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Course Content -->
                        <div class="course-content">
                            <h5 class="course-title"><?= sanitizeInput($curs['titlu']) ?></h5>
                            <p class="course-description">
                                <?= sanitizeInput($curs['descriere_scurta'] ?: truncateText($curs['descriere'], 120)) ?>
                            </p>
                            
                            <!-- Course Stats -->
                            <div class="course-stats">
                                <div class="stat-item">
                                    <i class="fas fa-users text-primary"></i>
                                    <span><?= $curs['enrolled_count'] ?> înscriși</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-clock text-success"></i>
                                    <span><?= $curs['durata_minute'] ?> min</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-video text-info"></i>
                                    <span><?= $curs['video_count'] ?> video</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-question-circle text-warning"></i>
                                    <span><?= $curs['quiz_count'] ?> quiz</span>
                                </div>
                            </div>
                            
                            <!-- Course Price -->
                            <div class="course-price">
                                <span class="price-current"><?= formatPrice($curs['pret']) ?></span>
                                <?php if ($curs['pret'] > 100): ?>
                                    <span class="price-original"><?= formatPrice($curs['pret'] * 1.3) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Course Actions -->
                            <div class="course-actions">
                                <?php if (isLoggedIn()): ?>
                                    <?php if (isEnrolledInCourse($_SESSION['user_id'], $curs['id'])): ?>
                                        <a href="curs.php?id=<?= $curs['id'] ?>" class="btn btn-success w-100">
                                            <i class="fas fa-play me-2"></i>Continuă cursul
                                        </a>
                                    <?php elseif (isInCart($_SESSION['user_id'], $curs['id'])): ?>
                                        <button class="btn btn-secondary w-100" disabled>
                                            <i class="fas fa-shopping-cart me-2"></i>În coș
                                        </button>
                                        <small class="text-muted text-center d-block mt-1">
                                            <a href="cos.php">Vezi coșul</a>
                                        </small>
                                    <?php else: ?>
                                        <button class="btn btn-primary w-100 add-to-cart-btn" 
                                                data-course-id="<?= $curs['id'] ?>"
                                                onclick="addToCart(<?= $curs['id'] ?>, this)">
                                            <i class="fas fa-shopping-cart me-2"></i>Adaugă în coș
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php?redirect=cursuri.php" class="btn btn-primary w-100">
                                        <i class="fas fa-sign-in-alt me-2"></i>Conectează-te pentru a cumpăra
                                    </a>
                                <?php endif; ?>
                                
                                <a href="curs.php?id=<?= $curs['id'] ?>" class="btn btn-outline-primary w-100 mt-2">
                                    <i class="fas fa-info-circle me-2"></i>Detalii curs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4>Nu s-au găsit cursuri</h4>
                        <p class="text-muted">Încearcă să ajustezi filtrele de căutare sau explorează toate cursurile.</p>
                        <a href="cursuri.php" class="btn btn-primary">
                            <i class="fas fa-graduation-cap me-2"></i>Vezi toate cursurile
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
<?php if (isLoggedIn()): ?>
// Enhanced add to cart function with better UX
function addToCart(courseId, buttonElement) {
    console.log('Adding course ID:', courseId);
    
    if (!<?= isLoggedIn() ? 'true' : 'false' ?>) {
        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname);
        return;
    }
    
    // Disable button and show loading
    const originalHtml = buttonElement.innerHTML;
    buttonElement.disabled = true;
    buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Se adaugă...';
    
    // Create form data
    const formData = new FormData();
    formData.append('course_id', courseId);
    
    fetch('ajax/add-to-cart.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Răspuns invalid de la server');
        }
        
        if (data.success) {
            // Update button with success state
            buttonElement.className = 'btn btn-secondary w-100';
            buttonElement.innerHTML = '<i class="fas fa-check me-2"></i>Adăugat în coș';
            buttonElement.disabled = true;
            
            // Update cart counter
            if (data.cart_count !== undefined) {
                updateCartCounter(data.cart_count);
            }
            
            // Show success notification
            showNotification(data.message, 'success');
            
            // Add link to cart
            const actionsContainer = buttonElement.parentElement;
            const cartLink = document.createElement('small');
            cartLink.className = 'text-muted text-center d-block mt-1';
            cartLink.innerHTML = '<a href="cos.php">Vezi coșul</a>';
            actionsContainer.appendChild(cartLink);
            
            // Add celebration effect
            addCelebrationEffect(buttonElement);
            
        } else {
            // Restore button on error
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalHtml;
            showNotification(data.message || 'Eroare necunoscută', 'error');
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        buttonElement.disabled = false;
        buttonElement.innerHTML = originalHtml;
        showNotification('Eroare de conexiune: ' + error.message, 'error');
    });
}

// Update cart counter with animation
function updateCartCounter(count) {
    const cartLinks = document.querySelectorAll('a[href="cos.php"]');
    cartLinks.forEach(cartLink => {
        const regex = /Coș \(\d+\)/;
        if (regex.test(cartLink.textContent)) {
            cartLink.textContent = cartLink.textContent.replace(regex, `Coș (${count})`);
        }
    });

    // Animate cart button
    cartLinks.forEach(cartLink => {
        cartLink.style.transform = 'scale(1.1)';
        cartLink.style.transition = 'transform 0.2s ease';
        setTimeout(() => {
            cartLink.style.transform = 'scale(1)';
        }, 200);
    });
}

// Add celebration effect
function addCelebrationEffect(element) {
    element.style.transform = 'scale(1.05)';
    element.style.transition = 'transform 0.3s ease';
    
    setTimeout(() => {
        element.style.transform = 'scale(1)';
    }, 300);
}

// Enhanced notification system
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close btn-close-white ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        color: white;
        font-weight: 500;
        z-index: 9999;
        transform: translateX(400px);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        min-width: 300px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        backdrop-filter: blur(10px);
    `;
    
    // Set color based on type
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        info: '#17a2b8'
    };
    notification.style.backgroundColor = colors[type] || colors.info;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 300);
    }, 5000);
}

<?php endif; ?>

// Live filtering functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchFilter');
    const levelSelect = document.getElementById('levelFilter');
    const priceSelect = document.getElementById('priceFilter');
    const sortSelect = document.getElementById('sortFilter');
    const courseItems = document.querySelectorAll('.course-item');
    const resultsCount = document.getElementById('resultsCount');

    // Auto-submit form when filters change
    [levelSelect, priceSelect, sortSelect].forEach(select => {
        if (select) {
            select.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        }
    });

    // Live search functionality
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterCourses();
            }, 300);
        });
    }

    function filterCourses() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        let visibleCount = 0;

        courseItems.forEach(item => {
            const title = item.dataset.title || '';
            const description = item.dataset.description || '';
            const matchesSearch = !searchTerm || 
                title.includes(searchTerm) || 
                description.includes(searchTerm);

            if (matchesSearch) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        if (resultsCount) {
            resultsCount.textContent = `${visibleCount} cursuri găsite`;
        }
    }
});

// Enhanced toggle course status function for admin
function toggleCourseStatus(courseId, newStatus) {
    if (!confirm(`Ești sigur că vrei să ${newStatus ? 'activezi' : 'dezactivezi'} acest curs?`)) {
        return;
    }

    fetch('ajax/toggle-course-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            course_id: courseId,
            status: newStatus,
            csrf_token: '<?= generateCSRFToken() ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Eroare: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('A apărut o eroare. Te rugăm să încerci din nou.');
    });
}
</script>

<?php include 'components/footer.php'; ?>