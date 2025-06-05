<?php
require_once '../config.php';

// Verifică dacă utilizatorul este admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = MSG_ERROR_ACCESS_DENIED;
    redirectTo('../login.php');
}

$page_title = 'Admin Dashboard - ' . SITE_NAME;

try {
    // Statistici generale
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE activ = TRUE");
    $total_users = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cursuri WHERE activ = TRUE");
    $total_courses = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscrieri_cursuri");
    $total_enrollments = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT SUM(pret) as total FROM cursuri c JOIN inscrieri_cursuri ic ON c.id = ic.curs_id");
    $total_revenue = $stmt->fetchColumn() ?: 0;
    
    // Utilizatori recenți
    $stmt = $pdo->query("
        SELECT id, nume, email, data_inregistrare, 
               (SELECT COUNT(*) FROM inscrieri_cursuri WHERE user_id = users.id) as cursuri_inscrise
        FROM users 
        WHERE activ = TRUE 
        ORDER BY data_inregistrare DESC 
        LIMIT 10
    ");
    $recent_users = $stmt->fetchAll();
    
    // Cursuri cu statistici
    $stmt = $pdo->query("
        SELECT c.*, 
               COUNT(DISTINCT ic.user_id) as enrolled_count,
               COUNT(DISTINCT cc.user_id) as cart_count,
               SUM(CASE WHEN ic.finalizat = 1 THEN 1 ELSE 0 END) as completed_count
        FROM cursuri c
        LEFT JOIN inscrieri_cursuri ic ON c.id = ic.curs_id
        LEFT JOIN cos_cumparaturi cc ON c.id = cc.curs_id
        WHERE c.activ = TRUE
        GROUP BY c.id
        ORDER BY enrolled_count DESC
        LIMIT 10
    ");
    $popular_courses = $stmt->fetchAll();
    
    // Activitate recentă
    $stmt = $pdo->query("
        SELECT 'enrollment' as type, ic.data_inscriere as date, u.nume as user_name, c.titlu as course_title
        FROM inscrieri_cursuri ic
        JOIN users u ON ic.user_id = u.id
        JOIN cursuri c ON ic.curs_id = c.id
        WHERE ic.data_inscriere >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        UNION ALL
        
        SELECT 'registration' as type, u.data_inregistrare as date, u.nume as user_name, '' as course_title
        FROM users u
        WHERE u.data_inregistrare >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND u.activ = TRUE
        
        ORDER BY date DESC
        LIMIT 20
    ");
    $recent_activity = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $total_users = 0;
    $total_courses = 0;
    $total_enrollments = 0;
    $total_revenue = 0;
    $recent_users = [];
    $popular_courses = [];
    $recent_activity = [];
}

include '../components/header.php';
?>

<div class="container-fluid py-4" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh;">
    
    <!-- Admin Profile Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="admin-profile-header">
                <div class="profile-background">
                    <div class="background-pattern"></div>
                </div>
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <div class="admin-info">
                            <div class="admin-badge">
                                <i class="fas fa-crown me-2"></i>Administrator Principal
                            </div>
                            <h1 class="admin-name">
                                <?php 
                                $current_user = getCurrentUser();
                                echo htmlspecialchars($current_user['nume']); 
                                ?>
                            </h1>
                            <div class="admin-details">
                                <div class="detail-item">
                                    <i class="fas fa-envelope text-primary me-2"></i>
                                    <span><?= htmlspecialchars($current_user['email']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-calendar text-success me-2"></i>
                                    <span>Membru din <?= date('F Y', strtotime($current_user['data_inregistrare'])) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="quick-stats">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="stat-number"><?= number_format($total_users) ?></div>
                                        <div class="stat-label">Utilizatori</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fas fa-graduation-cap"></i>
                                        </div>
                                        <div class="stat-number"><?= number_format($total_courses) ?></div>
                                        <div class="stat-label">Cursuri</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="stats-info">
                            <h3 class="text-white mb-0"><?= number_format($total_users) ?></h3>
                            <p class="text-white-50 mb-0">Utilizatori Activi</p>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-users fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="stats-info">
                            <h3 class="text-white mb-0"><?= number_format($total_courses) ?></h3>
                            <p class="text-white-50 mb-0">Cursuri Active</p>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-graduation-cap fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="stats-info">
                            <h3 class="text-white mb-0"><?= number_format($total_enrollments) ?></h3>
                            <p class="text-white-50 mb-0">Total Înscrierii</p>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-user-graduate fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="stats-info">
                            <h3 class="text-white mb-0"><?= formatPrice($total_revenue) ?></h3>
                            <p class="text-white-50 mb-0">Venituri Totale</p>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-euro-sign fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Cards -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="fas fa-tools me-2"></i>Acțiuni Administrative
            </h4>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="action-card">
                        <div class="action-icon bg-primary">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h5 class="card-title">Progres Utilizatori</h5>
                        <p class="card-text">Monitorizează progresul detaliat al tuturor utilizatorilor platformei</p>
                        <a href="/admin/admin-progres-user.php" class="btn btn-primary">
                            <i class="fas fa-users me-2"></i>Vezi Progresul
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="action-card">
                        <div class="action-icon bg-success">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <h5 class="card-title">Raport Excel</h5>
                        <p class="card-text">Generează raport complet cu activitatea site-ului (ora României)</p>
                        <a href="#" onclick="generateReport('excel')" class="btn btn-success">
                            <i class="fas fa-download me-2"></i>Generează Raport
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="action-card">
                        <div class="action-icon bg-danger">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h5 class="card-title">Content Manager</h5>
                        <p class="card-text">Gestionează cursuri, video-uri, exerciții și quiz-uri</p>
                        <a href="content-manager.php" class="btn btn-danger">
                            <i class="fas fa-edit me-2"></i>Gestionare Conținut
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables -->
    <div class="row">
        <!-- Recent Users -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2 text-primary"></i>Utilizatori Recenți
                    </h5>
                    <a href="/admin/admin-progres-user.php" class="btn btn-sm btn-outline-primary">

                        Vezi toți
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Utilizator</th>
                                    <th>Email</th>
                                    <th>Cursuri</th>
                                    <th>Data</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-2">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <strong><?= sanitizeInput($user['nume']) ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= sanitizeInput($user['email']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $user['cursuri_inscrise'] ?></span>
                                    </td>
                                    <td>
                                        <small><?= date('d.m.Y', strtotime($user['data_inregistrare'])) ?></small>
                                    </td>
                                    <td>
                                        <a href="/admin/admin-progres-user.php?user_id=<?= $user['id'] ?>" 
       class="btn btn-sm btn-outline-info" title="Vezi progresul">

                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Popular Courses -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-star me-2 text-warning"></i>Cursuri Populare
                    </h5>
                    <a href="content-manager.php" class="btn btn-sm btn-outline-primary">
                        Gestionează
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Curs</th>
                                    <th>Înscriși</th>
                                    <th>Finalizat</th>
                                    <th>În Coș</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_courses as $course): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?= sanitizeInput($course['titlu']) ?></strong>
                                            <br>
                                            <small class="text-success fw-bold"><?= formatPrice($course['pret']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?= $course['enrolled_count'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $course['completed_count'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?= $course['cart_count'] ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Timeline -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2 text-info"></i>Activitate Recentă (ultimele 7 zile)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_activity)): ?>
                        <div class="activity-timeline">
                            <?php foreach ($recent_activity as $activity): ?>
                                <div class="timeline-item d-flex mb-3">
                                    <div class="timeline-marker me-3">
                                        <?php if ($activity['type'] == 'enrollment'): ?>
                                            <div class="timeline-icon bg-success">
                                                <i class="fas fa-user-graduate text-white"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="timeline-icon bg-primary">
                                                <i class="fas fa-user-plus text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="timeline-content flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <?php if ($activity['type'] == 'enrollment'): ?>
                                                    <strong><?= sanitizeInput($activity['user_name']) ?></strong>
                                                    s-a înscris la cursul
                                                    <strong class="text-primary"><?= sanitizeInput($activity['course_title']) ?></strong>
                                                <?php else: ?>
                                                    <strong><?= sanitizeInput($activity['user_name']) ?></strong>
                                                    s-a înregistrat pe platformă
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= timeAgo($activity['date']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nu există activitate recentă.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Admin Profile Header */
.admin-profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 2rem;
    color: white;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.profile-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    opacity: 0.1;
}

.background-pattern {
    width: 100%;
    height: 100%;
    background-image: radial-gradient(circle at 20% 50%, white 2px, transparent 2px),
                      radial-gradient(circle at 80% 50%, white 2px, transparent 2px);
    background-size: 50px 50px;
}

.admin-badge {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 25px;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.admin-name {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}

.admin-details {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
}

.detail-item {
    display: flex;
    align-items: center;
    font-size: 1rem;
}

/* Quick Stats */
.quick-stats {
    background: rgba(255,255,255,0.1);
    border-radius: 15px;
    padding: 1.5rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
}

.stat-card {
    background: rgba(255,255,255,0.15);
    border-radius: 10px;
    padding: 1rem;
    text-align: center;
    border: 1px solid rgba(255,255,255,0.2);
    transition: all 0.3s ease;
}

.stat-card:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-3px);
}

.stat-icon {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    color: white;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: white;
    line-height: 1;
}

.stat-label {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.8);
    margin-top: 0.25rem;
}

/* Statistics Cards */
.stats-card {
    border-radius: 10px;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

/* Action Cards */
.action-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    height: 100%;
}

.action-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.action-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2rem;
    color: white;
}

.action-card .card-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.action-card .card-text {
    color: #6c757d;
    margin-bottom: 1.5rem;
}

/* User Avatar */
.user-avatar {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.9rem;
}

/* Timeline */
.activity-timeline {
    max-height: 400px;
    overflow-y: auto;
}

.timeline-icon {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.timeline-content {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
    border: 1px solid #e9ecef;
}

/* Cards */
.card {
    border: none;
    border-radius: 15px;
}

.card-header {
    border-bottom: 1px solid #e9ecef;
    border-radius: 15px 15px 0 0 !important;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-name {
        font-size: 2rem;
    }
    
    .admin-details {
        flex-direction: column;
        gap: 1rem;
    }
    
    .action-card {
        padding: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .action-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .quick-stats {
        margin-top: 1rem;
    }
}

@media (max-width: 576px) {
    .admin-profile-header {
        padding: 1.5rem;
    }
    
    .admin-name {
        font-size: 1.8rem;
    }
    
    .stat-number {
        font-size: 1.2rem;
    }
    
    .action-card {
        padding: 1rem;
    }
}
</style>

<script>
// Funcție pentru generarea raportului Excel
function generateReport(type) {
    if (type === 'excel') {
        const originalBtn = event.target.closest('a');
        const originalContent = originalBtn.innerHTML;
        originalBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Se generează...';
        
        generateExcelReport();
        
        setTimeout(() => {
            originalBtn.innerHTML = originalContent;
        }, 3000);
    }
}

function generateExcelReport() {
    const now = new Date();
    const romaniaTime = new Intl.DateTimeFormat('ro-RO', {
        timeZone: 'Europe/Bucharest',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    }).format(now);
    
    const reportData = [];
    
    reportData.push(['RAPORT ACTIVITATE PLATFORMĂ EDUCAȚIE FINANCIARĂ']);
    reportData.push(['Generat la data: ' + romaniaTime + ' (Ora României)']);
    reportData.push([]);
    
    reportData.push(['STATISTICI GENERALE']);
    reportData.push(['Tip Statistică', 'Valoare']);
    reportData.push(['Utilizatori activi', '<?= $total_users ?>']);
    reportData.push(['Cursuri active', '<?= $total_courses ?>']);
    reportData.push(['Total înscrierii', '<?= $total_enrollments ?>']);
    reportData.push(['Venituri totale', '<?= formatPrice($total_revenue) ?>']);
    reportData.push([]);
    
    reportData.push(['UTILIZATORI RECENȚI']);
    reportData.push(['Nume', 'Email', 'Cursuri înscrise', 'Data înregistrării']);
    <?php foreach ($recent_users as $user): ?>
    reportData.push(['<?= sanitizeInput($user['nume']) ?>', '<?= sanitizeInput($user['email']) ?>', '<?= $user['cursuri_inscrise'] ?>', '<?= date('d.m.Y', strtotime($user['data_inregistrare'])) ?>']);
    <?php endforeach; ?>
    reportData.push([]);
    
    reportData.push(['CURSURI POPULARE']);
    reportData.push(['Titlu curs', 'Preț', 'Înscriși', 'Finalizați', 'În coș']);
    <?php foreach ($popular_courses as $course): ?>
    reportData.push(['<?= sanitizeInput($course['titlu']) ?>', '<?= formatPrice($course['pret']) ?>', '<?= $course['enrolled_count'] ?>', '<?= $course['completed_count'] ?>', '<?= $course['cart_count'] ?>']);
    <?php endforeach; ?>
    reportData.push([]);
    
    reportData.push(['ACTIVITATE RECENTĂ (ultimele 7 zile)']);
    reportData.push(['Tip activitate', 'Utilizator', 'Detalii', 'Data']);
    <?php foreach ($recent_activity as $activity): ?>
    <?php if ($activity['type'] == 'enrollment'): ?>
    reportData.push(['Înscriere curs', '<?= sanitizeInput($activity['user_name']) ?>', '<?= sanitizeInput($activity['course_title']) ?>', '<?= date('d.m.Y H:i', strtotime($activity['date'])) ?>']);
    <?php else: ?>
    reportData.push(['Înregistrare utilizator', '<?= sanitizeInput($activity['user_name']) ?>', 'Cont nou creat', '<?= date('d.m.Y H:i', strtotime($activity['date'])) ?>']);
    <?php endif; ?>
    <?php endforeach; ?>
    
    let csvContent = "data:text/csv;charset=utf-8,\uFEFF";
    reportData.forEach(function(rowArray) {
        let row = rowArray.map(cell => {
            if (typeof cell === 'string' && (cell.includes(',') || cell.includes('"') || cell.includes('\n'))) {
                return '"' + cell.replace(/"/g, '""') + '"';
            }
            return cell;
        }).join(",");
        csvContent += row + "\r\n";
    });
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    
    const timestamp = now.toISOString().slice(0,19).replace(/:/g, '-');
    link.setAttribute("download", `raport_activitate_${timestamp}.csv`);
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Raportul a fost generat și descărcat cu succes!', 'success');
}

function showComingSoon(feature) {
    showNotification(`Funcția "${feature}" va fi disponibilă în curând!`, 'info');
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'info' ? 'info-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Smooth scrolling pentru linkuri interne
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

// Auto-refresh pentru activitatea recentă
setInterval(function() {
    // Se poate adăuga AJAX pentru actualizare automată
}, 300000);
</script>

<?php include '../components/footer.php'; ?>-card {
