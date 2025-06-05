<div class="calculator-container">
    <h4 class="mb-4">
        <i class="fas fa-clock me-2"></i>
        Comparația Dramatică: Start la 25 vs 35 ani
    </h4>
    
    <div class="alert alert-warning mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="alert-heading mb-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    De ce 10 ani fac diferența ENORMĂ?
                </h5>
                <p class="mb-0">
                    Această simulare îți va arăta de ce <strong>fiecare an conte</strong> când vine vorba de economisirea pentru pensie. 
                    Rezultatele te vor surprinde!
                </p>
            </div>
            <div class="col-md-4 text-center">
                <i class="fas fa-hourglass-half fa-4x text-warning"></i>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Parametrii Comparației -->
        <div class="col-lg-6">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-cog me-2"></i>
                        Setările Comparației
                    </h6>
                    
                    <form id="comparisonForm">
                        <!-- Suma Lunară -->
                        <div class="mb-3">
                            <label for="monthlyAmount" class="form-label">Suma Economisită Lunar</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="form-control" id="monthlyAmount" value="500" min="100" step="50">
                            </div>
                            <small class="form-text text-muted">Aceeași sumă pentru ambele scenarii</small>
                        </div>

                        <!-- Randamentul Anual -->
                        <div class="mb-3">
                            <label for="annualReturn" class="form-label">Randamentul Anual (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="annualReturn" value="7" min="3" max="15" step="0.1">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="form-text text-muted">Randamentul mediu pe termen lung</small>
                        </div>

                        <!-- Vârsta de Pensionare -->
                        <div class="mb-3">
                            <label for="retirementAge" class="form-label">Vârsta de Pensionare</label>
                            <select class="form-select" id="retirementAge">
                                <option value="60">60 ani</option>
                                <option value="65" selected>65 ani</option>
                                <option value="67">67 ani</option>
                                <option value="70">70 ani</option>
                            </select>
                        </div>

                        <!-- Scenarii Suplimentare -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeInflation" checked>
                                <label class="form-check-label" for="includeInflation">
                                    Include inflația (4% anual)
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showRealValue" checked>
                                <label class="form-check-label" for="showRealValue">
                                    Arată puterea de cumpărare reală
                                </label>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100" onclick="runComparison()">
                            <i class="fas fa-play me-2"></i>
                            Rulează Comparația Dramatică
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Rezultate Principale -->
        <div class="col-lg-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-exclamation me-2"></i>
                        Rezultatul Șocant
                    </h6>
                </div>
                <div class="card-body">
                    <div id="comparisonResults" class="text-center">
                        <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Apasă butonul pentru a vedea diferența dramatică între cele două scenarii!</p>
                    </div>
                </div>
            </div>

            <!-- Mesajul Motivațional -->
            <div class="card mt-3 border-success" id="motivationalMessage" style="display: none;">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Lecția de Învățat
                    </h6>
                </div>
                <div class="card-body">
                    <div id="motivationalContent">
                        <!-- Conținutul va fi generat dinamic -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparația Detaliată -->
    <div class="row mt-4" id="detailedComparison" style="display: none;">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user-graduate me-2"></i>
                        Ana (începe la 25 ani)
                    </h6>
                </div>
                <div class="card-body">
                    <div id="ana-results">
                        <!-- Rezultatele pentru Ana -->
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-user-tie me-2"></i>
                        Bogdan (începe la 35 ani)
                    </h6>
                </div>
                <div class="card-body">
                    <div id="bogdan-results">
                        <!-- Rezultatele pentru Bogdan -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafic Comparativ -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Evoluția Comparativă: Puterea Timpului
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="comparisonChart" style="display: none;"></canvas>
                    <div id="comparisonChartPlaceholder" class="text-center py-5">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Graficul comparativ va fi afișat după calculare</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Detaliat An cu An -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-table me-2"></i>
                        Progresul An cu An
                    </h6>
                </div>
                <div class="card-body">
                    <div id="yearlyProgressTable" style="display: none;">
                        <!-- Tabelul va fi generat dinamic -->
                    </div>
                    <div id="tablePlaceholder" class="text-center py-4">
                        <i class="fas fa-table fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Tabelul detaliat va fi afișat după calculare</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Concluzii și Lecții -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-thumbs-up me-2"></i>
                        De ce Contează Timpul
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-clock text-success me-2"></i>
                            <strong>Dobânda compusă:</strong> Cu cât mai mult timp, cu atât mai puternică
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-snowball text-success me-2"></i>
                            <strong>Efectul bulgăre:</strong> Creșterea accelerează în timp
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-calendar-plus text-success me-2"></i>
                            <strong>Fiecare an conte:</strong> Chiar și un an în plus face diferența
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-rocket text-success me-2"></i>
                            <strong>Start timpuriu:</strong> Compensează pentru contribuții mai mici
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-brain text-success me-2"></i>
                            <strong>Psihologic:</strong> Habitul se formează mai ușor când ești tânăr
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Costul Amânării
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-times text-danger me-2"></i>
                            <strong>Timp pierdut:</strong> Nu se poate recupera
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-chart-line-down text-danger me-2"></i>
                            <strong>Contribuții mai mari:</strong> Trebuie să compensezi
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-weight-hanging text-danger me-2"></i>
                            <strong>Presiune financiară:</strong> Procente mai mari din venit
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-stress text-danger me-2"></i>
                            <strong>Stres psihologic:</strong> "Trebuie să recuperez timpul pierdut"
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-question-circle text-danger me-2"></i>
                            <strong>Incertitudine:</strong> Mai puțin timp pentru corecții
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">
                        <i class="fas fa-rocket me-2"></i>
                        Lecția Cea Mai Importantă
                    </h5>
                    <p class="card-text lead">
                        <strong>CEL MAI BUN MOMENT SĂ ÎNCEPI A FOST ACUM 10 ANI.</strong><br>
                        <strong>AL DOILEA CEL MAI BUN MOMENT ESTE ASTĂZI!</strong>
                    </p>
                    <a href="#" class="btn btn-warning btn-lg" onclick="showActionPlan()">
                        <i class="fas fa-play-circle me-2"></i>
                        Vreau să încep ACUM!
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calculator-container {
    padding: 1rem 0;
}

.comparison-metric {
    text-align: center;
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 1rem;
}

.metric-ana {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
}

.metric-bogdan {
    background: linear-gradient(135deg, #ffc107, #e0a800);
    color: #212529;
}

.metric-difference {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    border: 3px solid #fff;
    box-shadow: 0 0 20px rgba(220, 53, 69, 0.3);
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

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
}

.detail-item:last-child {
    border-bottom: none;
}

.winner-badge {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.dramatic-number {
    font-size: 3rem;
    font-weight: 900;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.table-comparison {
    font-size: 0.9rem;
}

.table-comparison .table-success {
    background-color: rgba(40, 167, 69, 0.1);
}

.table-comparison .table-warning {
    background-color: rgba(255, 193, 7, 0.1);
}
</style>

<script>
let comparisonChart = null;

function runComparison() {
    const monthlyAmount = parseFloat(document.getElementById('monthlyAmount').value) || 500;
    const annualReturn = parseFloat(document.getElementById('annualReturn').value) || 7;
    const retirementAge = parseInt(document.getElementById('retirementAge').value) || 65;
    const includeInflation = document.getElementById('includeInflation').checked;
    const showRealValue = document.getElementById('showRealValue').checked;
    
    const inflationRate = includeInflation ? 4 : 0;
    
    // Calculează pentru Ana (25-65 ani = 40 ani)
    const anaYears = retirementAge - 25;
    const anaData = calculateAccumulation(monthlyAmount, annualReturn, anaYears, 25);
    
    // Calculează pentru Bogdan (35-65 ani = 30 ani)
    const bogdanYears = retirementAge - 35;
    const bogdanData = calculateAccumulation(monthlyAmount, annualReturn, bogdanYears, 35);
    
    // Calculează valorile reale cu inflația
    const anaRealValue = showRealValue ? anaData.finalAmount / Math.pow(1 + inflationRate/100, anaYears) : anaData.finalAmount;
    const bogdanRealValue = showRealValue ? bogdanData.finalAmount / Math.pow(1 + inflationRate/100, bogdanYears) : bogdanData.finalAmount;
    
    // Afișează rezultatele
    displayDramaticResults(anaData, bogdanData, anaRealValue, bogdanRealValue, showRealValue);
    
    // Creează graficul
    createComparisonChart(anaData, bogdanData, retirementAge);
    
    // Creează tabelul
    createYearlyTable(anaData, bogdanData, retirementAge);
    
    // Afișează secțiunile
    document.getElementById('detailedComparison').style.display = 'block';
    document.getElementById('motivationalMessage').style.display = 'block';
}

function calculateAccumulation(monthlyAmount, annualReturn, years, startAge) {
    const monthlyRate = annualReturn / 100 / 12;
    const totalMonths = years * 12;
    
    let data = [];
    let currentValue = 0;
    let totalContributed = 0;
    
    // Calculează evoluția an cu an
    for (let year = 0; year <= years; year++) {
        if (year === 0) {
            data.push({
                year: year,
                age: startAge + year,
                value: 0,
                contributed: 0,
                interest: 0
            });
        } else {
            // Contribuțiile anului
            const yearlyContribution = monthlyAmount * 12;
            totalContributed += yearlyContribution;
            
            // Calculează valoarea cu dobânda compusă
            for (let month = 1; month <= 12; month++) {
                currentValue += monthlyAmount;
                currentValue *= (1 + monthlyRate);
            }
            
            const interest = currentValue - totalContributed;
            
            data.push({
                year: year,
                age: startAge + year,
                value: currentValue,
                contributed: totalContributed,
                interest: interest
            });
        }
    }
    
    return {
        finalAmount: currentValue,
        totalContributed: totalContributed,
        totalInterest: currentValue - totalContributed,
        years: years,
        startAge: startAge,
        data: data
    };
}

function displayDramaticResults(anaData, bogdanData, anaRealValue, bogdanRealValue, showRealValue) {
    const difference = anaData.finalAmount - bogdanData.finalAmount;
    const contributionDifference = anaData.totalContributed - bogdanData.totalContributed;
    const efficiency = difference / contributionDifference; // Câți lei câștigă Ana pentru fiecare leu în plus contribuit
    
    const resultsDiv = document.getElementById('comparisonResults');
    
    resultsDiv.innerHTML = `
        <div class="comparison-metric metric-difference winner-badge">
            <div class="dramatic-number">${formatCurrency(difference)}</div>
            <div class="result-label">Ana are MAI MULT decât Bogdan!</div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="comparison-metric metric-ana">
                    <div class="result-value">${formatCurrency(anaData.finalAmount)}</div>
                    <div class="result-label">Ana (start la 25 ani)</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="comparison-metric metric-bogdan">
                    <div class="result-value">${formatCurrency(bogdanData.finalAmount)}</div>
                    <div class="result-label">Bogdan (start la 35 ani)</div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-danger">
            <h6><i class="fas fa-bomb me-2"></i>Datele ȘOCANTE:</h6>
            <ul class="mb-0">
                <li><strong>Ana contribuie ${anaData.years} ani, Bogdan ${bogdanData.years} ani</strong></li>
                <li><strong>Diferența de contribuții: ${formatCurrency(contributionDifference)}</strong></li>
                <li><strong>Câștigul Anei per leu extra: ${efficiency.toFixed(1)} lei!</strong></li>
                ${showRealValue ? `<li><strong>Valoarea reală Ana: ${formatCurrency(anaRealValue)}</strong></li>` : ''}
            </ul>
        </div>
    `;

    // Afișează detaliile pentru fiecare
    document.getElementById('ana-results').innerHTML = generatePersonResults(anaData, 'Ana', 'success');
    document.getElementById('bogdan-results').innerHTML = generatePersonResults(bogdanData, 'Bogdan', 'warning');

    // Mesajul motivațional
    const motivationalDiv = document.getElementById('motivationalContent');
    motivationalDiv.innerHTML = `
        <div class="text-center mb-3">
            <h5 class="text-success">🎯 Lecția Principală</h5>
        </div>
        
        <p class="lead text-center">
            <strong>Începând cu doar 10 ani mai devreme, Ana câștigă ${formatCurrency(difference)} în plus!</strong>
        </p>
        
        <div class="row text-center">
            <div class="col-md-4">
                <div class="bg-light p-3 rounded">
                    <h6>Contribuții Extra</h6>
                    <strong class="text-primary">${formatCurrency(contributionDifference)}</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-light p-3 rounded">
                    <h6>Câștig Extra</h6>
                    <strong class="text-success">${formatCurrency(difference)}</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-light p-3 rounded">
                    <h6>ROI pe Diferență</h6>
                    <strong class="text-warning">${(((difference - contributionDifference) / contributionDifference) * 100).toFixed(0)}%</strong>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info mt-3 mb-0">
            <i class="fas fa-lightbulb me-2"></i>
            Pentru fiecare ${formatCurrency(contributionDifference / 100)} în plus pe care îi contribuie Ana, 
            ea câștigă ${formatCurrency(difference / 100)} la pensie!
        </div>
    `;
}

function generatePersonResults(data, name, variant) {
    const roi = ((data.totalInterest / data.totalContributed) * 100);
    
    return `
        <div class="detail-item">
            <strong>Perioada de Economisire:</strong>
            <span class="fw-bold">${data.years} ani</span>
        </div>
        
        <div class="detail-item">
            <strong>Total Contribuit:</strong>
            <span class="text-primary fw-bold">${formatCurrency(data.totalContributed)}</span>
        </div>
        
        <div class="detail-item">
            <strong>Dobânda Câștigată:</strong>
            <span class="text-success fw-bold">${formatCurrency(data.totalInterest)}</span>
        </div>
        
        <div class="detail-item">
            <strong>Valoarea Finală:</strong>
            <span class="fw-bold text-${variant}">${formatCurrency(data.finalAmount)}</span>
        </div>
        
        <div class="detail-item">
            <strong>ROI Total:</strong>
            <span class="fw-bold">${roi.toFixed(0)}%</span>
        </div>
        
        <div class="detail-item">
            <strong>Multiplicator:</strong>
            <span class="fw-bold">${(data.finalAmount / data.totalContributed).toFixed(1)}x</span>
        </div>
        
        ${variant === 'success' ? `
            <div class="alert alert-success mt-3 mb-0">
                <small><i class="fas fa-trophy me-1"></i>
                <strong>${name}</strong> a folosit puterea timpului în avantajul ei!</small>
            </div>
        ` : `
            <div class="alert alert-warning mt-3 mb-0">
                <small><i class="fas fa-clock me-1"></i>
                <strong>${name}</strong> a pierdut 10 ani prețioși de dobândă compusă!</small>
            </div>
        `}
    `;
}

function createComparisonChart(anaData, bogdanData, retirementAge) {
    const ctx = document.getElementById('comparisonChart').getContext('2d');
    
    if (comparisonChart) {
        comparisonChart.destroy();
    }
    
    // Creează setul de date pentru Ana (25 - retirementAge)
    const anaLabels = anaData.data.map(d => d.age);
    const anaValues = anaData.data.map(d => d.value);
    const anaContributions = anaData.data.map(d => d.contributed);
    
    // Creează setul de date pentru Bogdan (35 - retirementAge) + extinde cu 0 pentru anii 25-34
    const bogdanLabels = [];
    const bogdanValues = [];
    const bogdanContributions = [];
    
    // Adaugă anii 25-34 cu valori 0 pentru Bogdan
    for (let age = 25; age < 35; age++) {
        bogdanLabels.push(age);
        bogdanValues.push(0);
        bogdanContributions.push(0);
    }
    
    // Adaugă datele reale pentru Bogdan
    bogdanData.data.forEach(d => {
        bogdanLabels.push(d.age);
        bogdanValues.push(d.value);
        bogdanContributions.push(d.contributed);
    });
    
    comparisonChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: anaLabels,
            datasets: [
                {
                    label: 'Ana (Start la 25)',
                    data: anaValues,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 4,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 2,
                    pointHoverRadius: 6
                },
                {
                    label: 'Bogdan (Start la 35)',
                    data: bogdanValues,
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    borderWidth: 4,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 2,
                    pointHoverRadius: 6
                },
                {
                    label: 'Contribuții Ana',
                    data: anaContributions,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.05)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    pointRadius: 1
                },
                {
                    label: 'Contribuții Bogdan',
                    data: bogdanContributions,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.05)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    pointRadius: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Puterea Dramatică a Începutului Timpuriu',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            return `Vârsta: ${context[0].label} ani`;
                        },
                        label: function(context) {
                            return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Vârsta (ani)'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Valoarea Acumulată (RON)'
                    },
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
            },
            elements: {
                point: {
                    hoverBackgroundColor: '#fff',
                    hoverBorderWidth: 3
                }
            }
        }
    });
    
    // Afișează graficul
    document.getElementById('comparisonChart').style.display = 'block';
    document.getElementById('comparisonChartPlaceholder').style.display = 'none';
}

function createYearlyTable(anaData, bogdanData, retirementAge) {
    const tableDiv = document.getElementById('yearlyProgressTable');
    
    let tableHTML = `
        <div class="table-responsive">
            <table class="table table-comparison table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Vârsta</th>
                        <th>Ana - Contribuit</th>
                        <th>Ana - Valoare</th>
                        <th>Bogdan - Contribuit</th>
                        <th>Bogdan - Valoare</th>
                        <th>Diferența</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    // Generează rândurile pentru fiecare vârstă de la 25 la retirementAge
    for (let age = 25; age <= retirementAge; age++) {
        const anaIndex = age - 25;
        const bogdanIndex = age - 35;
        
        const anaContributed = anaIndex >= 0 && anaIndex < anaData.data.length ? anaData.data[anaIndex].contributed : 0;
        const anaValue = anaIndex >= 0 && anaIndex < anaData.data.length ? anaData.data[anaIndex].value : 0;
        
        const bogdanContributed = bogdanIndex >= 0 && bogdanIndex < bogdanData.data.length ? bogdanData.data[bogdanIndex].contributed : 0;
        const bogdanValue = bogdanIndex >= 0 && bogdanIndex < bogdanData.data.length ? bogdanData.data[bogdanIndex].value : 0;
        
        const difference = anaValue - bogdanValue;
        
        // Highlight doar la fiecare 5 ani pentru a nu fi prea aglomerat
        const shouldHighlight = age % 5 === 0 || age === retirementAge;
        const rowClass = shouldHighlight ? (anaValue > bogdanValue ? 'table-success' : 'table-warning') : '';
        
        tableHTML += `
            <tr class="${rowClass}">
                <td><strong>${age}</strong></td>
                <td>${formatCurrencyShort(anaContributed)}</td>
                <td><strong>${formatCurrencyShort(anaValue)}</strong></td>
                <td>${formatCurrencyShort(bogdanContributed)}</td>
                <td><strong>${formatCurrencyShort(bogdanValue)}</strong></td>
                <td class="${difference >= 0 ? 'text-success' : 'text-danger'} fw-bold">
                    ${difference >= 0 ? '+' : ''}${formatCurrencyShort(difference)}
                </td>
            </tr>
        `;
    }
    
    tableHTML += `
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            <div class="row">
                <div class="col-md-6">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Observații Cheie:</h6>
                        <ul class="mb-0">
                            <li>Ana începe să acumuleze de la 25 ani</li>
                            <li>Bogdan începe de la 35 ani (10 ani diferență)</li>
                            <li>Rândurile evidențiate sunt la fiecare 5 ani</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-chart-line me-2"></i>Puterea Dobânzii Compuse:</h6>
                        <ul class="mb-0">
                            <li>Diferența crește exponențial în timp</li>
                            <li>Ultimii 10 ani sunt cei mai puternici</li>
                            <li>Timpul este mai important decât suma</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    tableDiv.innerHTML = tableHTML;
    
    // Afișează tabelul
    document.getElementById('yearlyProgressTable').style.display = 'block';
    document.getElementById('tablePlaceholder').style.display = 'none';
}

function showActionPlan() {
    alert(`🚀 PLANUL TĂU DE ACȚIUNE:

1️⃣ ASTĂZI: Deschide un cont de economii
2️⃣ MÂINE: Configurează virament automat 
3️⃣ SĂPTĂMÂNA VIITOARE: Cercetează opțiuni de investiții
4️⃣ LUNA ACEASTA: Începe să investești regular

💡 Sfat: Începe cu orice sumă, chiar și 100 lei/lună!
⏰ Fiecare zi conta! Nu mai amâna!`);
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
        return (amount / 1000000).toFixed(1) + 'M';
    } else if (amount >= 1000) {
        return (amount / 1000).toFixed(0) + 'K';
    }
    return amount.toFixed(0);
}

// Funcție pentru colectarea datelor (pentru salvarea progresului)
function gatherCalculatorData() {
    return {
        monthlyAmount: document.getElementById('monthlyAmount').value,
        annualReturn: document.getElementById('annualReturn').value,
        retirementAge: document.getElementById('retirementAge').value,
        includeInflation: document.getElementById('includeInflation').checked,
        showRealValue: document.getElementById('showRealValue').checked,
        timestamp: new Date().toISOString()
    };
}

// Auto-calculare la schimbarea valorilor
document.addEventListener('DOMContentLoaded', function() {
    const inputs = ['monthlyAmount', 'annualReturn', 'retirementAge'];
    
    inputs.forEach(inputId => {
        const element = document.getElementById(inputId);
        if (element) {
            element.addEventListener('input', debounce(runComparison, 1000));
        }
    });
    
    // Event listeners pentru checkbox-uri
    document.getElementById('includeInflation').addEventListener('change', runComparison);
    document.getElementById('showRealValue').addEventListener('change', runComparison);
});

// Debounce function
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

<!-- Încarcă Chart.js pentru grafice -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>