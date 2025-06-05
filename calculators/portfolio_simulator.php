<div class="calculator-container">
    <h4 class="mb-4">
        <i class="fas fa-chart-pie me-2"></i>
        Simulare Portofoliu Virtual 10.000 lei
    </h4>
    
    <div class="row">
        <!-- Setări Portofoliu -->
        <div class="col-lg-6">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-sliders-h me-2"></i>
                        Configurează Portofoliul
                    </h6>
                    
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Buget total:</strong> 10.000 RON
                    </div>
                    
                    <form id="portfolioForm">
                        <!-- Acțiuni Românești -->
                        <div class="mb-3">
                            <label for="stocksRO" class="form-label">
                                Acțiuni Românești (BVB) <span class="text-muted">- RON</span>
                            </label>
                            <input type="range" class="form-range" id="stocksRO" min="0" max="100" value="30" oninput="updateAllocation()">
                            <div class="d-flex justify-content-between">
                                <small>0%</small>
                                <small id="stocksROLabel" class="fw-bold text-primary">30% (3.000 RON)</small>
                                <small>100%</small>
                            </div>
                            <small class="text-muted">BRD, Banca Transilvania, Hidroelectrica</small>
                        </div>

                        <!-- Acțiuni Internaționale -->
                        <div class="mb-3">
                            <label for="stocksIntl" class="form-label">
                                Acțiuni Internaționale <span class="text-muted">- RON</span>
                            </label>
                            <input type="range" class="form-range" id="stocksIntl" min="0" max="100" value="25" oninput="updateAllocation()">
                            <div class="d-flex justify-content-between">
                                <small>0%</small>
                                <small id="stocksIntlLabel" class="fw-bold text-success">25% (2.500 RON)</small>
                                <small>100%</small>
                            </div>
                            <small class="text-muted">ETF-uri S&P 500, Apple, Microsoft</small>
                        </div>

                        <!-- Obligațiuni -->
                        <div class="mb-3">
                            <label for="bonds" class="form-label">
                                Obligațiuni <span class="text-muted">- RON</span>
                            </label>
                            <input type="range" class="form-range" id="bonds" min="0" max="100" value="25" oninput="updateAllocation()">
                            <div class="d-flex justify-content-between">
                                <small>0%</small>
                                <small id="bondsLabel" class="fw-bold text-warning">25% (2.500 RON)</small>
                                <small>100%</small>
                            </div>
                            <small class="text-muted">Obligațiuni de stat, corporative</small>
                        </div>

                        <!-- Fonduri Mutuale -->
                        <div class="mb-3">
                            <label for="funds" class="form-label">
                                Fonduri Mutuale <span class="text-muted">- RON</span>
                            </label>
                            <input type="range" class="form-range" id="funds" min="0" max="100" value="15" oninput="updateAllocation()">
                            <div class="d-flex justify-content-between">
                                <small>0%</small>
                                <small id="fundsLabel" class="fw-bold text-info">15% (1.500 RON)</small>
                                <small>100%</small>
                            </div>
                            <small class="text-muted">BRD, BCR Asset Management</small>
                        </div>

                        <!-- Cash/Depozite -->
                        <div class="mb-3">
                            <label for="cash" class="form-label">
                                Cash/Depozite <span class="text-muted">- RON</span>
                            </label>
                            <input type="range" class="form-range" id="cash" min="0" max="100" value="5" oninput="updateAllocation()">
                            <div class="d-flex justify-content-between">
                                <small>0%</small>
                                <small id="cashLabel" class="fw-bold text-secondary">5% (500 RON)</small>
                                <small>100%</small>
                            </div>
                            <small class="text-muted">Lichiditate pentru oportunități</small>
                        </div>

                        <!-- Validare alocare -->
                        <div class="alert alert-warning" id="allocationWarning" style="display: none;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Alocarea trebuie să fie 100%! Acum: <span id="totalAllocation">100</span>%
                        </div>

                        <!-- Perioada simulare -->
                        <div class="mb-3">
                            <label for="timeHorizon" class="form-label">Perioada Simulare</label>
                            <select class="form-select" id="timeHorizon">
                                <option value="1">1 an</option>
                                <option value="3">3 ani</option>
                                <option value="5" selected>5 ani</option>
                                <option value="10">10 ani</option>
                                <option value="20">20 ani</option>
                            </select>
                        </div>

                        <button type="button" class="btn btn-primary w-100" onclick="simulatePortfolio()">
                            <i class="fas fa-play me-2"></i>
                            Simulează Portofoliul
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Rezultate și Grafice -->
        <div class="col-lg-6">
            <!-- Grafic Alocare -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Alocarea Portofoliului
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="allocationChart" height="200"></canvas>
                </div>
            </div>

            <!-- Rezultate Simulare -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Rezultate Simulare
                    </h6>
                </div>
                <div class="card-body">
                    <div id="simulationResults" class="text-center">
                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Configurează portofoliul și apasă "Simulează" pentru a vedea rezultatele.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafic Evoluție în Timp -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-area me-2"></i>
                        Evoluția Portofoliului în Timp
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" style="display: none;"></canvas>
                    <div id="performancePlaceholder" class="text-center py-5">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Graficul de performanță va fi afișat după simulare</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sfaturi Investiții -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Sfaturi pentru Portofoliu
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-check-circle text-success me-2"></i>Reguli de Aur</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-arrow-right text-primary me-2"></i>
                                    <strong>Diversifică:</strong> Nu pune toate ouăle într-un coș
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-arrow-right text-primary me-2"></i>
                                    <strong>Regula 100-vârsta:</strong> % acțiuni = 100 - vârsta ta
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-arrow-right text-primary me-2"></i>
                                    <strong>Rebalansează:</strong> Anual sau când alocarea se abate cu >5%
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-exclamation-triangle text-warning me-2"></i>Atenție la:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-times text-danger me-2"></i>
                                    Concentrarea excesivă într-un sector
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-times text-danger me-2"></i>
                                    Investirea emoțională (teamă/lăcomie)
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-times text-danger me-2"></i>
                                    Ignorarea inflației și taxelor
                                </li>
                            </ul>
                        </div>
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

.allocation-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.allocation-item:last-child {
    border-bottom: none;
}

.risk-level {
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: 500;
    text-align: center;
    margin-bottom: 1rem;
}

.risk-conservative {
    background-color: #28a745;
    color: white;
}

.risk-moderate {
    background-color: #ffc107;
    color: #212529;
}

.risk-aggressive {
    background-color: #dc3545;
    color: white;
}

.performance-metric {
    text-align: center;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
}

.metric-positive {
    background-color: #d4edda;
    color: #155724;
}

.metric-negative {
    background-color: #f8d7da;
    color: #721c24;
}

.metric-neutral {
    background-color: #e2e3e5;
    color: #383d41;
}

.form-range::-webkit-slider-thumb {
    background: #007bff;
}

.form-range::-moz-range-thumb {
    background: #007bff;
    border: none;
}
</style>

<script>
let allocationChart = null;
let performanceChart = null;
const TOTAL_BUDGET = 10000;

// Randamente anuale istorice medii (%)
const EXPECTED_RETURNS = {
    stocksRO: 8.5,      // BVB
    stocksIntl: 10.0,   // S&P 500
    bonds: 5.5,         // Obligațiuni
    funds: 7.5,         // Fonduri mutuale
    cash: 2.0           // Depozite
};

// Volatilitatea (deviația standard anuală)
const VOLATILITY = {
    stocksRO: 18.0,
    stocksIntl: 16.0,
    bonds: 4.0,
    funds: 12.0,
    cash: 0.5
};

function updateAllocation() {
    const stocksRO = parseInt(document.getElementById('stocksRO').value);
    const stocksIntl = parseInt(document.getElementById('stocksIntl').value);
    const bonds = parseInt(document.getElementById('bonds').value);
    const funds = parseInt(document.getElementById('funds').value);
    const cash = parseInt(document.getElementById('cash').value);
    
    const total = stocksRO + stocksIntl + bonds + funds + cash;
    
    // Auto-ajustare dacă totalul nu este 100%
    if (total !== 100) {
        const difference = 100 - total;
        // Ajustează cash-ul pentru a face totalul 100%
        const newCash = Math.max(0, Math.min(100, cash + difference));
        document.getElementById('cash').value = newCash;
    }
    
    // Recalculează valorile după ajustare
    const finalStocksRO = parseInt(document.getElementById('stocksRO').value);
    const finalStocksIntl = parseInt(document.getElementById('stocksIntl').value);
    const finalBonds = parseInt(document.getElementById('bonds').value);
    const finalFunds = parseInt(document.getElementById('funds').value);
    const finalCash = parseInt(document.getElementById('cash').value);
    
    // Actualizează label-urile
    document.getElementById('stocksROLabel').textContent = `${finalStocksRO}% (${formatCurrency(finalStocksRO * TOTAL_BUDGET / 100)})`;
    document.getElementById('stocksIntlLabel').textContent = `${finalStocksIntl}% (${formatCurrency(finalStocksIntl * TOTAL_BUDGET / 100)})`;
    document.getElementById('bondsLabel').textContent = `${finalBonds}% (${formatCurrency(finalBonds * TOTAL_BUDGET / 100)})`;
    document.getElementById('fundsLabel').textContent = `${finalFunds}% (${formatCurrency(finalFunds * TOTAL_BUDGET / 100)})`;
    document.getElementById('cashLabel').textContent = `${finalCash}% (${formatCurrency(finalCash * TOTAL_BUDGET / 100)})`;
    
    // Ascunde avertismentul
    document.getElementById('allocationWarning').style.display = 'none';
    
    // Actualizează graficul de alocare
    updateAllocationChart();
}

function updateAllocationChart() {
    const ctx = document.getElementById('allocationChart').getContext('2d');
    
    const stocksRO = parseInt(document.getElementById('stocksRO').value);
    const stocksIntl = parseInt(document.getElementById('stocksIntl').value);
    const bonds = parseInt(document.getElementById('bonds').value);
    const funds = parseInt(document.getElementById('funds').value);
    const cash = parseInt(document.getElementById('cash').value);
    
    if (allocationChart) {
        allocationChart.destroy();
    }
    
    allocationChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Acțiuni RO', 'Acțiuni Intl', 'Obligațiuni', 'Fonduri', 'Cash'],
            datasets: [{
                data: [stocksRO, stocksIntl, bonds, funds, cash],
                backgroundColor: [
                    '#007bff',  // Albastru
                    '#28a745',  // Verde
                    '#ffc107',  // Galben
                    '#17a2b8',  // Cyan
                    '#6c757d'   // Gri
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const percentage = context.parsed;
                            const value = formatCurrency(percentage * TOTAL_BUDGET / 100);
                            return `${context.label}: ${percentage}% (${value})`;
                        }
                    }
                }
            }
        }
    });
}

function simulatePortfolio() {
    const stocksRO = parseInt(document.getElementById('stocksRO').value) / 100;
    const stocksIntl = parseInt(document.getElementById('stocksIntl').value) / 100;
    const bonds = parseInt(document.getElementById('bonds').value) / 100;
    const funds = parseInt(document.getElementById('funds').value) / 100;
    const cash = parseInt(document.getElementById('cash').value) / 100;
    const years = parseInt(document.getElementById('timeHorizon').value);
    
    // Verifică alocarea
    const total = (stocksRO + stocksIntl + bonds + funds + cash) * 100;
    if (Math.abs(total - 100) > 0.1) {
        alert('Alocarea trebuie să fie exact 100%!');
        return;
    }
    
    // Calculează randamentul așteptat al portofoliului
    const portfolioReturn = 
        stocksRO * EXPECTED_RETURNS.stocksRO +
        stocksIntl * EXPECTED_RETURNS.stocksIntl +
        bonds * EXPECTED_RETURNS.bonds +
        funds * EXPECTED_RETURNS.funds +
        cash * EXPECTED_RETURNS.cash;
    
    // Calculează volatilitatea portofoliului (simplificat)
    const portfolioVolatility = Math.sqrt(
        Math.pow(stocksRO * VOLATILITY.stocksRO, 2) +
        Math.pow(stocksIntl * VOLATILITY.stocksIntl, 2) +
        Math.pow(bonds * VOLATILITY.bonds, 2) +
        Math.pow(funds * VOLATILITY.funds, 2) +
        Math.pow(cash * VOLATILITY.cash, 2)
    );
    
    // Simulează evoluția în timp
    const simulationData = [];
    let currentValue = TOTAL_BUDGET;
    
    for (let year = 0; year <= years; year++) {
        if (year === 0) {
            simulationData.push({
                year: year,
                value: TOTAL_BUDGET,
                bestCase: TOTAL_BUDGET,
                worstCase: TOTAL_BUDGET
            });
        } else {
            // Scenario de bază
            currentValue *= (1 + portfolioReturn / 100);
            
            // Cel mai bun caz (+1 deviație standard)
            const bestCase = TOTAL_BUDGET * Math.pow(1 + (portfolioReturn + portfolioVolatility) / 100, year);
            
            // Cel mai rău caz (-1 deviație standard)
            const worstCase = TOTAL_BUDGET * Math.pow(1 + (portfolioReturn - portfolioVolatility) / 100, year);
            
            simulationData.push({
                year: year,
                value: currentValue,
                bestCase: bestCase,
                worstCase: Math.max(worstCase, TOTAL_BUDGET * 0.3) // Protecție împotriva valorilor prea negative
            });
        }
    }
    
    const finalValue = simulationData[simulationData.length - 1].value;
    const totalGain = finalValue - TOTAL_BUDGET;
    const totalReturn = (totalGain / TOTAL_BUDGET) * 100;
    const annualReturn = Math.pow(finalValue / TOTAL_BUDGET, 1 / years) - 1;
    
    // Determină nivelul de risc
    let riskLevel, riskClass, riskDescription;
    if (portfolioVolatility < 8) {
        riskLevel = 'Conservator';
        riskClass = 'risk-conservative';
        riskDescription = 'Risc scăzut, randamente stabile';
    } else if (portfolioVolatility < 15) {
        riskLevel = 'Moderat';
        riskClass = 'risk-moderate'; 
        riskDescription = 'Echilibru între risc și randament';
    } else {
        riskLevel = 'Agresiv';
        riskClass = 'risk-aggressive';
        riskDescription = 'Risc ridicat, potențial de randament mare';
    }
    
    // Afișează rezultatele
    displaySimulationResults(finalValue, totalGain, totalReturn, annualReturn, portfolioReturn, portfolioVolatility, riskLevel, riskClass, riskDescription, years);
    
    // Creează graficul de performanță
    createPerformanceChart(simulationData);
}

function displaySimulationResults(finalValue, totalGain, totalReturn, annualReturn, expectedReturn, volatility, riskLevel, riskClass, riskDescription, years) {
    const resultsDiv = document.getElementById('simulationResults');
    
    const gainClass = totalGain >= 0 ? 'metric-positive' : 'metric-negative';
    
    resultsDiv.innerHTML = `
        <div class="risk-level ${riskClass}">
            <strong>Profil de Risc: ${riskLevel}</strong><br>
            <small>${riskDescription}</small>
        </div>
        
        <div class="performance-metric ${gainClass}">
            <h4>${formatCurrency(finalValue)}</h4>
            <small>Valoarea Finală (după ${years} ani)</small>
        </div>
        
        <div class="row">
            <div class="col-6">
                <div class="allocation-item">
                    <strong>Investiția Inițială:</strong>
                    <span>${formatCurrency(TOTAL_BUDGET)}</span>
                </div>
                <div class="allocation-item">
                    <strong>Câștig Total:</strong>
                    <span class="${totalGain >= 0 ? 'text-success' : 'text-danger'} fw-bold">
                        ${totalGain >= 0 ? '+' : ''}${formatCurrency(totalGain)}
                    </span>
                </div>
                <div class="allocation-item">
                    <strong>Randament Total:</strong>
                    <span class="${totalReturn >= 0 ? 'text-success' : 'text-danger'} fw-bold">
                        ${totalReturn >= 0 ? '+' : ''}${totalReturn.toFixed(1)}%
                    </span>
                </div>
            </div>
            <div class="col-6">
                <div class="allocation-item">
                    <strong>Randament Anual:</strong>
                    <span class="fw-bold">${(annualReturn * 100).toFixed(1)}%</span>
                </div>
                <div class="allocation-item">
                    <strong>Randament Așteptat:</strong>
                    <span class="text-primary fw-bold">${expectedReturn.toFixed(1)}%</span>
                </div>
                <div class="allocation-item">
                    <strong>Volatilitate:</strong>
                    <span class="text-warning fw-bold">${volatility.toFixed(1)}%</span>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Notă:</strong> Aceasta este o simulare bazată pe randamente istorice. 
            Rezultatele reale pot diferi semnificativ.
        </div>
        
        ${totalReturn > 50 ? `
            <div class="alert alert-success">
                <i class="fas fa-trophy me-2"></i>
                <strong>Excelent!</strong> Portofoliul tău are potențial de creștere mare în ${years} ani!
            </div>
        ` : ''}
        
        ${volatility > 20 ? `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Atenție:</strong> Portofoliul are volatilitate mare. Consideră diversificarea.
            </div>
        ` : ''}
    `;
}

function createPerformanceChart(data) {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    if (performanceChart) {
        performanceChart.destroy();
    }
    
    const labels = data.map(d => `An ${d.year}`);
    const values = data.map(d => d.value);
    const bestCase = data.map(d => d.bestCase);
    const worstCase = data.map(d => d.worstCase);
    
    performanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Scenariul de Bază',
                    data: values,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4
                },
                {
                    label: 'Scenariul Optimist',
                    data: bestCase,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false
                },
                {
                    label: 'Scenariul Pesimist',
                    data: worstCase,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Evoluția Portofoliului în Diferite Scenarii'
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
                    beginAtZero: false,
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
    
    // Afișează graficul
    document.getElementById('performanceChart').style.display = 'block';
    document.getElementById('performancePlaceholder').style.display = 'none';
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

// Funcție pentru colectarea datelor (pentru salvarea progresului)
function gatherCalculatorData() {
    return {
        stocksRO: document.getElementById('stocksRO').value,
        stocksIntl: document.getElementById('stocksIntl').value,
        bonds: document.getElementById('bonds').value,
        funds: document.getElementById('funds').value,
        cash: document.getElementById('cash').value,
        timeHorizon: document.getElementById('timeHorizon').value,
        timestamp: new Date().toISOString()
    };
}

// Inițializare
document.addEventListener('DOMContentLoaded', function() {
    updateAllocation();
    updateAllocationChart();
});
</script>

<!-- Încarcă Chart.js pentru grafice -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>