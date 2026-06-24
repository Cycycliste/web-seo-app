<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'seo_audit_tool');
define('DB_USER', 'root');
define('DB_PASS', '');
define('PAGESPEED_API_KEY', 'AIzaSyALPNseiP9IRNoNR7zGzZJpxFTPXVGkiAE');


try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Simple helper to start session safely
function safe_session_start() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}
