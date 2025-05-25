<?php
$content = '88 x 66 x 30 cm; 17,5 quilogramas';

$parts = explode(';', $content);

if (!isset($parts[1])) {
    echo '';
    exit;
}

$unit = trim($parts[1]);  // "17,5 quilogramas"

echo "Original unit string: " . $unit . "<br />";

// Extract the numeric value
$pattern = '/(\d+[\.,]?\d*)/';
preg_match($pattern, $unit, $scalars);
$numeric_value = $scalars[0] ?? null;

echo "Numeric value: ";
print_r($numeric_value);
echo "<br />";

// Extract the unit text (what remains after removing the number)
$unit_text = trim(preg_replace($pattern, '', $unit));

echo "Unit text: ";
print_r($unit_text);
echo "<br />";

$content = '250 × 265.5 × 201,25 mm; 10g';

$content = explode(';', $content)[0];

preg_match_all('/\d+[.,]?\d*/', $content, $matches);

$values = str_replace(',', '.', $matches[0]);

rsort($values, SORT_NUMERIC);

$dimensions = implode(';', $values) . ';';

print_r($dimensions);
