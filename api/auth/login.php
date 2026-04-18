<?php
$body = getRequestBody();

$email    = strtolower(trim($body['email'] ?? ''));
$password = $body['password'] ?? '';

if (!$email || !$password) {
    jsonError('Email-ul si parola sunt obligatorii.');
}

$db   = getDB();
$user = $db->users->findOne(['email' => $email]);

if (!$user || !password_verify($password, $user['password'])) {
    jsonError('Email sau parola incorecte.', 401);
}

$userId = (string) $user['_id'];
$token  = generateToken($userId);

jsonSuccess([
    'token' => $token,
    'user'  => [
        'id'    => $userId,
        'name'  => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'] ?? '',
    ],
]);
