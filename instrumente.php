<?php
require_once 'config.php';

$page_title = 'Instrumente Financiare - ' . SITE_NAME;

include 'components/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="row mb-5">
        <div class="col-md-8 mx-auto text-center">
            <h1 class="h2 mb-3">
                <i class="fas fa-calculator me-2"></i>Instrumente Financiare Interactive
            </h1>
            <p class="lead text-muted">
                Calculatoare și instrumente gratuite pentru a-ți planifica viitorul financiar
            </p>
        </div>
    </div>

    <!-- Calculator Economii -->
    <section id="calculator-economii" class="mb-5">
        <div class="row">
            <div class="col-lg-6">
                <div class="card calculator-card">
                    <div class="card-header calculator-header">
                        <h4 class="mb-0">
                            <i class="fas fa-piggy-bank me-2"></i>Calculator Economii
                        </h4>
                        <p class="text-muted mb-0">Descoperă puterea dobânzii compuse</p>
                    </div>
                    <div class="card-body">
                        <form id="savingsCalculator">
                            <div class="mb-3">
                                <label for="savingsPrincipal" class="form-label">Suma inițială (RON)</label>
                                <input type="number" class="form-control" id="savingsPrincipal" 
                                       min="0" step="100">
                            </div>
                            
                            <div class="mb-3">
                                <label for="savingsMonthly" class="form-label">Depozit lunar (RON)</label>
                                <input type="number" class="form-control" id="savingsMonthly" 
                                        min="0" step="50">
                            </div>
                            
                            <div class="mb-3">
                                <label for="savingsRate" class="form-label">Rata anuală dobânzii (%)</label>
                                <input type="number" class="form-control" id="savingsRate" 
                                       min="0" max="20" step="0.1">
                            </div>
                            
                            <div class="mb-4">
                                <label for="savingsYears" class="form-label">Perioada (ani)</label>
                                <input type="number" class="form-control" id="savingsYears" 
                                       min="1" max="50">
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-calculate me-2"></i>Calculează
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div id="savingsResult" class="calculator-result" style="display: none;">
                    <!-- Rezultat va fi afișat aici prin JavaScript -->
                </div>
                <div class="chart-container">
                    <canvas id="savingsChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </section>

    <!-- Calculator Credite -->
    <section id="calculator-credite" class="mb-5">
        <div class="row">
            <div class="col-lg-6">
                <div class="card calculator-card">
                    <div class="card-header calculator-header">
                        <h4 class="mb-0">
                            <i class="fas fa-credit-card me-2"></i>Calculator Credite
                        </h4>
                        <p class="text-muted mb-0">Calculează ratele și costul total al creditului</p>
                    </div>
                    <div class="card-body">
                        <form id="loanCalculator">
                            <div class="mb-3">
                                <label for="loanAmount" class="form-label">Suma creditului (RON)</label>
                                <input type="number" class="form-control" id="loanAmount" 
                                       min="1000" step="1000">
                            </div>
                            
                            <div class="mb-3">
                                <label for="loanRate" class="form-label">Rata anuală dobânzii (%)</label>
                                <input type="number" class="form-control" id="loanRate" 
                                        min="1" max="30" step="0.1">
                            </div>
                            
                            <div class="mb-4">
                                <label for="loanYears" class="form-label">Perioada (ani)</label>
                                <input type="number" class="form-control" id="loanYears" 
                                        min="1" max="40">
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-calculate me-2"></i>Calculează
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div id="loanResult" class="calculator-result" style="display: none;">
                    <!-- Rezultat va fi afișat aici prin JavaScript -->
                </div>
                <div class="chart-container">
                    <canvas id="loanChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </section>

    <!-- Planificator Buget -->
    <section id="planificator-buget" class="mb-5">
        <div class="row">
            <div class="col-lg-6">
                <div class="card calculator-card">
                    <div class="card-header calculator-header">
                        <h4 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Planificator Buget 50/30/20
                        </h4>
                        <p class="text-muted mb-0">Organizează-ți bugetul după regula 50/30/20</p>
                    </div>
                    <div class="card-body">
                        <form id="budgetCalculator">
                            <div class="mb-3">
                                <label for="monthlyIncome" class="form-label">Venitul lunar net (RON)</label>
                                <input type="number" class="form-control" id="monthlyIncome" 
                                        min="500" step="100">
                            </div>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Regula 50/30/20</h6>
                                <ul class="mb-0 small">
                                    <li><strong>50% Necesități:</strong> Chirie, utilități, mâncare</li>
                                    <li><strong>30% Dorințe:</strong> Distracție, shopping, hobby-uri</li>
                                    <li><strong>20% Economii:</strong> Economii și investiții</li>
                                </ul>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-chart-pie me-2"></i>Calculează Bugetul
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div id="budgetResult" class="calculator-result" style="display: none;">
                    <!-- Rezultat va fi afișat aici prin JavaScript -->
                </div>
                <div class="chart-container">
                    <canvas id="budgetChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </section>

    <!-- Test Inteligență Financiară -->
<section id="test-inteligenta" >
    <div class="row justify-content-center p-5">
        <div class="col-lg-8">
            <div class="card calculator-card">
                <div class="card-header calculator-header text-center">
                    <h4 class="mb-2">
                        <i class="fas fa-brain me-2"></i>Test de Inteligență Financiară
                    </h4>
                    <p class="text-muted mb-0">Evaluează-ți cunoștințele financiare în 5 minute</p>
                </div>
                <div class="card-body text-center">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <i class="fas fa-question-circle fa-2x text-primary mb-2"></i>
                            <h6>15 Întrebări</h6>
                            <small class="text-muted">Chestionar comprehensiv</small>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-clock fa-2x text-success mb-2"></i>
                            <h6>5 Minute</h6>
                            <small class="text-muted">Timp estimat</small>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-certificate fa-2x text-warning mb-2"></i>
                            <h6>Rezultat Instant</h6>
                            <small class="text-muted">Cu recomandări personalizate</small>
                        </div>
                    </div>
                    
                    <a href="quiz-start.php?id=1" class="btn btn-lg btn-success">
                        <i class="fas fa-play me-2"></i>Începe Testul
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>


    <!-- Sfaturi și Resurse -->
    <section class="mb-5">
        <div class="row">
            <div class="col-12 mb-4">
                <h3 class="text-center">
                    <i class="fas fa-lightbulb me-2"></i>Sfaturi pentru Planificarea Financiară
                </h3>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="tip-card">
                    <div class="tip-icon">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <h5>Începe devreme să economisești</h5>
                    <p>Cu cât începi mai devreme să economisești, cu atât mai mult vei beneficia de puterea dobânzii compuse. Chiar și sume mici pot deveni semnificative în timp.</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="tip-card">
                    <div class="tip-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h5>Creează un fond de urgență</h5>
                    <p>Un fond de urgență care să acopere 3-6 luni de cheltuieli te va proteja în caz de situații neprevăzute și îți va oferi liniște sufletească.</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="tip-card">
                    <div class="tip-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5>Investește în educația ta</h5>
                    <p>Cea mai bună investiție este în tine însuți. Educația financiară îți va permite să iei decizii mai bune pe parcursul întregii vieți.</p>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Chart.js pentru grafice -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Cache buster - forțează încărcarea noului cod
console.log('Script version: 2024-06-09-v2');

// Variabile globale pentru grafice
let savingsChartInstance = null;
let loanChartInstance = null;
let budgetChartInstance = null;

// Calculator Economii
document.getElementById('savingsCalculator').addEventListener('submit', function(e) {
    e.preventDefault();
    calculateSavings();
});

function calculateSavings() {
    const principal = parseFloat(document.getElementById('savingsPrincipal').value) || 0;
    const monthlyDeposit = parseFloat(document.getElementById('savingsMonthly').value) || 0;
    const annualRate = parseFloat(document.getElementById('savingsRate').value) || 0;
    const years = parseFloat(document.getElementById('savingsYears').value) || 0;

    if (principal <= 0 && monthlyDeposit <= 0) {
        alert('Te rugăm să introduci suma inițială sau depozitul lunar.');
        return;
    }

    const monthlyRate = annualRate / 100 / 12;
    const months = years * 12;

    let futureValue = 0;

    // Calculează valoarea finală pentru suma inițială
    if (principal > 0) {
        futureValue += principal * Math.pow(1 + monthlyRate, months);
    }

    // Calculează valoarea finală pentru depozitele lunare
    if (monthlyDeposit > 0 && monthlyRate > 0) {
        futureValue += monthlyDeposit * (Math.pow(1 + monthlyRate, months) - 1) / monthlyRate;
    } else if (monthlyDeposit > 0) {
        futureValue += monthlyDeposit * months;
    }

    const totalDeposits = principal + (monthlyDeposit * months);
    const totalInterest = futureValue - totalDeposits;

    displaySavingsResult(futureValue, totalDeposits, totalInterest, years);
    createSavingsChart(principal, monthlyDeposit, annualRate, years);
}

function displaySavingsResult(futureValue, totalDeposits, totalInterest, years) {
    const resultContainer = document.getElementById('savingsResult');
    resultContainer.innerHTML = `
        <div class="calculator-result-content">
            <h5><i class="fas fa-chart-line me-2"></i>Rezultatul Calculului</h5>
            <div class="result-grid">
                <div class="result-item">
                    <span class="result-label">Valoarea finală:</span>
                    <span class="result-value primary">${formatPrice(futureValue)}</span>
                </div>
                <div class="result-item">
                    <span class="result-label">Total depus:</span>
                    <span class="result-value">${formatPrice(totalDeposits)}</span>
                </div>
                <div class="result-item">
                    <span class="result-label">Dobândă câștigată:</span>
                    <span class="result-value success">${formatPrice(totalInterest)}</span>
                </div>
                <div class="result-item">
                    <span class="result-label">Perioada:</span>
                    <span class="result-value">${years} ani</span>
                </div>
            </div>
            <div class="result-insight">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Insight:</strong> În ${years} ani, dobânda compusă îți va aduce ${formatPrice(totalInterest)} în plus!
            </div>
        </div>
    `;
    resultContainer.style.display = 'block';
}

// Calculator Credite
document.getElementById('loanCalculator').addEventListener('submit', function(e) {
    e.preventDefault();
    calculateLoan();
});

function calculateLoan() {
    const loanAmount = parseFloat(document.getElementById('loanAmount').value) || 0;
    const annualRate = parseFloat(document.getElementById('loanRate').value) || 0;
    const years = parseFloat(document.getElementById('loanYears').value) || 0;

    if (loanAmount <= 0 || annualRate <= 0 || years <= 0) {
        alert('Te rugăm să completezi toate câmpurile cu valori pozitive.');
        return;
    }

    const monthlyRate = annualRate / 100 / 12;
    const months = years * 12;

    const monthlyPayment = loanAmount * (monthlyRate * Math.pow(1 + monthlyRate, months)) /
        (Math.pow(1 + monthlyRate, months) - 1);

    const totalPayment = monthlyPayment * months;
    const totalInterest = totalPayment - loanAmount;

    displayLoanResult(monthlyPayment, totalPayment, totalInterest, loanAmount);
    createLoanChart(loanAmount, monthlyPayment, monthlyRate, months);
}

function displayLoanResult(monthlyPayment, totalPayment, totalInterest, loanAmount) {
    const resultContainer = document.getElementById('loanResult');
    resultContainer.innerHTML = `
        <div class="calculator-result-content">
            <h5><i class="fas fa-credit-card me-2"></i>Rezultatul Calculului</h5>
            <div class="result-grid">
                <div class="result-item">
                    <span class="result-label">Rata lunară:</span>
                    <span class="result-value primary">${formatPrice(monthlyPayment)}</span>
                </div>
                <div class="result-item">
                    <span class="result-label">Total de plată:</span>
                    <span class="result-value">${formatPrice(totalPayment)}</span>
                </div>
                <div class="result-item">
                    <span class="result-label">Total dobândă:</span>
                    <span class="result-value danger">${formatPrice(totalInterest)}</span>
                </div>
                <div class="result-item">
                    <span class="result-label">Suma împrumutată:</span>
                    <span class="result-value">${formatPrice(loanAmount)}</span>
                </div>
            </div>
            <div class="result-insight">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Atenție:</strong> Vei plăti ${formatPrice(totalInterest)} în plus față de suma împrumutată!
            </div>
        </div>
    `;
    resultContainer.style.display = 'block';
}

// Planificator Buget
document.getElementById('budgetCalculator').addEventListener('submit', function(e) {
    e.preventDefault();
    calculateBudget();
});

function calculateBudget() {
    const monthlyIncome = parseFloat(document.getElementById('monthlyIncome').value) || 0;

    if (monthlyIncome <= 0) {
        alert('Te rugăm să introduci venitul lunar.');
        return;
    }

    const needs = monthlyIncome * 0.5;
    const wants = monthlyIncome * 0.3;
    const savings = monthlyIncome * 0.2;

    displayBudgetResult(monthlyIncome, needs, wants, savings);
    createBudgetChart(needs, wants, savings);
}

function displayBudgetResult(monthlyIncome, needs, wants, savings) {
    const resultContainer = document.getElementById('budgetResult');
    resultContainer.innerHTML = `
        <div class="calculator-result-content">
            <h5><i class="fas fa-chart-pie me-2"></i>Planul tău de buget 50/30/20</h5>
            <div class="result-grid">
                <div class="result-item">
                    <span class="result-label">Necesități (50%):</span>
                    <span class="result-value primary">${formatPrice(needs)}</span>
                    <small class="result-description">Chirie, utilități, mâncare</small>
                </div>
                <div class="result-item">
                    <span class="result-label">Dorințe (30%):</span>
                    <span class="result-value warning">${formatPrice(wants)}</span>
                    <small class="result-description">Distracție, shopping, hobby</small>
                </div>
                <div class="result-item">
                    <span class="result-label">Economii (20%):</span>
                    <span class="result-value success">${formatPrice(savings)}</span>
                    <small class="result-description">Economii, investiții</small>
                </div>
                <div class="result-item">
                    <span class="result-label">Venit total:</span>
                    <span class="result-value">${formatPrice(monthlyIncome)}</span>
                </div>
            </div>
            <div class="result-insight">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Sfat:</strong> Această împărțire te va ajuta să ai o viață financiară echilibrată!
            </div>
        </div>
    `;
    resultContainer.style.display = 'block';
}

// Funcții pentru grafice
function createSavingsChart(principal, monthlyDeposit, annualRate, years) {
    const ctx = document.getElementById('savingsChart').getContext('2d');
    const months = years * 12;
    const monthlyRate = annualRate / 100 / 12;

    const labels = [];
    const principalData = [];
    const depositsData = [];
    const interestData = [];

    for (let year = 0; year <= years; year++) {
        const month = year * 12;
        labels.push(`Anul ${year}`);

        let principalValue = principal * Math.pow(1 + monthlyRate, month);
        let depositsValue = monthlyDeposit * month;
        let totalValue = principalValue;

        if (monthlyDeposit > 0 && monthlyRate > 0 && month > 0) {
            totalValue += monthlyDeposit * (Math.pow(1 + monthlyRate, month) - 1) / monthlyRate;
        } else {
            totalValue += depositsValue;
        }

        let interestValue = totalValue - principal - depositsValue;

        principalData.push(principal);
        depositsData.push(depositsValue);
        interestData.push(Math.max(0, interestValue));
    }

    if (savingsChartInstance) {
        savingsChartInstance.destroy();
    }

    savingsChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Suma inițială',
                data: principalData,
                backgroundColor: '#2c5aa0',
                stack: 'stack1'
            }, {
                label: 'Depozite lunare',
                data: depositsData,
                backgroundColor: '#f8c146',
                stack: 'stack1'
            }, {
                label: 'Dobândă câștigată',
                data: interestData,
                backgroundColor: '#28a745',
                stack: 'stack1'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Evoluția Economiilor în Timp'
                },
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatPrice(value);
                        }
                    }
                }
            }
        }
    });
}

function createLoanChart(loanAmount, monthlyPayment, monthlyRate, totalMonths) {
    console.log('=== DEBUGGING LOAN CHART ===');
    console.log('Creating loan chart with corrected labels...');
    
    const ctx = document.getElementById('loanChart').getContext('2d');

    const labels = [];
    const principalData = [];
    const interestData = [];
    let remainingBalance = loanAmount;

    for (let month = 1; month <= Math.min(12, totalMonths); month++) {
        const interestPayment = remainingBalance * monthlyRate;
        const principalPayment = monthlyPayment - interestPayment;
        remainingBalance -= principalPayment;

        labels.push(`Luna ${month}`);
        principalData.push(principalPayment);
        interestData.push(interestPayment);
    }

    if (loanChartInstance) {
        console.log('Destroying existing chart...');
        loanChartInstance.destroy();
    }

    // Debug: Verifică exact ce labels folosim
    const datasetLabels = ['Plată principală', 'Plată dobândă'];
    console.log('Dataset labels before chart creation:', datasetLabels);
    
    loanChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: datasetLabels[0], // Folosim variabila pentru debugging
                data: principalData,
                backgroundColor: '#2c5aa0'
            }, {
                label: datasetLabels[1], // Folosim variabila pentru debugging
                data: interestData,
                backgroundColor: '#dc3545'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Distribuția Plăților Lunare (Primul An)'
                },
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatPrice(value);
                        }
                    }
                }
            }
        }
    });
    
    console.log('Chart created successfully with labels:', datasetLabels);
    console.log('=== END DEBUGGING ===');
}

function createBudgetChart(needs, wants, savings) {
    const ctx = document.getElementById('budgetChart').getContext('2d');

    if (budgetChartInstance) {
        budgetChartInstance.destroy();
    }

    budgetChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Necesități (50%)', 'Dorințe (30%)', 'Economii (20%)'],
            datasets: [{
                data: [needs, wants, savings],
                backgroundColor: [
                    '#dc3545',
                    '#ffc107',
                    '#28a745'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Distribuția Bugetului 50/30/20'
                },
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + formatPrice(context.parsed);
                        }
                    }
                }
            }
        }
    });
}

// Funcție pentru formatarea prețurilor
function formatPrice(price) {
    return new Intl.NumberFormat('ro-RO', {
        style: 'currency',
        currency: 'RON'
    }).format(price);
}

// Smooth scroll pentru ancorele din pagină
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>

<?php include 'components/footer.php'; ?>