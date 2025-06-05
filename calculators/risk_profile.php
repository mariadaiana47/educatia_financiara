<div class="calculator-container">
    <h4 class="mb-4">
        <i class="fas fa-balance-scale me-2"></i>
        Test Profil de Risc Investițional
    </h4>
    
    <div class="row">
        <!-- Test Section -->
        <div class="col-lg-8">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-question-circle me-2"></i>
                        Răspunde la întrebări pentru a-ți descoperi profilul
                    </h6>
                    
                    <form id="riskTestForm">
                        <!-- Question 1 -->
                        <div class="question-card mb-4">
                            <h6>1. Ce vârstă ai?</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="age" value="3" id="age1">
                                <label class="form-check-label" for="age1">Sub 30 de ani</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="age" value="2" id="age2">
                                <label class="form-check-label" for="age2">30-50 de ani</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="age" value="1" id="age3">
                                <label class="form-check-label" for="age3">Peste 50 de ani</label>
                            </div>
                        </div>

                        <!-- Question 2 -->
                        <div class="question-card mb-4">
                            <h6>2. Care este obiectivul tău de investiții?</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="objective" value="1" id="obj1">
                                <label class="form-check-label" for="obj1">Să îmi păstrez banii în siguranță</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="objective" value="2" id="obj2">
                                <label class="form-check-label" for="obj2">Să câștig mai mult decât inflația</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="objective" value="3" id="obj3">
                                <label class="form-check-label" for="obj3">Să maximizez câștigurile pe termen lung</label>
                            </div>
                        </div>

                        <!-- Question 3 -->
                        <div class="question-card mb-4">
                            <h6>3. Cât timp plănuiești să investești banii?</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="timeframe" value="1" id="time1">
                                <label class="form-check-label" for="time1">Sub 2 ani</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="timeframe" value="2" id="time2">
                                <label class="form-check-label" for="time2">2-10 ani</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="timeframe" value="3" id="time3">
                                <label class="form-check-label" for="time3">Peste 10 ani</label>
                            </div>
                        </div>

                        <!-- Question 4 -->
                        <div class="question-card mb-4">
                            <h6>4. Cum ai reacționa dacă investiția ta ar scădea cu 20% într-o lună?</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="reaction" value="1" id="react1">
                                <label class="form-check-label" for="react1">Aș vinde imediat din frică</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="reaction" value="2" id="react2">
                                <label class="form-check-label" for="react2">Aș aștepta să se recupereze</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="reaction" value="3" id="react3">
                                <label class="form-check-label" for="react3">Aș cumpăra mai mult (e oportunitate!)</label>
                            </div>
                        </div>

                        <!-- Question 5 -->
                        <div class="question-card mb-4">
                            <h6>5. Ce procent din venit poți risca în investiții?</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="income_percent" value="1" id="income1">
                                <label class="form-check-label" for="income1">Sub 5%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="income_percent" value="2" id="income2">
                                <label class="form-check-label" for="income2">5-15%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="income_percent" value="3" id="income3">
                                <label class="form-check-label" for="income3">Peste 15%</label>
                            </div>
                        </div>

                        <!-- Question 6 -->
                        <div class="question-card mb-4">
                            <h6>6. Care afirmație te descrie cel mai bine?</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mindset" value="1" id="mind1">
                                <label class="form-check-label" for="mind1">Prefer siguranța, chiar dacă câștig puțin</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mindset" value="2" id="mind2">
                                <label class="form-check-label" for="mind2">Accept riscuri moderate pentru câștiguri decente</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mindset" value="3" id="mind3">
                                <label class="form-check-label" for="mind3">Îmi place să risc pentru câștiguri mari</label>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100" onclick="calculateRiskProfile()">
                            <i class="fas fa-calculate me-2"></i>Descoperă-mi Profilul de Risc
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="col-lg-4">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i>
                        Profilul Tău de Risc
                    </h6>
                </div>
                <div class="card-body">
                    <div id="riskResults" class="text-center">
                        <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Completează testul pentru a-ți descoperi profilul de risc.</p>
                    </div>
                </div>
            </div>

            <!-- Tips Card -->
            <div class="card mt-3" id="tipsCard" style="display: none;">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Recomandări pentru Tine
                    </h6>
                </div>
                <div class="card-body">
                    <div id="investmentTips"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Portfolio Allocation Chart -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card" id="allocationCard" style="display: none;">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Alocarea Recomandată a Portofoliului
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="allocationChart"></canvas>
                        </div>
                        <div class="col-md-6">
                            <div id="allocationDetails"></div>
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

.question-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
}

.risk-profile {
    text-align: center;
    padding: 2rem;
    border-radius: 15px;
    color: white;
    margin-bottom: 1rem;
}

.conservative {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.moderate {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
}

.aggressive {
    background: linear-gradient(135deg, #dc3545, #fd7e14);
}

.profile-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.profile-description {
    font-size: 1rem;
    opacity: 0.9;
}

.allocation-item {
    background: #f8f9fa;
    border-left: 4px solid #007bff;
    padding: 1rem;
    margin: 0.5rem 0;
    border-radius: 0 8px 8px 0;
}

.allocation-stocks {
    border-left-color: #dc3545;
}

.allocation-bonds {
    border-left-color: #28a745;
}

.allocation-cash {
    border-left-color: #ffc107;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let allocationChart = null;

function calculateRiskProfile() {
    const formData = new FormData(document.getElementById('riskTestForm'));
    let totalScore = 0;
    let questionsAnswered = 0;

    // Calculate score
    for (let [key, value] of formData.entries()) {
        totalScore += parseInt(value);
        questionsAnswered++;
    }

    if (questionsAnswered < 6) {
        alert('Te rugăm să răspunzi la toate întrebările!');
        return;
    }

    const averageScore = totalScore / questionsAnswered;
    let profile, allocation;

    // Determine risk profile
    if (averageScore <= 1.5) {
        profile = 'conservative';
        allocation = { stocks: 20, bonds: 60, cash: 20 };
    } else if (averageScore <= 2.5) {
        profile = 'moderate';
        allocation = { stocks: 50, bonds: 40, cash: 10 };
    } else {
        profile = 'aggressive';
        allocation = { stocks: 80, bonds: 15, cash: 5 };
    }

    displayRiskProfile(profile, allocation, totalScore);
    createAllocationChart(allocation);
    showInvestmentTips(profile);
}

function displayRiskProfile(profile, allocation, score) {
    const resultsDiv = document.getElementById('riskResults');
    const allocationCard = document.getElementById('allocationCard');
    
    const profiles = {
        conservative: {
            title: 'Investitor Conservator',
            description: 'Preferi siguranța și stabilitatea. Ești dispus să accepți randamente mai mici pentru a evita riscurile mari.',
            icon: 'shield-alt'
        },
        moderate: {
            title: 'Investitor Moderat',
            description: 'Accepți riscuri moderate pentru randamente decente. Ai o abordare echilibrată față de investiții.',
            icon: 'balance-scale'
        },
        aggressive: {
            title: 'Investitor Agresiv',
            description: 'Ești dispus să accepți riscuri mari pentru randamente mari. Ai toleranță mare la volatilitate.',
            icon: 'rocket'
        }
    };

    const profileData = profiles[profile];

    resultsDiv.innerHTML = `
        <div class="risk-profile ${profile}">
            <i class="fas fa-${profileData.icon} fa-3x mb-3"></i>
            <div class="profile-title">${profileData.title}</div>
            <div class="profile-description">${profileData.description}</div>
            <div class="mt-3">
                <small>Scor total: ${score}/18 puncte</small>
            </div>
        </div>
    `;

    allocationCard.style.display = 'block';
}

function createAllocationChart(allocation) {
    const ctx = document.getElementById('allocationChart').getContext('2d');
    
    if (allocationChart) {
        allocationChart.destroy();
    }
    
    allocationChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Acțiuni', 'Obligațiuni', 'Numerar/Depozite'],
            datasets: [{
                data: [allocation.stocks, allocation.bonds, allocation.cash],
                backgroundColor: ['#dc3545', '#28a745', '#ffc107'],
                borderWidth: 3,
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
                            return context.label + ': ' + context.parsed + '%';
                        }
                    }
                }
            }
        }
    });

    // Display allocation details
    const detailsDiv = document.getElementById('allocationDetails');
    detailsDiv.innerHTML = `
        <h6 class="mb-3">Alocarea Recomandată:</h6>
        
        <div class="allocation-item allocation-stocks">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Acțiuni</strong>
                    <br><small class="text-muted">Companii, ETF-uri pe acțiuni</small>
                </div>
                <div class="text-end">
                    <span class="h5 text-danger">${allocation.stocks}%</span>
                </div>
            </div>
        </div>

        <div class="allocation-item allocation-bonds">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Obligațiuni</strong>
                    <br><small class="text-muted">Obligațiuni de stat, corporative</small>
                </div>
                <div class="text-end">
                    <span class="h5 text-success">${allocation.bonds}%</span>
                </div>
            </div>
        </div>

        <div class="allocation-item allocation-cash">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Numerar</strong>
                    <br><small class="text-muted">Depozite, conturi de economii</small>
                </div>
                <div class="text-end">
                    <span class="h5 text-warning">${allocation.cash}%</span>
                </div>
            </div>
        </div>
    `;
}

function showInvestmentTips(profile) {
    const tipsCard = document.getElementById('tipsCard');
    const tipsDiv = document.getElementById('investmentTips');

    const tips = {
        conservative: [
            'Începe cu fonduri mutuale echilibrate',
            'Consideră obligațiuni de stat românești',
            'Păstrează un fond de urgență solid',
            'Evită acțiunile individuale riscante',
            'Informează-te despre Pilonul III'
        ],
        moderate: [
            'Diversifică între acțiuni și obligațiuni',
            'Consideră ETF-uri pentru diversificare',
            'Investește regulat (Dollar Cost Averaging)',
            'Revizuiește portofoliul anual',
            'Învață despre analiza fundamentală'
        ],
        aggressive: [
            'Concentrează-te pe acțiuni de creștere',
            'Consideră piețe emergente',
            'Folosește strategii mai complexe',
            'Monitorizează zilnic investițiile',
            'Învață despre opțiuni și derivate'
        ]
    };

    let html = '<ul class="list-unstyled">';
    tips[profile].forEach(tip => {
        html += `
            <li class="mb-2">
                <i class="fas fa-check text-success me-2"></i>
                ${tip}
            </li>
        `;
    });
    html += '</ul>';

    tipsDiv.innerHTML = html;
    tipsCard.style.display = 'block';
}

function gatherCalculatorData() {
    const formData = new FormData(document.getElementById('riskTestForm'));
    const answers = {};
    
    for (let [key, value] of formData.entries()) {
        answers[key] = value;
    }
    
    return {
        answers: answers,
        timestamp: new Date().toISOString()
    };
}

// Auto-save answers
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input[type="radio"]');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            // Auto-calculate when all questions are answered
            const formData = new FormData(document.getElementById('riskTestForm'));
            let questionsAnswered = 0;
            for (let [key, value] of formData.entries()) {
                questionsAnswered++;
            }
            
            if (questionsAnswered >= 6) {
                setTimeout(calculateRiskProfile, 500);
            }
        });
    });
});
</script>