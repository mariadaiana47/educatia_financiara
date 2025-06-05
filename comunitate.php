<?php
require_once 'config.php';

$page_title = 'Comunitate - ' . SITE_NAME;

// Filtre și sortare
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'recent';
$filter = isset($_GET['filter']) ? sanitizeInput($_GET['filter']) : 'all';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Construiește query-ul
$where_conditions = ['t.activ = 1'];
$params = [];

if ($search) {
    $where_conditions[] = '(t.titlu LIKE ? OR t.continut LIKE ?)';
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($filter === 'pinned') {
    $where_conditions[] = 't.pinned = 1';
}

$where_clause = implode(' AND ', $where_conditions);

// Ordinea
$order_by = 't.pinned DESC, t.data_creare DESC'; // default
switch ($sort) {
    case 'popular':
        $order_by = 't.pinned DESC, nr_likes DESC';
        break;
    case 'commented':
        $order_by = 't.pinned DESC, nr_comentarii DESC';
        break;
    case 'views':
        $order_by = 't.pinned DESC, t.vizualizari DESC';
        break;
}

try {
    // Obține topicurile cu statistici
    $stmt = $pdo->prepare("
        SELECT t.*, u.nume as autor_nume, u.rol as autor_rol,
               COUNT(DISTINCT c.id) as nr_comentarii,
               COUNT(DISTINCT l.id) as nr_likes,
               EXISTS(SELECT 1 FROM likes_topicuri WHERE topic_id = t.id AND user_id = ?) as user_liked
        FROM topicuri_comunitate t
        LEFT JOIN users u ON t.autor_id = u.id
        LEFT JOIN comentarii_topicuri c ON t.id = c.topic_id AND c.activ = 1
        LEFT JOIN likes_topicuri l ON t.id = l.topic_id
        WHERE $where_clause
        GROUP BY t.id
        ORDER BY $order_by
    ");
    
    $stmt->execute(array_merge([isLoggedIn() ? $_SESSION['user_id'] : 0], $params));
    $topicuri = $stmt->fetchAll();
    
    // Statistici generale
    $stmt = $pdo->query("SELECT COUNT(*) FROM topicuri_comunitate WHERE activ = 1");
    $total_topicuri = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM comentarii_topicuri WHERE activ = 1");
    $total_comentarii = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM comentarii_topicuri WHERE activ = 1");
    $membri_activi = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $topicuri = [];
    $total_topicuri = 0;
    $total_comentarii = 0;
    $membri_activi = 0;
}

include 'components/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 mb-2">
                <i class="fas fa-users me-2"></i>Comunitate
            </h1>
            <p class="text-muted">
                Discută despre educație financiară cu alți membri ai comunității
            </p>
        </div>
        <div class="col-md-4 text-md-end">
            <?php if (isAdmin()): ?>
                <a href="topic-nou.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Adaugă Topic
                </a>
            <?php elseif (isLoggedIn()): ?>
                <button class="btn btn-outline-primary" onclick="showInfoModal()">
                    <i class="fas fa-info-circle me-2"></i>Cum particip?
                </button>
            <?php else: ?>
                <a href="login.php?redirect=comunitate.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Conectează-te pentru a participa
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistici -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary"><?= $total_topicuri ?></h3>
                    <p class="mb-0">Topicuri</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success"><?= $total_comentarii ?></h3>
                    <p class="mb-0">Comentarii</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info"><?= $membri_activi ?></h3>
                    <p class="mb-0">Membri Activi</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-warning"><?= count($topicuri) ?></h3>
                    <p class="mb-0">Topicuri Găsite</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtre și căutare -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Caută în comunitate</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= sanitizeInput($search) ?>" 
                           placeholder="Caută topicuri...">
                </div>
                <div class="col-md-3">
                    <label for="sort" class="form-label">Sortare</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Cele mai recente</option>
                        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Cele mai populare</option>
                        <option value="commented" <?= $sort === 'commented' ? 'selected' : '' ?>>Cele mai comentate</option>
                        <option value="views" <?= $sort === 'views' ? 'selected' : '' ?>>Cele mai vizualizate</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter" class="form-label">Filtru</label>
                    <select class="form-select" id="filter" name="filter">
                        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Toate topicurile</option>
                        <option value="pinned" <?= $filter === 'pinned' ? 'selected' : '' ?>>Doar pinned</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>Caută
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista topicurilor -->
    <?php if (!empty($topicuri)): ?>
        <div class="topics-list">
            <?php foreach ($topicuri as $topic): ?>
                <div class="card mb-3 topic-card <?= $topic['pinned'] ? 'pinned' : '' ?>">
                    <div class="card-body">
                        <div class="row align-items-start">
                            <div class="col-md-8">
                                <div class="d-flex align-items-start">
                                    <?php if ($topic['pinned']): ?>
                                        <span class="badge bg-warning me-2" title="Topic important">
                                            <i class="fas fa-thumbtack"></i>
                                        </span>
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1">
                                            <a href="topic.php?id=<?= $topic['id'] ?>" class="text-decoration-none">
                                                <?= sanitizeInput($topic['titlu']) ?>
                                            </a>
                                        </h5>
                                        <p class="text-muted mb-2">
                                            <?= sanitizeInput(truncateText($topic['continut'], 150)) ?>
                                        </p>
                                        <div class="topic-meta">
                                            <span class="me-3">
                                                <i class="fas fa-user me-1"></i>
                                                <?= sanitizeInput($topic['autor_nume']) ?>
                                                <?php if ($topic['autor_rol'] === 'admin'): ?>
                                                    <span class="badge bg-primary ms-1">Admin</span>
                                                <?php endif; ?>
                                            </span>
                                            <span class="me-3">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= timeAgo($topic['data_creare']) ?>
                                            </span>
                                            <span class="me-3">
                                                <i class="fas fa-eye me-1"></i>
                                                <?= $topic['vizualizari'] ?> vizualizări
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="topic-stats">
                                    <div class="d-flex justify-content-end align-items-center gap-3">
                                        <div class="text-center">
                                            <div class="stat-number"><?= $topic['nr_comentarii'] ?></div>
                                            <div class="stat-label">Comentarii</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="stat-number text-danger">
                                                <?php if (isLoggedIn()): ?>
                                                    <button class="btn btn-link p-0 text-decoration-none like-btn <?= $topic['user_liked'] ? 'liked' : '' ?>" 
                                                            data-topic-id="<?= $topic['id'] ?>">
                                                        <i class="<?= $topic['user_liked'] ? 'fas' : 'far' ?> fa-heart"></i>
                                                        <span class="like-count"><?= $topic['nr_likes'] ?></span>
                                                    </button>
                                                <?php else: ?>
                                                    <i class="far fa-heart"></i> <?= $topic['nr_likes'] ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="stat-label">Like-uri</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
            <h4>Nu s-au găsit topicuri</h4>
            <p class="text-muted">
                <?php if ($search): ?>
                    Încearcă să cauți cu alți termeni.
                <?php else: ?>
                    Fii primul care începe o discuție!
                <?php endif; ?>
            </p>
            <?php if (isAdmin()): ?>
                <a href="topic-nou.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Adaugă primul topic
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal informativ pentru utilizatori -->
<?php if (isLoggedIn() && !isAdmin()): ?>
<div class="modal fade" id="infoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cum pot participa în comunitate?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Ca membru al comunității, poți:</p>
                <ul>
                    <li><strong>Comenta</strong> la topicurile existente</li>
                    <li><strong>Da like</strong> topicurilor care îți plac</li>
                    <li><strong>Participa</strong> la discuții constructive</li>
                </ul>
                <p class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Doar administratorii pot crea topicuri noi pentru a menține calitatea discuțiilor.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Am înțeles</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.topic-card {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.topic-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-left-color: var(--primary-color);
}

.topic-card.pinned {
    background-color: #fef9e7;
    border-left-color: #f39c12;
}

.topic-meta {
    font-size: 0.875rem;
    color: #6c757d;
}

.stat-number {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark-color);
}

.stat-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
}

.like-btn {
    transition: all 0.3s ease;
    color: #dc3545;
}

.like-btn:hover {
    transform: scale(1.1);
}

.like-btn.liked i {
    animation: heartBeat 0.5s;
}

@keyframes heartBeat {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}
</style>

<script>
<?php if (isLoggedIn()): ?>
// Funcție pentru like/unlike
document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', async function(e) {
        e.preventDefault();
        const topicId = this.dataset.topicId;
        
        try {
            const response = await fetch('ajax/like-topic.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ topic_id: topicId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                const icon = this.querySelector('i');
                const count = this.querySelector('.like-count');
                
                if (icon.classList.contains('far')) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    this.classList.add('liked');
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    this.classList.remove('liked');
                }
                
                count.textContent = data.likes;
            } else {
                alert(data.message || 'A apărut o eroare!');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('A apărut o eroare la procesarea cererii!');
        }
    });
});

function showInfoModal() {
    const modal = new bootstrap.Modal(document.getElementById('infoModal'));
    modal.show();
}
<?php endif; ?>
</script>

<?php include 'components/footer.php'; ?>