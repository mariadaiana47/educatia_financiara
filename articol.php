<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: blog.php');
    exit;
}

$articol_id = (int) $_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT a.*, u.nume as autor_nume
        FROM articole a
        JOIN users u ON a.autor_id = u.id
        WHERE a.id = ? AND a.activ = 1
    ");
    $stmt->execute([$articol_id]);
    $articol = $stmt->fetch();

    if (!$articol) {
        header('Location: blog.php');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE articole SET vizualizari = vizualizari + 1 WHERE id = ?");
    $stmt->execute([$articol_id]);

    $stmt = $pdo->prepare("
        SELECT a.*, u.nume as autor_nume
        FROM articole a
        JOIN users u ON a.autor_id = u.id
        WHERE a.id != ? AND a.activ = 1
        ORDER BY a.data_publicare DESC
        LIMIT 4
    ");
    $stmt->execute([$articol_id]);
    $articole_similare = $stmt->fetchAll();

} catch (PDOException $e) {
    header('Location: blog.php');
    exit;
}

$page_title = $articol['titlu'] . ' - ' . SITE_NAME;

include 'components/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="index.php" class="text-decoration-none">
                    <i class="fas fa-home me-1"></i>Acasă
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="blog.php" class="text-decoration-none">Blog</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= strlen($articol['titlu']) > 50 ? substr($articol['titlu'], 0, 50) . '...' : $articol['titlu'] ?>
            </li>
        </ol>
    </nav>

    <div class="row">
        <!-- Conținutul principal -->
        <div class="col-lg-8">
            <article class="card shadow-sm">
                <div class="card-body">
                    <!-- Header articol -->
                    <header class="mb-4">
                        <h1 class="h2 mb-3 text-primary">
                            <?= htmlspecialchars($articol['titlu']) ?>
                        </h1>

                        <div class="d-flex align-items-center text-muted mb-4">
                            <div class="me-4">
                                <i class="fas fa-user me-1"></i>
                                <strong><?= htmlspecialchars($articol['autor_nume']) ?></strong>
                            </div>
                            <div class="me-4">
                                <i class="fas fa-calendar me-1"></i>
                                <?= date('d.m.Y', strtotime($articol['data_publicare'])) ?>
                            </div>
                            <div>
                                <i class="fas fa-eye me-1"></i>
                                <?= number_format($articol['vizualizari']) ?> vizualizări
                            </div>
                        </div>
                    </header>

                    <!-- Conținutul articolului -->
                    <div class="article-content">
                        <?php if (!empty($articol['continut_scurt'])): ?>
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle me-2"></i>Rezumat</h5>
                                <p class="mb-0"><?= htmlspecialchars($articol['continut_scurt']) ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="article-body">
                            <?= $articol['continut'] ?>
                        </div>
                    </div>

                    <!-- Footer articol -->
                    <footer class="mt-5 pt-4 border-top">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="blog.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Înapoi la Blog
                                </a>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="btn-group" role="group">
                                    <button class="btn btn-outline-primary btn-sm" onclick="shareOnFacebook()">
                                        <i class="fab fa-facebook-f"></i>
                                    </button>
                                    <button class="btn btn-outline-info btn-sm" onclick="shareOnTwitter()">
                                        <i class="fab fa-twitter"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="copyToClipboard()">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </footer>
                </div>
            </article>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Articole similare -->
            <?php if (!empty($articole_similare)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-newspaper me-2"></i>Alte Articole
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($articole_similare as $index => $art): ?>
                            <div class="mb-3">
                                <h6>
                                    <a href="articol.php?id=<?= $art['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($art['titlu']) ?>
                                    </a>
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?= date('d.m.Y', strtotime($art['data_publicare'])) ?>
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-eye me-1"></i>
                                    <?= $art['vizualizari'] ?> vizualizări
                                </small>
                            </div>
                            <?php if ($index < count($articole_similare) - 1): ?>
                                <hr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Info box -->
            <div class="card mb-4 bg-light">
                <div class="card-body text-center">
                    <i class="fas fa-lightbulb fa-3x text-warning mb-3"></i>
                    <h6>Educația Financiară</h6>
                    <p class="small text-muted mb-3">
                        Dezvoltă-ți cunoștințele financiare cu articolele noastre practice și ușor de înțeles.
                    </p>
                    <a href="blog.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-book me-2"></i>Explorează Blog-ul
                    </a>
                </div>
            </div>

            <!-- Cursuri promovate -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-graduation-cap me-2"></i>Cursuri Recomandate
                    </h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        Completează-ți cunoștințele cu cursurile noastre practice de educație financiară.
                    </p>
                    <a href="cursuri.php" class="btn btn-success btn-sm w-100">
                        <i class="fas fa-play me-2"></i>Vezi Cursurile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function copyToClipboard() {
        const url = window.location.href;

        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function () {
                showNotification('Link-ul a fost copiat!', 'success');
            });
        } else {
            // Fallback pentru browsere mai vechi
            const textArea = document.createElement('textarea');
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showNotification('Link-ul a fost copiat!', 'success');
        }
    }

    function shareOnFacebook() {
        const url = window.location.href;
        const shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
        window.open(shareUrl, '_blank', 'width=600,height=400');
    }

    function shareOnTwitter() {
        const url = window.location.href;
        const title = document.querySelector('h1').textContent;
        const shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
        window.open(shareUrl, '_blank', 'width=600,height=400');
    }

    function showNotification(message, type) {
        // Creăm o notificare simplă
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; opacity: 0; transition: opacity 0.3s;';
        toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'} me-2"></i>${message}`;

        document.body.appendChild(toast);

        // Animație fade in
        setTimeout(() => toast.style.opacity = '1', 100);

        // Eliminăm notificarea după 3 secunde
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }
</script>

<?php include 'components/footer.php'; ?>