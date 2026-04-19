<?php
$userId = requireAuth();
$body   = getRequestBody();

$updateData = [];
if (isset($body['name']) && trim($body['name'])) {
    $updateData['name'] = trim($body['name']);
}
if (isset($body['phone'])) {
    $updateData['phone'] = trim($body['phone']);
}

if (empty($updateData)) {
    jsonError('Nicio data de actualizat.');
}

$db = getDB();
$db->users->updateOne(
    ['_id' => new \MongoDB\BSON\ObjectId($userId)],
    ['$set' => $updateData]
);

$user = $db->users->findOne(['_id' => new \MongoDB\BSON\ObjectId($userId)]);

jsonSuccess([
    'id'    => (string) $user['_id'],
    'name'  => $user['name'],
    'email' => $user['email'],
    'phone' => $user['phone'] ?? '',
]);
