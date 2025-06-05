<?php
require_once '../config.php';

// Verifică dacă utilizatorul este admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = MSG_ERROR_ACCESS_DENIED;
    redirectTo('../login.php');
}

$page_title = 'Administrator ' . SITE_NAME;
$current_user = getCurrentUser();

try {
    // Statistici de bază pentru afișare
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE activ = TRUE");
    $total_users = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cursuri WHERE activ = TRUE");
    $total_courses = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscrieri_cursuri");
    $total_enrollments = $stmt->fetchColumn();
    
    // Activitate recentă
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM inscrieri_cursuri 
        WHERE DATE(data_inscriere) = CURDATE()
    ");
    $today_enrollments = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $total_users = 0;
    $total_courses = 0;
    $total_enrollments = 0;
    $today_enrollments = 0;
}

include '../components/header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.admin-container {
    background: transparent;
    min-height: calc(100vh - 120px);
    position: relative;
    overflow: hidden;
}

.floating-particles {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.particle {
    position: absolute;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    animation: float 6s ease-in-out infinite;
}

.particle:nth-child(1) { width: 4px; height: 4px; left: 10%; animation-delay: 0s; }
.particle:nth-child(2) { width: 6px; height: 6px; left: 20%; animation-delay: 1s; }
.particle:nth-child(3) { width: 3px; height: 3px; left: 30%; animation-delay: 2s; }
.particle:nth-child(4) { width: 5px; height: 5px; left: 40%; animation-delay: 1.5s; }
.particle:nth-child(5) { width: 4px; height: 4px; left: 50%; animation-delay: 0.5s; }
.particle:nth-child(6) { width: 6px; height: 6px; left: 60%; animation-delay: 3s; }
.particle:nth-child(7) { width: 3px; height: 3px; left: 70%; animation-delay: 2.5s; }
.particle:nth-child(8) { width: 5px; height: 5px; left: 80%; animation-delay: 1.8s; }

@keyframes float {
    0%, 100% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
}

.welcome-hero {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 30px;
    padding: 4rem 3rem;
    margin: 2rem 0;
    text-align: center;
    position: relative;
    z-index: 2;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
    color: white;
    overflow: hidden;
}

.welcome-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.admin-avatar {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
    border: 3px solid rgba(255,255,255,0.3);
    backdrop-filter: blur(10px);
    position: relative;
    z-index: 3;
    animation: pulse 3s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); box-shadow: 0 0 30px rgba(255,255,255,0.3); }
    50% { transform: scale(1.05); box-shadow: 0 0 50px rgba(255,255,255,0.5); }
}

.admin-avatar i {
    font-size: 3rem;
    color: white;
}

.glitch-text {
    font-size: 3.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
    position: relative;
    z-index: 3;
    text-shadow: 0 0 20px rgba(255,255,255,0.5);
}

.subtitle-fancy {
    font-size: 1.3rem;
    opacity: 0.9;
    margin-bottom: 2rem;
    position: relative;
    z-index: 3;
}

.status-badge {
    background: linear-gradient(135deg, #ff6b6b, #ffa500);
    color: white;
    padding: 1rem 2rem;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 600;
    display: inline-block;
    margin: 1rem 0;
    box-shadow: 0 10px 30px rgba(255, 107, 107, 0.4);
    position: relative;
    z-index: 3;
    animation: glow 2s ease-in-out infinite alternate;
}

@keyframes glow {
    from { box-shadow: 0 10px 30px rgba(255, 107, 107, 0.4); }
    to { box-shadow: 0 15px 40px rgba(255, 107, 107, 0.7); }
}

.glass-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 25px;
    padding: 2.5rem;
    margin: 2rem 0;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
    position: relative;
    z-index: 2;
    transition: all 0.3s ease;
}

.glass-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.2);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.stat-card {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
    transform: rotate(45deg);
    transition: all 0.3s ease;
    opacity: 0;
}

.stat-card:hover::before {
    opacity: 1;
    animation: shimmer 1s ease-in-out;
}

@keyframes shimmer {
    0% { transform: translateX(-100%) rotate(45deg); }
    100% { transform: translateX(100%) rotate(45deg); }
}

.stat-card:hover {
    transform: translateY(-5px) scale(1.02);
    border-color: rgba(255, 255, 255, 0.4);
}

.stat-number {
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    color: white;
    text-shadow: 0 0 20px rgba(255,255,255,0.5);
}

.stat-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.feature-card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
}

.feature-card:hover {
    transform: translateY(-10px);
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.3);
}

.feature-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2rem;
    color: white;
    transition: all 0.3s ease;
}

.feature-card:hover .feature-icon {
    transform: scale(1.1) rotate(10deg);
    background: linear-gradient(135deg, rgba(255,255,255,0.3), rgba(255,255,255,0.2));
}

.feature-title {
    color: white;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.feature-description {
    color: rgba(255, 255, 255, 0.95);
    line-height: 1.6;
    font-weight: 500;
}

.time-display {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 15px;
    padding: 1rem 2rem;
    margin: 2rem 0;
    text-align: center;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.digital-clock {
    font-family: 'Courier New', monospace;
    font-size: 2rem;
    font-weight: bold;
    color: #00ff88;
    text-shadow: 0 0 20px #00ff88;
    margin-bottom: 0.5rem;
}

.date-display {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1rem;
}

@media (max-width: 768px) {
    .glitch-text {
        font-size: 2.5rem;
    }
    
    .admin-avatar {
        width: 80px;
        height: 80px;
    }
    
    .admin-avatar i {
        font-size: 2rem;
    }
    
    .stats-grid, .feature-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .glass-card {
        padding: 1.5rem;
    }
    
    .digital-clock {
        font-size: 1.5rem;
    }
}
</style>

<div class="admin-container">
    <!-- Floating Particles Background -->
    <div class="floating-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="container py-4" style="position: relative; z-index: 2;">
        <!-- Hero Section -->
        <div class="welcome-hero">
            <div class="admin-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1 class="glitch-text">
                Administrator
            </h1>
            <p class="subtitle-fancy">
                Bun venit, <?= htmlspecialchars(explode(' ', $current_user['nume'])[0]) ?>! 
                Sistemul este sub controlul tău complet.
            </p>
            <div class="status-badge">
                <i class="fas fa-shield-alt me-2"></i>
                ACCES MAXIM AUTORIZAT
            </div>
        </div>

        <!-- Real-time Clock -->
        <div class="time-display">
            <div class="digital-clock" id="digitalClock"></div>
            <div class="date-display" id="dateDisplay"></div>
        </div>

        <!-- Statistics Grid -->
        <div class="glass-card">
            <h3 class="text-center mb-4" style="color: white; font-weight: 700;">
                <i class="fas fa-chart-pulse me-2"></i>
                STATUS PLATFORMĂ ÎN TIMP REAL
            </h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" data-target="<?= $total_users ?>">0</div>
                    <div class="stat-label">Utilizatori Activi</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" data-target="<?= $total_courses ?>">0</div>
                    <div class="stat-label">Cursuri Operaționale</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" data-target="<?= $total_enrollments ?>">0</div>
                    <div class="stat-label">Înscrierii Totale</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" data-target="<?= $today_enrollments ?>">0</div>
                    <div class="stat-label">Înscrieri Astăzi</div>
                </div>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="glass-card">
            <h3 class="text-center mb-4" style="color: white; font-weight: 700;">
                <i class="fas fa-cogs me-2"></i>
                CAPABILITĂȚI ADMINISTRATIVE
            </h3>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h5 class="feature-title">Control Utilizatori</h5>
                    <p class="feature-description">
                        Monitorizare completă și gestionare avansată a tuturor utilizatorilor platformei.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h5 class="feature-title">Management Educațional</h5>
                    <p class="feature-description">
                        Dezvoltare și administrare conținut, cursuri și materiale educaționale.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5 class="feature-title">Analiză & Raportare</h5>
                    <p class="feature-description">
                        Generare rapoarte avansate și analiză detaliată a performanței sistemului.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h5 class="feature-title">Securitate Maximă</h5>
                    <p class="feature-description">
                        Protecție avansată și control complet asupra securității platformei.
                    </p>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="glass-card text-center">
            <h4 style="color: white; margin-bottom: 1.5rem;">
                <i class="fas fa-server me-2"></i>
                STATUS SISTEM
            </h4>
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <i class="fas fa-circle text-success fa-2x mb-2"></i>
                        <p style="color: rgba(255,255,255,0.8); margin: 0;">Server Online</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <i class="fas fa-circle text-success fa-2x mb-2"></i>
                        <p style="color: rgba(255,255,255,0.8); margin: 0;">Database Activ</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <i class="fas fa-circle text-success fa-2x mb-2"></i>
                        <p style="color: rgba(255,255,255,0.8); margin: 0;">Securitate OK</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <i class="fas fa-circle text-success fa-2x mb-2"></i>
                        <p style="color: rgba(255,255,255,0.8); margin: 0;">Backup Activ</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4">
            <p style="color: rgba(255,255,255,0.6); margin: 0;">
                <i class="fas fa-shield-check me-2"></i>
                Toate sistemele funcționează în parametri normali
            </p>
        </div>
    </div>
</div>

<script>
// Real-time Clock
function updateClock() {
    const now = new Date();
    const clock = document.getElementById('digitalClock');
    const date = document.getElementById('dateDisplay');
    
    const timeString = now.toLocaleTimeString('ro-RO', {
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    
    const dateString = now.toLocaleDateString('ro-RO', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    clock.textContent = timeString;
    date.textContent = dateString.charAt(0).toUpperCase() + dateString.slice(1);
}

// Animated Counter
function animateCounters() {
    const counters = document.querySelectorAll('.stat-number');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        let current = 0;
        const increment = target / 100;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            counter.textContent = Math.floor(current).toLocaleString();
        }, 20);
    });
}

// Glitch Effect for Title
function createGlitchEffect() {
    const glitchText = document.querySelector('.glitch-text');
    const originalText = glitchText.textContent;
    
    setInterval(() => {
        if (Math.random() < 0.1) { // 10% chance
            glitchText.style.textShadow = `
                ${Math.random() * 4 - 2}px ${Math.random() * 4 - 2}px 0 #ff00ff,
                ${Math.random() * 4 - 2}px ${Math.random() * 4 - 2}px 0 #00ffff
            `;
            
            setTimeout(() => {
                glitchText.style.textShadow = '0 0 20px rgba(255,255,255,0.5)';
            }, 100);
        }
    }, 2000);
}

// Initialize everything
document.addEventListener('DOMContentLoaded', function() {
    updateClock();
    setInterval(updateClock, 1000);
    
    setTimeout(animateCounters, 500);
    createGlitchEffect();
    
    // Staggered animation for cards
    const cards = document.querySelectorAll('.glass-card, .stat-card, .feature-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.8s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 150 + 800);
    });
});

// Mouse trail effect
document.addEventListener('mousemove', function(e) {
    if (Math.random() < 0.1) {
        const trail = document.createElement('div');
        trail.style.position = 'fixed';
        trail.style.left = e.clientX + 'px';
        trail.style.top = e.clientY + 'px';
        trail.style.width = '4px';
        trail.style.height = '4px';
        trail.style.background = 'rgba(255,255,255,0.6)';
        trail.style.borderRadius = '50%';
        trail.style.pointerEvents = 'none';
        trail.style.zIndex = '9999';
        trail.style.animation = 'trailFade 1s ease-out forwards';
        
        document.body.appendChild(trail);
        
        setTimeout(() => {
            trail.remove();
        }, 1000);
    }
});

// Add trail fade animation
const style = document.createElement('style');
style.textContent = `
    @keyframes trailFade {
        0% { opacity: 1; transform: scale(1); }
        100% { opacity: 0; transform: scale(0); }
    }
`;
document.head.appendChild(style);
</script>

<?php include '../components/footer.php'; ?>