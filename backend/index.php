<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/routes/api.php';

// Set CORS headers and handle preflight
setCorsHeaders();

// Start session
session_name('studyquest_session');
session_start();

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['REQUEST_URI'];

// Parse request path robustly (handles any casing like /studyquest or /StudyQuest)
$path = $_SERVER['PATH_INFO'] ?? '';
if (!$path) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    
    if ($scriptName && stripos($requestUri, $scriptName) === 0) {
        $path = substr($requestUri, strlen($scriptName));
    } else {
        $pos = stripos($requestUri, 'index.php');
        if ($pos !== false) {
            $path = substr($requestUri, $pos + strlen('index.php'));
        } else {
            $path = $requestUri;
        }
    }
}

$path = '/' . ltrim($path, '/');

// Remove /api prefix if present
if (str_starts_with($path, '/api')) {
    $path = substr($path, 4) ?: '/';
}

// Dispatch
dispatch($method, $path);
