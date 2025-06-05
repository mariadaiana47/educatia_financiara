<?php
// Determină calea corectă pentru linkuri în funcție de locația fișierului
$current_dir = dirname($_SERVER['PHP_SELF']);
$is_admin = (basename($current_dir) === 'admin');
$base_path = $is_admin ? '../' : '';

// Pentru footer, vrem să mergem întotdeauna la paginile principale
$footer_base = $is_admin ? '../' : '';
?>
</main>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <!-- About Section -->
            <div class="col-lg-4 col-md-6 mb-4">
                <h5 class="d-flex align-items-center">
                    <img src="<?= $footer_base ?>assets/logo.png" alt="Logo" class="footer-logo me-2" onerror="this.style.display='none'">
                    Educația Financiară
                </h5>
                <p class="text-muted">
                    Misiunea noastră este să aducem educația financiară mai aproape de toată lumea,
                    ajutându-te să îți construiești o viață financiară stabilă și prosperă.
                </p>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h5>Navigare Rapidă</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="<?= $footer_base ?>index.php">
                            <i class="fas fa-home me-2"></i>Acasă
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= $footer_base ?>cursuri.php">
                            <i class="fas fa-graduation-cap me-2"></i>Cursuri
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= $footer_base ?>blog.php">
                            <i class="fas fa-blog me-2"></i>Blog
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= $footer_base ?>instrumente.php">
                            <i class="fas fa-calculator me-2"></i>Instrumente
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= $footer_base ?>comunitate.php">
                            <i class="fas fa-users me-2"></i>Comunitate
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= $footer_base ?>despre.php">
                            <i class="fas fa-info-circle me-2"></i>Despre Noi
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Contact & Support -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h5>Contact & Suport</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-envelope me-2"></i>
                        <a href="mailto:contact@educatie-financiara.ro">
                            contact@educatie-financiara.ro
                        </a>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-phone me-2"></i>
                        <a href="tel:+40721123456">
                            +40 721 123 456
                        </a>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Cluj-Napoca, România
                    </li>
                    <li class="mb-2 mt-3">
                        <a href="<?= $footer_base ?>contact.php">
                            <i class="fas fa-paper-plane me-2"></i>Formular Contact
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Newsletter Subscription -->
        <?php if (!isLoggedIn()): ?>
            <div class="row mt-4 pt-4 border-top border-secondary">
                <div class="col-lg-8 mx-auto text-center">
                    <h5>Primește Sfaturi Financiare în Inbox</h5>
                    <p class="text-muted">Abonează-te la newsletter pentru a primi cele mai noi articole și sfaturi
                        financiare.</p>
                    <form class="d-flex justify-content-center mt-3" action="<?= $footer_base ?>newsletter-subscribe.php" method="POST">
                        <div class="input-group" style="max-width: 400px;">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="email" class="form-control" placeholder="Adresa ta de email" name="email" required>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-paper-plane me-1"></i>Abonează-te
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Section -->
        <div class="row mt-4 pt-4 border-top border-secondary">
            <div class="col-12">
                <div class="row text-center">
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as total_cursuri FROM cursuri WHERE activ = TRUE");
                        $total_cursuri = $stmt->fetchColumn();

                        $stmt = $pdo->query("SELECT COUNT(*) as total_utilizatori FROM users WHERE activ = TRUE");
                        $total_utilizatori = $stmt->fetchColumn();

                        $stmt = $pdo->query("SELECT COUNT(*) as total_articole FROM articole WHERE activ = TRUE");
                        $total_articole = $stmt->fetchColumn();

                        $stmt = $pdo->query("SELECT COUNT(*) as total_completari FROM inscrieri_cursuri WHERE finalizat = TRUE");
                        $total_completari = $stmt->fetchColumn();
                    } catch (PDOException $e) {
                        $total_cursuri = 5;
                        $total_utilizatori = 100;
                        $total_articole = 25;
                        $total_completari = 250;
                    }
                    ?>
                    <div class="col-6 col-md-3 mb-3">
                        <div class="stat-number"><?= $total_cursuri ?></div>
                        <div class="stat-label">Cursuri Active</div>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <div class="stat-number"><?= number_format($total_utilizatori) ?></div>
                        <div class="stat-label">Studenți Înregistrați</div>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <div class="stat-number"><?= $total_articole ?></div>
                        <div class="stat-label">Articole Publicate</div>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <div class="stat-number"><?= number_format($total_completari) ?></div>
                        <div class="stat-label">Cursuri Finalizate</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="row mt-4 pt-4 border-top border-secondary">
            <div class="col-md-6">
                <p class="mb-0 text-muted">
                    &copy; <?= date('Y') ?> Educația Financiară pentru Toți. Toate drepturile rezervate.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0 text-muted">
                    Construit cu <i class="fas fa-heart text-danger"></i> pentru educația financiară
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" class="btn btn-primary position-fixed d-flex align-items-center justify-content-center"
   style="bottom: 20px; right: 20px; display: none; z-index: 1000; border-radius: 50%; width: 50px; height: 50px; padding: 0;">
   <i class="fas fa-arrow-up"></i>
</button>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JavaScript -->
<script src="<?= $footer_base ?>assets/script.js"></script>

<!-- JavaScript pentru funcționalități comune -->
<script>
    // Loading spinner functions
    function showLoading() {
        document.getElementById('loadingSpinner').style.display = 'block';
    }

    function hideLoading() {
        document.getElementById('loadingSpinner').style.display = 'none';
    }

    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function () {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function (alert) {
            setTimeout(function () {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });

        // Back to top button
        const backToTopButton = document.getElementById('backToTop');

        window.addEventListener('scroll', function () {
            if (window.pageYOffset > 300) {
                backToTopButton.style.display = 'block';
            } else {
                backToTopButton.style.display = 'none';
            }
        });

        backToTopButton.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    });

    // Confirm delete actions
    function confirmDelete(message = 'Ești sigur că vrei să ștergi acest element?') {
        return confirm(message);
    }

    // Add to cart function
    function addToCart(courseId, buttonElement) {
        if (!<?= isLoggedIn() ? 'true' : 'false' ?>) {
            window.location.href = '<?= $footer_base ?>login.php';
            return;
        }

        showLoading();

        fetch('<?= $footer_base ?>ajax/add-to-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                course_id: courseId,
                csrf_token: '<?= generateCSRFToken() ?>'
            })
        })
            .then(response => response.json())
            .then(data => {
                hideLoading();

                if (data.success) {
                    // Update cart count
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.cart_count;
                    } else if (data.cart_count > 0) {
                        // Create badge if it doesn't exist
                        const cartLink = document.querySelector('a[href="<?= $footer_base ?>cos.php"]');
                        if (cartLink) {
                            cartLink.insertAdjacentHTML('beforeend', `<span class="cart-badge">${data.cart_count}</span>`);
                        }
                    }

                    // Update button
                    if (buttonElement) {
                        buttonElement.innerHTML = '<i class="fas fa-check me-2"></i>Adăugat în Coș';
                        buttonElement.classList.remove('btn-primary');
                        buttonElement.classList.add('btn-success');
                        buttonElement.disabled = true;
                    }

                    // Show success message
                    showAlert('success', data.message);
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showAlert('error', 'A apărut o eroare. Te rugăm să încerci din nou.');
            });
    }

    // Show alert function
    function showAlert(type, message) {
        const alertContainer = document.querySelector('.main-content');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const iconClass = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';

        const alertHTML = `
                <div class="container mt-3">
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        <i class="${iconClass} me-2"></i>${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            `;

        alertContainer.insertAdjacentHTML('afterbegin', alertHTML);

        // Auto-hide after 5 seconds
        setTimeout(function () {
            const newAlert = alertContainer.querySelector('.alert');
            if (newAlert) {
                const bsAlert = new bootstrap.Alert(newAlert);
                bsAlert.close();
            }
        }, 5000);
    }
</script>
</body>

</html>