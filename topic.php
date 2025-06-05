<?php
require_once 'config.php';

$topic_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$topic_id) {
    $_SESSION['error_message'] = 'Topic invalid!';
    redirectTo('comunitate.php');
}

// Incrementează vizualizările
try {
    $stmt = $pdo->prepare("UPDATE topicuri_comunitate SET vizualizari = vizualizari + 1 WHERE id = ?");
    $stmt->execute([$topic_id]);
} catch (PDOException $e) {
    // Nu afișa eroare pentru vizualizări
}

// Obține detaliile topicului
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.nume as autor_nume, u.email as autor_email, u.rol as autor_rol, u.avatar as autor_avatar,
               COUNT(DISTINCT l.id) as nr_likes,
               COUNT(DISTINCT c.id) as nr_comentarii,
               EXISTS(SELECT 1 FROM likes_topicuri WHERE topic_id = t.id AND user_id = ?) as user_liked
        FROM topicuri_comunitate t
        LEFT JOIN users u ON t.autor_id = u.id
        LEFT JOIN likes_topicuri l ON t.id = l.topic_id
        LEFT JOIN comentarii_topicuri c ON t.id = c.topic_id AND c.activ = 1
        WHERE t.id = ? AND t.activ = 1
        GROUP BY t.id
    ");
    $stmt->execute([isLoggedIn() ? $_SESSION['user_id'] : 0, $topic_id]);
    $topic = $stmt->fetch();
    
    if (!$topic) {
        $_SESSION['error_message'] = 'Topicul nu a fost găsit!';
        redirectTo('comunitate.php');
    }
    
    // Obține comentariile
    $stmt = $pdo->prepare("
        SELECT c.*, u.nume, u.email, u.rol, u.avatar
        FROM comentarii_topicuri c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.topic_id = ? AND c.activ = 1
        ORDER BY c.data_comentariu DESC
    ");
    $stmt->execute([$topic_id]);
    $comentarii = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Eroare la încărcarea topicului!';
    redirectTo('comunitate.php');
}

// Procesează adăugarea comentariului (doar pentru utilizatori autentificați)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Token de securitate invalid!';
    } else {
        $continut = sanitizeInput($_POST['continut'] ?? '');
        
        if (empty($continut)) {
            $_SESSION['error_message'] = 'Comentariul nu poate fi gol!';
        } elseif (strlen($continut) < 5) {
            $_SESSION['error_message'] = 'Comentariul trebuie să aibă minim 5 caractere!';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO comentarii_topicuri (topic_id, user_id, continut, data_comentariu, activ)
                    VALUES (?, ?, ?, NOW(), 1)
                ");
                $stmt->execute([$topic_id, $_SESSION['user_id'], $continut]);
                
                $_SESSION['success_message'] = 'Comentariul a fost adăugat cu succes!';
                redirectTo("topic.php?id=$topic_id#comentarii");
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'A apărut o eroare la adăugarea comentariului!';
            }
        }
    }
}

$page_title = sanitizeInput($topic['titlu']) . ' - Comunitate - ' . SITE_NAME;
include 'components/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Afișează mesajele -->
            <?= displaySessionMessages() ?>

            <!-- Buton înapoi -->
            <a href="comunitate.php" class="btn btn-outline-secondary mb-4">
                <i class="fas fa-arrow-left me-2"></i>Înapoi la comunitate
            </a>

            <!-- Topic principal -->
            <div class="card mb-4" id="main-topic">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <?php if ($topic['pinned']): ?>
                                <span class="badge bg-warning mb-2">
                                    <i class="fas fa-thumbtack me-1"></i>Topic Pinned
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Acțiuni Admin -->
                        <?php if (isAdmin()): ?>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-cog"></i> Admin
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="topic-nou.php?edit=<?= $topic_id ?>">
                                            <i class="fas fa-edit me-2"></i>Editează Topic
                                        </a>
                                    </li>
                                    <li>
                                        <button class="dropdown-item text-warning" onclick="togglePinTopic(<?= $topic_id ?>)">
                                            <i class="fas fa-thumbtack me-2"></i>
                                            <?= $topic['pinned'] ? 'Dezlipește' : 'Lipește' ?> Topic
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item text-danger" onclick="deleteTopic(<?= $topic_id ?>)">
                                            <i class="fas fa-trash me-2"></i>Șterge Topic
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="h3 mb-3"><?= sanitizeInput($topic['titlu']) ?></h1>
                    
                    <!-- Informații autor -->
                    <div class="d-flex align-items-center mb-3">
                        <div class="author-avatar me-3">
                            <?php if ($topic['autor_avatar']): ?>
                                <img src="<?= UPLOAD_PATH . 'avatare/' . $topic['autor_avatar'] ?>" 
                                     alt="Avatar" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" 
                                     style="width: 50px; height: 50px;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="fw-bold">
                                <?= sanitizeInput($topic['autor_nume']) ?>
                                <?php if ($topic['autor_rol'] === 'admin'): ?>
                                    <span class="badge bg-primary ms-1">Admin</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-clock me-1"></i>
                                <?= date('d.m.Y H:i', strtotime($topic['data_creare'])) ?>
                                <span class="mx-2">•</span>
                                <i class="fas fa-eye me-1"></i>
                                <?= $topic['vizualizari'] ?> vizualizări
                                <span class="mx-2">•</span>
                                <i class="fas fa-comments me-1"></i>
                                <span id="comments-count"><?= $topic['nr_comentarii'] ?></span> comentarii
                            </div>
                        </div>
                    </div>
                    
                    <!-- Conținut topic -->
                    <div class="topic-content mb-4">
                        <?= nl2br(sanitizeInput($topic['continut'])) ?>
                    </div>
                    
                    <!-- Acțiuni -->
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <?php if (isLoggedIn()): ?>
                                <button class="btn btn-outline-danger btn-sm like-btn <?= $topic['user_liked'] ? 'liked' : '' ?>"
                                        data-topic-id="<?= $topic['id'] ?>">
                                    <i class="<?= $topic['user_liked'] ? 'fas' : 'far' ?> fa-heart"></i> 
                                    <span class="like-count"><?= $topic['nr_likes'] ?></span>
                                </button>
                            <?php else: ?>
                                <span class="btn btn-outline-secondary btn-sm disabled">
                                    <i class="far fa-heart"></i> <?= $topic['nr_likes'] ?>
                                </span>
                                <small class="text-muted ms-2">Conectează-te pentru a da like</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="share-buttons">
                            <button class="btn btn-outline-secondary btn-sm" onclick="shareOnFacebook()">
                                <i class="fab fa-facebook-f"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="shareOnTwitter()">
                                <i class="fab fa-twitter"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="copyLink()">
                                <i class="fas fa-link"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secțiune comentarii -->
            <div class="card" id="comentarii">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-comments me-2"></i>Comentarii (<span id="comment-header-count"><?= count($comentarii) ?></span>)
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Formular adăugare comentariu -->
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <div class="mb-3">
                                <label for="continut" class="form-label">Adaugă un comentariu:</label>
                                <textarea name="continut" id="continut" class="form-control" rows="3" 
                                          placeholder="Scrie comentariul tău aici..." required minlength="5"></textarea>
                                <div class="form-text">Minim 5 caractere</div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Trimite comentariu
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <a href="login.php?redirect=topic.php?id=<?= $topic_id ?>">Conectează-te</a> 
                            pentru a putea comenta și da like.
                        </div>
                    <?php endif; ?>

                    <!-- Lista comentarii -->
                    <div class="comments-list" id="comments-container">
                        <?php if (empty($comentarii)): ?>
                            <p class="text-muted text-center py-4" id="no-comments-message">
                                Nu există comentarii încă. 
                                <?php if (isLoggedIn()): ?>
                                    Fii primul care comentează!
                                <?php endif; ?>
                            </p>
                        <?php else: ?>
                            <?php foreach ($comentarii as $comentariu): ?>
                                <div class="comment mb-4 pb-3 border-bottom" id="comment-<?= $comentariu['id'] ?>">
                                    <div class="d-flex">
                                        <div class="comment-avatar me-3">
                                            <?php if ($comentariu['avatar']): ?>
                                                <img src="<?= UPLOAD_PATH . 'avatare/' . $comentariu['avatar'] ?>" 
                                                     alt="Avatar" class="rounded-circle" 
                                                     style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="comment-header">
                                                <strong><?= sanitizeInput($comentariu['nume']) ?></strong>
                                                <?php if ($comentariu['rol'] === 'admin'): ?>
                                                    <span class="badge bg-primary ms-1">Admin</span>
                                                <?php endif; ?>
                                                <span class="text-muted ms-2 small">
                                                    <?= timeAgo($comentariu['data_comentariu']) ?>
                                                    <?php if ($comentariu['data_actualizare'] != $comentariu['data_comentariu']): ?>
                                                        <i class="fas fa-edit ms-1" title="Editat"></i>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div class="comment-content mt-2" id="comment-content-<?= $comentariu['id'] ?>">
                                                <?= nl2br(sanitizeInput($comentariu['continut'])) ?>
                                            </div>
                                            
                                            <?php if (isLoggedIn() && ($_SESSION['user_id'] == $comentariu['user_id'] || isAdmin())): ?>
                                                <div class="comment-actions mt-2">
                                                    <?php if ($_SESSION['user_id'] == $comentariu['user_id']): ?>
                                                        <button class="btn btn-link btn-sm text-muted p-0 me-2" 
                                                                onclick="editComment(<?= $comentariu['id'] ?>)">
                                                            <i class="fas fa-edit me-1"></i>Editează
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($_SESSION['user_id'] == $comentariu['user_id'] || isAdmin()): ?>
                                                        <button class="btn btn-link btn-sm text-danger p-0" 
                                                                onclick="deleteComment(<?= $comentariu['id'] ?>)">
                                                            <i class="fas fa-trash me-1"></i>Șterge
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pentru editare comentariu -->
<div class="modal fade" id="editCommentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editează Comentariul</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="editCommentContent" class="form-label">Conținut comentariu:</label>
                    <textarea class="form-control" id="editCommentContent" rows="4" 
                              placeholder="Scrie comentariul tău aici..." minlength="5"></textarea>
                    <div class="form-text">Minim 5 caractere</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-primary" id="saveCommentBtn">
                    <i class="fas fa-save me-2"></i>Salvează
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.topic-content {
    font-size: 1.1rem;
    line-height: 1.7;
}

.like-btn {
    transition: all 0.3s ease;
}

.like-btn:hover {
    transform: scale(1.05);
}

.like-btn.liked {
    color: #dc3545;
    border-color: #dc3545;
}

.like-btn.liked:hover {
    background-color: #dc3545;
    color: white;
}

.comment {
    transition: all 0.3s ease;
}

.comment:hover {
    background-color: #f8f9fa;
    margin-left: -10px;
    margin-right: -10px;
    padding-left: 10px;
    padding-right: 10px;
}

.share-buttons button {
    transition: all 0.3s ease;
}

.share-buttons button:hover {
    transform: translateY(-2px);
}

.author-avatar img,
.comment-avatar img {
    border: 3px solid #e9ecef;
}

.comment-actions .btn {
    font-size: 0.875rem;
}

.comment.editing {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
}

.topic-deleting {
    opacity: 0.5;
    pointer-events: none;
}

.admin-actions {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.card:hover .admin-actions {
    opacity: 1;
}
</style>

<script>
<?php if (isLoggedIn()): ?>
// Like/Unlike functionality
document.querySelector('.like-btn').addEventListener('click', async function() {
    const topicId = this.dataset.topicId;
    const button = this;
    
    // Adaugă stare de loading
    button.disabled = true;
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span class="like-count">' + 
                      button.querySelector('.like-count').textContent + '</span>';
    
    try {
        const response = await fetch('ajax/like-topic.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ topic_id: parseInt(topicId) })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const icon = button.querySelector('i');
            const count = button.querySelector('.like-count');
            
            if (data.action === 'added') {
                icon.classList.remove('far');
                icon.classList.add('fas');
                button.classList.add('liked');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                button.classList.remove('liked');
            }
            
            count.textContent = data.likes;
            
            // Animație
            button.style.transform = 'scale(1.2)';
            setTimeout(() => {
                button.style.transform = 'scale(1)';
            }, 200);
            
            // Restaurează conținutul original cu noul număr
            const iconClass = data.action === 'added' ? 'fas' : 'far';
            button.innerHTML = `<i class="${iconClass} fa-heart"></i> <span class="like-count">${data.likes}</span>`;
        } else {
            alert(data.message || 'A apărut o eroare!');
            button.innerHTML = originalContent;
        }
    } catch (error) {
        console.error('Error:', error);
        alert('A apărut o eroare la procesarea cererii!');
        button.innerHTML = originalContent;
    } finally {
        button.disabled = false;
    }
});

// Variabilă globală pentru comentariul în curs de editare
let currentEditingCommentId = null;

// Edit comment functionality
async function editComment(commentId) {
    if (currentEditingCommentId) {
        alert('Finalizează mai întâi editarea comentariului curent!');
        return;
    }
    
    try {
        // Obține conținutul comentariului
        const response = await fetch('ajax/edit-comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                comment_id: commentId,
                action: 'get'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentEditingCommentId = commentId;
            
            // Populează modalul cu conținutul actual
            document.getElementById('editCommentContent').value = data.content;
            
            // Marchează comentariul ca fiind în editare
            document.getElementById(`comment-${commentId}`).classList.add('editing');
            
            // Afișează modalul
            const modal = new bootstrap.Modal(document.getElementById('editCommentModal'));
            modal.show();
            
            // Event listener pentru salvare
            document.getElementById('saveCommentBtn').onclick = () => saveComment(commentId);
            
            // Event listener pentru închiderea modalului
            const modalElement = document.getElementById('editCommentModal');
            modalElement.addEventListener('hidden.bs.modal', function() {
                if (currentEditingCommentId) {
                    document.getElementById(`comment-${currentEditingCommentId}`).classList.remove('editing');
                    currentEditingCommentId = null;
                }
            });
            
        } else {
            alert(data.message || 'Nu s-a putut încărca comentariul pentru editare!');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('A apărut o eroare la încărcarea comentariului!');
    }
}

// Save edited comment
async function saveComment(commentId) {
    const content = document.getElementById('editCommentContent').value.trim();
    const saveBtn = document.getElementById('saveCommentBtn');
    
    if (content.length < 5) {
        alert('Comentariul trebuie să aibă minim 5 caractere!');
        return;
    }
    
    // Adaugă stare de loading
    saveBtn.disabled = true;
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvează...';
    
    try {
        const response = await fetch('ajax/edit-comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                comment_id: commentId,
                action: 'save',
                content: content
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Actualizează conținutul comentariului în pagină
            document.getElementById(`comment-content-${commentId}`).innerHTML = data.content;
            
            // Închide modalul
            bootstrap.Modal.getInstance(document.getElementById('editCommentModal')).hide();
            
            // Afișează mesaj de succes
            showNotification(data.message, 'success');
            
        } else {
            alert(data.message || 'Nu s-a putut salva comentariul!');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('A apărut o eroare la salvarea comentariului!');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    }
}

// Delete comment functionality
async function deleteComment(commentId) {
    if (!confirm('Ești sigur că vrei să ștergi acest comentariu? Această acțiune nu poate fi anulată.')) {
        return;
    }
    
    try {
        const response = await fetch('ajax/delete-comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ comment_id: commentId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Animație de fade out
            const commentElement = document.getElementById(`comment-${commentId}`);
            commentElement.style.transition = 'opacity 0.5s ease';
            commentElement.style.opacity = '0';
            
            setTimeout(() => {
                commentElement.remove();
                
                // Actualizează numărul de comentarii
                const commentCount = document.getElementById('comment-header-count');
                const mainCommentsCount = document.getElementById('comments-count');
                const currentCount = parseInt(commentCount.textContent);
                const newCount = currentCount - 1;
                
                commentCount.textContent = newCount;
                mainCommentsCount.textContent = newCount;
                
                // Verifică dacă nu mai sunt comentarii
                const remainingComments = document.querySelectorAll('.comments-list .comment');
                if (remainingComments.length === 0) {
                    document.getElementById('comments-container').innerHTML = `
                        <p class="text-muted text-center py-4" id="no-comments-message">
                            Nu există comentarii încă. 
                            <?php if (isLoggedIn()): ?>
                                Fii primul care comentează!
                            <?php endif; ?>
                        </p>
                    `;
                }
            }, 500);
            
            showNotification(data.message, 'success');
            
        } else {
            alert(data.message || 'Nu s-a putut șterge comentariul!');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('A apărut o eroare la ștergerea comentariului!');
    }
}

<?php if (isAdmin()): ?>
// Delete topic functionality (Admin only)
async function deleteTopic(topicId) {
    if (!confirm('Ești sigur că vrei să ștergi acest topic? Această acțiune va șterge și toate comentariile și like-urile asociate și nu poate fi anulată.')) {
        return;
    }
    
    // Confirmă din nou pentru siguranță
    if (!confirm('ATENȚIE: Această acțiune va șterge permanent topicul și toate datele asociate. Confirmi?')) {
        return;
    }
    
    const mainTopic = document.getElementById('main-topic');
    mainTopic.classList.add('topic-deleting');
    
    try {
        const response = await fetch('ajax/delete-topic.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ topic_id: topicId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            
            // Redirecționează către comunitate după 2 secunde
            setTimeout(() => {
                window.location.href = 'comunitate.php';
            }, 2000);
            
        } else {
            alert(data.message || 'Nu s-a putut șterge topicul!');
            mainTopic.classList.remove('topic-deleting');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('A apărut o eroare la ștergerea topicului!');
        mainTopic.classList.remove('topic-deleting');
    }
}

// Toggle pin topic functionality
async function togglePinTopic(topicId) {
    try {
        const response = await fetch('ajax/pin-topic.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ topic_id: topicId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Actualizează badge-ul pinned
            const badgeContainer = document.querySelector('.badge.bg-warning');
            const dropdownButton = document.querySelector('[onclick*="togglePinTopic"]');
            
            if (data.pinned) {
                // Adaugă badge-ul dacă nu există
                if (!badgeContainer) {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'badge bg-warning mb-2';
                    newBadge.innerHTML = '<i class="fas fa-thumbtack me-1"></i>Topic Pinned';
                    
                    const titleElement = document.querySelector('.h3');
                    titleElement.parentNode.insertBefore(newBadge, titleElement);
                }
                
                // Actualizează textul butonului
                if (dropdownButton) {
                    dropdownButton.innerHTML = '<i class="fas fa-thumbtack me-2"></i>Dezlipește Topic';
                }
            } else {
                // Elimină badge-ul
                if (badgeContainer) {
                    badgeContainer.remove();
                }
                
                // Actualizează textul butonului
                if (dropdownButton) {
                    dropdownButton.innerHTML = '<i class="fas fa-thumbtack me-2"></i>Lipește Topic';
                }
            }
            
            showNotification(data.message, 'success');
            
        } else {
            alert(data.message || 'Nu s-a putut actualiza statusul topic-ului!');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('A apărut o eroare la procesarea cererii!');
    }
}
<?php endif; ?>

<?php endif; ?>

// Funcție pentru afișarea notificărilor
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

// Share functions
function shareOnFacebook() {
    const url = window.location.href;
    const title = <?= json_encode($topic['titlu']) ?>;
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}&quote=${encodeURIComponent(title)}`, '_blank');
}

function shareOnTwitter() {
    const url = window.location.href;
    const title = <?= json_encode($topic['titlu']) ?>;
    window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`, '_blank');
}

function copyLink() {
    const url = window.location.href;
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            showNotification('Link-ul a fost copiat!', 'success');
        });
    } else {
        const textArea = document.createElement('textarea');
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('Link-ul a fost copiat!', 'success');
    }
}

// Auto-resize textarea
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.querySelector('textarea[name="continut"]');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    }
    
    // Auto-resize pentru textarea din modal
    const editTextarea = document.getElementById('editCommentContent');
    if (editTextarea) {
        editTextarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    }
});
</script>

<?php include 'components/footer.php'; ?>