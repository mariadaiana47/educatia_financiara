<div class="calculator-container">
    <h4 class="mb-4">
        <i class="fas fa-search-dollar me-2"></i>
        Challenge: Găsește 200 lei să economisești
    </h4>
    
    <div class="row">
        <!-- Input Section -->
        <div class="col-lg-6">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-list me-2"></i>
                        Analizează-ți Cheltuielile
                    </h6>
                    
                    <form id="expenseForm">
                        <div class="mb-3">
                            <label for="monthlyIncome" class="form-label">Venit Lunar (RON)</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="form-control" id="monthlyIncome" value="3000" min="0" step="100">
                            </div>
                        </div>

                        <h6 class="mt-4 mb-3">Cheltuieli Lunare:</h6>

                        <div class="mb-3">
                            <label for="housing" class="form-label">Locuință (chirie/rată/întreținere)</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="form-control expense-input" id="housing" value="800" min="0" step="50">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="food" class="form-label">Mâncare & Băuturi</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="form-control expense-input" id="food" value="600" min="0" step="50">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="transport" class="form-label">Transport</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="expense-input form-control" id="transport" value="300" min="0" step="50">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="utilities" class="form-label">Utilități (curent, gaz, apă)</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="expense-input form-control" id="utilities" value="200" min="0" step="25">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="entertainment" class="form-label">Distracție & Ieșiri</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="expense-input form-control" id="entertainment" value="400" min="0" step="50">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="shopping" class="form-label">Cumpărături & Haine</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="expense-input form-control" id="shopping" value="300" min="0" step="50">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subscriptions" class="form-label">Abonamente (Netflix, Spotify, etc.)</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="expense-input form-control" id="subscriptions" value="150" min="0" step="25">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="other" class="form-label">Altele</label>
                            <div class="input-group">
                                <span class="input-group-text">RON</span>
                                <input type="number" class="expense-input form-control" id="other" value="200" min="0" step="50">
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100" onclick="analyzeExpenses()">
                            <i class="fas fa-search me-2"></i>Analizează & Găsește Economii
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="col-lg-6">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Oportunități de Economisire
                    </h6>
                </div>
                <div class="card-body">
                    <div id="savingsResults" class="text-center">
                        <i class="fas fa-search-dollar fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Completează cheltuielile pentru a găsi oportunități de economisire.</p>
                    </div>
                </div>
            </div>

            <!-- Challenge Progress -->
            <div class="card mt-3" id="challengeCard" style="display: none;">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-trophy me-2"></i>
                        Progresul Challenge-ului
                    </h6>
                </div>
                <div class="card-body">
                    <div id="challengeProgress"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expense Chart -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Distribuția Cheltuielilor
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="expenseChart" style="display: none;"></canvas>
                            <div id="chartPlaceholder" class="text-center py-5">
                                <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Graficul va fi afișat după analiză</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="expenseBreakdown"></div>
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

.savings-opportunity {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    color: white;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    text-align: center;
}

.opportunity-amount {
    font-size: 1.5rem;
    font-weight: 700;
}

.opportunity-description {
    font-size: 0.9rem;
    opacity: 0.9;
}

.challenge-progress {
    background: #e9ecef;
    border-radius: 10px;
    padding: 1rem;
    margin: 1rem 0;
}

.progress-bar-challenge {
    background: linear-gradient(90deg, #28a745, #20c997);
    height: 25px;
    border-radius: 10px;
    transition: width 0.5s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.expense-category {
    border-left: 4px solid #007bff;
    background: #f8f9fa;
    padding: 0.5rem;
    margin: 0.5rem 0;
    border-radius: 0 5px 5px 0;
}

.high-expense {
    border-left-color: #dc3545;
}

.medium-expense {
    border-left-color: #ffc107;
}

.low-expense {
    border-left-color: #28a745;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let expenseChart = null;

function analyzeExpenses() {
    const monthlyIncome = parseFloat(document.getElementById('monthlyIncome').value) || 0;
    const expenses = {
        housing: parseFloat(document.getElementById('housing').value) || 0,
        food: parseFloat(document.getElementById('food').value) || 0,
        transport: parseFloat(document.getElementById('transport').value) || 0,
        utilities: parseFloat(document.getElementById('utilities').value) || 0,
        entertainment: parseFloat(document.getElementById('entertainment').value) || 0,
        shopping: parseFloat(document.getElementById('shopping').value) || 0,
        subscriptions: parseFloat(document.getElementById('subscriptions').value) || 0,
        other: parseFloat(document.getElementById('other').value) || 0
    };

    const totalExpenses = Object.values(expenses).reduce((sum, val) => sum + val, 0);
    const remainingMoney = monthlyIncome - totalExpenses;

    if (monthlyIncome <= 0) {
        alert('Te rugăm să introduci venitul lunar!');
        return;
    }

    const opportunities = findSavingsOpportunities(expenses, monthlyIncome);
    displaySavingsOpportunities(opportunities, remainingMoney, totalExpenses, monthlyIncome);
    createExpenseChart(expenses);
    displayExpenseBreakdown(expenses, totalExpenses);
}

function findSavingsOpportunities(expenses, income) {
    const opportunities = [];
    const expenseCategories = {
        'entertainment': { name: 'Distracție & Ieșiri', suggestions: [
            'Ieși mai rar la restaurant (1-2 ori pe săptămână în loc de zilnic)',
            'Organizează seri de filme acasă în loc să mergi la cinema',
            'Caută evenimente gratuite în oraș',
            'Folosește aplicații de reduceri pentru restaurante'
        ]},
        'shopping': { name: 'Cumpărături & Haine', suggestions: [
            'Fă o listă înainte să te duci la cumpărături',
            'Așteaptă reducerile pentru hainele scumpe',
            'Cumpără second-hand pentru unele articole',
            'Evită cumpărăturile impulsive - gândește-te 24h înainte'
        ]},
        'subscriptions': { name: 'Abonamente', suggestions: [
            'Anulează abonamentele pe care nu le folosești lunar',
            'Împarte abonamentele cu familia/prietenii',
            'Folosește perioada de probă gratuită înainte să te abonezi',
            'Verifică dacă ai abonamente duplicate'
        ]},
        'food': { name: 'Mâncare & Băuturi', suggestions: [
            'Gătește mai des acasă în loc să comanzi',
            'Fă meniu săptămânal și cumpără doar ce îți trebuie',
            'Evită să cumperi când ești flămând',
            'Încearcă să reduci cafeaua/băuturile de la cafenele'
        ]},
        'transport': { name: 'Transport', suggestions: [
            'Folosește transportul în comun în loc de taxi/Uber',
            'Merge pe jos sau cu bicicleta pentru distanțe scurte',
            'Organizează car-sharing cu colegii',
            'Planifică rutele să eviți consumul extra de combustibil'
        ]},
        'other': { name: 'Alte Cheltuieli', suggestions: [
            'Analizează toate cheltuielile din ultima lună',
            'Elimină micile cumpărături zilnice care se adună',
            'Negociază prețurile la servicii (telefon, internet)',
            'Folosește aplicații de cashback'
        ]}
    };

    // Calculează procentul din venit pentru fiecare categorie
    const percentages = {};
    Object.keys(expenses).forEach(category => {
        percentages[category] = (expenses[category] / income) * 100;
    });

    // Găsește oportunități bazate pe procente mari
    Object.keys(expenses).forEach(category => {
        if (category === 'housing' || category === 'utilities') return; // Skip necessary expenses
        
        const amount = expenses[category];
        const percentage = percentages[category];
        
        if (percentage > 15 && amount > 200) {
            // High potential savings
            const potentialSaving = Math.min(amount * 0.3, 150);
            opportunities.push({
                category: category,
                name: expenseCategories[category]?.name || category,
                currentAmount: amount,
                potentialSaving: potentialSaving,
                suggestions: expenseCategories[category]?.suggestions || [],
                priority: 'high'
            });
        } else if (percentage > 8 && amount > 100) {
            // Medium potential savings
            const potentialSaving = Math.min(amount * 0.2, 100);
            opportunities.push({
                category: category,
                name: expenseCategories[category]?.name || category,
                currentAmount: amount,
                potentialSaving: potentialSaving,
                suggestions: expenseCategories[category]?.suggestions || [],
                priority: 'medium'
            });
        } else if (amount > 50) {
            // Small potential savings
            const potentialSaving = Math.min(amount * 0.15, 60);
            opportunities.push({
                category: category,
                name: expenseCategories[category]?.name || category,
                currentAmount: amount,
                potentialSaving: potentialSaving,
                suggestions: expenseCategories[category]?.suggestions || [],
                priority: 'low'
            });
        }
    });

    // Sortează după potențialul de economisire
    opportunities.sort((a, b) => b.potentialSaving - a.potentialSaving);
    
    return opportunities;
}

function displaySavingsOpportunities(opportunities, remaining, totalExpenses, income) {
    const resultsDiv = document.getElementById('savingsResults');
    const challengeCard = document.getElementById('challengeCard');
    const challengeProgress = document.getElementById('challengeProgress');
    
    const totalPotentialSavings = opportunities.reduce((sum, opp) => sum + opp.potentialSaving, 0);
    const challengeGoal = 200;
    const challengeProgressPercent = Math.min((totalPotentialSavings / challengeGoal) * 100, 100);
    
    let html = `
        <div class="text-center mb-3">
            <h5 class="text-${remaining >= 0 ? 'success' : 'danger'}">
                ${remaining >= 0 ? 'Ai economii de: ' : 'Îți lipsesc: '}
                ${formatCurrency(Math.abs(remaining))}
            </h5>
            <small class="text-muted">din venitul lunar de ${formatCurrency(income)}</small>
        </div>
    `;

    if (opportunities.length > 0) {
        html += `
            <div class="savings-opportunity">
                <div class="opportunity-amount">Poți economisi: ${formatCurrency(totalPotentialSavings)}</div>
                <div class="opportunity-description">prin optimizarea cheltuielilor</div>
            </div>
        `;

        opportunities.slice(0, 4).forEach(opp => {
            const priorityColor = opp.priority === 'high' ? 'danger' : opp.priority === 'medium' ? 'warning' : 'success';
            html += `
                <div class="card mb-2">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="text-${priorityColor}">${opp.name}</strong>
                                <br><small class="text-muted">Actual: ${formatCurrency(opp.currentAmount)}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-${priorityColor}">-${formatCurrency(opp.potentialSaving)}</span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small><strong>Sfaturi:</strong> ${opp.suggestions[0]}</small>
                        </div>
                    </div>
                </div>
            `;
        });
    } else {
        html += `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Cheltuielile tale par să fie deja optimizate! Încearcă să crești venitul sau să cauți economii mai mici.
            </div>
        `;
    }

    resultsDiv.innerHTML = html;

    // Show challenge progress
    challengeCard.style.display = 'block';
    challengeProgress.innerHTML = `
        <div class="challenge-progress">
            <div class="d-flex justify-content-between mb-2">
                <span><strong>Obiectiv Challenge:</strong> ${formatCurrency(challengeGoal)}</span>
                <span><strong>Găsit:</strong> ${formatCurrency(totalPotentialSavings)}</span>
            </div>
            <div class="progress-bar-challenge" style="width: ${challengeProgressPercent}%">
                ${challengeProgressPercent.toFixed(0)}%
            </div>
        </div>
        
        ${totalPotentialSavings >= challengeGoal ? `
            <div class="alert alert-success mt-3">
                <i class="fas fa-trophy me-2"></i>
                <strong>CHALLENGE COMPLETAT!</strong> Ai găsit ${formatCurrency(totalPotentialSavings)} de economisit!
            </div>
        ` : `
            <div class="alert alert-warning mt-3">
                <i class="fas fa-target me-2"></i>
                Îți mai lipsesc ${formatCurrency(challengeGoal - totalPotentialSavings)} pentru a completa challenge-ul.
                Încearcă să analizezi și alte categorii de cheltuieli.
            </div>
        `}
    `;
}

function createExpenseChart(expenses) {
    const ctx = document.getElementById('expenseChart').getContext('2d');
    
    if (expenseChart) {
        expenseChart.destroy();
    }
    
    const labels = ['Locuință', 'Mâncare', 'Transport', 'Utilități', 'Distracție', 'Cumpărături', 'Abonamente', 'Altele'];
    const data = [
        expenses.housing,
        expenses.food,
        expenses.transport,
        expenses.utilities,
        expenses.entertainment,
        expenses.shopping,
        expenses.subscriptions,
        expenses.other
    ];

    const colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
    ];
    
    expenseChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + formatCurrency(context.parsed) + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    
    document.getElementById('expenseChart').style.display = 'block';
    document.getElementById('chartPlaceholder').style.display = 'none';
}

function displayExpenseBreakdown(expenses, total) {
    const breakdownDiv = document.getElementById('expenseBreakdown');
    const categories = [
        { key: 'housing', name: 'Locuință', icon: 'home' },
        { key: 'food', name: 'Mâncare', icon: 'utensils' },
        { key: 'transport', name: 'Transport', icon: 'car' },
        { key: 'utilities', name: 'Utilități', icon: 'bolt' },
        { key: 'entertainment', name: 'Distracție', icon: 'film' },
        { key: 'shopping', name: 'Cumpărături', icon: 'shopping-bag' },
        { key: 'subscriptions', name: 'Abonamente', icon: 'credit-card' },
        { key: 'other', name: 'Altele', icon: 'ellipsis-h' }
    ];

    let html = '<h6 class="mb-3">Detalii Cheltuieli:</h6>';
    
    categories.forEach(cat => {
        const amount = expenses[cat.key];
        const percentage = total > 0 ? (amount / total * 100) : 0;
        const level = percentage > 25 ? 'high' : percentage > 15 ? 'medium' : 'low';
        
        html += `
            <div class="expense-category ${level}-expense">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-${cat.icon} me-2"></i>
                        <strong>${cat.name}</strong>
                    </div>
                    <div class="text-end">
                        <div>${formatCurrency(amount)}</div>
                        <small class="text-muted">${percentage.toFixed(1)}%</small>
                    </div>
                </div>
            </div>
        `;
    });

    html += `
        <div class="mt-3 p-2 bg-light rounded">
            <div class="d-flex justify-content-between">
                <strong>TOTAL CHELTUIELI:</strong>
                <strong class="text-primary">${formatCurrency(total)}</strong>
            </div>
        </div>
    `;

    breakdownDiv.innerHTML = html;
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('ro-RO', {
        style: 'currency',
        currency: 'RON',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

function gatherCalculatorData() {
    return {
        monthlyIncome: document.getElementById('monthlyIncome').value,
        housing: document.getElementById('housing').value,
        food: document.getElementById('food').value,
        transport: document.getElementById('transport').value,
        utilities: document.getElementById('utilities').value,
        entertainment: document.getElementById('entertainment').value,
        shopping: document.getElementById('shopping').value,
        subscriptions: document.getElementById('subscriptions').value,
        other: document.getElementById('other').value,
        timestamp: new Date().toISOString()
    };
}

// Auto-calculate când se schimbă valorile
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.expense-input, #monthlyIncome');
    
    inputs.forEach(input => {
        input.addEventListener('input', debounce(analyzeExpenses, 500));
    });
    
    analyzeExpenses();
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