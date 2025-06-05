<?php
require_once 'config.php';

$page_title = 'Dezvoltă-ți inteligența financiară astăzi! - ' . SITE_NAME;

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total_cursuri FROM cursuri WHERE activ = TRUE");
    $total_cursuri = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_utilizatori FROM users WHERE activ = TRUE");
    $total_utilizatori = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_articole FROM articole WHERE activ = TRUE");
    $total_articole = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_completari FROM inscrieri_cursuri WHERE finalizat = TRUE");
    $total_completari = $stmt->fetchColumn();
    
    $stmt = $pdo->query("
        SELECT id, titlu, descriere_scurta, pret, nivel, imagine, 
               (SELECT COUNT(*) FROM inscrieri_cursuri WHERE curs_id = cursuri.id) as enrolled_count
        FROM cursuri 
        WHERE activ = TRUE AND featured = TRUE 
        ORDER BY enrolled_count DESC 
        LIMIT 3
    ");
    $cursuri_recomandate = $stmt->fetchAll();
    
    $stmt = $pdo->query("
        SELECT id, titlu, continut_scurt, data_publicare, vizualizari, imagine,
               (SELECT nume FROM users WHERE id = articole.autor_id) as autor_nume
        FROM articole 
        WHERE activ = TRUE 
        ORDER BY data_publicare DESC 
        LIMIT 3
    ");
    $articole_recente = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $total_cursuri = 5;
    $total_utilizatori = 150;
    $total_articole = 25;
    $total_completari = 300;
    $cursuri_recomandate = [];
    $articole_recente = [];
}

include 'components/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center min-vh-100 py-5">
            <div class="col-lg-6">
                <div class="hero-content">
                    <h1 class="hero-title">
                        Dezvoltă-ți inteligența financiară 
                        <span class="text-secondary">astăzi!</span>
                    </h1>
                    <p class="hero-subtitle">
                        Învață să îți gestionezi banii eficient, să economisești inteligent și să investești 
                        pentru viitorul tău. Alătură-te comunității noastre de peste <?= number_format($total_utilizatori) ?> 
                        de oameni care și-au transformat viața financiară.
                    </p>
                    
                    <div class="hero-actions">
                        <?php if (isLoggedIn()): ?>
                            <a href="dashboard.php" class="btn btn-primary btn-lg me-3 btn-pulse">
                                <i class="fas fa-tachometer-alt me-2"></i>Continuă învățarea
                            </a>
                        <?php else: ?>
                            <a href="cursuri.php" class="btn btn-primary btn-lg me-3 btn-pulse">
                                <i class="fas fa-graduation-cap me-2"></i>Învață acum
                            </a>
                        <?php endif; ?>
                        
                        <a href="instrumente.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-calculator me-2"></i>Instrumente gratuite
                        </a>
                    </div>
                    
                    <!-- Trust indicators -->
                    <div class="hero-stats mt-4">
                        <div class="row">
                            <div class="col-4">
                                <div class="stat-item">
                                    <div class="stat-number"><?= $total_cursuri ?></div>
                                    <div class="stat-label">Cursuri</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <div class="stat-number"><?= number_format($total_completari) ?></div>
                                    <div class="stat-label">Absolvenți</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <div class="stat-number"><?= $total_articole ?></div>
                                    <div class="stat-label">Articole</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="hero-image">
                    <img src="assets/hero-finance.svg" alt="Educație Financiară" class="img-fluid" 
                         onerror="this.src='assets/logo.png'; this.style.maxHeight='300px';">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Beneficiile Educației Financiare -->
<section class="features-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">De ce să îți dezvolți inteligența financiară?</h2>
            <p class="section-subtitle">
                Educația financiară nu este doar despre bani - este despre libertate, securitate și oportunități
            </p>
        </div>
        
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <h4>Economii mai mari</h4>
                    <p>
                        Învață strategii dovedite pentru a economisi mai mult și a-ți construi un fond de urgență solid. 
                        Studenții noștri economisesc în medie cu 30% mai mult după finalizarea cursurilor.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h4>Decizii mai bune</h4>
                    <p>
                        Dezvoltă capacitatea de a lua decizii financiare informate. Evită capcanele comune și 
                        învață să evaluezi corect riscurile și oportunitățile.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>Evitarea datoriilor</h4>
                    <p>
                        Înțelege cum funcționează creditele și datoriile. Învață să eviți capcanele financiare 
                        și să gestionezi responsabil împrumuturile.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4>Investiții inteligente</h4>
                    <p>
                        Descoperă lumea investițiilor și învață să îți faci banii să lucreze pentru tine. 
                        De la acțiuni la fonduri mutuale, toate explicat simplu.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h4>Planificare pe termen lung</h4>
                    <p>
                        Învață să îți planifici viitorul financiar: de la pensia timpurie la obiectivele 
                        pe termen lung. Timpul este cel mai puternic aliat al tău.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-peace"></i>
                    </div>
                    <h4>Liniște sufletească</h4>
                    <p>
                        Elimină stresul financiar din viața ta. Cu cunoștințele potrivite, vei dormi liniștit 
                        știind că viitorul tău financiar este în siguranță.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cursuri Recomandate -->
<?php if (!empty($cursuri_recomandate)): ?>
<section class="courses-preview-section bg-light">
    <div class="container">
        <div class="row align-items-center mb-5">
            <div class="col-md-8">
                <h2 class="section-title">Cursurile noastre cele mai populare</h2>
                <p class="section-subtitle">
                    Alege din cursurile preferate de comunitatea noastră
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="cursuri.php" class="btn btn-primary">
                    <i class="fas fa-graduation-cap me-2"></i>Vezi toate cursurile
                </a>
            </div>
        </div>
        
        <div class="row">
            <?php foreach ($cursuri_recomandate as $curs): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="course-card">
                    <?php if ($curs['imagine']): ?>
                        <div class="course-image" style="background-image: url('<?= UPLOAD_PATH . 'cursuri/' . $curs['imagine'] ?>');">
                    <?php else: ?>
                        <div class="course-image" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));">
                    <?php endif; ?>
                        <div class="course-level">
                            <span class="badge badge-level <?= $curs['nivel'] ?>">
                                <?= ucfirst($curs['nivel']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="course-info">
                        <h5 class="course-title"><?= sanitizeInput($curs['titlu']) ?></h5>
                        <p class="course-description">
                            <?= sanitizeInput($curs['descriere_scurta'] ?: truncateText($curs['descriere_scurta'], 100)) ?>
                        </p>
                        
                        <div class="course-meta">
                            <span><i class="fas fa-users me-1"></i><?= $curs['enrolled_count'] ?> înscriși</span>
                        </div>
                        
                        <div class="course-price">
                            <?= formatPrice($curs['pret']) ?>
                        </div>
                        
                        <?php if (isLoggedIn()): ?>
                            <?php if (isAdmin()): ?>
                                <!-- Butoane speciale pentru admin -->
                                <div class="d-grid gap-2">
                                    <a href="admin/video-manager.php?curs_id=<?= $curs['id'] ?>" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-video me-2"></i>Gestionează Video
                                    </a>
                                    <a href="admin/exercise-manager.php?curs_id=<?= $curs['id'] ?>" 
                                       class="btn btn-outline-info">
                                        <i class="fas fa-tasks me-2"></i>Gestionează Exerciții
                                    </a>
                                </div>
                            <?php else: ?>
                                <!-- Butoane pentru utilizatori normali -->
                                <?php if (isEnrolledInCourse($_SESSION['user_id'], $curs['id'])): ?>
                                    <a href="curs.php?id=<?= $curs['id'] ?>" class="btn btn-success w-100">
                                        <i class="fas fa-play me-2"></i>Continuă cursul
                                    </a>
                                <?php elseif (isInCart($_SESSION['user_id'], $curs['id'])): ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="fas fa-shopping-cart me-2"></i>În coș
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-primary w-100" 
                                            onclick="addToCart(<?= $curs['id'] ?>, this)">
                                        <i class="fas fa-shopping-cart me-2"></i>Adaugă în coș
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Pentru vizitatori nelogați -->
                            <a href="login.php" class="btn btn-primary w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Conectează-te pentru a cumpăra
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Instrumente Interactive -->
<section class="tools-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Instrumente financiare gratuite</h2>
            <p class="section-subtitle">
                Calculatoare și instrumente pentru a-ți planifica viitorul financiar
            </p>
        </div>
        
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <h4>Calculator Economii</h4>
                    <p>
                        Descoperă puterea dobânzii compuse și vezi cum cresc economiile tale în timp.
                    </p>
                    <a href="instrumente.php#calculator-economii" class="btn btn-outline-primary">
                        <i class="fas fa-calculator me-2"></i>Calculează
                    </a>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h4>Simulator Credite</h4>
                    <p>
                        Calculează ratele lunare și costul total al creditului înainte să te angajezi.
                    </p>
                    <a href="instrumente.php#calculator-credite" class="btn btn-outline-primary">
                        <i class="fas fa-calculator me-2"></i>Simulează
                    </a>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h4>Planificator Buget</h4>
                    <p>
                        Folosește regula 50/30/20 pentru a-ți organiza perfect bugetul lunar.
                    </p>
                    <a href="instrumente.php#planificator-buget" class="btn btn-outline-primary">
                        <i class="fas fa-chart-pie me-2"></i>Planifică
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimoniale/Review-uri -->
<section class="testimonials-section bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Ce spun studenții noștri</h2>
            <p class="section-subtitle">
                Mii de oameni și-au transformat viața financiară cu ajutorul nostru
            </p>
        </div>
        
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">
                        "Cursurile m-au ajutat să economisesc pentru prima dată în viața mea. 
                        Acum am un fond de urgență și știu cum să îmi planific bugetul lunar."
                    </p>
                    <div class="testimonial-author">
                        <strong>Maria Ionescu</strong>
                        <span>Absolventă, București</span>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">
                        "Calculatoarele financiare sunt fantastice! M-au ajutat să înțeleg 
                        cât costă cu adevărat un credit și să negociez mai bine cu banca."
                    </p>
                    <div class="testimonial-author">
                        <strong>Alexandru Popescu</strong>
                        <span>Inginer, Cluj</span>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">
                        "Am început să investesc pentru prima dată după ce am terminat cursul 
                        de investiții. Explicațiile sunt clare și ușor de înțeles."
                    </p>
                    <div class="testimonial-author">
                        <strong>Elena Vasilescu</strong>
                        <span>Marketing Manager, Timișoara</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Articole Recente -->
<!-- Articole Recente - Secțiune corectată pentru pagina principală -->
<?php if (!empty($articole_recente)): ?>
<section class="blog-preview-section">
    <div class="container">
        <div class="row align-items-center mb-5">
            <div class="col-md-8">
                <h2 class="section-title">Învață din articolele noastre</h2>
                <p class="section-subtitle">
                    Sfaturi practice și strategii financiare actualizate regulat
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="blog.php" class="btn btn-primary">
                    <i class="fas fa-blog me-2"></i>Vezi toate articolele
                </a>
            </div>
        </div>
        
        <div class="row">
            <?php foreach ($articole_recente as $articol): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="blog-card h-100 shadow-sm">
                    <!-- Imaginea articolului cu calea corectă -->
                    <div class="blog-image-container position-relative">
                        <?php if (!empty($articol['imagine'])): ?>
                            <img src="assets/images/articles/<?= sanitizeInput($articol['imagine']) ?>" 
                                 alt="<?= sanitizeInput($articol['titlu']) ?>"
                                 class="blog-image"
                                 style="width: 100%; height: 200px; object-fit: cover;"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="blog-image-placeholder" style="display: none; width: 100%; height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); align-items: center; justify-content: center;">
                                <i class="fas fa-newspaper fa-3x text-white"></i>
                            </div>
                        <?php else: ?>
                            <div class="blog-image-placeholder" style="width: 100%; height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-newspaper fa-3x text-white"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Badge pentru articole populare -->
                        <?php if ($articol['vizualizari'] > 50): ?>
                            <span class="position-absolute top-0 end-0 badge bg-warning m-2">
                                <i class="fas fa-fire me-1"></i>Popular
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="blog-content p-4">
                        <h5 class="blog-title mb-2">
                            <a href="articol.php?id=<?= $articol['id'] ?>" class="text-decoration-none text-dark">
                                <?= sanitizeInput($articol['titlu']) ?>
                            </a>
                        </h5>
                        
                        <p class="blog-excerpt text-muted mb-3">
                            <?php 
                            $excerpt = $articol['continut_scurt'] ?: strip_tags($articol['continut']);
                            echo sanitizeInput(strlen($excerpt) > 100 ? substr($excerpt, 0, 100) . '...' : $excerpt);
                            ?>
                        </p>
                        
                        <div class="blog-meta d-flex justify-content-between align-items-center mb-3">
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i>
                                <?= sanitizeInput($articol['autor_nume']) ?>
                            </small>
                            <small class="text-muted">
                                <i class="fas fa-eye me-1"></i>
                                <?= number_format($articol['vizualizari']) ?>
                            </small>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?= date('d.m.Y', strtotime($articol['data_publicare'])) ?>
                            </small>
                            
                            <a href="articol.php?id=<?= $articol['id'] ?>" 
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-arrow-right me-1"></i>Citește
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- CTA suplimentar pentru blog -->
        <div class="text-center mt-4">
            <p class="text-muted mb-3">
                Avem peste <?= $total_articole ?> articole despre educația financiară
            </p>
            <a href="blog.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-book-open me-2"></i>Explorează toate articolele
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="cta-section">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="cta-title">Gata să îți schimbi viața financiară?</h2>
                <p class="cta-subtitle">
                    Alătură-te comunității noastre și începe să înveți astăzi. 
                    Primul pas către libertatea financiară îl faci chiar acum.
                </p>
                
                <div class="cta-actions">
                    <?php if (isLoggedIn()): ?>
                        <a href="cursuri.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-graduation-cap me-2"></i>Explorează cursurile
                        </a>
                        <a href="comunitate.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-users me-2"></i>Intră în comunitate
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary btn-lg me-3 btn-pulse">
                            <i class="fas fa-user-plus me-2"></i>Creează cont gratuit
                        </a>
                        <a href="instrumente.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-calculator me-2"></i>Încearcă instrumentele
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="cta-guarantee mt-4">
                    <div class="row justify-content-center">
                        <div class="col-md-4">
                            <i class="fas fa-shield-alt text-success me-2"></i>
                            <span>100% Gratuit să începi</span>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-users text-success me-2"></i>
                            <span><?= number_format($total_utilizatori) ?>+ membri activi</span>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-star text-success me-2"></i>
                            <span>4.9/5 rating mediu</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stats = document.querySelectorAll('.stat-number');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = parseInt(entry.target.textContent.replace(/[,\.]/g, ''));
                animateCounter(entry.target, target);
                observer.unobserve(entry.target);
            }
        });
    });
    
    stats.forEach(stat => observer.observe(stat));
});

function animateCounter(element, target) {
    let current = 0;
    const increment = target / 50;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current).toLocaleString('ro-RO');
    }, 40);
}

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
</script>

<?php include 'components/footer.php'; ?>