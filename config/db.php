<?php

// ================================================================
//  SIRAJ — Database & Application Configuration
//  Handles: DB connection, session management, auth helpers
// ================================================================


// ── Database Settings ────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');        // Change this to your MySQL password
define('DB_NAME', 'siraj');
define('SITE_URL', 'http://localhost/Siraj');


// ── Email (SMTP) Settings ────────────────────────────────────────
define('SMTP_HOST',  'smtp.gmail.com');
define('SMTP_PORT',  587);
define('SMTP_USER',  'sirajteam.official@gmail.com');
define('SMTP_PASS',  'zcqa yvsl udaw pnpb');   // 16-character Gmail App Password
define('FROM_EMAIL', 'sirajteam.official@gmail.com');
define('FROM_NAME',  'SIRAJ Lighting');


// ── Database Connection ──────────────────────────────────────────
// Returns a single shared PDO instance (singleton pattern).
// Throws an error and stops execution if connection fails.
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Stop execution and show a safe error message
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }

    return $pdo;
}


// ── Session Helper ───────────────────────────────────────────────
// Starts the session only if it has not already been started.
function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}


// ── Authentication Guards ────────────────────────────────────────
// These functions protect pages by redirecting unauthorized users.

// Requires the logged-in user to be an Admin.
function requireAdmin(): void
{
    startSession();

    $isAdmin = !empty($_SESSION['user_id'])
            && ($_SESSION['user_role'] ?? '') === 'admin';

    if (!$isAdmin) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

// Requires the logged-in user to be an Employee.
function requireEmployee(): void
{
    startSession();

    $isEmployee = !empty($_SESSION['user_id'])
               && ($_SESSION['user_role'] ?? '') === 'employee';

    if (!$isEmployee) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

// Requires any logged-in user (Admin or Employee).
function requireLogin(): void
{
    startSession();

    if (empty($_SESSION['user_id'])) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}


// ── Session State Checkers ───────────────────────────────────────

// Returns true if a user is currently logged in.
function isLoggedIn(): bool
{
    startSession();
    return !empty($_SESSION['user_id']);
}

// Returns true if the logged-in user has the Admin role.
function isAdmin(): bool
{
    startSession();
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

// Returns the current user's ID from the session. Returns 0 if not logged in.
function currentUserId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}
