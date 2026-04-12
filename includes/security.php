<?php

// ================================================================
//  SIRAJ — Security Headers
//  Include at the TOP of every protected page BEFORE any output.
//  Prevents the browser from caching protected pages,
//  so users cannot navigate back to them after logging out.
// ================================================================

// Tell the browser never to cache this response
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

// Additional security headers
header('X-Frame-Options: DENY');            // Prevents clickjacking
header('X-Content-Type-Options: nosniff'); // Prevents MIME-type sniffing
header('X-XSS-Protection: 1; mode=block'); // Basic XSS protection
