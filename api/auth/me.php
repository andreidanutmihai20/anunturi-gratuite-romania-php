<?php
$userId = requireAuth();

$db   = getDB();
$user = $db->users->findOne(['_id' => new \MongoDB\BSON\ObjectId($userId)]);

if (!$user) {
    jsonError('Utilizatorul nu a fost gasit.', 404);
}

jsonSuccess([
    'id'        => (string) $user['_id'],
    'name'      => $user['name'],
    'email'     => $user['email'],
    'phone'     => $user['phone'] ?? '',
    'avatar'    => $user['avatar'] ?? null,
    'createdAt' => isset($user['createdAt'])
        ? $user['createdAt']->toDateTime()->format('c')
        : null,
]);
