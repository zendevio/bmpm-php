#!/usr/bin/env php
<?php
/**
 * BMPM Rule Converter
 * Converts original PHP rule files to JSON format for the modern implementation.
 */

declare(strict_types=1);

// Language definitions for each name type
$languageDefs = [
    'gen' => [
        'any' => 1, 'arabic' => 2, 'cyrillic' => 4, 'czech' => 8, 'dutch' => 16,
        'english' => 32, 'french' => 64, 'german' => 128, 'greek' => 256,
        'greeklatin' => 512, 'hebrew' => 1024, 'hungarian' => 2048, 'italian' => 4096,
        'latvian' => 8192, 'polish' => 16384, 'portuguese' => 32768,
        'romanian' => 65536, 'russian' => 131072, 'spanish' => 262144, 'turkish' => 524288
    ],
    'ash' => [
        'any' => 1, 'cyrillic' => 2, 'english' => 4, 'french' => 8, 'german' => 16,
        'hebrew' => 32, 'hungarian' => 64, 'polish' => 128, 'romanian' => 256,
        'russian' => 512, 'spanish' => 1024
    ],
    'sep' => [
        'any' => 1, 'french' => 2, 'hebrew' => 4, 'italian' => 8,
        'portuguese' => 16, 'spanish' => 32
    ]
];

// Map type codes to folder names
$typeToFolder = [
    'gen' => 'Generic',
    'ash' => 'Ashkenazic',
    'sep' => 'Sephardic'
];

/**
 * Extract the variable name and array contents from a PHP file
 */
function extractArrayFromPhp(string $content, string $type, array $langDef): array {
    // Create language variable values for variable substitution
    $langVars = [];
    foreach ($langDef as $name => $value) {
        $langVars['$' . $name] = $value;
    }

    // Extract the main array variable name and content
    // Match patterns like: $rulesGerman = array( ... );
    // or $languageRules = array( ... );
    if (!preg_match('/\$(\w+)\s*=\s*array\s*\(/s', $content, $varMatch)) {
        return ['varName' => '', 'rules' => []];
    }

    $varName = $varMatch[1];

    // Find all array() entries using balanced parentheses matching
    $rules = [];
    $pos = 0;
    $len = strlen($content);

    while (($arrayPos = strpos($content, 'array(', $pos)) !== false) {
        // Skip the main array definition
        if ($arrayPos < strpos($content, $varMatch[0]) + strlen($varMatch[0])) {
            $pos = $arrayPos + 6;
            continue;
        }

        // Find the matching closing parenthesis
        $start = $arrayPos + 6; // After "array("
        $depth = 1;
        $end = $start;

        while ($end < $len && $depth > 0) {
            $char = $content[$end];
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            } elseif ($char === '"' || $char === "'") {
                // Skip string contents
                $quote = $char;
                $end++;
                while ($end < $len && ($content[$end] !== $quote || $content[$end - 1] === '\\')) {
                    $end++;
                }
            }
            $end++;
        }

        if ($depth === 0) {
            $arrayContent = substr($content, $start, $end - $start - 1);
            $rule = parseArrayContent($arrayContent, $langVars);
            if ($rule !== null) {
                $rules[] = $rule;
            }
        }

        $pos = $end;
    }

    return ['varName' => $varName, 'rules' => $rules];
}

/**
 * Parse the content of an array() call
 */
function parseArrayContent(string $content, array $langVars): ?array {
    // Split by comma, but respect strings
    $parts = smartSplit($content);

    if (count($parts) < 2) {
        return null;
    }

    // Determine rule type based on content
    $firstPart = trim($parts[0]);

    // Language detection rule: array('/pattern/', $lang, true/false)
    if (str_starts_with($firstPart, "'/") || str_starts_with($firstPart, '"/')) {
        return parseLanguageRule($parts, $langVars);
    }

    // Phonetic rule: array("pattern", "left", "right", "phonetic")
    // or 4-5 element array
    return parsePhoneticRule($parts, $langVars);
}

/**
 * Parse a language detection rule
 */
function parseLanguageRule(array $parts, array $langVars): ?array {
    if (count($parts) < 3) {
        return null;
    }

    $pattern = cleanString($parts[0]);
    $langExpr = trim($parts[1]);
    $accept = trim($parts[2]);

    // Evaluate the language expression (e.g., $german+$english)
    $langMask = evaluateLanguageExpression($langExpr, $langVars);

    return [
        'pattern' => $pattern,
        'languages' => $langMask,
        'accept' => ($accept === 'true')
    ];
}

/**
 * Parse a phonetic transformation rule
 */
function parsePhoneticRule(array $parts, array $langVars): ?array {
    if (count($parts) < 4) {
        return null;
    }

    $pattern = cleanString($parts[0]);
    $leftContext = cleanString($parts[1]);
    $rightContext = cleanString($parts[2]);
    $phonetic = cleanString($parts[3]);

    $rule = [
        'pattern' => $pattern,
        'leftContext' => $leftContext,
        'rightContext' => $rightContext,
        'phonetic' => $phonetic
    ];

    // Check for optional language mask (5th element)
    if (isset($parts[4])) {
        $langExpr = trim($parts[4]);
        if (!empty($langExpr) && $langExpr !== '0') {
            $rule['languageMask'] = evaluateLanguageExpression($langExpr, $langVars);
        }
    }

    // Check for optional logical operator (6th element)
    if (isset($parts[5])) {
        $logicalOp = cleanString($parts[5]);
        if (!empty($logicalOp) && $logicalOp !== 'ANY') {
            $rule['logicalOp'] = $logicalOp;
        }
    }

    // Remove empty optional fields
    if (isset($rule['languageMask']) && $rule['languageMask'] === 0) {
        unset($rule['languageMask']);
    }

    return $rule;
}

/**
 * Smart split by comma, respecting quoted strings and parentheses
 */
function smartSplit(string $content): array {
    $parts = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    $parenDepth = 0;
    $bracketDepth = 0;

    $len = strlen($content);
    for ($i = 0; $i < $len; $i++) {
        $char = $content[$i];
        $prevChar = $i > 0 ? $content[$i - 1] : '';

        if (!$inString) {
            if ($char === '"' || $char === "'") {
                $inString = true;
                $stringChar = $char;
            } elseif ($char === '(') {
                $parenDepth++;
            } elseif ($char === ')') {
                $parenDepth--;
            } elseif ($char === '[') {
                $bracketDepth++;
            } elseif ($char === ']') {
                $bracketDepth--;
            } elseif ($char === ',' && $parenDepth === 0 && $bracketDepth === 0) {
                $parts[] = trim($current);
                $current = '';
                continue;
            }
        } else {
            if ($char === $stringChar && $prevChar !== '\\') {
                $inString = false;
            }
        }

        $current .= $char;
    }

    if (trim($current) !== '') {
        $parts[] = trim($current);
    }

    return $parts;
}

/**
 * Clean a string value (remove quotes and unescape)
 */
function cleanString(string $value): string {
    $value = trim($value);

    // Handle empty string constants
    if ($value === '""' || $value === "''") {
        return '';
    }

    // Remove surrounding quotes
    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
        (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
        $value = substr($value, 1, -1);
    }

    // Unescape backslashes
    $value = stripslashes($value);

    return $value;
}

/**
 * Evaluate a language expression like $german+$english or ".($polish+$czech)."
 */
function evaluateLanguageExpression(string $expr, array $langVars): int {
    $expr = trim($expr);

    // Handle expression wrapped in string concatenation: ".($polish+$czech)."
    if (preg_match('/["\']\.?\s*\(\s*(.+?)\s*\)\s*\.?["\']/', $expr, $m)) {
        $expr = $m[1];
    }

    // Handle embedded language references in strings like "[$russian]"
    // These are phonetic alternation markers, not actual language masks
    if (preg_match('/^\[/', $expr)) {
        return 0;
    }

    // Handle true/false for accept field
    if ($expr === 'true') {
        return 1;
    }
    if ($expr === 'false') {
        return 0;
    }

    // Simple numeric value
    if (is_numeric($expr)) {
        return (int) $expr;
    }

    // Handle single variable
    if (str_starts_with($expr, '$') && !str_contains($expr, '+')) {
        return $langVars[$expr] ?? 0;
    }

    // Handle addition expressions: $german+$english+$french
    $total = 0;
    $parts = preg_split('/\s*\+\s*/', $expr);
    foreach ($parts as $part) {
        $part = trim($part);
        if (str_starts_with($part, '$')) {
            $total += $langVars[$part] ?? 0;
        } elseif (is_numeric($part)) {
            $total += (int) $part;
        }
    }

    return $total;
}

/**
 * Determine the output filename from the variable name
 */
function getOutputFilename(string $varName): string {
    // Convert variable names like $rulesGerman to rules_german.json
    // $languageRules to language_rules.json
    // $approxGerman to approx_german.json

    $name = preg_replace('/([a-z])([A-Z])/', '$1_$2', $varName);
    return strtolower($name) . '.json';
}

/**
 * Process a single file
 */
function processFile(string $inputFile, string $outputDir, string $type, array $langDef): bool {
    if (!file_exists($inputFile)) {
        echo "  SKIP: File not found: $inputFile\n";
        return false;
    }

    $content = file_get_contents($inputFile);
    if ($content === false) {
        echo "  ERROR: Cannot read file: $inputFile\n";
        return false;
    }

    $result = extractArrayFromPhp($content, $type, $langDef);

    if (empty($result['rules'])) {
        echo "  SKIP: No rules found in: " . basename($inputFile) . "\n";
        return false;
    }

    $outputFile = $outputDir . '/' . getOutputFilename($result['varName']);

    // Create descriptive metadata
    $metadata = [
        'name' => ucfirst(str_replace('_', ' ', pathinfo($outputFile, PATHINFO_FILENAME))),
        'description' => 'Converted from ' . basename($inputFile),
        'version' => '3.15',
        'source' => basename($inputFile),
        'rules' => $result['rules']
    ];

    $json = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    if (file_put_contents($outputFile, $json . "\n") === false) {
        echo "  ERROR: Cannot write: $outputFile\n";
        return false;
    }

    echo "  OK: " . basename($inputFile) . " -> " . basename($outputFile) . " (" . count($result['rules']) . " rules)\n";
    return true;
}

/**
 * Get list of files to process for a given type
 */
function getFilesToProcess(string $srcDir): array {
    $files = [];

    // Skip certain files
    $skip = ['languagenames.php', 'languagenames1.php', 'languagenames2.php'];

    foreach (glob($srcDir . '/*.php') as $file) {
        $basename = basename($file);
        if (!in_array($basename, $skip)) {
            $files[] = $file;
        }
    }

    return $files;
}

// Main execution
$srcRoot = dirname(__DIR__) . '/resources/bmpm-php-3.15'; // Original BMPM 3.15 PHP files
$dstRoot = dirname(__DIR__) . '/src/Rules/Data';

echo "BMPM Rule Converter\n";
echo "==================\n";
echo "Source: $srcRoot\n";
echo "Output: $dstRoot\n\n";

$stats = ['converted' => 0, 'skipped' => 0, 'errors' => 0];

foreach ($typeToFolder as $type => $folder) {
    $srcDir = $srcRoot . '/' . $type;
    $dstDir = $dstRoot . '/' . $folder;

    if (!is_dir($srcDir)) {
        echo "SKIP: Source directory not found: $srcDir\n\n";
        continue;
    }

    echo "Processing $folder ($type):\n";
    echo str_repeat('-', 50) . "\n";

    $files = getFilesToProcess($srcDir);
    $langDef = $languageDefs[$type];

    foreach ($files as $file) {
        $result = processFile($file, $dstDir, $type, $langDef);
        if ($result) {
            $stats['converted']++;
        } else {
            $stats['skipped']++;
        }
    }

    echo "\n";
}

echo "Summary:\n";
echo "========\n";
echo "Converted: {$stats['converted']}\n";
echo "Skipped: {$stats['skipped']}\n";
echo "Errors: {$stats['errors']}\n";
