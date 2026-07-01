<?php

define('ENVIRONMENT', 'production');

if (ENVIRONMENT === 'local') {
    define('SITE_URL', 'http://localhost/ITECA');
} else {
    define('SITE_URL', 'https://localmarket.howto.rocks');
}

define('SITE_NAME',    'LocalMarket');
define('SITE_TAGLINE', 'Buy & Sell Locally');
define('SITE_EMAIL',   'support@localmarket.com');
define('SITE_PHONE',   '+27 21 000 0000');
define('SITE_ADDRESS', 'Paarl, Western Cape, South Africa');

if (ENVIRONMENT === 'local') {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'localmarket');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    define('DB_HOST', 'sql308.infinityfree.com');
    define('DB_NAME', 'if0_42315087_localmarket');
    define('DB_USER', 'if0_42315087');
    define('DB_PASS', 'f9NLiQaKaRTI');
}

define('ADMIN_EMAIL_DOMAIN', '@admin.com');

define('UPLOAD_DIR',      'images/products/');
define('UPLOAD_URL',      SITE_URL . '/images/products/');
define('MAX_UPLOAD_MB',   5);
define('MAX_UPLOAD_BYTES', MAX_UPLOAD_MB * 1024 * 1024);

define('SESSION_TIMEOUT', 7200);

if (ENVIRONMENT === 'local') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
    $logs_dir = __DIR__ . '/logs';
    if (!is_dir($logs_dir)) @mkdir($logs_dir, 0755, true);
    ini_set('error_log', $logs_dir . '/error.log');
}