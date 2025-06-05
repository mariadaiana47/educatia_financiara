<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = MSG_ERROR_ACCESS_DENIED;
    redirectTo('../login.php');
}

$page_title = 'Gestionare Utilizatori - Admin';

// Filtre
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Query de bază
$where_conditions = ['1=1'];
$params = [];

if ($search) {
    $where_conditions[] = '(nume LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter && $role_filter !== 'all') {
    $where_conditions[] = 'rol = ?';
    $params[] = $role_filter;
}

if ($status_filter && $status_filter !== 'all') {
    $where_conditions[] = 'activ = ?';
    $params[] = $status_filter === 'active' ? 1 : 0;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Numărul total de utilizatori
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where_clause");
    $stmt->execute($params);
    $total_users = $stmt->fetchColumn();
    
    // Utilizatorii cu paginare
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(DISTINCT ic.curs_id) as cursuri_inscrise,
               COUNT(DISTINCT cc.curs_id) as cursuri_in_cos,
               SUM(CASE WHEN ic.finalizat = 1 THEN 1 ELSE 0 END) as cursuri_finalizate
        FROM users u
        LEFT JOIN inscrieri_cursuri ic ON u.id = ic.user_id
        LEFT JOIN cos_cumparaturi cc ON u.id = cc.user_id
        WHERE $where_clause
        GROUP BY u.id
        ORDER BY u.data_inregistrare DESC
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    $total_pages = ceil($total_users / $per_page);
    
} catch (PDOException $e) {
    $users = [];
    $total_users = 0;
    $total_pages = 0;
}

include '../components/header.php';
?>

<style>
.user-row.updating {
    opacity: 0.6;
    pointer-events: none;
}

.role-select:disabled {
    opacity: 0.6;
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.user-actions {
    position: relative;
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
}

.user-details-modal .modal-content {
    border-radius: 15px;
}

.user-details-modal .user-avatar-large {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    margin: 0 auto 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.stat-card {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #667eea;
}

.stat-label {
    font-size: 0.85rem;
    color: #666;
    font-weight: 500;
}
</style>

<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 mb-2">
                <i class="fas fa-users me-2"></i>Gestionare Utilizatori
            </h1>
            <p class="text-muted">
                Administrează utilizatorii platformei și permisiunile acestora
            </p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="dashboard-admin.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Înapoi la Dashboard
            </a>
        </div>
    </div>

    <!-- Filtre -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Căutare</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= sanitizeInput($search) ?>" 
                           placeholder="Nume sau email...">
                </div>
                <div class="col-md-3">
                    <label for="role" class="form-label">Rol</label>
                    <select class="form-select" id="role" name="role">
                        <option value="all" <?= $role_filter === 'all' ? 'selected' : '' ?>>Toate rolurile</option>
                        <option value="user" <?= $role_filter === 'user' ? 'selected' : '' ?>>Utilizator</option>
                        <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Toate</option>
                        <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Activ</option>
                        <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactiv</option>
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

    <!-- Rezultate -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                Utilizatori găsiți: <span class="text-primary"><?= number_format($total_users) ?></span>
            </h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="refreshUserList()">
                <i class="fas fa-sync-alt me-1"></i>Reîmprospătează
            </button>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($users)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Utilizator</th>
                                <th>Rol</th>
                                <th>Status</th>
                                <th>Cursuri</th>
                                <th>Înregistrat</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <?php foreach ($users as $user): ?>
                                <tr class="user-row" data-user-id="<?= $user['id'] ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <?php if ($user['avatar']): ?>
                                                    <img src="<?= UPLOAD_PATH . 'avatare/' . $user['avatar'] ?>" 
                                                         alt="Avatar" class="rounded-circle" 
                                                         style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" 
                                                         style="width: 40px; height: 40px;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <strong><?= sanitizeInput($user['nume']) ?></strong>
                                                <br>
                                                <small class="text-muted"><?= sanitizeInput($user['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm role-select" 
                                                data-user-id="<?= $user['id'] ?>"
                                                <?= $user['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                            <option value="user" <?= $user['rol'] === 'user' ? 'selected' : '' ?>>
                                                Utilizator
                                            </option>
                                            <option value="admin" <?= $user['rol'] === 'admin' ? 'selected' : '' ?>>
                                                Admin
                                            </option>
                                        </select>
                                    </td>
                                    <td class="status-cell">
                                        <?php if ($user['activ']): ?>
                                            <span class="badge bg-success">Activ</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactiv</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div><i class="fas fa-graduation-cap me-1"></i> <?= $user['cursuri_inscrise'] ?> înscris</div>
                                            <div><i class="fas fa-check-circle me-1"></i> <?= $user['cursuri_finalizate'] ?> finalizat</div>
                                            <div><i class="fas fa-shopping-cart me-1"></i> <?= $user['cursuri_in_cos'] ?> în coș</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <?= date('d.m.Y', strtotime($user['data_inregistrare'])) ?>
                                            <br>
                                            <span class="text-muted"><?= timeAgo($user['data_inregistrare']) ?></span>
                                        </div>
                                    </td>
                                    <td class="user-actions">
                                        <div class="btn-group" role="group">
                                            <!-- Toggle Status -->
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button type="button" 
                                                        class="btn btn-sm <?= $user['activ'] ? 'btn-outline-warning' : 'btn-outline-success' ?> toggle-status-btn"
                                                        data-user-id="<?= $user['id'] ?>"
                                                        title="<?= $user['activ'] ? 'Dezactivează' : 'Activează' ?>">
                                                    <i class="fas <?= $user['activ'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- Vezi detalii -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-info view-details-btn" 
                                                    data-user-id="<?= $user['id'] ?>"
                                                    title="Vezi detalii">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginare -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Paginare utilizatori">
                            <ul class="pagination justify-content-center mb-0">
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
                    <a href="utilizatori.php" class="btn btn-primary">
                        <i class="fas fa-refresh me-2"></i>Vezi toți utilizatorii
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal pentru detalii utilizator -->
<div class="modal fade user-details-modal" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user me-2"></i>Detalii Utilizator
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="userDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Se încarcă...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Închide
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Container pentru notificări -->
<div id="notificationContainer"></div>

<script>
// Configurare AJAX
const AJAX_CONFIG = {
    url: '../ajax/user-actions.php',
    csrf_token: '<?= generateCSRFToken() ?>'
};

// Funcție pentru afișarea notificărilor
function showNotification(message, type = 'success') {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const iconClass = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show notification`;
    notification.innerHTML = `
        <i class="${iconClass} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.getElementById('notificationContainer').appendChild(notification);
    
    // Auto-remove după 5 secunde
    setTimeout(() => {
        if (notification.parentNode) {
            const bsAlert = new bootstrap.Alert(notification);
            bsAlert.close();
        }
    }, 5000);
}

// Funcție pentru loading overlay
function showLoading(element) {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
    element.style.position = 'relative';
    element.appendChild(overlay);
    return overlay;
}

function hideLoading(overlay) {
    if (overlay && overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
    }
}

// Toggle status utilizator
document.addEventListener('click', function(e) {
    if (e.target.closest('.toggle-status-btn')) {
        const button = e.target.closest('.toggle-status-btn');
        const userId = button.dataset.userId;
        const userRow = document.querySelector(`tr[data-user-id="${userId}"]`);
        
        if (userRow.classList.contains('updating')) return;
        
        userRow.classList.add('updating');
        const overlay = showLoading(button.closest('.user-actions'));
        
        fetch(AJAX_CONFIG.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'toggle_status',
                user_id: parseInt(userId),
                csrf_token: AJAX_CONFIG.csrf_token
            })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading(overlay);
            userRow.classList.remove('updating');
            
            if (data.success) {
                // Actualizează badge-ul de status
                const statusCell = userRow.querySelector('.status-cell');
                statusCell.innerHTML = data.badge_html;
                
                // Actualizează butonul
                button.className = `btn btn-sm ${data.button_class} toggle-status-btn`;
                button.title = data.button_title;
                button.querySelector('i').className = `fas ${data.button_icon}`;
                
                showNotification(data.message, 'success');
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading(overlay);
            userRow.classList.remove('updating');
            console.error('Error:', error);
            showNotification('A apărut o eroare neașteptată', 'error');
        });
    }
});

// Schimbarea rolului utilizator
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('role-select')) {
        const select = e.target;
        const userId = select.dataset.userId;
        const newRole = select.value;
        const userRow = document.querySelector(`tr[data-user-id="${userId}"]`);
        const originalValue = select.querySelector('option[selected]')?.value || select.value;
        
        if (userRow.classList.contains('updating')) {
            select.value = originalValue;
            return;
        }
        
        userRow.classList.add('updating');
        select.disabled = true;
        const overlay = showLoading(select.closest('.user-actions'));
        
        fetch(AJAX_CONFIG.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'change_role',
                user_id: parseInt(userId),
                new_role: newRole,
                csrf_token: AJAX_CONFIG.csrf_token
            })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading(overlay);
            userRow.classList.remove('updating');
            select.disabled = false;
            
            if (data.success) {
                // Actualizează opțiunea selectată
                select.querySelectorAll('option').forEach(option => {
                    option.removeAttribute('selected');
                    if (option.value === data.new_role) {
                        option.setAttribute('selected', 'selected');
                    }
                });
                
                showNotification(data.message, 'success');
            } else {
                // Revine la valoarea originală
                select.value = originalValue;
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading(overlay);
            userRow.classList.remove('updating');
            select.disabled = false;
            select.value = originalValue;
            console.error('Error:', error);
            showNotification('A apărut o eroare neașteptată', 'error');
        });
    }
});

// Vizualizarea detaliilor utilizator
document.addEventListener('click', function(e) {
    if (e.target.closest('.view-details-btn')) {
        const button = e.target.closest('.view-details-btn');
        const userId = button.dataset.userId;
        
        // Afișează modal-ul
        const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
        modal.show();
        
        // Încarcă detaliile
        loadUserDetails(userId);
    }
});

// Funcție pentru încărcarea detaliilor utilizator
function loadUserDetails(userId) {
    const contentDiv = document.getElementById('userDetailsContent');
    contentDiv.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Se încarcă...</span>
            </div>
        </div>
    `;
    
    fetch(AJAX_CONFIG.url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'get_user_details',
            user_id: parseInt(userId),
            csrf_token: AJAX_CONFIG.csrf_token
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const user = data.user;
            const avatarInitials = user.nume.split(' ').map(n => n.charAt(0)).join('').toUpperCase();
            
            contentDiv.innerHTML = `
                <div class="text-center mb-4">
                    <div class="user-avatar-large">
                        ${avatarInitials}
                    </div>
                    <h4>${user.nume}</h4>
                    <p class="text-muted">${user.email}</p>
                    <span class="badge ${user.activ ? 'bg-success' : 'bg-danger'} me-2">
                        ${user.activ ? 'Activ' : 'Inactiv'}
                    </span>
                    <span class="badge ${user.rol === 'admin' ? 'bg-primary' : 'bg-secondary'}">
                        ${user.rol === 'admin' ? 'Administrator' : 'Utilizator'}
                    </span>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle me-2"></i>Informații Generale</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>ID:</strong></td>
                                <td>${user.id}</td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td>${user.email}</td>
                            </tr>
                            <tr>
                                <td><strong>Rol:</strong></td>
                                <td>${user.rol === 'admin' ? 'Administrator' : 'Utilizator'}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge ${user.activ ? 'bg-success' : 'bg-danger'}">
                                        ${user.activ ? 'Activ' : 'Inactiv'}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Înregistrat:</strong></td>
                                <td>${user.data_inregistrare}</td>
                            </tr>
                            <tr>
                                <td><strong>Ultima actualizare:</strong></td>
                                <td>${user.data_actualizare}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-graduation-cap me-2"></i>Statistici Cursuri</h6>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-value">${user.cursuri_inscrise}</div>
                                <div class="stat-label">Cursuri Înscrise</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">${user.cursuri_finalizate}</div>
                                <div class="stat-label">Finalizate</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">${user.cursuri_in_cos}</div>
                                <div class="stat-label">În Coș</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">${user.completion_rate}%</div>
                                <div class="stat-label">Rata Finalizare</div>
                            </div>
                        </div>
                        
                        ${user.cursuri_inscrise > 0 ? `
                            <div class="mt-3">
                                <div class="progress mb-2">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: ${user.completion_rate}%">
                                        ${user.completion_rate}%
                                    </div>
                                </div>
                                <small class="text-muted">Progres general cursuri</small>
                            </div>
                        ` : ''}
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>Ultima înscriere:</strong> ${user.ultima_inscriere}
                            </small>
                        </div>
                    </div>
                </div>
            `;
        } else {
            contentDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        contentDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                A apărut o eroare la încărcarea detaliilor
            </div>
        `;
    });
}

// Funcție pentru refresh manual
function refreshUserList() {
    location.reload();
}

// Auto-refresh la fiecare 30 de secunde (opțional)
let autoRefreshInterval;

function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        // Verifică dacă nu sunt operațiuni în curs
        const updatingRows = document.querySelectorAll('.user-row.updating');
        if (updatingRows.length === 0) {
            refreshUserList();
        }
    }, 30000); // 30 secunde
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

// Pornește auto-refresh-ul când pagina se încarcă
document.addEventListener('DOMContentLoaded', function() {
    // Uncomment pentru auto-refresh
    // startAutoRefresh();
    
    // Oprește auto-refresh-ul când utilizatorul părăsește pagina
    window.addEventListener('beforeunload', stopAutoRefresh);
});

// Gestionarea erorilor AJAX globale
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason);
    showNotification('A apărut o eroare de rețea', 'error');
});
</script>

<?php include '../components/footer.php'; ?>