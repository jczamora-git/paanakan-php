<?php
require_once __DIR__ . '/EmailService.php';

try {
    $s = new EmailService();
    echo "EmailService instantiated OK\n";
} catch (Exception $e) {
    echo "Error instantiating EmailService: " . $e->getMessage() . "\n";
}
