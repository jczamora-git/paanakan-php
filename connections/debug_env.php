<?php
// Debug script to show what the simple .env parser reads
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    echo ".env not found at: $envFile\n";
    exit(1);
}

$parsed = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $i => $line) {
    $raw = $line;
    $trimmed = trim($line);
    if ($trimmed === '' || strpos($trimmed, '#') === 0) continue;
    if (strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $k = trim($k);
    $v = trim($v);
    $v = str_replace(["\r", "\n"], '', $v);
    $v_unquoted = preg_replace('/^"|"$/', '', $v);
    $parsed[$k] = $v_unquoted;
    $_ENV[$k] = $v_unquoted;
    echo "Line {$i}: raw='" . addslashes($raw) . "' key='{$k}' value='" . addslashes($v) . "' unquoted='" . addslashes($v_unquoted) . "'\n";
}

echo "\nParsed array:\n";
print_r($parsed);
echo "\ngetenv('SENDGRID_API_KEY')='" . getenv('SENDGRID_API_KEY') . "'\n";

echo "\nParsed var_dump (from parsed array):\n";
var_dump($parsed);
echo "\nParsed keys and byte info:\n";
foreach (array_keys($parsed) as $k) {
    $len = strlen($k);
    echo "Key: '" . $k . "' (len={$len})\n";
    $bytes = [];
    for ($i = 0; $i < min($len, 40); $i++) {
        $bytes[] = ord($k[$i]);
    }
    echo "Bytes: " . implode(',', $bytes) . "\n";
}

if (isset($_ENV['SENDGRID_API_KEY'])) {
    echo "\n".$_ENV['SENDGRID_API_KEY']."\n";
} else {
    echo "\n";
}

echo "Done.\n";
