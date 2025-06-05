<div class="calculator-container">
    <div class="text-center py-5">
        <div class="mb-4">
            <i class="fas fa-tools fa-4x text-primary"></i>
        </div>
        <h4 class="mb-3">Exercițiul va fi implementat în curând</h4>
        <p class="text-muted mb-4">
            Lucram la implementarea acestui calculator. Va fi disponibil în curând!
        </p>
        <div class="alert alert-info d-inline-block">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Exercițiu:</strong> <?= sanitizeInput($exercitiu['titlu']) ?>
        </div>
        
        <!-- Placeholder pentru progres -->
        <div class="mt-4">
            <button type="button" class="btn btn-success" onclick="markAsComplete()">
                <i class="fas fa-check me-2"></i>Am înțeles conceptele
            </button>
        </div>
    </div>
</div>

<script>
// Funcție pentru colectarea datelor (pentru salvarea progresului)
function gatherCalculatorData() {
    return {
        exerciseTitle: '<?= sanitizeInput($exercitiu['titlu']) ?>',
        timestamp: new Date().toISOString(),
        status: 'viewed'
    };
}
</script>