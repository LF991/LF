<?php
/**
 * Simple test to call the delivery API
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

echo "Testing API...\n";

// Simulate the API call
$_SERVER['REQUEST_METHOD'] = 'GET';

include __DIR__ . '/backend/admin/delivery.php';
?>

