<?php
require_once __DIR__ . '/../vendor/autoload.php';

function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Incarca .env (local development)
loadEnv(__DIR__ . '/../.env');

function getDB(): \MongoDB\Database {
    static $db = null;
    if ($db !== null) return $db;

    $uri = getenv('MONGODB_URI') ?: $_ENV['MONGODB_URI'] ?? null;
    $dbName = getenv('MONGODB_DB') ?: $_ENV['MONGODB_DB'] ?? 'olxclone';

    if (!$uri) {
        http_response_code(500);
        echo json_encode(['error' => 'MONGODB_URI nu este setat in variabilele de mediu.']);
        exit;
    }

    try {
        $client = new \MongoDB\Client($uri);
        $db = $client->selectDatabase($dbName);
        return $db;
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Eroare conexiune MongoDB: ' . $e->getMessage()]);
        exit;
    }
}
