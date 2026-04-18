<?php
$userId = requireAuth();
$id     = $_GET['id'] ?? '';
$body   = getRequestBody();

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

// Verifica proprietarul
if ((string) $listing['sellerId'] !== $userId) {
    jsonError('Nu esti proprietarul acestui anunt.', 403);
}

$allowed = ['title', 'description', 'price', 'currency', 'negotiable',
            'category', 'subcategory', 'images', 'location', 'condition', 'status'];

$update = ['updatedAt' => new \MongoDB\BSON\UTCDateTime()];
foreach ($allowed as $field) {
    if (array_key_exists($field, $body)) {
        $update[$field] = $body[$field];
    }
}

$db->listings->updateOne(['_id' => $objectId], ['$set' => $update]);

$updated = $db->listings->findOne(['_id' => $objectId]);

jsonSuccess([
    'id'      => (string) $updated['_id'],
    'title'   => $updated['title'],
    'message' => 'Anuntul a fost actualizat.',
]);
