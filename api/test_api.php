<?php
/**
 * API test script - açıq xəta mesajlarını göstərir
 * Xəta tapıldıqdan sonra bu faylı silin: api/test_api.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

$results = [];

// 1. Config yüklə
try {
    require_once __DIR__ . '/../config.php';
    $results['config'] = 'OK';
} catch (Throwable $e) {
    $results['config'] = 'XƏTA: ' . $e->getMessage();
    echo json_encode(['success' => false, 'diagnostic' => $results], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// 2. MySQLi bağlantı
try {
    $conn = getDBConnection();
    $results['mysqli'] = 'OK';
    $conn->close();
} catch (Throwable $e) {
    $results['mysqli'] = 'XƏTA: ' . $e->getMessage();
}

// 3. PDO bağlantı
try {
    global $pdo;
    if ($pdo) {
        $pdo->query("SELECT 1");
        $results['pdo'] = 'OK';
    } else {
        $results['pdo'] = 'XƏTA: PDO null (verilənlər bazasına qoşulma uğursuz)';
    }
} catch (Throwable $e) {
    $results['pdo'] = 'XƏTA: ' . $e->getMessage();
}

// 4. Restaurants cədvəli
try {
    $conn = getDBConnection();
    $tableCheck = $conn->query("SHOW TABLES LIKE 'restaurants'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $r = $conn->query("SELECT COUNT(*) as c FROM restaurants");
        $row = $r ? $r->fetch_assoc() : null;
        $results['restaurants_table'] = $row ? ('OK (' . $row['c'] . ' restoran)') : 'OK';
    } else {
        $results['restaurants_table'] = 'OK (cədvəl yaradılacaq)';
    }
    $conn->close();
} catch (Throwable $e) {
    $results['restaurants_table'] = 'XƏTA: ' . $e->getMessage();
}

echo json_encode([
    'success' => !in_array(false, array_map(fn($v) => strpos($v, 'XƏTA') === false, $results)),
    'diagnostic' => $results,
    'db_port' => defined('DB_PORT') ? DB_PORT : 'N/A',
    'environment' => defined('ENVIRONMENT') ? ENVIRONMENT : 'N/A'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
