<?php
// cos.php
require_once 'config.php';

// Verifică autentificarea
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'cos.php';
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$page_title = 'Coș de cumpărături - ' . SITE_NAME;

// Funcții helper pentru coș
function removeFromCart($userId, $courseId)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM cos_cumparaturi WHERE user_id = ? AND curs_id = ?");
        $stmt->execute([$userId, $courseId]);

        return [
            'success' => true,
            'message' => 'Cursul a fost eliminat din coș'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Eroare la eliminarea din coș'
        ];
    }
}

function processCheckout($userId)
{
    global $pdo;

    try {
        // Începe tranzacția
        $pdo->beginTransaction();

        // Obține toate cursurile din coș
        $stmt = $pdo->prepare("
            SELECT c.curs_id, curs.titlu, curs.pret
            FROM cos_cumparaturi c
            JOIN cursuri curs ON c.curs_id = curs.id
            WHERE c.user_id = ? AND curs.activ = 1
        ");
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll();

        if (empty($cartItems)) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Coșul este gol'
            ];
        }

        $enrolledCourses = [];

        // Înscrie utilizatorul la fiecare curs
        foreach ($cartItems as $item) {
            // Verifică dacă nu este deja înscris
            $stmt = $pdo->prepare("SELECT id FROM inscrieri_cursuri WHERE user_id = ? AND curs_id = ?");
            $stmt->execute([$userId, $item['curs_id']]);

            if (!$stmt->fetch()) {
                // Înscrie utilizatorul
                $stmt = $pdo->prepare("
                    INSERT INTO inscrieri_cursuri (user_id, curs_id, data_inscriere, progress, finalizat) 
                    VALUES (?, ?, NOW(), 0, 0)
                ");
                $stmt->execute([$userId, $item['curs_id']]);
                $enrolledCourses[] = $item['curs_id'];
            }
        }

        // Golește coșul
        $stmt = $pdo->prepare("DELETE FROM cos_cumparaturi WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Salvează tranzacția (pentru istoric)
        $totalAmount = array_sum(array_column($cartItems, 'pret'));
        // Aici poți adăuga cod pentru a salva tranzacția într-un tabel de istoric

        // Confirmă tranzacția
        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Comanda a fost procesată cu succes!',
            'enrolled_courses' => count($enrolledCourses)
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        return [
            'success' => false,
            'message' => 'Eroare la procesarea comenzii: ' . $e->getMessage()
        ];
    }
}

// Procesează acțiunile AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'remove_item':
            $courseId = (int) $_POST['course_id'];
            $result = removeFromCart($userId, $courseId);
            echo json_encode($result);
            exit;

        case 'checkout':
            $result = processCheckout($userId);
            echo json_encode($result);
            exit;
    }
}

// Obține elementele din coș
$cartItems = getCartItems();
$cartTotal = getCartTotal();
$cartCount = count($cartItems);

include 'components/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-2">
                        <i class="fas fa-shopping-cart me-2"></i>Coșul meu
                    </h1>
                    <p class="text-muted">
                        <?php if ($cartCount > 0): ?>
                            Ai <?= $cartCount ?>     <?= $cartCount === 1 ? 'curs' : 'cursuri' ?> în coș
                        <?php else: ?>
                            Coșul tău este gol
                        <?php endif; ?>
                    </p>
                </div>
                <?php if ($cartCount > 0): ?>
                    <a href="cursuri.php" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i>Adaugă mai multe cursuri
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($cartCount > 0): ?>
                <div class="row">
                    <!-- Lista cursurilor -->
                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Cursurile tale</h5>
                            </div>
                            <div class="card-body p-0">
                                <!-- Secțiunea tabelului din cos.php - VERSIUNEA REPARATĂ -->
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Curs</th>
                                                <th>Nivel</th>
                                                <th>Durată</th>
                                                <th>Preț</th>
                                                <th width="80">Acțiuni</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cartItemsTable">
                                            <?php foreach ($cartItems as $item): ?>
                                                <tr data-course-id="<?= $item['curs_id'] ?>">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if (!empty($item['imagine'])): ?>
                                                                <img src="<?= UPLOAD_PATH . 'cursuri/' . $item['imagine'] ?>"
                                                                    alt="<?= htmlspecialchars($item['titlu']) ?>"
                                                                    class="rounded me-3"
                                                                    style="width: 60px; height: 60px; object-fit: cover;">
                                                            <?php else: ?>
                                                                <div class="bg-primary rounded me-3 d-flex align-items-center justify-content-center"
                                                                    style="width: 60px; height: 60px;">
                                                                    <i class="fas fa-graduation-cap text-white"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <h6 class="mb-1"><?= htmlspecialchars($item['titlu']) ?></h6>
                                                                <small class="text-muted">
                                                                    <?= htmlspecialchars($item['descriere_scurta'] ?? 'Fără descriere') ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-level <?= $item['nivel'] ?? 'incepator' ?>">
                                                            <?= ucfirst($item['nivel'] ?? 'Începător') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <i class="fas fa-clock text-muted me-1"></i>
                                                        <?= isset($item['durata_minute']) && $item['durata_minute'] > 0 ? $item['durata_minute'] . ' min' : 'N/A' ?>
                                                    </td>
                                                    <td>
                                                        <strong class="text-primary">
                                                            <?= formatPrice($item['pret']) ?>
                                                        </strong>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-danger remove-item"
                                                            data-course-id="<?= $item['curs_id'] ?>" title="Elimină din coș">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rezumatul comenzii -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Rezumatul comenzii</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Cursuri (<?= $cartCount ?>):</span>
                                    <span><?= formatPrice($cartTotal) ?></span>
                                </div>

                                <div class="d-flex justify-content-between mb-3">
                                    <span>TVA (19%):</span>
                                    <span><?= formatPrice($cartTotal * 0.19) ?></span>
                                </div>

                                <hr>

                                <div class="d-flex justify-content-between mb-4">
                                    <strong>Total:</strong>
                                    <strong class="text-primary fs-5" id="totalAmount">
                                        <?= formatPrice($cartTotal * 1.19) ?>
                                    </strong>
                                </div>

                                <button class="btn btn-success btn-lg w-100 mb-3" id="checkoutBtn">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Finalizează comanda
                                </button>

                                <div class="text-center">
                                    <small class="text-muted">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        Plată securizată SSL
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Garanții -->
                        <div class="card shadow-sm mt-4">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Garanții
                                </h6>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">
                                        <i class="fas fa-infinity text-primary me-2"></i>
                                        <small>Acces nelimitat la cursuri</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-mobile-alt text-primary me-2"></i>
                                        <small>Compatibil mobile și desktop</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-certificate text-primary me-2"></i>
                                        <small>Certificat de finalizare</small>
                                    </li>
                                    <li>
                                        <i class="fas fa-headset text-primary me-2"></i>
                                        <small>Suport tehnic 24/7</small>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Coș gol -->
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-shopping-cart fa-4x text-muted"></i>
                    </div>
                    <h3 class="mb-3">Coșul tău este gol</h3>
                    <p class="text-muted mb-4">
                        Explorează cursurile noastre și adaugă-le în coș pentru a începe învățarea
                    </p>
                    <a href="cursuri.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-graduation-cap me-2"></i>
                        Explorează cursurile
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmare -->
<div class="modal fade" id="checkoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Finalizează comanda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Ești pe cale să achiziționezi <strong><?= $cartCount ?></strong>
                    <?= $cartCount === 1 ? 'curs' : 'cursuri' ?> în valoare de
                    <strong class="text-primary"><?= formatPrice($cartTotal * 1.19) ?></strong>.
                </p>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    După finalizarea plății, vei avea acces imediat la toate cursurile achiziționate.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-success" id="confirmCheckout">
                    <i class="fas fa-credit-card me-2"></i>
                    Confirmă plata
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Elimină element din coș
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function () {
                const courseId = this.dataset.courseId;
                const row = this.closest('tr');

                if (confirm('Ești sigur că vrei să elimini acest curs din coș?')) {
                    // Animație de eliminare
                    row.style.opacity = '0.5';
                    this.disabled = true;

                    fetch('cos.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=remove_item&course_id=${courseId}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Elimină rândul cu animație
                                row.style.transition = 'all 0.3s ease';
                                row.style.transform = 'translateX(-100%)';
                                setTimeout(() => {
                                    location.reload(); // Reîncarcă pagina pentru a actualiza totalul
                                }, 300);
                            } else {
                                alert(data.message);
                                row.style.opacity = '1';
                                this.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Eroare la eliminarea din coș');
                            row.style.opacity = '1';
                            this.disabled = false;
                        });
                }
            });
        });

        // Checkout
        const checkoutBtn = document.getElementById('checkoutBtn');
        const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));

        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', function () {
                checkoutModal.show();
            });
        }

        // Confirmă checkout
        document.getElementById('confirmCheckout').addEventListener('click', function () {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesez...';

            fetch('cos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=checkout'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Succes - redirecționează la dashboard
                        window.location.href = 'dashboard.php?success=checkout&courses=' + data.enrolled_courses;
                    } else {
                        alert(data.message);
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-credit-card me-2"></i>Confirmă plata';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Eroare la procesarea plății');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-credit-card me-2"></i>Confirmă plata';
                });
        });
    });
</script>

<style>
    .badge-level.incepator {
        background-color: #28a745;
        color: white;
    }

    .badge-level.intermediar {
        background-color: #ffc107;
        color: #212529;
    }

    .badge-level.avansat {
        background-color: #dc3545;
        color: white;
    }

    .remove-item:hover {
        transform: scale(1.1);
    }

    .card {
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(44, 90, 160, 0.05);
    }
</style>

<?php include 'components/footer.php'; ?>