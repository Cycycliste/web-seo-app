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
    error_log('DB connect failed: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// Simple helper to start session safely
function safe_session_start() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Add a column to a table if it does not already exist.
 *
 * Lets fresh installs and existing instances stay in sync without forcing the
 * single admin to run schema.sql again after a pull. Uses information_schema so
 * it works on every MySQL/MariaDB version we support (no IF NOT EXISTS needed).
 *
 * @param PDO    $pdo
 * @param string $table
 * @param string $column
 * @param string $definition E.g. "TIMESTAMP NULL DEFAULT NULL".
 * @return void
 */
function ensure_column(PDO $pdo, $table, $column, $definition) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->execute([$table, $column]);
    if ((int)$stmt->fetchColumn() === 0) {
        // $table/$column come from server-side constants in this codebase, not user input.
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

// Bootstrap migrations — add new columns the running app expects.
try {
    ensure_column($pdo, 'audits', 'share_token_expires_at', 'TIMESTAMP NULL DEFAULT NULL');
    ensure_column($pdo, 'audits', 'share_token_revoked_at', 'TIMESTAMP NULL DEFAULT NULL');
} catch (PDOException $e) {
    error_log('Schema migration failed: ' . $e->getMessage());
}
