<div class="calculator-container">
    <h4 class="mb-4">
        <i class="fas fa-user-clock me-2"></i>
        Calculator Necesar Pensie Personalizat
    </h4>
    
    <div class="row">
        <!-- Date Personale -->
        <div class="col-lg-6">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-user me-2"></i>
                        Profilul Tău Personal
                    </h6>
                    
                    <form id="retirementForm">
                        <!-- Vârsta Actuală -->
                        <div class="mb-3">
                            <label for="currentAge" class="form-label">Vârsta Actuală</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="currentAge" value="30" min="18" max="65">
                                <span class="input-group-text">ani</span>
                            </div>
                            <small class="form-text text-muted">Câți ani ai acum</small>
                        </div>

                        <!-- Vârsta de Pensionare -->
                        <div class="mb-3">
                            <label for="retirementAge" class="form-label">Vârsta Dorită de Pensionare</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="retirementAge" value="65" min="50" max="75">
                                <span class="input-group-text">ani</span>
                            </div>
                            <small class="form-text text-muted">Când vrei să te pensionezi</small>
                        </div>

                        <!-- Venitul Lunar Actual -->
                        <div class="mb-3">
                            <label for="currentIncome" class="form-label">Venitul Lunar Actual (Net)</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="form-control" id="currentIncome" value="5000" min="1000" step="100">
                            </div>
                            <small class="form-text text-muted">Salariul tău net lunar actual</small>
                        </div>

                        <!-- Stilul de Viață Dorit -->
                        <div class="mb-3">
                            <label for="lifestyleTarget" class="form-label">Stilul de Viață Dorit la Pensie</label>
                            <select class="form-select" id="lifestyleTarget" onchange="updateLifestylePercentage()">
                                <option value="60">Modest (60% din venitul actual)</option>
                                <option value="70">Confortabil (70% din venitul actual)</option>
                                <option value="80" selected>Foarte Bun (80% din venitul actual)</option>
                                <option value="90">Luxos (90% din venitul actual)</option>
                                <option value="100">Identic cu acum (100%)</option>
                                <option value="custom">Personalizat</option>
                            </select>
                        </div>

                        <!-- Procent Personalizat -->
                        <div class="mb-3" id="customPercentageDiv" style="display: none;">
                            <label for="customPercentage" class="form-label">Procent Personalizat (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="customPercentage" value="80" min="40" max="120">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <!-- Economii Existente -->
                        <div class="mb-3">
                            <label for="currentSavings" class="form-label">Economii Existente</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="form-control" id="currentSavings" value="50000" min="0" step="1000">
                            </div>
                            <small class="form-text text-muted">Ce economii ai deja (toate sursele)</small>
                        </div>

                        <!-- Inflația Estimată -->
                        <div class="mb-3">
                            <label for="inflationRate" class="form-label">Inflația Anuală Estimată (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="inflationRate" value="4" min="1" max="10" step="0.1">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="form-text text-muted">Inflația medie pe termen lung</small>
                        </div>

                        <!-- Randamentul Investițiilor -->
                        <div class="mb-3">
                            <label for="investmentReturn" class="form-label">Randamentul Anual al Investițiilor (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="investmentReturn" value="7" min="3" max="15" step="0.1">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="form-text text-muted">Randamentul real estimat (după inflație: ~3%)</small>
                        </div>

                        <button type="button" class="btn btn-primary w-100" onclick="calculateRetirement()">
                            <i class="fas fa-calculator me-2"></i>
                            Calculează Necesarul pentru Pensie
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
                        <i class="fas fa-piggy-bank me-2"></i>
                        Planul Tău de Pensionare
                    </h6>
                </div>
                <div class="card-body">
                    <div id="retirementResults" class="text-center">
                        <i class="fas fa-user-clock fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Completează datele și apasă "Calculează" pentru a-ți vedea planul de pensionare personalizat.</p>
                    </div>
                </div>
            </div>

            <!-- Sursele de Pensie -->
            <div class="card mt-3 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-layer-group me-2"></i>
                        Sursele Tale de Pensie
                    </h6>
                </div>
                <div class="card-body">
                    <div id="pensionSources">
                        <p class="text-muted text-center">Detaliile vor fi afișate după calculare</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafic Acumulare -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-area me-2"></i>
                        Acumularea pentru Pensie în Timp
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="retirementChart" style="display: none;"></canvas>
                    <div id="retirementChartPlaceholder" class="text-center py-5">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Graficul de acumulare va fi afișat după calculare</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scenarii Alternative -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Scenarii Alternative de Economisire
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="scenariosChart" style="display: none;"></canvas>
                    <div id="scenariosPlaceholder" class="text-center py-5">
                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Comparația scenariilor va fi afișată după calculare</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sfaturi Pensionare -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Strategii de Succes
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Începe cât mai devreme:</strong> Timpul este cel mai puternic aliat
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Automatizează:</strong> Virament automat lunar către investiții
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Crește contribuțiile:</strong> La fiecare mărire de salariu
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Diversifică:</strong> Nu pune toate ouăle într-un coș
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Revizuiește anual:</strong> Ajustează planul după nevoie
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
                        Riscuri de Evitat
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-times text-danger me-2"></i>
                            <strong>Amânarea:</strong> "O să încep anul viitor"
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-times text-danger me-2"></i>
                            <strong>Subestimarea inflației:</strong> Puterea de cumpărare scade
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-times text-danger me-2"></i>
                            <strong>Dependența doar de pensia de stat:</strong> Nu va fi suficientă
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-times text-danger me-2"></i>
                            <strong>Retragerea timpurie:</strong> Din economiile de pensie
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-times text-danger me-2"></i>
                            <strong>Lipsa planului:</strong> Fără obiective clare
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

.result-metric {
    text-align: center;
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 1rem;
}

.metric-target {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
}

.metric-monthly {
    background: linear-gradient(135deg, #007bff, #6610f2);
    color: white;
}

.metric-total {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    color: #212529;
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

.pension-source {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
}

.pension-source:last-child {
    border-bottom: none;
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
let retirementChart = null;
let scenariosChart = null;

function updateLifestylePercentage() {
    const target = document.getElementById('lifestyleTarget').value;
    const customDiv = document.getElementById('customPercentageDiv');
    
    if (target === 'custom') {
        customDiv.style.display = 'block';
    } else {
        customDiv.style.display = 'none';
    }
}

function calculateRetirement() {
    const currentAge = parseInt(document.getElementById('currentAge').value) || 30;
    const retirementAge = parseInt(document.getElementById('retirementAge').value) || 65;
    const currentIncome = parseFloat(document.getElementById('currentIncome').value) || 5000;
    const lifestyleTarget = document.getElementById('lifestyleTarget').value;
    const customPercentage = parseFloat(document.getElementById('customPercentage').value) || 80;
    const currentSavings = parseFloat(document.getElementById('currentSavings').value) || 0;
    const inflationRate = parseFloat(document.getElementById('inflationRate').value) || 4;
    const investmentReturn = parseFloat(document.getElementById('investmentReturn').value) || 7;

    // Validări
    if (currentAge >= retirementAge) {
        alert('Vârsta de pensionare trebuie să fie mai mare decât vârsta actuală!');
        return;
    }

    // Calculează procentajul țintă
    const targetPercentage = lifestyleTarget === 'custom' ? customPercentage : parseFloat(lifestyleTarget);
    
    // Calculează anii până la pensionare
    const yearsToRetirement = retirementAge - currentAge;
    
    // Calculează venitul necesar la pensie (ajustat pentru inflație)
    const inflationAdjustment = Math.pow(1 + inflationRate / 100, yearsToRetirement);
    const futureIncome = currentIncome * (targetPercentage / 100) * inflationAdjustment;
    
    // Calculează necesarul total (folosind regula 4% - poți retrage 4% anual)
    const totalNeeded = futureIncome * 12 / 0.04; // sau * 25
    
    // Calculează valoarea viitoare a economiilor existente
    const futureValueOfCurrentSavings = currentSavings * Math.pow(1 + investmentReturn / 100, yearsToRetirement);
    
    // Calculează necesarul de economisit
    const remainingNeeded = Math.max(0, totalNeeded - futureValueOfCurrentSavings);
    
    // Calculează rata lunară necesară (anuitate)
    const monthlyRate = investmentReturn / 100 / 12;
    const totalMonths = yearsToRetirement * 12;
    
    let monthlyNeeded = 0;
    if (remainingNeeded > 0 && monthlyRate > 0) {
        monthlyNeeded = remainingNeeded * monthlyRate / (Math.pow(1 + monthlyRate, totalMonths) - 1);
    }
    
    // Calculează procentajul din venitul actual
    const percentageOfIncome = (monthlyNeeded / currentIncome) * 100;
    
    // Estimează sursele de pensie românești
    const pensionSources = calculateRomanianPensionSources(currentIncome, retirementAge, currentAge);
    
    // Afișează rezultatele
    displayRetirementResults(
        totalNeeded, monthlyNeeded, percentageOfIncome, futureIncome, 
        yearsToRetirement, pensionSources, currentSavings, futureValueOfCurrentSavings
    );
    
    // Creează graficele
    createRetirementChart(currentSavings, monthlyNeeded, investmentReturn, yearsToRetirement);
    createScenariosChart(currentSavings, remainingNeeded, investmentReturn, yearsToRetirement);
}

function calculateRomanianPensionSources(currentIncome, retirementAge, currentAge) {
    // Estimări pentru sistemul românesc
    const workingYears = retirementAge - currentAge;
    const totalWorkingYears = retirementAge - 25; // Presupunem că a început să lucreze la 25
    
    // Pensia de stat (Pilonul I) - foarte conservativă
    const statePension = Math.min(currentIncome * 0.3, 2000); // Max ~2000 RON
    
    // Pilonul II (pensie privată obligatorie) - 3.75% din salariu brut
    const grossIncome = currentIncome / 0.76; // Aproximativ brut din net
    const pillar2Contribution = grossIncome * 0.0375;
    const pillar2Value = pillar2Contribution * 12 * workingYears * Math.pow(1.05, workingYears / 2);
    const pillar2MonthlyPension = pillar2Value / (20 * 12); // Presupunem 20 ani de pensie
    
    // Pilonul III (pensie privată facultativă) - estimare conservativă
    const pillar3MonthlyPension = currentIncome * 0.1; // Dacă contribuie regulat
    
    return {
        statePension: statePension,
        pillar2Monthly: pillar2MonthlyPension,
        pillar3Monthly: pillar3MonthlyPension,
        totalFromPensions: statePension + pillar2MonthlyPension + pillar3MonthlyPension
    };
}

function displayRetirementResults(totalNeeded, monthlyNeeded, percentageOfIncome, futureIncome, yearsToRetirement, pensionSources, currentSavings, futureValueOfCurrentSavings) {
    const resultsDiv = document.getElementById('retirementResults');
    
    // Determină nivelul de dificultate
    let difficulty, difficultyClass, advice;
    if (percentageOfIncome <= 15) {
        difficulty = 'Ușor de Atins';
        difficultyClass = 'text-success';
        advice = 'Planul tău este foarte realizabil!';
    } else if (percentageOfIncome <= 25) {
        difficulty = 'Moderat';
        difficultyClass = 'text-warning';
        advice = 'Cu disciplină, poți atinge obiectivul!';
    } else {
        difficulty = 'Provocator';
        difficultyClass = 'text-danger';
        advice = 'Consideră să ajustezi expectațiile sau să începi mai devreme.';
    }
    
    resultsDiv.innerHTML = `
        <div class="result-metric metric-target">
            <div class="result-value">${formatCurrency(totalNeeded)}</div>
            <div class="result-label">Necesarul Total la Pensie</div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="result-metric metric-monthly">
                    <div class="result-value">${formatCurrency(monthlyNeeded)}</div>
                    <div class="result-label">Economisire Lunară Necesară</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="result-metric metric-total">
                    <div class="result-value">${percentageOfIncome.toFixed(1)}%</div>
                    <div class="result-label">Din Venitul Actual</div>
                </div>
            </div>
        </div>
        
        <div class="result-item">
            <strong>Venit Necesar la Pensie:</strong>
            <span class="fw-bold">${formatCurrency(futureIncome)}/lună</span>
        </div>
        
        <div class="result-item">
            <strong>Ani până la Pensie:</strong>
            <span class="fw-bold">${yearsToRetirement} ani</span>
        </div>
        
        <div class="result-item">
            <strong>Economii Actuale (viitoare):</strong>
            <span class="fw-bold">${formatCurrency(futureValueOfCurrentSavings)}</span>
        </div>
        
        <div class="result-item">
            <strong>Dificultate Plan:</strong>
            <span class="${difficultyClass} fw-bold">${difficulty}</span>
        </div>
        
        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>${advice}</strong>
        </div>
        
        ${percentageOfIncome > 20 ? `
            <div class="alert alert-warning mt-2">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Sfat:</strong> ${percentageOfIncome.toFixed(1)}% din venit este mult. 
                Consideră să începi cu mai puțin și să crești gradual contribuțiile.
            </div>
        ` : ''}
    `;

    // Afișează sursele de pensie
    updatePensionSources(pensionSources, futureIncome);
}

function updatePensionSources(sources, targetIncome) {
    const sourcesDiv = document.getElementById('pensionSources');
    
    const gap = Math.max(0, targetIncome - sources.totalFromPensions);
    const gapPercentage = (gap / targetIncome) * 100;
    
    sourcesDiv.innerHTML = `
        <div class="pension-source">
            <strong>Pensia de Stat (Pilonul I):</strong>
            <span class="fw-bold">${formatCurrency(sources.statePension)}</span>
        </div>
        
        <div class="pension-source">
            <strong>Pilonul II (Pensie Privată):</strong>
            <span class="fw-bold">${formatCurrency(sources.pillar2Monthly)}</span>
        </div>
        
        <div class="pension-source">
            <strong>Pilonul III (Facultativ):</strong>
            <span class="fw-bold">${formatCurrency(sources.pillar3Monthly)}</span>
        </div>
        
        <div class="pension-source border-top pt-2">
            <strong>Total din Pensii:</strong>
            <span class="text-primary fw-bold">${formatCurrency(sources.totalFromPensions)}</span>
        </div>
        
        <div class="pension-source">
            <strong>Deficit de Acoperit:</strong>
            <span class="text-danger fw-bold">${formatCurrency(gap)}</span>
        </div>
        
        <div class="alert alert-${gapPercentage > 50 ? 'warning' : 'info'} mt-3 mb-0">
            <small>
                <i class="fas fa-info-circle me-1"></i>
                Pensiile românești vor acoperi aproximativ ${(100 - gapPercentage).toFixed(0)}% 
                din venitul țintă. Restul trebuie acoperit din economii private.
            </small>
        </div>
    `;
}

function createRetirementChart(currentSavings, monthlyContribution, annualReturn, years) {
    const ctx = document.getElementById('retirementChart').getContext('2d');
    
    if (retirementChart) {
        retirementChart.destroy();
    }
    
    // Generează datele pentru grafic
    const data = [];
    let totalValue = currentSavings;
    let totalContributions = currentSavings;
    
    for (let year = 0; year <= years; year++) {
        if (year === 0) {
            data.push({
                year: year,
                value: currentSavings,
                contributions: currentSavings,
                growth: 0
            });
        } else {
            // Adaugă contribuțiile anuale
            const yearlyContribution = monthlyContribution * 12;
            totalContributions += yearlyContribution;
            totalValue += yearlyContribution;
            
            // Aplică randamentul
            totalValue *= (1 + annualReturn / 100);
            
            data.push({
                year: year,
                value: totalValue,
                contributions: totalContributions,
                growth: totalValue - totalContributions
            });
        }
    }
    
    const labels = data.map(d => `An ${d.year}`);
    const values = data.map(d => d.value);
    const contributions = data.map(d => d.contributions);
    const growth = data.map(d => d.growth);
    
    retirementChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Valoarea Totală',
                    data: values,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Contribuții Totale',
                    data: contributions,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false
                },
                {
                    label: 'Creșterea din Investiții',
                    data: growth,
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
                    text: 'Acumularea Economiilor pentru Pensie'
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
    document.getElementById('retirementChart').style.display = 'block';
    document.getElementById('retirementChartPlaceholder').style.display = 'none';
}

function createScenariosChart(currentSavings, remainingNeeded, baseReturn, years) {
    const scenarios = [
        { name: 'Conservator (5%)', return: 5, contribution: 0 },
        { name: 'Moderat (7%)', return: 7, contribution: 0 },
        { name: 'Agresiv (10%)', return: 10, contribution: 0 }
    ];
    
    // Calculează contribuția necesară pentru fiecare scenariu
    scenarios.forEach(scenario => {
        const monthlyRate = scenario.return / 100 / 12;
        const totalMonths = years * 12;
        
        if (remainingNeeded > 0 && monthlyRate > 0) {
            scenario.contribution = remainingNeeded * monthlyRate / (Math.pow(1 + monthlyRate, totalMonths) - 1);
        }
    });
    
    const ctx = document.getElementById('scenariosChart').getContext('2d');
    
    if (scenariosChart) {
        scenariosChart.destroy();
    }
    
    scenariosChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: scenarios.map(s => s.name),
            datasets: [{
                label: 'Contribuție Lunară Necesară (RON)',
                data: scenarios.map(s => s.contribution),
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)',   // Verde
                    'rgba(0, 123, 255, 0.7)',   // Albastru
                    'rgba(255, 193, 7, 0.7)'    // Galben
                ],
                borderColor: [
                    '#28a745',
                    '#007bff', 
                    '#ffc107'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Contribuția Necesară în Diferite Scenarii de Randament'
                },
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Contribuție necesară: ' + formatCurrency(context.parsed.y);
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
    document.getElementById('scenariosChart').style.display = 'block';
    document.getElementById('scenariosPlaceholder').style.display = 'none';
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
        currentAge: document.getElementById('currentAge').value,
        retirementAge: document.getElementById('retirementAge').value,
        currentIncome: document.getElementById('currentIncome').value,
        lifestyleTarget: document.getElementById('lifestyleTarget').value,
        customPercentage: document.getElementById('customPercentage').value,
        currentSavings: document.getElementById('currentSavings').value,
        inflationRate: document.getElementById('inflationRate').value,
        investmentReturn: document.getElementById('investmentReturn').value,
        timestamp: new Date().toISOString()
    };
}

// Auto-calculare când se schimbă valorile
document.addEventListener('DOMContentLoaded', function() {
    const inputs = ['currentAge', 'retirementAge', 'currentIncome', 'lifestyleTarget', 'currentSavings', 'inflationRate', 'investmentReturn'];
    
    inputs.forEach(inputId => {
        const element = document.getElementById(inputId);
        if (element) {
            element.addEventListener('input', debounce(calculateRetirement, 1000));
        }
    });
    
    // Calculare inițială
    setTimeout(calculateRetirement, 500);
});

// Debounce function pentru a nu calcula prea des
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