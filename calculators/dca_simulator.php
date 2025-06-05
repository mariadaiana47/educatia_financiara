<div class="calculator-container">
    <h4 class="mb-4">
        <i class="fas fa-calendar-alt me-2"></i>
        Simulare Dollar Cost Averaging (DCA)
    </h4>
    
    <div class="row">
        <!-- Parametrii DCA -->
        <div class="col-lg-6">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-cog me-2"></i>
                        Parametrii Strategiei DCA
                    </h6>
                    
                    <form id="dcaForm">
                        <!-- Investiția Lunară -->
                        <div class="mb-3">
                            <label for="monthlyAmount" class="form-label">Suma Lunară (RON)</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="form-control" id="monthlyAmount" value="500" min="50" step="50">
                            </div>
                            <small class="form-text text-muted">Suma pe care o investești în fiecare lună</small>
                        </div>

                        <!-- Tipul de Investiție -->
                        <div class="mb-3">
                            <label for="investmentType" class="form-label">Tipul de Investiție</label>
                            <select class="form-select" id="investmentType" onchange="updateExpectedReturn()">
                                <option value="sp500">S&P 500 ETF (10% anual)</option>
                                <option value="bvb">Acțiuni BVB (8.5% anual)</option>
                                <option value="mixed">Portofoliu Mixt (7.5% anual)</option>
                                <option value="bonds">Obligațiuni (5.5% anual)</option>
                                <option value="custom">Personalizat</option>
                            </select>
                        </div>

                        <!-- Randament Anual Așteptat -->
                        <div class="mb-3">
                            <label for="expectedReturn" class="form-label">Randament Anual Așteptat (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="expectedReturn" value="10" min="0" max="30" step="0.1">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="form-text text-muted">Randamentul mediu anual estimat</small>
                        </div>

                        <!-- Perioada Investiție -->
                        <div class="mb-3">
                            <label for="investmentPeriod" class="form-label">Perioada Investiție</label>
                            <input type="range" class="form-range" id="investmentPeriod" min="1" max="30" value="10" oninput="updatePeriodLabel(this.value)">
                            <div class="d-flex justify-content-between">
                                <small>1 an</small>
                                <small id="periodLabel" class="fw-bold">10 ani</small>
                                <small>30 ani</small>
                            </div>
                        </div>

                        <!-- Volatilitate -->
                        <div class="mb-3">
                            <label for="volatility" class="form-label">Volatilitate Anuală (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="volatility" value="16" min="1" max="50" step="1">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="form-text text-muted">Măsura variabilității prețurilor (risc)</small>
                        </div>

                        <!-- Suma Inițială Opțională -->
                        <div class="mb-3">
                            <label for="initialAmount" class="form-label">Suma Inițială (Opțional)</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="form-control" id="initialAmount" value="0" min="0" step="100">
                            </div>
                            <small class="form-text text-muted">Suma cu care începi (poți lăsa 0)</small>
                        </div>

                        <button type="button" class="btn btn-primary w-100" onclick="simulateDCA()">
                            <i class="fas fa-play me-2"></i>
                            Simulează Strategia DCA
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
                        Rezultate DCA vs Lump Sum
                    </h6>
                </div>
                <div class="card-body">
                    <div id="dcaResults" class="text-center">
                        <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Configurează parametrii și apasă "Simulează" pentru a vedea cum funcționează strategia DCA.</p>
                    </div>
                </div>
            </div>

            <!-- Comparație Strategii -->
            <div class="card mt-3 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-balance-scale me-2"></i>
                        Comparație Strategii
                    </h6>
                </div>
                <div class="card-body">
                    <div id="strategyComparison">
                        <p class="text-muted text-center">Comparația va fi afișată după simulare</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafic Evoluție -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-area me-2"></i>
                        Evoluția Investiției: DCA vs Lump Sum
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="dcaChart" style="display: none;"></canvas>
                    <div id="dcaChartPlaceholder" class="text-center py-5">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Graficul va fi afișat după simulare</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scenarii de Piață -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Performanța în Diferite Scenarii de Piață
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="scenarioChart" style="display: none;"></canvas>
                    <div id="scenarioPlaceholder" class="text-center py-5">
                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Analiza scenariilor va fi afișată după simulare</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informații despre DCA -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-thumbs-up me-2"></i>
                        Avantajele DCA
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Reduce riscul timing-ului:</strong> Nu trebuie să ghicești momentul perfect
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Netezește volatilitatea:</strong> Cumperi mai multe acțiuni când prețurile sunt mici
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Disciplină financiară:</strong> Investești regulat, indiferent de emoții
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Accesibil:</strong> Poți începe cu sume mici
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Automatizabil:</strong> Poți automatiza procesul
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Limitările DCA
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-minus text-warning me-2"></i>
                            <strong>Piețe în creștere:</strong> Lump sum poate fi mai profitabil
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-minus text-warning me-2"></i>
                            <strong>Costurile tranzacțiilor:</strong> Mai multe comisioane
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-minus text-warning me-2"></i>
                            <strong>Cashul idle:</strong> Banii neînvestiți nu aduc randament
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-minus text-warning me-2"></i>
                            <strong>Randamentul mediu:</strong> Poate rata câștiguri mari
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-minus text-warning me-2"></i>
                            <strong>Perioada necesară:</strong> Beneficiile se văd pe termen lung
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calculator-container {
    padding: 1rem 0;
}

.result-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.result-item:last-child {
    border-bottom: none;
}

.result-metric {
    text-align: center;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
}

.metric-dca {
    background-color: #d4edda;
    color: #155724;
    border: 2px solid #28a745;
}

.metric-lump {
    background-color: #cce7ff;
    color: #0056b3;
    border: 2px solid #007bff;
}

.metric-winner {
    background-color: #fff3cd;
    color: #856404;
    border: 2px solid #ffc107;
    font-weight: bold;
}

.scenario-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
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
let dcaChart = null;
let scenarioChart = null;

// Date pentru tipurile de investiții
const INVESTMENT_TYPES = {
    sp500: { return: 10.0, volatility: 16.0, name: 'S&P 500 ETF' },
    bvb: { return: 8.5, volatility: 18.0, name: 'Acțiuni BVB' },
    mixed: { return: 7.5, volatility: 12.0, name: 'Portofoliu Mixt' },
    bonds: { return: 5.5, volatility: 4.0, name: 'Obligațiuni' },
    custom: { return: 10.0, volatility: 16.0, name: 'Personalizat' }
};

function updateExpectedReturn() {
    const type = document.getElementById('investmentType').value;
    if (type !== 'custom') {
        document.getElementById('expectedReturn').value = INVESTMENT_TYPES[type].return;
        document.getElementById('volatility').value = INVESTMENT_TYPES[type].volatility;
    }
}

function updatePeriodLabel(value) {
    document.getElementById('periodLabel').textContent = value + ' ani';
}

function simulateDCA() {
    const monthlyAmount = parseFloat(document.getElementById('monthlyAmount').value) || 0;
    const expectedReturn = parseFloat(document.getElementById('expectedReturn').value) || 0;
    const years = parseInt(document.getElementById('investmentPeriod').value) || 1;
    const volatility = parseFloat(document.getElementById('volatility').value) || 0;
    const initialAmount = parseFloat(document.getElementById('initialAmount').value) || 0;

    if (monthlyAmount <= 0) {
        alert('Te rugăm să introduci o sumă lunară validă!');
        return;
    }

    // Calculează totalul investit
    const totalMonths = years * 12;
    const totalInvested = initialAmount + (monthlyAmount * totalMonths);

    // Simulare DCA cu volatilitate
    const dcaData = simulateDCAStrategy(initialAmount, monthlyAmount, expectedReturn, volatility, years);
    
    // Simulare Lump Sum
    const lumpSumData = simulateLumpSumStrategy(totalInvested, expectedReturn, volatility, years);

    // Afișează rezultatele
    displayDCAResults(dcaData, lumpSumData, totalInvested, years);
    
    // Creează graficele
    createDCAChart(dcaData, lumpSumData);
    createScenarioChart(initialAmount, monthlyAmount, expectedReturn, volatility, years);
}

function simulateDCAStrategy(initialAmount, monthlyAmount, annualReturn, volatility, years) {
    const monthlyReturn = annualReturn / 100 / 12;
    const monthlyVolatility = volatility / 100 / Math.sqrt(12);
    const totalMonths = years * 12;
    
    let data = [];
    let currentValue = initialAmount;
    let totalInvested = initialAmount;
    let shares = initialAmount > 0 ? initialAmount / 100 : 0; // Presupunem preț inițial de 100 RON/acțiune
    let sharePrice = 100;
    
    // Simulează luna cu luna
    for (let month = 0; month <= totalMonths; month++) {
        if (month === 0) {
            data.push({
                month: month,
                value: initialAmount,
                invested: initialAmount,
                profit: 0,
                sharePrice: sharePrice,
                shares: shares
            });
        } else {
            // Calculează noul preț al acțiunii cu volatilitate
            const randomFactor = (Math.random() - 0.5) * 2; // -1 la 1
            const monthlyChange = monthlyReturn + (randomFactor * monthlyVolatility);
            sharePrice *= (1 + monthlyChange);
            
            // Adaugă contribuția lunară
            totalInvested += monthlyAmount;
            const newShares = monthlyAmount / sharePrice;
            shares += newShares;
            
            // Calculează valoarea curentă
            currentValue = shares * sharePrice;
            const profit = currentValue - totalInvested;
            
            data.push({
                month: month,
                value: currentValue,
                invested: totalInvested,
                profit: profit,
                sharePrice: sharePrice,
                shares: shares
            });
        }
    }
    
    return data;
}

function simulateLumpSumStrategy(totalAmount, annualReturn, volatility, years) {
    const monthlyReturn = annualReturn / 100 / 12;
    const monthlyVolatility = volatility / 100 / Math.sqrt(12);
    const totalMonths = years * 12;
    
    let data = [];
    let currentValue = totalAmount;
    let sharePrice = 100;
    const shares = totalAmount / sharePrice;
    
    for (let month = 0; month <= totalMonths; month++) {
        if (month === 0) {
            data.push({
                month: month,
                value: totalAmount,
                invested: totalAmount,
                profit: 0,
                sharePrice: sharePrice,
                shares: shares
            });
        } else {
            // Calculează noul preț cu aceeași volatilitate ca DCA
            const randomFactor = (Math.random() - 0.5) * 2;
            const monthlyChange = monthlyReturn + (randomFactor * monthlyVolatility);
            sharePrice *= (1 + monthlyChange);
            
            currentValue = shares * sharePrice;
            const profit = currentValue - totalAmount;
            
            data.push({
                month: month,
                value: currentValue,
                invested: totalAmount,
                profit: profit,
                sharePrice: sharePrice,
                shares: shares
            });
        }
    }
    
    return data;
}

function displayDCAResults(dcaData, lumpSumData, totalInvested, years) {
    const dcaFinal = dcaData[dcaData.length - 1];
    const lumpSumFinal = lumpSumData[lumpSumData.length - 1];
    
    const dcaReturn = ((dcaFinal.value - dcaFinal.invested) / dcaFinal.invested) * 100;
    const lumpSumReturn = ((lumpSumFinal.value - lumpSumFinal.invested) / lumpSumFinal.invested) * 100;
    
    const winner = dcaFinal.value > lumpSumFinal.value ? 'DCA' : 'Lump Sum';
    const difference = Math.abs(dcaFinal.value - lumpSumFinal.value);
    
    const resultsDiv = document.getElementById('dcaResults');
    
    resultsDiv.innerHTML = `
        <div class="result-metric metric-${winner === 'DCA' ? 'dca' : 'lump'} metric-winner">
            <h5><i class="fas fa-trophy me-2"></i>Câștigător: ${winner}</h5>
            <small>Cu ${formatCurrency(difference)} mai mult</small>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="result-metric metric-dca">
                    <h6>Strategia DCA</h6>
                    <div class="fs-4">${formatCurrency(dcaFinal.value)}</div>
                    <small>Câștig: ${formatCurrency(dcaFinal.profit)} (${dcaReturn.toFixed(1)}%)</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="result-metric metric-lump">
                    <h6>Lump Sum</h6>
                    <div class="fs-4">${formatCurrency(lumpSumFinal.value)}</div>
                    <small>Câștig: ${formatCurrency(lumpSumFinal.profit)} (${lumpSumReturn.toFixed(1)}%)</small>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <div class="result-item">
                <strong>Total Investit:</strong>
                <span>${formatCurrency(totalInvested)}</span>
            </div>
            <div class="result-item">
                <strong>Perioada:</strong>
                <span>${years} ani</span>
            </div>
            <div class="result-item">
                <strong>Acțiuni DCA:</strong>
                <span>${dcaFinal.shares.toFixed(2)}</span>
            </div>
            <div class="result-item">
                <strong>Preț Final/Acțiune:</strong>
                <span>${formatCurrency(dcaFinal.sharePrice)}</span>
            </div>
        </div>
    `;

    // Actualizează comparația strategiilor
    updateStrategyComparison(dcaReturn, lumpSumReturn, winner);
}

function updateStrategyComparison(dcaReturn, lumpSumReturn, winner) {
    const comparisonDiv = document.getElementById('strategyComparison');
    
    comparisonDiv.innerHTML = `
        <div class="text-center mb-3">
            <h6>Analiza Performanței</h6>
        </div>
        
        <div class="result-item">
            <strong>DCA Randament:</strong>
            <span class="${dcaReturn >= 0 ? 'text-success' : 'text-danger'} fw-bold">
                ${dcaReturn >= 0 ? '+' : ''}${dcaReturn.toFixed(1)}%
            </span>
        </div>
        
        <div class="result-item">
            <strong>Lump Sum Randament:</strong>
            <span class="${lumpSumReturn >= 0 ? 'text-success' : 'text-danger'} fw-bold">
                ${lumpSumReturn >= 0 ? '+' : ''}${lumpSumReturn.toFixed(1)}%
            </span>
        </div>
        
        <div class="result-item">
            <strong>Diferența:</strong>
            <span class="fw-bold">
                ${Math.abs(dcaReturn - lumpSumReturn).toFixed(1)} puncte procentuale
            </span>
        </div>
        
        <div class="alert alert-info mt-3 mb-0">
            <small>
                <i class="fas fa-info-circle me-1"></i>
                ${winner === 'DCA' ? 
                    'DCA a fost mai profitabil în această simulare, probabil datorită volatilității pieței.' : 
                    'Lump Sum a fost mai profitabil, sugerând o piață în general ascendentă.'
                }
            </small>
        </div>
    `;
}

function createDCAChart(dcaData, lumpSumData) {
    const ctx = document.getElementById('dcaChart').getContext('2d');
    
    if (dcaChart) {
        dcaChart.destroy();
    }
    
    const labels = dcaData.map((d, i) => {
        const year = Math.floor(i / 12);
        const month = i % 12;
        return year === 0 && month === 0 ? 'Start' : `${year}a ${month}l`;
    });
    
    dcaChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Strategia DCA',
                    data: dcaData.map(d => d.value),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Strategia Lump Sum',
                    data: lumpSumData.map(d => d.value),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Total Investit (DCA)',
                    data: dcaData.map(d => d.invested),
                    borderColor: '#6c757d',
                    backgroundColor: 'rgba(108, 117, 125, 0.1)',
                    borderWidth: 1,
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
                    text: 'Comparație DCA vs Lump Sum în Timp'
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
    
    // Afișează graficul
    document.getElementById('dcaChart').style.display = 'block';
    document.getElementById('dcaChartPlaceholder').style.display = 'none';
}

function createScenarioChart(initialAmount, monthlyAmount, expectedReturn, volatility, years) {
    // Simulează diferite scenarii de piață
    const scenarios = [
        { name: 'Piață în Scădere', return: expectedReturn - 5, color: '#dc3545' },
        { name: 'Piață Laterală', return: expectedReturn - 2, color: '#6c757d' },
        { name: 'Piață Normală', return: expectedReturn, color: '#28a745' },
        { name: 'Piață în Creștere', return: expectedReturn + 3, color: '#007bff' },
        { name: 'Bull Market', return: expectedReturn + 6, color: '#ffc107' }
    ];
    
    const dcaResults = [];
    const lumpSumResults = [];
    const labels = [];
    
    scenarios.forEach(scenario => {
        const dcaData = simulateDCAStrategy(initialAmount, monthlyAmount, scenario.return, volatility, years);
        const totalInvested = initialAmount + (monthlyAmount * years * 12);
        const lumpSumData = simulateLumpSumStrategy(totalInvested, scenario.return, volatility, years);
        
        dcaResults.push(dcaData[dcaData.length - 1].value);
        lumpSumResults.push(lumpSumData[lumpSumData.length - 1].value);
        labels.push(scenario.name);
    });
    
    const ctx = document.getElementById('scenarioChart').getContext('2d');
    
    if (scenarioChart) {
        scenarioChart.destroy();
    }
    
    scenarioChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'DCA',
                    data: dcaResults,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: '#28a745',
                    borderWidth: 2
                },
                {
                    label: 'Lump Sum',
                    data: lumpSumResults,
                    backgroundColor: 'rgba(0, 123, 255, 0.7)',
                    borderColor: '#007bff',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Performanța în Diferite Condiții de Piață'
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
            }
        }
    });
    
    // Afișează graficul
    document.getElementById('scenarioChart').style.display = 'block';
    document.getElementById('scenarioPlaceholder').style.display = 'none';
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
        monthlyAmount: document.getElementById('monthlyAmount').value,
        investmentType: document.getElementById('investmentType').value,
        expectedReturn: document.getElementById('expectedReturn').value,
        investmentPeriod: document.getElementById('investmentPeriod').value,
        volatility: document.getElementById('volatility').value,
        initialAmount: document.getElementById('initialAmount').value,
        timestamp: new Date().toISOString()
    };
}

// Inițializare
document.addEventListener('DOMContentLoaded', function() {
    updateExpectedReturn();
    updatePeriodLabel(document.getElementById('investmentPeriod').value);
});
</script>

<!-- Încarcă Chart.js pentru grafice -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>