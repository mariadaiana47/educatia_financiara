<div class="calculator-container">
    <h4 class="mb-4">
        <i class="fas fa-chart-exponential-growth me-2"></i>
        Calculator Dobândă Compusă Personal
    </h4>
    
    <div class="row">
        <!-- Formular Input -->
        <div class="col-lg-6">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-calculator me-2"></i>
                        Parametrii Investiției
                    </h6>
                    
                    <form id="compoundForm">
                        <div class="mb-3">
                            <label for="initialAmount" class="form-label">Suma Inițială (RON)</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="form-control" id="initialAmount" value="1000" min="0" step="100">
                            </div>
                            <small class="form-text text-muted">Suma cu care începi investiția</small>
                        </div>

                        <div class="mb-3">
                            <label for="monthlyContribution" class="form-label">Contribuție Lunară (RON)</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="form-control" id="monthlyContribution" value="500" min="0" step="50">
                            </div>
                            <small class="form-text text-muted">Suma pe care o adaugi lunar</small>
                        </div>

                        <div class="mb-3">
                            <label for="annualRate" class="form-label">Rata Anuală (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="annualRate" value="7" min="0" max="50" step="0.1">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="form-text text-muted">Randamentul anual estimat</small>
                        </div>

                        <div class="mb-3">
                            <label for="years" class="form-label">Perioada (Ani)</label>
                            <input type="range" class="form-range" id="years" min="1" max="50" value="20" oninput="updateYearsLabel(this.value)">
                            <div class="d-flex justify-content-between">
                                <small>1 an</small>
                                <small id="yearsLabel" class="fw-bold">20 ani</small>
                                <small>50 ani</small>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100" onclick="calculateCompound()">
                            <i class="fas fa-calculate me-2"></i>
                            Calculează
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Rezultate -->
        <div class="col-lg-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Rezultatele Tale
                    </h6>
                </div>
                <div class="card-body">
                    <div id="results" class="text-center">
                        <i class="fas fa-calculator fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Completează formularul și apasă "Calculează" pentru a vedea rezultatele.</p>
                    </div>
                </div>
            </div>

            <!-- Sfaturi -->
            <div class="card mt-3 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Sfaturi Utile
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Începe devreme:</strong> Timpul este cel mai puternic factor
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Fii constant:</strong> Contribuții regulate aduc rezultate mari
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Nu retrage:</strong> Lasă banii să crească compus
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Diversifică:</strong> Nu pune toate ouăle într-un coș
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Graficul evoluției -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-area me-2"></i>
                        Evoluția Investiției în Timp
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="compoundChart" style="display: none;"></canvas>
                    <div id="chartPlaceholder" class="text-center py-5">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
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

.result-metric {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 1rem;
    text-align: center;
}

.result-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.result-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.breakdown-item:last-child {
    border-bottom: none;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chart = null;

function updateYearsLabel(value) {
    document.getElementById('yearsLabel').textContent = value + ' ani';
}

function calculateCompound() {
    const initialAmount = parseFloat(document.getElementById('initialAmount').value) || 0;
    const monthlyContribution = parseFloat(document.getElementById('monthlyContribution').value) || 0;
    const annualRate = parseFloat(document.getElementById('annualRate').value) || 0;
    const years = parseInt(document.getElementById('years').value) || 1;

    if (initialAmount < 0 || monthlyContribution < 0 || annualRate < 0 || years < 1) {
        alert('Te rugăm să introduci valori valide!');
        return;
    }

    const monthlyRate = annualRate / 100 / 12;
    let data = [];
    let currentAmount = initialAmount;
    let totalContributions = initialAmount;
    
    for (let year = 0; year <= years; year++) {
        if (year === 0) {
            data.push({
                year: year,
                amount: initialAmount,
                contributions: initialAmount,
                interest: 0
            });
        } else {
            for (let month = 1; month <= 12; month++) {
                currentAmount += monthlyContribution;
                totalContributions += monthlyContribution;
                currentAmount = currentAmount * (1 + monthlyRate);
            }
            
            const totalInterest = currentAmount - totalContributions;
            
            data.push({
                year: year,
                amount: currentAmount,
                contributions: totalContributions,
                interest: totalInterest
            });
        }
    }

    const finalAmount = data[data.length - 1].amount;
    const finalContributions = data[data.length - 1].contributions;
    const finalInterest = data[data.length - 1].interest;

    displayResults(finalAmount, finalContributions, finalInterest, years);
    createChart(data);
}

function displayResults(finalAmount, totalContributions, totalInterest, years) {
    const resultsDiv = document.getElementById('results');
    
    const roi = totalContributions > 0 ? ((totalInterest / totalContributions) * 100) : 0;
    
    resultsDiv.innerHTML = `
        <div class="result-metric">
            <div class="result-value">${formatCurrency(finalAmount)}</div>
            <div class="result-label">Valoarea Finală</div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="breakdown-item">
                    <strong>Contribuții Totale:</strong>
                    <span class="text-primary fw-bold">${formatCurrency(totalContributions)}</span>
                </div>
                <div class="breakdown-item">
                    <strong>Dobânda Câștigată:</strong>
                    <span class="text-success fw-bold">${formatCurrency(totalInterest)}</span>
                </div>
                <div class="breakdown-item">
                    <strong>ROI Total:</strong>
                    <span class="text-warning fw-bold">${roi.toFixed(1)}%</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="breakdown-item">
                    <strong>Perioada:</strong>
                    <span class="fw-bold">${years} ani</span>
                </div>
                <div class="breakdown-item">
                    <strong>Multiplicator:</strong>
                    <span class="text-danger fw-bold">${(finalAmount / totalContributions).toFixed(1)}x</span>
                </div>
            </div>
        </div>
        
        <div class="alert alert-success mt-3">
            <i class="fas fa-trophy me-2"></i>
            <strong>Felicitări!</strong> În ${years} ani, prin economisirea regulată, vei avea 
            <strong>${formatCurrency(totalInterest)}</strong> mai mult decât ai contribuit!
        </div>
    `;
}

function createChart(data) {
    const ctx = document.getElementById('compoundChart').getContext('2d');
    
    if (chart) {
        chart.destroy();
    }
    
    const labels = data.map(d => `An ${d.year}`);
    const amounts = data.map(d => d.amount);
    const contributions = data.map(d => d.contributions);
    const interests = data.map(d => d.interest);
    
    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Valoarea Totală',
                    data: amounts,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Contribuții',
                    data: contributions,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false
                },
                {
                    label: 'Dobânda Câștigată',
                    data: interests,
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    borderWidth: 2,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Evoluția Investiției în Timp'
                },
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
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
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
    
    document.getElementById('compoundChart').style.display = 'block';
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
        initialAmount: document.getElementById('initialAmount').value,
        monthlyContribution: document.getElementById('monthlyContribution').value,
        annualRate: document.getElementById('annualRate').value,
        years: document.getElementById('years').value,
        timestamp: new Date().toISOString()
    };
}

// Auto-calculare când se schimbă valorile
document.addEventListener('DOMContentLoaded', function() {
    const inputs = ['initialAmount', 'monthlyContribution', 'annualRate', 'years'];
    
    inputs.forEach(inputId => {
        const element = document.getElementById(inputId);
        if (element) {
            element.addEventListener('input', debounce(calculateCompound, 500));
        }
    });
    
    calculateCompound();
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