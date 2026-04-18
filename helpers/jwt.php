<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function getJwtSecret(): string {
    $secret = getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? null);
    if (!$secret) {
        jsonError('JWT_SECRET nu este configurat.', 500);
    }
    return $secret;
}

function generateToken(string $userId): string {
    $payload = [
        'iss' => 'olx-clone',
        'iat' => time(),
        'exp' => time() + (7 * 24 * 60 * 60), // 7 zile
        'userId' => $userId,
    ];
    return JWT::encode($payload, getJwtSecret(), 'HS256');
}

function verifyToken(string $token): ?array {
    try {
        $decoded = JWT::decode($token, new Key(getJwtSecret(), 'HS256'));
        return (array) $decoded;
    } catch (\Exception $e) {
        return null;
    }
}
