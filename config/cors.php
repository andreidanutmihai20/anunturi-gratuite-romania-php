<?php
function setCorsHeaders(): void {
    $frontendUrl = getenv('FRONTEND_URL') ?: $_ENV['FRONTEND_URL'] ?? '*';

    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: ' . $frontendUrl);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');

    // Preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
