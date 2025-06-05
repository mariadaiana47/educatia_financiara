<?php
// Test pentru mapping calculator

$exercitii_test = [
    'Calculator Dobândă Compusă Personal',
    'Planificatorul de Economii pe 5 ani',
    'Challenge: Găsește 200 lei să economisești',
    'Test Profil de Risc Investițional',
    'Simulare Portofoliu Virtual 10.000 lei'
];

function determineCalculatorType($titlu) {
    $titlu_lower = strtolower($titlu);
    
    // Mapare titluri la tipuri de calculatoare
    $mappings = [
        'dobândă compusă' => 'compound_interest',
        'dobanda compusa' => 'compound_interest',
        'compound interest' => 'compound_interest',
        'economisire' => 'savings_plan',
        'economii' => 'savings_plan',
        'savings' => 'savings_plan',
        'planificator' => 'savings_plan',
        'planificatorul' => 'savings_plan',
        'challenge' => 'expense_tracker',
        'cheltuieli' => 'expense_tracker',
        'expense' => 'expense_tracker',
        'tracking' => 'expense_tracker',
        'găsește' => 'expense_tracker',
        'gaseste' => 'expense_tracker',
        'profil de risc' => 'risk_profile',
        'test profil' => 'risk_profile',
        'portofoliu virtual' => 'portfolio_simulator',
        'simulare portofoliu' => 'portfolio_simulator',
        'dollar cost averaging' => 'dca_simulator',
        'simulare dollar' => 'dca_simulator',
        'dca' => 'dca_simulator',
        'necesar pensie' => 'retirement_calculator',
        'calculator necesar' => 'retirement_calculator',
        'start la 25' => 'age_comparison',
        'start la 35' => 'age_comparison',
        'comparația dramatică' => 'age_comparison',
        '25 vs 35' => 'age_comparison'
    ];
    
    foreach ($mappings as $keyword => $calculator) {
        if (strpos($titlu_lower, $keyword) !== false) {
            return $calculator;
        }
    }
    
    // Default calculator
    return 'default_calculator';
}

echo "<h3>Test Calculator Mapping</h3>";

foreach ($exercitii_test as $titlu) {
    $calculator_type = determineCalculatorType($titlu);
    $calculator_path = "calculators/{$calculator_type}.php";
    $file_exists = file_exists($calculator_path);
    
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Titlu:</strong> $titlu<br>";
    echo "<strong>Calculator Type:</strong> $calculator_type<br>";
    echo "<strong>Path:</strong> $calculator_path<br>";
    echo "<strong>File Exists:</strong> " . ($file_exists ? 'YES' : 'NO') . "<br>";
    echo "</div>";
}

echo "<h4>Files in calculators directory:</h4>";
if (is_dir('calculators')) {
    $files = scandir('calculators');
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "- $file<br>";
        }
    }
} else {
    echo "Directory calculators not found!";
}
?>