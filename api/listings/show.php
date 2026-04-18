<?php
$id = $_GET['id'] ?? '';

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

// Incrementeaza numarul de vizualizari
$db->listings->updateOne(
    ['_id' => $objectId],
    ['$inc' => ['views' => 1]]
);

// Adauga datele vanzatorului
$seller = null;
if (!empty($listing['sellerId'])) {
    $sellerDoc = $db->users->findOne(
        ['_id' => $listing['sellerId']],
        ['projection' => ['name' => 1, 'phone' => 1, 'email' => 1, 'createdAt' => 1]]
    );
    if ($sellerDoc) {
        $seller = [
            'id'        => (string) $sellerDoc['_id'],
            'name'      => $sellerDoc['name'],
            'phone'     => $sellerDoc['phone'] ?? '',
            'email'     => $sellerDoc['email'],
            'createdAt' => isset($sellerDoc['createdAt'])
                ? $sellerDoc['createdAt']->toDateTime()->format('c') : null,
        ];
    }
}

jsonSuccess([
    'id'          => (string) $listing['_id'],
    'title'       => $listing['title'],
    'description' => $listing['description'],
    'price'       => $listing['price'],
    'currency'    => $listing['currency'] ?? 'RON',
    'negotiable'  => $listing['negotiable'] ?? false,
    'category'    => $listing['category'],
    'subcategory' => $listing['subcategory'] ?? '',
    'images'      => $listing['images'] ?? [],
    'location'    => $listing['location'] ?? [],
    'condition'   => $listing['condition'] ?? 'folosit',
    'status'      => $listing['status'] ?? 'activ',
    'seller'      => $seller,
    'views'       => ($listing['views'] ?? 0) + 1,
    'createdAt'   => isset($listing['createdAt'])
        ? $listing['createdAt']->toDateTime()->format('c') : null,
]);
