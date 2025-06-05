<?php
// Start session dacă nu e pornită
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$page_title = 'Administrare Mesaje Contact - ' . SITE_NAME;

// Conectează la baza de date dacă nu e deja conectată
if (!isset($db)) {
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $db = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } else {
        global $db, $pdo, $connection, $conn;
        if (isset($pdo)) $db = $pdo;
        elseif (isset($connection)) $db = $connection;
        elseif (isset($conn)) $db = $conn;
    }
}

$success_message = null;
$error_message = null;

// Procesează acțiunile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $message_id = $_POST['message_id'] ?? 0;
    
    try {
        switch ($action) {
            case 'mark_read':
                $stmt = $db->prepare("UPDATE contact_messages SET status = 'citit', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$message_id]);
                $success_message = "Mesajul a fost marcat ca citit.";
                break;
                
            case 'close':
                $stmt = $db->prepare("UPDATE contact_messages SET status = 'inchis', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$message_id]);
                $success_message = "Mesajul a fost închis.";
                break;
                
            case 'delete':
                $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
                $stmt->execute([$message_id]);
                $success_message = "Mesajul a fost șters.";
                break;
        }
    } catch (Exception $e) {
        $error_message = "Eroare: " . $e->getMessage();
    }
}

// Filtrare și paginare
$status_filter = $_GET['status'] ?? 'toate';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Construiește query-ul
$where_conditions = [];
$params = [];

if ($status_filter !== 'toate') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(nume LIKE ? OR email LIKE ? OR subiect LIKE ? OR mesaj LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Numără total pentru paginare
$count_query = "SELECT COUNT(*) as total FROM contact_messages {$where_clause}";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $per_page);

// Obține mesajele
$query = "SELECT * FROM contact_messages {$where_clause} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}";
$stmt = $db->prepare($query);
$stmt->execute($params);
$messages = $stmt->fetchAll();

// Statistici rapide
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'nou' THEN 1 ELSE 0 END) as nou,
    SUM(CASE WHEN status = 'citit' THEN 1 ELSE 0 END) as citit,
    SUM(CASE WHEN status = 'inchis' THEN 1 ELSE 0 END) as inchis,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as astazi
FROM contact_messages";
$stats = $db->query($stats_query)->fetch();

include 'components/header.php';
?>

<div class="container-fluid p-5">
    <!-- Header și Statistici -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2 mb-0">
                    Mesaje de contact
                </h1>
                <a href="contact.php" class="btn btn-outline-primary">
                    <i class="fas fa-plus me-2"></i>Vezi pagina de contact!
                </a>
            </div>
            
            <!-- Statistici -->
            <div class="row g-3 mb-4 justify-content-center">
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="stat-card bg-primary text-white">
                        <div class="stat-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= $stats['total'] ?></div>
                            <div class="stat-label">Total</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="stat-card bg-warning text-white">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= $stats['nou'] ?></div>
                            <div class="stat-label">Noi</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="stat-card bg-info text-white">
                        <div class="stat-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= $stats['citit'] ?></div>
                            <div class="stat-label">Citite</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="stat-card bg-secondary text-white">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= $stats['inchis'] ?></div>
                            <div class="stat-label">Închise</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="stat-card bg-gradient-warning text-white">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= $stats['astazi'] ?></div>
                            <div class="stat-label">Astăzi</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mesaje de notificare -->
    <?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?= $error_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Filtre și Căutare -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="toate" <?= $status_filter === 'toate' ? 'selected' : '' ?>>Toate</option>
                        <option value="nou" <?= $status_filter === 'nou' ? 'selected' : '' ?>>Noi</option>
                        <option value="citit" <?= $status_filter === 'citit' ? 'selected' : '' ?>>Citite</option>
                        <option value="inchis" <?= $status_filter === 'inchis' ? 'selected' : '' ?>>Închise</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Căutare</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Caută în nume, email, subiect sau mesaj..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Filtrează
                    </button>
                    <a href="?" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista Mesajelor -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                Mesaje 
                <span class="badge bg-secondary"><?= $total_records ?></span>
            </h5>
            <small class="text-muted">
                Pagina <?= $page ?> din <?= $total_pages ?>
            </small>
        </div>
        <div class="card-body p-0">
            <?php if (empty($messages)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox text-muted" style="font-size: 3rem;"></i>
                <h4 class="text-muted mt-3">Nu sunt mesaje</h4>
                <p class="text-muted">Nu există mesaje care să corespundă criteriilor de filtrare.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Expeditor</th>
                            <th>Subiect</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                        <tr class="message-row <?= $message['status'] === 'nou' ? 'table-warning' : '' ?>" 
                            data-message-id="<?= $message['id'] ?>">
                            <td>
                                <strong>#<?= $message['id'] ?></strong>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <strong><?= htmlspecialchars($message['nume']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($message['email']) ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="message-preview">
                                    <strong><?= htmlspecialchars($message['subiect']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars(substr($message['mesaj'], 0, 100)) ?><?= strlen($message['mesaj']) > 100 ? '...' : '' ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <small>
                                    <?= date('d.m.Y H:i', strtotime($message['created_at'])) ?>
                                </small>
                            </td>
                            <td>
                                <?php
                                $status_badges = [
                                    'nou' => 'bg-warning',
                                    'citit' => 'bg-info',
                                    'inchis' => 'bg-secondary'
                                ];
                                $badge_class = $status_badges[$message['status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= ucfirst($message['status']) ?></span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                            onclick="viewMessage(<?= $message['id'] ?>)" 
                                            title="Vizualizează mesaj">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($message['status'] === 'nou'): ?>
                                    <button type="button" class="btn btn-outline-info btn-sm" 
                                            onclick="markAsRead(<?= $message['id'] ?>)" 
                                            title="Marchează ca citit">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($message['status'] !== 'inchis'): ?>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                                            onclick="closeMessage(<?= $message['id'] ?>)" 
                                            title="Închide mesaj">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm" 
                                            onclick="deleteMessage(<?= $message['id'] ?>)" 
                                            title="Șterge mesaj">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Paginare -->
        <?php if ($total_pages > 1): ?>
        <div class="card-footer">
            <nav aria-label="Paginare mesaje">
                <ul class="pagination justify-content-center mb-0">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal pentru vizualizare mesaj -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mesaj #<span id="modalMessageId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalMessageContent">
                <!-- Conținutul va fi încărcat prin JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
            </div>
        </div>
    </div>
</div>

<!-- Form-uri ascunse pentru acțiuni rapide -->
<form id="quickActionForm" method="POST" style="display: none;">
    <input type="hidden" name="action" id="quickAction">
    <input type="hidden" name="message_id" id="quickMessageId">
</form>

<style>
/* Statistici Cards */
.stat-card {
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    opacity: 0.9;
}

/* Table Styling */
.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.message-row.table-warning {
    background-color: rgba(255, 193, 7, 0.1);
}

.message-preview {
    max-width: 300px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .btn-group-sm .btn {
        padding: 0.25rem 0.4rem;
        font-size: 0.7rem;
    }
    
    .stat-card {
        padding: 1rem;
        text-align: center;
        flex-direction: column;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
}

/* Loading States */
.btn.loading {
    pointer-events: none;
    opacity: 0.6;
}

.btn.loading::after {
    content: "";
    display: inline-block;
    width: 12px;
    height: 12px;
    margin-left: 8px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Gradient Backgrounds */
.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
}
</style>

<script>
// Mesajele din PHP pentru JavaScript
const messages = <?= json_encode($messages) ?>;

// Funcții pentru acțiuni
function viewMessage(messageId) {
    const message = messages.find(m => m.id == messageId);
    if (!message) return;
    
    document.getElementById('modalMessageId').textContent = messageId;
    
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6>Expeditor:</h6>
                <p><strong>${escapeHtml(message.nume)}</strong><br>
                <a href="mailto:${escapeHtml(message.email)}">${escapeHtml(message.email)}</a></p>
            </div>
            <div class="col-md-6">
                <h6>Data:</h6>
                <p>${new Date(message.created_at).toLocaleString('ro-RO')}</p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h6>Subiect:</h6>
                <p><strong>${escapeHtml(message.subiect)}</strong></p>
            </div>
            <div class="col-md-6">
                <h6>Status:</h6>
                <p><span class="badge bg-info">${message.status}</span></p>
            </div>
        </div>
        <hr>
        <h6>Mesaj:</h6>
        <div class="bg-light p-3 rounded">
            ${escapeHtml(message.mesaj).replace(/\n/g, '<br>')}
        </div>
        <hr>
        <small class="text-muted">
            <strong>IP:</strong> ${message.ip_address || 'N/A'}<br>
            <strong>Browser:</strong> ${message.user_agent || 'N/A'}
        </small>
    `;
    
    document.getElementById('modalMessageContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('messageModal')).show();
}

function markAsRead(messageId) {
    document.getElementById('quickAction').value = 'mark_read';
    document.getElementById('quickMessageId').value = messageId;
    document.getElementById('quickActionForm').submit();
}

function closeMessage(messageId) {
    if (confirm('Sigur vrei să închizi acest mesaj?')) {
        document.getElementById('quickAction').value = 'close';
        document.getElementById('quickMessageId').value = messageId;
        document.getElementById('quickActionForm').submit();
    }
}

function deleteMessage(messageId) {
    if (confirm('Sigur vrei să ștergi definitiv acest mesaj? Această acțiune nu poate fi anulată.')) {
        document.getElementById('quickAction').value = 'delete';
        document.getElementById('quickMessageId').value = messageId;
        document.getElementById('quickActionForm').submit();
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Auto-dismiss alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 300);
        }, 5000);
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey) {
        switch(e.key) {
            case 'f':
            case 'F':
                e.preventDefault();
                document.getElementById('search').focus();
                break;
        }
    }
});
</script>

<?php include 'components/footer.php'; ?>