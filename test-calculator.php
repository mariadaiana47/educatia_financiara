<?php
echo "<h3>Test Calculator System</h3>";

$exercitiu = [
    'titlu' => 'Calculator Dobândă Compusă Personal',
    'descriere' => 'Test description'
];

function determineCalculatorType($titlu) {
    $titlu_lower = strtolower($titlu);
    if (strpos($titlu_lower, 'dobândă compusă') !== false) {
        return 'compound_interest';
    }
    return 'default_calculator';
}

$calculator_type = determineCalculatorType($exercitiu['titlu']);
$calculator_path = "calculators/{$calculator_type}.php";

echo "<p><strong>Calculator type:</strong> $calculator_type</p>";
echo "<p><strong>Calculator path:</strong> $calculator_path</p>";
echo "<p><strong>File exists:</strong> " . (file_exists($calculator_path) ? 'YES' : 'NO') . "</p>";
echo "<p><strong>Current directory:</strong> " . getcwd() . "</p>";

// List calculators directory
if (is_dir('calculators')) {
    echo "<p><strong>Calculators directory contents:</strong></p>";
    $files = scandir('calculators');
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<p>- $file</p>";
        }
    }
} else {
    echo "<p style='color: red;'>Calculators directory not found!</p>";
}

if (file_exists($calculator_path)) {
    echo "<hr><h4>Loading Calculator:</h4>";
    include $calculator_path;
} else {
    echo "<p style='color: red;'>Calculator file not found!</p>";
    
    // Try default
    if (file_exists('calculators/default_calculator.php')) {
        echo "<p style='color: blue;'>Loading default calculator:</p>";
        include 'calculators/default_calculator.php';
    }
}
?>