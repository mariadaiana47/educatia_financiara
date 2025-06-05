<div class="calculator-container">
    <h4 class="mb-4">
        <i class="fas fa-piggy-bank me-2"></i>
        Planificatorul de Economii pe 5 ani
    </h4>
    
    <div class="row">
        <!-- Input Section -->
        <div class="col-lg-6">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-target me-2"></i>
                        Obiectivele Tale
                    </h6>
                    
                    <form id="savingsForm">
                        <div class="mb-3">
                            <label for="currentSavings" class="form-label">Economii Curente (RON)</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="form-control" id="currentSavings" value="5000" min="0" step="100">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="monthlyIncome" class="form-label">Venit Lunar Net (RON)</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="form-control" id="monthlyIncome" value="3000" min="0" step="100">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="monthlyExpenses" class="form-label">Cheltuieli Lunare (RON)</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="form-control" id="monthlyExpenses" value="2200" min="0" step="100">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="savingsGoal" class="form-label">Obiectiv de Economisire (RON)</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="form-control" id="savingsGoal" value="50000" min="0" step="1000">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="goalPurpose" class="form-label">Scopul Economisirii</label>
                            <select class="form-select" id="goalPurpose">
                                <option value="emergency">Fond de Urgență</option>
                                <option value="house">Avans pentru Casă</option>
                                <option value="car">Mașină Nouă</option>
                                <option value="vacation">Vacanță de Vis</option>
                                <option value="business">Start Business</option>
                                <option value="other">Altele</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="timeframe" class="form-label">Perioada (Ani)</label>
                            <input type="range" class="form-range" id="timeframe" min="1" max="10" value="5" oninput="updateTimeLabel(this.value)">
                            <div class="d-flex justify-content-between">
                                <small>1 an</small>
                                <small id="timeLabel" class="fw-bold">5 ani</small>
                                <small>10 ani</small>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100" onclick="calculateSavings()">
                            <i class="fas fa-calculate me-2"></i>Calculează Planul
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="col-lg-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Planul Tău de Economii
                    </h6>
                </div>
                <div class="card-body">
                    <div id="savingsResults" class="text-center">
                        <i class="fas fa-piggy-bank fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Completează formularul pentru a-ți vedea planul personalizat de economii.</p>
                    </div>
                </div>
            </div>

            <!-- Tips -->
            <div class="card mt-3 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Sfaturi pentru Economisire
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Automatizează:</strong> Configură virament automat către economii
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Obiective SMART:</strong> Specifice, măsurabile, realizabile
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Fondul de urgență:</strong> 3-6 luni de cheltuieli
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Revizuiește lunar:</strong> Ajustează planul regulat
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Chart -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Evoluția Economiilor în Timp
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="savingsChart" style="display: none;"></canvas>
                    <div id="chartPlaceholder" class="text-center py-5">
                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Graficul va fi afișat după calculare</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calculator-container {
    padding: 1rem 0;
}

.savings-milestone {
    background: linear-gradient(135deg, #17a2b8, #007bff);
    color: white;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    text-align: center;
}

.milestone-year {
    font-size: 1.2rem;
    font-weight: 600;
}

.milestone-amount {
    font-size: 1.8rem;
    font-weight: 700;
}

.progress-indicator {
    background: #e9ecef;
    border-radius: 10px;
    padding: 0.5rem;
    margin: 0.5rem 0;
}

.progress-bar-custom {
    background: linear-gradient(90deg, #28a745, #20c997);
    height: 20px;
    border-radius: 10px;
    transition: width 0.3s ease;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let savingsChart = null;

function updateTimeLabel(value) {
    document.getElementById('timeLabel').textContent = value + ' ani';
}

function calculateSavings() {
    const currentSavings = parseFloat(document.getElementById('currentSavings').value) || 0;
    const monthlyIncome = parseFloat(document.getElementById('monthlyIncome').value) || 0;
    const monthlyExpenses = parseFloat(document.getElementById('monthlyExpenses').value) || 0;
    const savingsGoal = parseFloat(document.getElementById('savingsGoal').value) || 0;
    const timeframe = parseInt(document.getElementById('timeframe').value) || 5;
    const goalPurpose = document.getElementById('goalPurpose').value;

    if (monthlyIncome <= 0 || monthlyExpenses < 0 || savingsGoal <= 0) {
        alert('Te rugăm să introduci valori valide!');
        return;
    }

    const monthlyAvailable = monthlyIncome - monthlyExpenses;
    const monthlyNeeded = (savingsGoal - currentSavings) / (timeframe * 12);
    
    const data = [];
    let currentAmount = currentSavings;
    
    for (let year = 0; year <= timeframe; year++) {
        if (year === 0) {
            data.push({
                year: year,
                amount: currentSavings
            });
        } else {
            currentAmount += monthlyNeeded * 12;
            data.push({
                year: year,
                amount: Math.min(currentAmount, savingsGoal)
            });
        }
    }

    displaySavingsResults(currentSavings, monthlyAvailable, monthlyNeeded, savingsGoal, timeframe, goalPurpose, data);
    createSavingsChart(data);
}

function displaySavingsResults(current, available, needed, goal, years, purpose, data) {
    const resultsDiv = document.getElementById('savingsResults');
    
    const feasible = available >= needed;
    const progressPercentage = (current / goal) * 100;
    
    const purposeText = {
        'emergency': 'Fond de Urgență',
        'house': 'Avans pentru Casă',
        'car': 'Mașină Nouă',
        'vacation': 'Vacanță de Vis',
        'business': 'Start Business',
        'other': 'Obiectiv Personal'
    };

    resultsDiv.innerHTML = `
        <div class="savings-milestone">
            <div class="milestone-year">Obiectiv: ${purposeText[purpose]}</div>
            <div class="milestone-amount">${formatCurrency(goal)}</div>
            <small>în ${years} ani</small>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="progress-indicator">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Progres Current</small>
                        <small>${progressPercentage.toFixed(1)}%</small>
                    </div>
                    <div class="progress-bar-custom" style="width: ${Math.min(progressPercentage, 100)}%"></div>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <div class="row">
                <div class="col-6">
                    <strong>Disponibil lunar:</strong>
                    <div class="text-${available >= needed ? 'success' : 'danger'} fw-bold">
                        ${formatCurrency(available)}
                    </div>
                </div>
                <div class="col-6">
                    <strong>Necesar lunar:</strong>
                    <div class="text-${available >= needed ? 'success' : 'warning'} fw-bold">
                        ${formatCurrency(needed)}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-${feasible ? 'success' : 'warning'} mt-3">
            <i class="fas fa-${feasible ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${feasible ? 
                `<strong>Obiectivul este realizabil!</strong> Poți economisi ${formatCurrency(needed)} lunar și vei atinge obiectivul în ${years} ani.` :
                `<strong>Atenție!</strong> Îți lipsesc ${formatCurrency(needed - available)} lunar. Consideră să reduci cheltuielile sau să prelungești termenul.`
            }
        </div>
        
        ${feasible ? `
            <div class="mt-3">
                <small class="text-muted">
                    <strong>Sfat:</strong> Configurează un transfer automat de ${formatCurrency(needed)} în prima zi a fiecărei luni!
                </small>
            </div>
        ` : ''}
    `;
}

function createSavingsChart(data) {
    const ctx = document.getElementById('savingsChart').getContext('2d');
    
    if (savingsChart) {
        savingsChart.destroy();
    }
    
    const labels = data.map(d => `An ${d.year}`);
    const amounts = data.map(d => d.amount);
    
    savingsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Economii Totale',
                data: amounts,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#28a745',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Planul Tău de Economii'
                },
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Economii: ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrencyShort(value);
                        }
                    }
                }
            }
        }
    });
    
    document.getElementById('savingsChart').style.display = 'block';
    document.getElementById('chartPlaceholder').style.display = 'none';
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('ro-RO', {
        style: 'currency',
        currency: 'RON',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

function formatCurrencyShort(amount) {
    if (amount >= 1000000) {
        return (amount / 1000000).toFixed(1) + 'M RON';
    } else if (amount >= 1000) {
        return (amount / 1000).toFixed(0) + 'K RON';
    }
    return amount.toFixed(0) + ' RON';
}

function gatherCalculatorData() {
    return {
        currentSavings: document.getElementById('currentSavings').value,
        monthlyIncome: document.getElementById('monthlyIncome').value,
        monthlyExpenses: document.getElementById('monthlyExpenses').value,
        savingsGoal: document.getElementById('savingsGoal').value,
        timeframe: document.getElementById('timeframe').value,
        goalPurpose: document.getElementById('goalPurpose').value,
        timestamp: new Date().toISOString()
    };
}

// Auto-calculate când se schimbă valorile
document.addEventListener('DOMContentLoaded', function() {
    const inputs = ['currentSavings', 'monthlyIncome', 'monthlyExpenses', 'savingsGoal', 'timeframe'];
    
    inputs.forEach(inputId => {
        const element = document.getElementById(inputId);
        if (element) {
            element.addEventListener('input', debounce(calculateSavings, 500));
        }
    });
    
    calculateSavings();
});

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>