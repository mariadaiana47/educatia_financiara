<?php
require_once 'config.php';

$page_title = 'Despre Noi - ' . SITE_NAME;
include 'components/header.php';
?>

<div class="container-fluid">
    <!-- Hero Section -->
    <section class="hero-section bg-gradient-primary text-white py-5 mb-5">
        <div class="container">
            <div class="row align-items-center min-vh-50">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">
                        Despre <span class="text-warning">Educația Financiară</span>
                    </h1>
                    <p class="lead mb-4">
                        Misiunea noastră este să aducem educația financiară mai aproape de toată lumea, 
                        oferind instrumente și cunoștințe pentru o viață financiară sănătoasă.
                    </p>
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <div class="stat-card">
                            <h3 class="h4 fw-bold">1000+</h3>
                            <small>Utilizatori activi</small>
                        </div>
                        <div class="stat-card">
                            <h3 class="h4 fw-bold">50+</h3>
                            <small>Articole publicate</small>
                        </div>
                        <div class="stat-card">
                            <h3 class="h4 fw-bold">5</h3>
                            <small>Cursuri complete</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="hero-illustration">
                        <i class="fas fa-graduation-cap display-1 text-warning mb-3"></i>
                        <div class="floating-icons">
                            <i class="fas fa-coins floating-icon-1"></i>
                            <i class="fas fa-chart-line floating-icon-2"></i>
                            <i class="fas fa-piggy-bank floating-icon-3"></i>
                            <i class="fas fa-credit-card floating-icon-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Povestea Noastră -->
    <section class="py-5 mb-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="h1 mb-4">
                        <i class="fas fa-book-open text-primary me-3"></i>Povestea Noastră
                    </h2>
                    <p class="lead text-muted">
                        Totul a început dintr-o observație simplă: prea mulți oameni iau decizii financiare importante 
                        fără să aibă cunoștințele necesare.
                    </p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="story-card h-100">
                        <div class="story-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h4>Ideea Inițială</h4>
                        <p class="text-muted">
                            Am realizat că educația financiară nu este predată în școli, iar majoritatea oamenilor 
                            învață despre bani prin încercare și eroare. Ne-am gândit: "De ce să nu existe o platformă 
                            care să facă educația financiară accesibilă tuturor?"
                        </p>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="story-card h-100">
                        <div class="story-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h4>Dezvoltarea Platformei</h4>
                        <p class="text-muted">
                            Am petrecut luni de zile cercetând, dezvoltând și testând. Am vorbit cu experți financiari, 
                            am studiat cele mai bune practici și am creat o platformă care să combine teoria cu practica, 
                            într-un mod ușor de înțeles.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Misiunea și Viziunea -->
    <section class="py-5 mb-5 bg-light">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6">
                    <div class="mission-card">
                        <div class="mission-icon mb-4">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3 class="h2 mb-4">Misiunea Noastră</h3>
                        <p class="lead mb-4">
                            Să democratizăm accesul la educația financiară de calitate și să îi ajutăm pe oameni 
                            să ia decizii financiare informate.
                        </p>
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-3"></i>
                                Educație accesibilă pentru toți
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-3"></i>
                                Instrumente practice și aplicabile
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-3"></i>
                                Comunitate de sprijin și învățare
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="vision-card">
                        <div class="vision-icon mb-4">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3 class="h2 mb-4">Viziunea Noastră</h3>
                        <p class="lead mb-4">
                            O societate în care fiecare persoană are cunoștințele și încrederea necesare 
                            pentru a-și gestiona eficient finanțele personale.
                        </p>
                        <blockquote class="blockquote border-start border-primary border-4 ps-4">
                            <p class="mb-3 fst-italic">
                                "Educația financiară nu este un privilegiu, ci un drept fundamental în societatea modernă."
                            </p>
                            <footer class="blockquote-footer">
                                <cite title="Echipa Educația Financiară">Echipa noastră</cite>
                            </footer>
                        </blockquote>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Valorile Noastre -->
    <section class="py-5 mb-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="h1 mb-4">
                    <i class="fas fa-heart text-danger me-3"></i>Valorile Noastre
                </h2>
                <p class="lead text-muted">
                    Principiile care ne ghidează în tot ceea ce facem
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="value-card text-center h-100">
                        <div class="value-icon mb-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Accesibilitate</h4>
                        <p class="text-muted">
                            Educația financiară trebuie să fie disponibilă pentru toți, 
                            indiferent de background sau venit.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="value-card text-center h-100">
                        <div class="value-icon mb-3">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Transparență</h4>
                        <p class="text-muted">
                            Oferim informații clare, corecte și fără agenda ascunsă. 
                            Nu promovăm produse financiare specifice.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="value-card text-center h-100">
                        <div class="value-icon mb-3">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h4>Educație Continuă</h4>
                        <p class="text-muted">
                            Lumea financiară se schimbă constant. Ne actualizăm mereu 
                            conținutul pentru a rămâne relevanți.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="value-card text-center h-100">
                        <div class="value-icon mb-3">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <h4>Comunitate</h4>
                        <p class="text-muted">
                            Învățarea este mai eficientă în comunitate. 
                            Încurajăm schimbul de experiențe și ajutorul reciproc.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Ce Oferim -->
    <section class="py-5 mb-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="h1 mb-4 text-dark">
                    <i class="fas fa-star text-warning me-3"></i>Ce Oferim
                </h2>
                <p class="lead text-muted">
                    O platformă completă pentru educația ta financiară
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="modern-feature-card">
                        <div class="feature-number">01</div>
                        <div class="feature-icon-modern mb-3">
                            <i class="fas fa-play-circle"></i>
                        </div>
                        <h4>Cursuri Video Interactive</h4>
                        <p class="text-muted">
                            Cursuri structurate cu video-uri explicative, exerciții practice 
                            și teste de evaluare pentru fiecare modul.
                        </p>
                        <div class="feature-highlight"></div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="modern-feature-card">
                        <div class="feature-number">02</div>
                        <div class="feature-icon-modern mb-3">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <h4>Instrumente Financiare</h4>
                        <p class="text-muted">
                            Calculatoare pentru buget, economii, credite și planificare financiară. 
                            Toate rezultatele se salvează în contul tău.
                        </p>
                        <div class="feature-highlight"></div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="modern-feature-card">
                        <div class="feature-number">03</div>
                        <div class="feature-icon-modern mb-3">
                            <i class="fas fa-newspaper"></i>
                        </div>
                        <h4>Articole și Ghiduri</h4>
                        <p class="text-muted">
                            Articole practice, studii de caz și ghiduri pas-cu-pas 
                            pentru toate aspectele finanțelor personale.
                        </p>
                        <div class="feature-highlight"></div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="modern-feature-card">
                        <div class="feature-number">04</div>
                        <div class="feature-icon-modern mb-3">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h4>Comunitate Activă</h4>
                        <p class="text-muted">
                            Forum unde poți pune întrebări, împărtăși experiențe 
                            și învăța de la alți membri ai comunității.
                        </p>
                        <div class="feature-highlight"></div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="modern-feature-card">
                        <div class="feature-number">05</div>
                        <div class="feature-icon-modern mb-3">
                            <i class="fas fa-brain"></i>
                        </div>
                        <h4>Quiz-uri și Teste</h4>
                        <p class="text-muted">
                            Teste de inteligență financiară și quiz-uri pentru a-ți 
                            evalua progresul și a identifica zonele de îmbunătățit.
                        </p>
                        <div class="feature-highlight"></div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="modern-feature-card">
                        <div class="feature-number">06</div>
                        <div class="feature-icon-modern mb-3">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4>Urmărire Progres</h4>
                        <p class="text-muted">
                            Dashboard personal pentru a-ți urmări progresul, 
                            cursurile completate și rezultatele la teste.
                        </p>
                        <div class="feature-highlight"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Echipa (Placeholder) -->
    <section class="py-5 mb-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="h1 mb-4">
                    <i class="fas fa-users text-primary me-3"></i>Echipa Noastră
                </h2>
                <p class="lead text-muted">
                    Oameni pasionați de educația financiară și tehnologie
                </p>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4 col-md-6">
                    <div class="team-card text-center">
                        <div class="team-avatar mb-3">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h4>Echipa de Dezvoltare</h4>
                        <p class="text-primary">Dezvoltatori & Designeri</p>
                        <p class="text-muted">
                            Creăm și menținem platforma tehnică, asigurându-ne că experiența 
                            utilizatorului este perfectă.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="team-card text-center">
                        <div class="team-avatar mb-3">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h4>Echipa de Conținut</h4>
                        <p class="text-primary">Experți Financiari & Educatori</p>
                        <p class="text-muted">
                            Creează cursurile, articolele și materialele educaționale, 
                            asigurându-se că informațiile sunt corecte și accesibile.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 bg-gradient-success text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="h2 mb-3">Gata să îți începi călătoria în educația financiară?</h3>
                    <p class="lead mb-0">
                        Alătură-te comunității noastre și începe să îți construiești un viitor financiar mai bun astăzi!
                    </p>
                </div>
                <div class="col-lg-4 text-end">
                    <a href="cursuri.php" class="btn btn-light btn-lg me-3">
                        <i class="fas fa-graduation-cap me-2"></i>Vezi Cursurile
                    </a>
                    <a href="comunitate.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-users me-2"></i>Comunitate
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Info -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 text-center">
                    <div class="contact-item">
                        <div class="contact-icon mb-3">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5>Email</h5>
                        <p class="text-muted">contact@educatie-financiara.ro</p>
                    </div>
                </div>
                
                <div class="col-lg-4 text-center">
                    <div class="contact-item">
                        <div class="contact-icon mb-3">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h5>Comunitate</h5>
                        <p class="text-muted">
                            <a href="comunitate.php" class="text-decoration-none">
                                Alătură-te discuțiilor din comunitate
                            </a>
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 text-center">
                    <div class="contact-item">
                        <div class="contact-icon mb-3">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h5>Suport</h5>
                        <p class="text-muted">
                            <a href="contact.php" class="text-decoration-none">
                                Trimite-ne o întrebare
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 30% 70%, rgba(248, 193, 70, 0.1) 0%, transparent 50%);
}

.min-vh-50 {
    min-height: 50vh;
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 1rem 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Floating Icons Animation */
.hero-illustration {
    position: relative;
}

.floating-icons {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
}

.floating-icons i {
    position: absolute;
    color: rgba(248, 193, 70, 0.7);
    animation: float 3s ease-in-out infinite;
}

.floating-icon-1 {
    top: 20%;
    left: 10%;
    animation-delay: 0s;
}

.floating-icon-2 {
    top: 30%;
    right: 20%;
    animation-delay: 0.5s;
}

.floating-icon-3 {
    bottom: 30%;
    left: 20%;
    animation-delay: 1s;
}

.floating-icon-4 {
    bottom: 20%;
    right: 10%;
    animation-delay: 1.5s;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

/* Story Cards */
.story-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #f0f0f0;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.story-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.story-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #f8c146 0%, #ffa726 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.story-icon i {
    font-size: 1.5rem;
    color: white;
}

/* Mission & Vision Cards */
.mission-card, .vision-card {
    background: white;
    border-radius: 16px;
    padding: 2.5rem;
    height: 100%;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.mission-icon, .vision-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mission-icon i, .vision-icon i {
    font-size: 2rem;
    color: white;
}

/* Value Cards */
.value-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #f0f0f0;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.value-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.value-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.value-icon i {
    font-size: 1.5rem;
    color: white;
}

/* Modern Feature Cards */
.modern-feature-card {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    border: 1px solid #f0f0f0;
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
    text-align: center;
    height: 100%;
}

.modern-feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 50px rgba(44, 90, 160, 0.15);
    border-color: #2c5aa0;
}

.modern-feature-card:hover .feature-highlight {
    width: 100%;
}

.modern-feature-card:hover .feature-icon-modern {
    transform: scale(1.1);
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
}

.modern-feature-card:hover .feature-icon-modern i {
    color: white;
}

.feature-number {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #f8c146 0%, #ffa726 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
    font-size: 0.9rem;
}

.feature-icon-modern {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    transition: all 0.4s ease;
    border: 3px solid #f0f0f0;
}

.feature-icon-modern i {
    font-size: 2rem;
    color: #2c5aa0;
    transition: all 0.4s ease;
}

.feature-highlight {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 4px;
    width: 0;
    background: linear-gradient(135deg, #2c5aa0 0%, #f8c146 100%);
    transition: width 0.4s ease;
}

.modern-feature-card h4 {
    color: #2c5aa0;
    margin-bottom: 1rem;
    font-weight: 600;
}

/* Team Cards */
.team-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #f0f0f0;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.team-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.team-avatar {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.team-avatar i {
    font-size: 2.5rem;
    color: white;
}

/* Contact Items */
.contact-item {
    padding: 1.5rem;
}

.contact-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.contact-icon i {
    font-size: 1.5rem;
    color: white;
}

/* Gradient Backgrounds */
.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
}

/* Responsive Design */
@media (max-width: 768px) {
    .display-4 {
        font-size: 2.5rem;
    }
    
    .stat-card {
        margin-bottom: 1rem;
    }
    
    .story-card,
    .value-card,
    .team-card {
        margin-bottom: 2rem;
    }
    
    .floating-icons {
        display: none;
    }
}

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #2c5aa0;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #1e3d6f;
}
</style>

<?php include 'components/footer.php'; ?>