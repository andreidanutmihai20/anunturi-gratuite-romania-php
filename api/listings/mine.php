<?php
$userId = requireAuth();
$db     = getDB();

$cursor = $db->listings->find(
    ['sellerId' => new \MongoDB\BSON\ObjectId($userId)],
    ['sort' => ['createdAt' => -1]]
);

$listings = [];
foreach ($cursor as $doc) {
    $listings[] = [
        'id'          => (string) $doc['_id'],
        'title'       => $doc['title'],
        'price'       => $doc['price'],
        'currency'    => $doc['currency'] ?? 'RON',
        'category'    => $doc['category'],
        'images'      => $doc['images'] ?? [],
        'location'    => $doc['location'] ?? [],
        'status'      => $doc['status'] ?? 'activ',
        'views'       => $doc['views'] ?? 0,
        'condition'   => $doc['condition'] ?? 'folosit',
        'createdAt'   => isset($doc['createdAt'])
            ? $doc['createdAt']->toDateTime()->format('c') : null,
    ];
}

jsonSuccess($listings);
