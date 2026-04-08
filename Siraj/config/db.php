<?php
// ============================================================
//  SIRAJ — Database Configuration
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');           // ← your MySQL password
define('DB_NAME', 'siraj');
define('SITE_URL', 'http://localhost/Siraj');

define('SMTP_HOST',  'smtp.gmail.com');
define('SMTP_PORT',  587);
define('SMTP_USER',  'sirajteam.official@gmail.com');  // ← your Gmail
define('SMTP_PASS',  'zcqa yvsl udaw pnpb');       // ← 16-char App Password
define('FROM_EMAIL', 'sirajteam.official@gmail.com');
define('FROM_NAME',  'SIRAJ Lighting');

// ─── PDO Connection ───────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'DB Error: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// ─── Auth Helpers ─────────────────────────────────────────
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function requireAdmin(): void {
    startSession();
    if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

function requireEmployee(): void {
    startSession();
    if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'employee') {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

function requireLogin(): void {
    startSession();
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

function isLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    startSession();
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

function currentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}
