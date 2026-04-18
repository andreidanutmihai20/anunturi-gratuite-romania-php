<?php
// Router simplu pentru PHP built-in server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Daca fisierul exista pe disk, serveste-l direct
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Toate requesturile merg la index.php
require __DIR__ . '/index.php';
