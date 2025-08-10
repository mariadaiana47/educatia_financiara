/**
 * EDUCAȚIA FINANCIARĂ - SCRIPT.JS
 * JavaScript principal pentru funcționalitățile interactive
 */

let currentQuizData = null;
let currentQuestionIndex = 0;
let quizTimer = null;
let calculatorResults = {};


/**
 * Funcție pentru debug
 */
function log(message, data) {
    if (console && console.log) {
        console.log('[EduFinance]', message, data || '');
    }
}

/**
 * Formatează prețul în RON
 */
function formatPrice(price) {
    return new Intl.NumberFormat('ro-RO', {
        style: 'currency',
        currency: 'RON'
    }).format(price);
}

/**
 * Formatează numărul cu separatori
 */
function formatNumber(number, decimals) {
    decimals = decimals || 2;
    return new Intl.NumberFormat('ro-RO', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
}

/**
 * Validează email-ul
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validează parola (min 8 caractere, literă mare, mică, cifră)
 */
function validatePassword(password) {
    const re = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;
    return re.test(password);
}

/**
 * Afișează toast notification
 */
function showToast(message, type, duration) {
    type = type || 'info';
    duration = duration || 5000;

    const toastContainer = document.getElementById('toastContainer') || createToastContainer();

    const toastId = 'toast-' + Date.now();
    const iconMap = {
        success: 'fas fa-check-circle text-success',
        error: 'fas fa-exclamation-circle text-danger',
        warning: 'fas fa-exclamation-triangle text-warning',
        info: 'fas fa-info-circle text-info'
    };

    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center">
                    <i class="${iconMap[type]} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHTML);

    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: duration });
    toast.show();

    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

/**
 * Creează container-ul pentru toast-uri dacă nu există
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1055';
    document.body.appendChild(container);
    return container;
}


/**
 * Adaugă curs în coș
 */
async function addToCart(courseId, buttonElement) {
    try {
        showLoading();

        const response = await fetch('ajax/add-to-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                course_id: courseId,
                csrf_token: window.csrfToken || ''
            })
        });

        const data = await response.json();

        if (data.success) {
            updateCartBadge(data.cart_count);

            if (buttonElement) {
                updateAddToCartButton(buttonElement, 'added');
            }

            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }

    } catch (error) {
        log('Error adding to cart:', error);
        showToast('A apărut o eroare. Te rugăm să încerci din nou.', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * Elimină curs din coș
 */
async function removeFromCart(courseId, buttonElement) {
    if (!confirm('Ești sigur că vrei să elimini acest curs din coș?')) {
        return;
    }

    try {
        showLoading();

        const response = await fetch('ajax/remove-from-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                course_id: courseId,
                csrf_token: window.csrfToken || ''
            })
        });

        const data = await response.json();

        if (data.success) {
            updateCartBadge(data.cart_count);

            const cartRow = document.querySelector('[data-course-id="' + courseId + '"]');
            if (cartRow) {
                cartRow.style.transition = 'opacity 0.3s ease';
                cartRow.style.opacity = '0';
                setTimeout(function() {
                    cartRow.remove();
                    updateCartTotal();
                }, 300);
            }

            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }

    } catch (error) {
        log('Error removing from cart:', error);
        showToast('A apărut o eroare. Te rugăm să încerci din nou.', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * Actualizează badge-ul coșului
 */
function updateCartBadge(count) {
    const cartBadge = document.querySelector('.cart-badge');
    const cartLink = document.querySelector('a[href="cos.php"]');

    if (count > 0) {
        if (cartBadge) {
            cartBadge.textContent = count;
        } else if (cartLink) {
            cartLink.insertAdjacentHTML('beforeend', '<span class="cart-badge">' + count + '</span>');
        }
    } else {
        if (cartBadge) {
            cartBadge.remove();
        }
    }
}

/**
 * Actualizează butonul "Adaugă în coș"
 */
function updateAddToCartButton(button, state) {
    switch (state) {
        case 'added':
            button.innerHTML = '<i class="fas fa-check me-2"></i>Adăugat în Coș';
            button.classList.remove('btn-primary');
            button.classList.add('btn-success');
            button.disabled = true;
            break;
        case 'enrolled':
            button.innerHTML = '<i class="fas fa-graduation-cap me-2"></i>Înscris';
            button.classList.remove('btn-primary');
            button.classList.add('btn-info');
            button.disabled = true;
            break;
        case 'loading':
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Se adaugă...';
            button.disabled = true;
            break;
    }
}

/**
 * Actualizează totalul coșului
 */
function updateCartTotal() {
    const cartItems = document.querySelectorAll('.cart-item');
    let total = 0;

    cartItems.forEach(function(item) {
        const priceElement = item.querySelector('.item-price');
        if (priceElement) {
            const priceText = priceElement.textContent;
            const price = parseFloat(priceText.replace(/[^\d,]/g, '').replace(',', '.'));
            total += price;
        }
    });

    const totalElement = document.querySelector('.cart-total');
    if (totalElement) {
        totalElement.textContent = formatPrice(total);
    }
}


/**
 * Pornește un quiz
 */
async function startQuiz(quizId) {
    try {
        showLoading();

        const response = await fetch('ajax/start-quiz.php?quiz_id=' + quizId);
        const data = await response.json();

        if (data.success) {
            currentQuizData = data.quiz;
            currentQuestionIndex = 0;

            window.location.href = 'quiz-start.php?id=' + quizId;
        } else {
            showToast(data.message, 'error');
        }

    } catch (error) {
        log('Error starting quiz:', error);
        showToast('A apărut o eroare la pornirea quiz-ului.', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * Navighează la următoarea întrebare
 */
function nextQuestion() {
    const selectedOption = document.querySelector('.quiz-option.selected');

    if (!selectedOption) {
        showToast('Te rugăm să selectezi un răspuns.', 'warning');
        return;
    }

    saveQuizAnswer();

    currentQuestionIndex++;

    if (currentQuestionIndex < currentQuizData.questions.length) {
        loadQuestion(currentQuestionIndex);
        updateQuizProgress();
    } else {
        finishQuiz();
    }
}

/**
 * Navighează la întrebarea anterioară
 */
function previousQuestion() {
    if (currentQuestionIndex > 0) {
        currentQuestionIndex--;
        loadQuestion(currentQuestionIndex);
        updateQuizProgress();
    }
}

/**
 * Încarcă o întrebare
 */
function loadQuestion(index) {
    const question = currentQuizData.questions[index];

    const questionNumber = document.querySelector('.question-number');
    if (questionNumber) {
        questionNumber.textContent = (index + 1) + '/' + currentQuizData.questions.length;
    }

    const questionText = document.querySelector('.question-text');
    if (questionText) {
        questionText.textContent = question.text;
    }

    const optionsContainer = document.querySelector('.quiz-options');
    if (optionsContainer) {
        optionsContainer.innerHTML = '';

        question.options.forEach(function(option, optionIndex) {
            const optionHTML = `
                <div class="quiz-option" data-option-id="${option.id}" onclick="selectQuizOption(this)">
                    <div class="d-flex align-items-center">
                        <div class="option-radio me-3">
                            <i class="far fa-circle"></i>
                        </div>
                        <div class="option-text">
                            ${option.text}
                        </div>
                    </div>
                </div>
            `;
            optionsContainer.insertAdjacentHTML('beforeend', optionHTML);
        });

        const savedAnswer = getSavedQuizAnswer(index);
        if (savedAnswer) {
            const optionElement = document.querySelector('[data-option-id="' + savedAnswer + '"]');
            if (optionElement) {
                selectQuizOption(optionElement);
            }
        }
    }
}

/**
 * Selectează o opțiune de răspuns
 */
function selectQuizOption(element) {
    document.querySelectorAll('.quiz-option').forEach(function(option) {
        option.classList.remove('selected');
        const icon = option.querySelector('.option-radio i');
        if (icon) {
            icon.className = 'far fa-circle';
        }
    });

    element.classList.add('selected');
    const selectedIcon = element.querySelector('.option-radio i');
    if (selectedIcon) {
        selectedIcon.className = 'fas fa-check-circle';
    }
}

/**
 * Actualizează bara de progres a quiz-ului
 */
function updateQuizProgress() {
    const progress = ((currentQuestionIndex + 1) / currentQuizData.questions.length) * 100;
    const progressBar = document.querySelector('.quiz-progress-bar');

    if (progressBar) {
        progressBar.style.width = progress + '%';
    }
}

/**
 * Salvează răspunsul curent
 */
function saveQuizAnswer() {
    const selectedOption = document.querySelector('.quiz-option.selected');

    if (selectedOption) {
        const optionId = selectedOption.dataset.optionId;

        if (!window.quizAnswers) {
            window.quizAnswers = {};
        }

        window.quizAnswers[currentQuestionIndex] = optionId;
    }
}

/**
 * Obține răspunsul salvat pentru o întrebare
 */
function getSavedQuizAnswer(questionIndex) {
    return window.quizAnswers ? window.quizAnswers[questionIndex] : null;
}

/**
 * Finalizează quiz-ul
 */
async function finishQuiz() {
    try {
        showLoading();

        const response = await fetch('ajax/finish-quiz.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                quiz_id: currentQuizData.id,
                answers: window.quizAnswers,
                csrf_token: window.csrfToken || ''
            })
        });

        const data = await response.json();

        if (data.success) {
            window.location.href = 'quiz-result.php?result_id=' + data.result_id;
        } else {
            showToast(data.message, 'error');
        }

    } catch (error) {
        log('Error finishing quiz:', error);
        showToast('A apărut o eroare la finalizarea quiz-ului.', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * Pornește timer-ul pentru quiz
 */
function startQuizTimer(duration) {
    let timeLeft = duration * 60;

    quizTimer = setInterval(function() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;

        const timerElement = document.querySelector('.quiz-timer');
        if (timerElement) {
            timerElement.textContent = minutes + ':' + seconds.toString().padStart(2, '0');

            if (timeLeft <= 300) {
                timerElement.classList.add('text-danger');
            }
        }

        timeLeft--;

        if (timeLeft < 0) {
            clearInterval(quizTimer);
            showToast('Timpul a expirat! Quiz-ul se va finaliza automat.', 'warning');
            setTimeout(finishQuiz, 2000);
        }
    }, 1000);
}


/**
 * Calculator de economii cu dobândă compusă
 */
function calculateSavings() {
    const principalEl = document.getElementById('savingsPrincipal');
    const monthlyEl = document.getElementById('savingsMonthly');
    const rateEl = document.getElementById('savingsRate');
    const yearsEl = document.getElementById('savingsYears');

    const principal = principalEl ? parseFloat(principalEl.value) || 0 : 0;
    const monthlyDeposit = monthlyEl ? parseFloat(monthlyEl.value) || 0 : 0;
    const annualRate = rateEl ? parseFloat(rateEl.value) || 0 : 0;
    const years = yearsEl ? parseFloat(yearsEl.value) || 0 : 0;

    if (principal <= 0 && monthlyDeposit <= 0) {
        showToast('Te rugăm să introduci suma inițială sau depozitul lunar.', 'warning');
        return;
    }

    const monthlyRate = annualRate / 100 / 12;
    const months = years * 12;

    let futureValue = 0;

    if (principal > 0) {
        futureValue += principal * Math.pow(1 + monthlyRate, months);
    }

    if (monthlyDeposit > 0 && monthlyRate > 0) {
        futureValue += monthlyDeposit * (Math.pow(1 + monthlyRate, months) - 1) / monthlyRate;
    } else if (monthlyDeposit > 0) {
        futureValue += monthlyDeposit * months;
    }

    const totalDeposits = principal + (monthlyDeposit * months);
    const totalInterest = futureValue - totalDeposits;

    displayCalculatorResult('savings', {
        futureValue: futureValue,
        totalDeposits: totalDeposits,
        totalInterest: totalInterest,
        years: years
    });

    createSavingsChart(principal, monthlyDeposit, annualRate, years);
}

/**
 * Calculator de credite
 */
function calculateLoan() {
    const amountEl = document.getElementById('loanAmount');
    const rateEl = document.getElementById('loanRate');
    const yearsEl = document.getElementById('loanYears');

    const loanAmount = amountEl ? parseFloat(amountEl.value) || 0 : 0;
    const annualRate = rateEl ? parseFloat(rateEl.value) || 0 : 0;
    const years = yearsEl ? parseFloat(yearsEl.value) || 0 : 0;

    if (loanAmount <= 0 || annualRate <= 0 || years <= 0) {
        showToast('Te rugăm să completezi toate câmpurile cu valori pozitive.', 'warning');
        return;
    }

    const monthlyRate = annualRate / 100 / 12;
    const months = years * 12;

    const monthlyPayment = loanAmount * (monthlyRate * Math.pow(1 + monthlyRate, months)) /
        (Math.pow(1 + monthlyRate, months) - 1);

    const totalPayment = monthlyPayment * months;
    const totalInterest = totalPayment - loanAmount;

    displayCalculatorResult('loan', {
        monthlyPayment: monthlyPayment,
        totalPayment: totalPayment,
        totalInterest: totalInterest,
        loanAmount: loanAmount
    });

    createLoanChart(loanAmount, monthlyPayment, monthlyRate, months);
}

/**
 * Calculator de buget 50/30/20
 */
function calculateBudget() {
    const incomeEl = document.getElementById('monthlyIncome');
    const monthlyIncome = incomeEl ? parseFloat(incomeEl.value) || 0 : 0;

    if (monthlyIncome <= 0) {
        showToast('Te rugăm să introduci venitul lunar.', 'warning');
        return;
    }

    const needs = monthlyIncome * 0.5;
    const wants = monthlyIncome * 0.3;
    const savings = monthlyIncome * 0.2;

    displayCalculatorResult('budget', {
        monthlyIncome: monthlyIncome,
        needs: needs,
        wants: wants,
        savings: savings
    });

    createBudgetChart(needs, wants, savings);
}

/**
 * Afișează rezultatul calculatorului
 */
function displayCalculatorResult(type, data) {
    const resultContainer = document.querySelector('#' + type + 'Result');
    if (!resultContainer) return;

    let resultHTML = '';

    switch (type) {
        case 'savings':
            resultHTML = `
                <div class="calculator-result">
                    <h4>Rezultatul Calculului</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="result-item">
                                <span class="result-label">Valoarea finală:</span>
                                <span class="result-value">${formatPrice(data.futureValue)}</span>
                            </div>
                            <div class="result-item">
                                <span class="result-label">Total depus:</span>
                                <span class="result-value">${formatPrice(data.totalDeposits)}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="result-item">
                                <span class="result-label">Dobândă câștigată:</span>
                                <span class="result-value text-success">${formatPrice(data.totalInterest)}</span>
                            </div>
                            <div class="result-item">
                                <span class="result-label">Perioada:</span>
                                <span class="result-value">${data.years} ani</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'loan':
            resultHTML = `
                <div class="calculator-result">
                    <h4>Rezultatul Calculului</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="result-item">
                                <span class="result-label">Rata lunară:</span>
                                <span class="result-value">${formatPrice(data.monthlyPayment)}</span>
                            </div>
                            <div class="result-item">
                                <span class="result-label">Total de plată:</span>
                                <span class="result-value">${formatPrice(data.totalPayment)}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="result-item">
                                <span class="result-label">Total dobândă:</span>
                                <span class="result-value text-danger">${formatPrice(data.totalInterest)}</span>
                            </div>
                            <div class="result-item">
                                <span class="result-label">Suma împrumutată:</span>
                                <span class="result-value">${formatPrice(data.loanAmount)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'budget':
            resultHTML = `
                <div class="calculator-result">
                    <h4>Planul tău de buget 50/30/20</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="result-item">
                                <span class="result-label">Necesități (50%):</span>
                                <span class="result-value">${formatPrice(data.needs)}</span>
                                <small class="text-muted d-block">Chirie, utilități, mâncare</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="result-item">
                                <span class="result-label">Dorințe (30%):</span>
                                <span class="result-value">${formatPrice(data.wants)}</span>
                                <small class="text-muted d-block">Distracție, shopping, hobby</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="result-item">
                                <span class="result-label">Economii (20%):</span>
                                <span class="result-value text-success">${formatPrice(data.savings)}</span>
                                <small class="text-muted d-block">Economii, investiții</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            break;
    }

    resultContainer.innerHTML = resultHTML;
    resultContainer.style.display = 'block';

    resultContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}


/**
 * Creează graficul pentru economii
 */
function createSavingsChart(principal, monthlyDeposit, annualRate, years) {
    if (typeof Chart === 'undefined') return;

    const canvas = document.getElementById('savingsChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const months = years * 12;
    const monthlyRate = annualRate / 100 / 12;

    const labels = [];
    const principalData = [];
    const depositsData = [];
    const interestData = [];

    for (let month = 0; month <= months; month += 12) {
        labels.push('Anul ' + (month / 12));

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
        interestData.push(interestValue);
    }

    if (window.savingsChartInstance) {
        window.savingsChartInstance.destroy();
    }

    window.savingsChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                    label: 'Suma inițială',
                    data: principalData,
                    backgroundColor: '#2c5aa0',
                    stack: 'stack1'
                },
                {
                    label: 'Depozite lunare',
                    data: depositsData,
                    backgroundColor: '#f8c146',
                    stack: 'stack1'
                },
                {
                    label: 'Dobândă câștigată',
                    data: interestData,
                    backgroundColor: '#28a745',
                    stack: 'stack1'
                }
            ]
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

/**
 * Creează graficul pentru credite
 */
function createLoanChart(loanAmount, monthlyPayment, monthlyRate, totalMonths) {
    if (typeof Chart === 'undefined') return;

    const canvas = document.getElementById('loanChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    const labels = [];
    const principalData = [];
    const interestData = [];
    let remainingBalance = loanAmount;

    for (let month = 1; month <= Math.min(12, totalMonths); month++) {
        const interestPayment = remainingBalance * monthlyRate;
        const principalPayment = monthlyPayment - interestPayment;
        remainingBalance -= principalPayment;

        labels.push('Luna ' + month);
        principalData.push(principalPayment);
        interestData.push(interestPayment);
    }

    if (window.loanChartInstance) {
        window.loanChartInstance.destroy();
    }

    window.loanChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                    label: 'Plată principală',
                    data: principalData,
                    backgroundColor: '#2c5aa0'
                },
                {
                    label: 'Plată dobândă',
                    data: interestData,
                    backgroundColor: '#dc3545'
                }
            ]
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
}

/**
 * Creează graficul circular pentru buget
 */
function createBudgetChart(needs, wants, savings) {
    if (typeof Chart === 'undefined') return;

    const canvas = document.getElementById('budgetChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    if (window.budgetChartInstance) {
        window.budgetChartInstance.destroy();
    }

    window.budgetChartInstance = new Chart(ctx, {
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


/**
 * Validează formularul de înregistrare
 */
function validateRegisterForm() {
    const nameEl = document.getElementById('nume');
    const emailEl = document.getElementById('email');
    const passwordEl = document.getElementById('parola');
    const confirmPasswordEl = document.getElementById('confirma_parola');

    const name = nameEl ? nameEl.value.trim() : '';
    const email = emailEl ? emailEl.value.trim() : '';
    const password = passwordEl ? passwordEl.value : '';
    const confirmPassword = confirmPasswordEl ? confirmPasswordEl.value : '';

    let isValid = true;
    let errors = [];

    if (!name || name.length < 2) {
        errors.push('Numele trebuie să aibă minimum 2 caractere.');
        isValid = false;
    }

    if (!email || !validateEmail(email)) {
        errors.push('Te rugăm să introduci o adresă de email validă.');
        isValid = false;
    }

    if (!password || !validatePassword(password)) {
        errors.push('Parola trebuie să aibă minimum 8 caractere, o literă mare, o literă mică și o cifră.');
        isValid = false;
    }

    if (password !== confirmPassword) {
        errors.push('Parolele nu se potrivesc.');
        isValid = false;
    }

    if (!isValid) {
        showToast(errors.join('<br>'), 'error');
    }

    return isValid;
}

/**
 * Validează formularul de conectare
 */
function validateLoginForm() {
    const emailEl = document.getElementById('email');
    const passwordEl = document.getElementById('parola');

    const email = emailEl ? emailEl.value.trim() : '';
    const password = passwordEl ? passwordEl.value : '';

    let isValid = true;
    let errors = [];

    if (!email || !validateEmail(email)) {
        errors.push('Te rugăm să introduci o adresă de email validă.');
        isValid = false;
    }

    if (!password || password.length < 1) {
        errors.push('Te rugăm să introduci parola.');
        isValid = false;
    }

    if (!isValid) {
        showToast(errors.join('<br>'), 'error');
    }

    return isValid;
}

/**
 * Afișează/ascunde parola
 */
function togglePassword(inputId, buttonElement) {
    const input = document.getElementById(inputId);
    const icon = buttonElement.querySelector('i');

    if (input && icon) {
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
}

/**
 * Auto-save pentru formulare lungi
 */
function enableAutoSave(formId, storageKey) {
    const form = document.getElementById(formId);
    if (!form) return;

    const savedData = localStorage.getItem(storageKey);
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(function(key) {
                const input = form.querySelector('[name="' + key + '"]');
                if (input && input.type !== 'password') {
                    input.value = data[key];
                }
            });
        } catch (e) {
            console.warn('Eroare la încărcarea datelor salvate:', e);
        }
    }

    form.addEventListener('input', function(e) {
        const formData = new FormData(form);
        const data = {};

        for (let pair of formData.entries()) {
            const key = pair[0];
            const value = pair[1];
            if (!key.includes('parola') && !key.includes('password')) {
                data[key] = value;
            }
        }

        localStorage.setItem(storageKey, JSON.stringify(data));
    });

    form.addEventListener('submit', function() {
        localStorage.removeItem(storageKey);
    });
}


/**
 * Căutare live pentru cursuri/articole
 */
function setupLiveSearch(inputId, resultsContainerId, searchUrl) {
    const input = document.getElementById(inputId);
    const resultsContainer = document.getElementById(resultsContainerId);

    if (!input || !resultsContainer) return;

    let searchTimeout;

    input.addEventListener('input', function() {
        const query = this.value.trim();

        clearTimeout(searchTimeout);

        if (query.length < 2) {
            resultsContainer.innerHTML = '';
            return;
        }

        searchTimeout = setTimeout(function() {
            fetch(searchUrl + '?q=' + encodeURIComponent(query))
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        displaySearchResults(data.results, resultsContainer);
                    }
                })
                .catch(function(error) {
                    log('Search error:', error);
                });
        }, 300);
    });
}

/**
 * Afișează rezultatele căutării
 */
function displaySearchResults(results, container) {
    if (results.length === 0) {
        container.innerHTML = '<div class="text-muted p-3">Nu s-au găsit rezultate.</div>';
        return;
    }

    let html = '';
    results.forEach(function(result) {
        html += `
            <div class="search-result-item p-3 border-bottom">
                <h6><a href="${result.url}" class="text-decoration-none">${result.title}</a></h6>
                <p class="text-muted small mb-0">${result.excerpt}</p>
            </div>
        `;
    });

    container.innerHTML = html;
}

/**
 * Filtrare pentru cursuri
 */
function filterCourses() {
    const levelEl = document.getElementById('levelFilter');
    const priceEl = document.getElementById('priceFilter');
    const searchEl = document.getElementById('searchFilter');

    const level = levelEl ? levelEl.value : '';
    const priceRange = priceEl ? priceEl.value : '';
    const searchTerm = searchEl ? searchEl.value.toLowerCase() : '';

    const courseCards = document.querySelectorAll('.course-card');

    courseCards.forEach(function(card) {
        let shouldShow = true;

        if (level && level !== 'all') {
            const courseLevel = card.dataset.level;
            if (courseLevel !== level) {
                shouldShow = false;
            }
        }

        if (priceRange && priceRange !== 'all') {
            const coursePrice = parseFloat(card.dataset.price) || 0;
            const rangeParts = priceRange.split('-');
            const min = parseFloat(rangeParts[0]);
            const max = rangeParts[1] ? parseFloat(rangeParts[1]) : null;

            if (max) {
                if (coursePrice < min || coursePrice > max) {
                    shouldShow = false;
                }
            } else {
                if (coursePrice < min) {
                    shouldShow = false;
                }
            }
        }

        if (searchTerm) {
            const titleEl = card.querySelector('.course-title');
            const descEl = card.querySelector('.course-description');

            const courseTitle = titleEl ? titleEl.textContent.toLowerCase() : '';
            const courseDescription = descEl ? descEl.textContent.toLowerCase() : '';

            if (!courseTitle.includes(searchTerm) && !courseDescription.includes(searchTerm)) {
                shouldShow = false;
            }
        }

        card.style.display = shouldShow ? 'block' : 'none';
    });

    updateResultsCount();
}

/**
 * Actualizează contorul de rezultate
 */
function updateResultsCount() {
    const visibleCourses = document.querySelectorAll('.course-card[style*="block"], .course-card:not([style*="none"])');
    const counter = document.getElementById('resultsCount');

    if (counter) {
        counter.textContent = visibleCourses.length + ' cursuri găsite';
    }
}


/**
 * Toggle like pentru topicuri
 */
async function toggleTopicLike(topicId, buttonElement) {
    try {
        const response = await fetch('ajax/toggle-like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                topic_id: topicId,
                csrf_token: window.csrfToken || ''
            })
        });

        const data = await response.json();

        if (data.success) {
            const icon = buttonElement.querySelector('i');
            const countSpan = buttonElement.querySelector('.like-count');

            if (data.liked) {
                icon.classList.remove('far');
                icon.classList.add('fas', 'text-danger');
                buttonElement.classList.add('liked');
            } else {
                icon.classList.remove('fas', 'text-danger');
                icon.classList.add('far');
                buttonElement.classList.remove('liked');
            }

            if (countSpan) {
                countSpan.textContent = data.like_count;
            }
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        log('Error toggling like:', error);
        showToast('A apărut o eroare.', 'error');
    }
}

/**
 * Încarcă mai multe comentarii
 */
async function loadMoreComments(topicId, offset) {
    offset = offset || 0;

    try {
        showLoading();

        const response = await fetch('ajax/load-comments.php?topic_id=' + topicId + '&offset=' + offset);
        const data = await response.json();

        if (data.success) {
            const commentsContainer = document.getElementById('commentsContainer');

            if (offset === 0) {
                commentsContainer.innerHTML = data.html;
            } else {
                commentsContainer.insertAdjacentHTML('beforeend', data.html);
            }

            const loadMoreBtn = document.getElementById('loadMoreComments');
            if (data.has_more) {
                loadMoreBtn.style.display = 'block';
                loadMoreBtn.onclick = function() {
                    loadMoreComments(topicId, offset + data.loaded_count);
                };
            } else {
                loadMoreBtn.style.display = 'none';
            }
        }
    } catch (error) {
        log('Error loading comments:', error);
        showToast('A apărut o eroare la încărcarea comentariilor.', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * Adaugă comentariu nou
 */
async function addComment(topicId, formElement) {
    const formData = new FormData(formElement);

    try {
        showLoading();

        const response = await fetch('ajax/add-comment.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            formElement.reset();

            await loadMoreComments(topicId, 0);

            showToast('Comentariul a fost adăugat cu succes!', 'success');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        log('Error adding comment:', error);
        showToast('A apărut o eroare la adăugarea comentariului.', 'error');
    } finally {
        hideLoading();
    }
}


/**
 * Actualizează progresul unui curs
 */
async function updateCourseProgress(courseId, progress) {
    try {
        const response = await fetch('ajax/update-progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                course_id: courseId,
                progress: progress,
                csrf_token: window.csrfToken || ''
            })
        });

        const data = await response.json();

        if (data.success) {
            const progressBar = document.querySelector('[data-course-id="' + courseId + '"] .progress-bar');
            if (progressBar) {
                progressBar.style.width = progress + '%';
                progressBar.textContent = Math.round(progress) + '%';
            }

            if (progress >= 100) {
                showToast('Felicitări! Ai finalizat cursul!', 'success');

                const courseCard = document.querySelector('[data-course-id="' + courseId + '"]');
                if (courseCard) {
                    courseCard.classList.add('completed');
                    const badge = courseCard.querySelector('.completion-badge');
                    if (badge) {
                        badge.style.display = 'block';
                    }
                }
            }
        }
    } catch (error) {
        log('Error updating progress:', error);
    }
}

/**
 * Marchează o lecție ca finalizată
 */
function markLessonCompleted(lessonId, courseId) {
    const lessonElement = document.querySelector('[data-lesson-id="' + lessonId + '"]');
    if (lessonElement) {
        lessonElement.classList.add('completed');
        const checkIcon = lessonElement.querySelector('.completion-check');
        if (checkIcon) {
            checkIcon.style.display = 'inline';
        }
    }

    const totalLessons = document.querySelectorAll('[data-lesson-id]').length;
    const completedLessons = document.querySelectorAll('[data-lesson-id].completed').length;
    const progress = (completedLessons / totalLessons) * 100;

    updateCourseProgress(courseId, progress);
}


/**
 * Debounce function pentru search
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction() {
        const args = arguments;
        const later = function() {
            clearTimeout(timeout);
            func.apply(null, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Funcție pentru loading spinner
 */
function showLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.style.display = 'block';
    }
}

function hideLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.style.display = 'none';
    }
}

/**
 * Copy to clipboard
 */
async function copyToClipboard(text, button) {
    try {
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(text);
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }

        showToast('Copiat în clipboard!', 'success');

        if (button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Copiat!';
            setTimeout(function() {
                button.innerHTML = originalText;
            }, 2000);
        }
    } catch (err) {
        showToast('Nu s-a putut copia în clipboard.', 'error');
    }
}

/**
 * Share functionality
 */
function shareContent(title, url) {
    if (navigator.share) {
        navigator.share({
            title: title,
            url: url
        });
    } else {
        copyToClipboard(url);
    }
}

/**
 * Print functionality
 */
function printPage() {
    window.print();
}

/**
 * Export data to CSV
 */
function exportToCSV(data, filename) {
    const csv = data.map(function(row) {
        return row.map(function(field) {
            return '"' + field + '"';
        }).join(',');
    }).join('\n');

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', filename);
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}


/**
 * Inițializare la încărcarea paginii
 */
document.addEventListener('DOMContentLoaded', function() {
    log('Initializing application...');

    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    popoverTriggerList.forEach(function(popoverTriggerEl) {
        new bootstrap.Popover(popoverTriggerEl);
    });

    if (document.getElementById('registerForm')) {
        enableAutoSave('registerForm', 'registerFormData');
    }

    if (document.getElementById('profileForm')) {
        enableAutoSave('profileForm', 'profileFormData');
    }

    setupLiveSearch('courseSearch', 'searchResults', 'ajax/search-courses.php');
    setupLiveSearch('articleSearch', 'searchResults', 'ajax/search-articles.php');

    const savingsForm = document.getElementById('savingsCalculator');
    if (savingsForm) {
        savingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            calculateSavings();
        });
    }

    const loanForm = document.getElementById('loanCalculator');
    if (loanForm) {
        loanForm.addEventListener('submit', function(e) {
            e.preventDefault();
            calculateLoan();
        });
    }

    const budgetForm = document.getElementById('budgetCalculator');
    if (budgetForm) {
        budgetForm.addEventListener('submit', function(e) {
            e.preventDefault();
            calculateBudget();
        });
    }

    const levelFilter = document.getElementById('levelFilter');
    const priceFilter = document.getElementById('priceFilter');
    const searchFilter = document.getElementById('searchFilter');

    if (levelFilter) levelFilter.addEventListener('change', filterCourses);
    if (priceFilter) priceFilter.addEventListener('change', filterCourses);
    if (searchFilter) searchFilter.addEventListener('input', debounce(filterCourses, 300));

    updateResultsCount();

    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
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

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(function(img) {
            imageObserver.observe(img);
        });
    }

    log('Application initialized successfully');
});


const csrfMeta = document.querySelector('meta[name="csrf-token"]');
if (csrfMeta) {
    window.csrfToken = csrfMeta.getAttribute('content');
}

log('Script.js loaded successfully');