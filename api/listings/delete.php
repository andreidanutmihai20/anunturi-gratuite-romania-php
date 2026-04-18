<?php
$userId = requireAuth();
$id     = $_GET['id'] ?? '';

try {
    $objectId = new \MongoDB\BSON\ObjectId($id);
} catch (\Exception $e) {
    jsonError('ID anunt invalid.', 400);
}

$db      = getDB();
$listing = $db->listings->findOne(['_id' => $objectId]);

if (!$listing) {
    jsonError('Anuntul nu a fost gasit.', 404);
}

if ((string) $listing['sellerId'] !== $userId) {
    jsonError('Nu esti proprietarul acestui anunt.', 403);
}

$db->listings->deleteOne(['_id' => $objectId]);

jsonSuccess(['message' => 'Anuntul a fost sters cu succes.']);
