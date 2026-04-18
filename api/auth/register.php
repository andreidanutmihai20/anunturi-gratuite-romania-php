<?php
$body = getRequestBody();

$name     = trim($body['name'] ?? '');
$email    = strtolower(trim($body['email'] ?? ''));
$password = $body['password'] ?? '';
$phone    = trim($body['phone'] ?? '');

// Validare
if (!$name || !$email || !$password) {
    jsonError('Numele, email-ul si parola sunt obligatorii.');
}
if (strlen($name) < 2) {
    jsonError('Numele trebuie sa aiba minim 2 caractere.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Adresa de email nu este valida.');
}
if (strlen($password) < 6) {
    jsonError('Parola trebuie sa aiba minim 6 caractere.');
}

$db    = getDB();
$users = $db->users;

// Verifica daca email-ul exista deja
$existing = $users->findOne(['email' => $email]);
if ($existing) {
    jsonError('Acest email este deja inregistrat.', 409);
}

// Creeaza utilizatorul
$userId = new \MongoDB\BSON\ObjectId();
$doc = [
    '_id'       => $userId,
    'name'      => $name,
    'email'     => $email,
    'password'  => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
    'phone'     => $phone,
    'avatar'    => null,
    'createdAt' => new \MongoDB\BSON\UTCDateTime(),
];

$users->insertOne($doc);

$token = generateToken((string) $userId);

jsonSuccess([
    'token' => $token,
    'user'  => [
        'id'    => (string) $userId,
        'name'  => $name,
        'email' => $email,
        'phone' => $phone,
    ],
], 201);
