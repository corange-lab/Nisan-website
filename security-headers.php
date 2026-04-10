<?php
// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

// Content Security Policy — allows GTM, GA4, Google Ads tracking
header("Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self' 'unsafe-inline' 'unsafe-eval' " .
        "https://www.googletagmanager.com https://*.googletagmanager.com " .
        "https://www.google-analytics.com https://*.google-analytics.com " .
        "https://www.googleadservices.com https://googleads.g.doubleclick.net " .
        "https://www.google.com https://connect.facebook.net; " .
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
    "font-src 'self' https://fonts.gstatic.com; " .
    "img-src 'self' data: https: blob:; " .
    "frame-src https://www.googletagmanager.com https://td.doubleclick.net; " .
    "connect-src 'self' https://www.google-analytics.com https://*.google-analytics.com " .
        "https://www.googletagmanager.com https://stats.g.doubleclick.net; "
);
?>

