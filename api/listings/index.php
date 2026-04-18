<?php
$db = getDB();

// ===== GET - Lista anunturi cu filtre =====
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $filter   = ['status' => 'activ'];
    $page     = max(1, (int) ($_GET['page'] ?? 1));
    $limit    = min(48, max(1, (int) ($_GET['limit'] ?? 24)));
    $sort     = $_GET['sort'] ?? 'newest';

    // Filtre optionale
    if (!empty($_GET['category']) && $_GET['category'] !== 'all') {
        $filter['category'] = $_GET['category'];
    }
    if (!empty($_GET['city'])) {
        $filter['location.city'] = new \MongoDB\BSON\Regex(preg_quote($_GET['city']), 'i');
    }
    if (!empty($_GET['condition'])) {
        $filter['condition'] = $_GET['condition'];
    }
    if (!empty($_GET['minPrice']) || !empty($_GET['maxPrice'])) {
        $priceFilter = [];
        if (!empty($_GET['minPrice'])) $priceFilter['$gte'] = (float) $_GET['minPrice'];
        if (!empty($_GET['maxPrice'])) $priceFilter['$lte'] = (float) $_GET['maxPrice'];
        $filter['price'] = $priceFilter;
    }
    if (!empty($_GET['search'])) {
        $regex = new \MongoDB\BSON\Regex(preg_quote($_GET['search']), 'i');
        $filter['$or'] = [
            ['title'       => $regex],
            ['description' => $regex],
        ];
    }

    // Sortare
    $sortOptions = [
        'newest'     => ['createdAt' => -1],
        'oldest'     => ['createdAt' => 1],
        'price_asc'  => ['price' => 1],
        'price_desc' => ['price' => -1],
    ];
    $sortBy = $sortOptions[$sort] ?? ['createdAt' => -1];

    $total    = $db->listings->countDocuments($filter);
    $cursor   = $db->listings->find($filter, [
        'sort'  => $sortBy,
        'skip'  => ($page - 1) * $limit,
        'limit' => $limit,
    ]);

    $listings = [];
    foreach ($cursor as $doc) {
        // Adauga datele vanzatorului
        $seller = null;
        if (!empty($doc['sellerId'])) {
            $sellerDoc = $db->users->findOne(
                ['_id' => $doc['sellerId']],
                ['projection' => ['name' => 1, 'phone' => 1]]
            );
            if ($sellerDoc) {
                $seller = ['id' => (string)$sellerDoc['_id'], 'name' => $sellerDoc['name'], 'phone' => $sellerDoc['phone'] ?? ''];
            }
        }

        $listings[] = [
            'id'          => (string) $doc['_id'],
            'title'       => $doc['title'],
            'description' => mb_substr($doc['description'], 0, 200) . '...',
            'price'       => $doc['price'],
            'currency'    => $doc['currency'] ?? 'RON',
            'negotiable'  => $doc['negotiable'] ?? false,
            'category'    => $doc['category'],
            'subcategory' => $doc['subcategory'] ?? '',
            'images'      => $doc['images'] ?? [],
            'location'    => $doc['location'] ?? [],
            'condition'   => $doc['condition'] ?? 'folosit',
            'seller'      => $seller,
            'views'       => $doc['views'] ?? 0,
            'createdAt'   => isset($doc['createdAt'])
                ? $doc['createdAt']->toDateTime()->format('c') : null,
        ];
    }

    jsonSuccess([
        'listings'    => $listings,
        'total'       => $total,
        'pages'       => (int) ceil($total / $limit),
        'currentPage' => $page,
    ]);
}

// ===== POST - Creeaza anunt nou =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = requireAuth();
    $body   = getRequestBody();

    $title       = trim($body['title'] ?? '');
    $description = trim($body['description'] ?? '');
    $price       = $body['price'] ?? null;
    $category    = trim($body['category'] ?? '');
    $city        = trim($body['location']['city'] ?? '');

    if (!$title || !$description || $price === null || !$category || !$city) {
        jsonError('Titlu, descriere, pret, categorie si oras sunt obligatorii.');
    }
    if (strlen($title) < 5) jsonError('Titlul trebuie sa aiba minim 5 caractere.');
    if (strlen($description) < 10) jsonError('Descrierea trebuie sa aiba minim 10 caractere.');
    if ($price < 0) jsonError('Pretul nu poate fi negativ.');

    $listingId = new \MongoDB\BSON\ObjectId();
    $doc = [
        '_id'         => $listingId,
        'title'       => $title,
        'description' => $description,
        'price'       => (float) $price,
        'currency'    => $body['currency'] ?? 'RON',
        'negotiable'  => (bool) ($body['negotiable'] ?? false),
        'category'    => $category,
        'subcategory' => $body['subcategory'] ?? '',
        'images'      => $body['images'] ?? [],
        'location'    => [
            'city'   => $city,
            'county' => $body['location']['county'] ?? '',
        ],
        'condition'   => $body['condition'] ?? 'folosit',
        'status'      => 'activ',
        'sellerId'    => new \MongoDB\BSON\ObjectId($userId),
        'views'       => 0,
        'createdAt'   => new \MongoDB\BSON\UTCDateTime(),
        'updatedAt'   => new \MongoDB\BSON\UTCDateTime(),
    ];

    $db->listings->insertOne($doc);

    jsonSuccess(['id' => (string) $listingId, ...$body], 201);
}

jsonError('Metoda HTTP nu este permisa.', 405);
