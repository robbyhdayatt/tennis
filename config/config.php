<?php
// Application Configuration
session_start();

// Base URL - sesuaikan dengan path aplikasi Anda
define('BASE_URL', 'http://localhost/tennis/');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting (set ke 0 di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database
require_once __DIR__ . '/database.php';
// Include CSRF protection
require_once __DIR__ . '/../includes/csrf.php';
?>