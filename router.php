<?php
// Router pentru PHP built-in server
// Serveste fisierele statice direct, altfel trimite la index.php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Daca fisierul exista fizic (nu e cazul nostru, dar e buna practica)
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Tot traficul merge la index.php
require_once __DIR__ . '/index.php';
