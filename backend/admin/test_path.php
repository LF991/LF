<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getDB();
    if (!$pdo) {
        echo json_encode(['error' => 'DB connection failed']);
        exit;
    }
    echo json_encode(['success' => true, 'message' => 'Path works correctly']);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

