<?php
function requireAuth(): string {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        jsonError('Autentificare necesara. Token lipsa.', 401);
    }

    $token = substr($authHeader, 7);
    $payload = verifyToken($token);

    if (!$payload || empty($payload['userId'])) {
        jsonError('Token invalid sau expirat.', 401);
    }

    return $payload['userId'];
}

function optionalAuth(): ?string {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        return null;
    }

    $token = substr($authHeader, 7);
    $payload = verifyToken($token);
    return $payload['userId'] ?? null;
}
