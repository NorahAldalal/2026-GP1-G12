<?php

// ================================================================
//  SIRAJ — Logout
//  Destroys the session completely and redirects to the welcome page.
//  Also clears the session cookie from the browser.
// ================================================================

require_once __DIR__ . '/includes/security.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session data
$_SESSION = [];

// Delete the session cookie from the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// Send user to the welcome page
header('Location: index.php');
exit;
