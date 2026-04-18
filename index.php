<?php
declare(strict_types=1);

// ============================================================
// HELPERS
// ============================================================
function getEnv(string $key, string $default = ''): string {
    return getenv($key) ?: ($GLOBALS['_ENV'][$key] ?? $default);
}

function jsonOut(mixed $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function err(string $msg, int $code = 400): never {
    jsonOut(['message' => $msg], $code);
}

function body(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

// ============================================================
// CORS
// ============================================================
$origin = getEnv('FRONTEND_URL', '*');
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ============================================================
// JWT (fara library extern)
// ============================================================
function b64e(string $d): string {
    return rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
}
function b64d(string $d): string {
    return base64_decode(strtr($d, '-_', '+/'));
}
function jwtCreate(string $userId): string {
    $secret  = getEnv('JWT_SECRET', 'secret_fallback_32chars_minimum!!');
    $header  = b64e(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = b64e(json_encode(['userId' => $userId, 'iat' => time(), 'exp' => time() + 604800]));
    $sig     = b64e(hash_hmac('sha256', "$header.$payload", $secret, true));
    return "$header.$payload.$sig";
}
function jwtVerify(string $token): ?string {
    $secret = getEnv('JWT_SECRET', 'secret_fallback_32chars_minimum!!');
    $parts  = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$h, $p, $s] = $parts;
    $expected = b64e(hash_hmac('sha256', "$h.$p", $secret, true));
    if (!hash_equals($expected, $s)) return null;
    $data = json_decode(b64d($p), true);
    if (!$data || ($data['exp'] ?? 0) < time()) return null;
    return $data['userId'];
}

// ============================================================
// MONGODB (driver direct, fara library)
// ============================================================
function mgr(): MongoDB\Driver\Manager {
    static $m = null;
    if ($m) return $m;
    $uri = getEnv('MONGODB_URI');
    if (!$uri) err('MONGODB_URI lipseste din Railway Variables.', 500);
    try {
        $m = new MongoDB\Driver\Manager($uri);
    } catch (Exception $e) {
        err('MongoDB connect error: ' . $e->getMessage(), 500);
    }
    return $m;
}
function db(): string { return getEnv('MONGODB_DB', 'olxclone'); }
function col(string $c): string { return db() . '.' . $c; }

function docToArr(mixed $doc): array {
    $a = (array) $doc;
    foreach ($a as $k => $v) {
        if ($v instanceof MongoDB\BSON\ObjectId)    $a[$k] = (string) $v;
        if ($v instanceof MongoDB\BSON\UTCDateTime) $a[$k] = $v->toDateTime()->format('c');
        if ($v instanceof MongoDB\BSON\Document || $v instanceof stdClass)
            $a[$k] = docToArr($v);
    }
    if (isset($a['_id'])) { $a['id'] = $a['_id']; unset($a['_id']); }
    return $a;
}

function findOne(string $c, array $filter): ?array {
    try {
        $cur = mgr()->executeQuery(col($c), new MongoDB\Driver\Query($filter, ['limit' => 1]));
        $res = $cur->toArray();
        return empty($res) ? null : docToArr($res[0]);
    } catch (Exception $e) { err('DB error: ' . $e->getMessage(), 500); }
}

function findMany(string $c, array $filter, array $opts = []): array {
    try {
        $cur = mgr()->executeQuery(col($c), new MongoDB\Driver\Query($filter, $opts));
        return array_map('docToArr', $cur->toArray());
    } catch (Exception $e) { err('DB error: ' . $e->getMessage(), 500); }
}

function countDocs(string $c, array $filter): int {
    try {
        $res = mgr()->executeReadCommand(db(), new MongoDB\Driver\Command(['count' => $c, 'query' => $filter]))->toArray();
        return (int)($res[0]->n ?? 0);
    } catch (Exception $e) { return 0; }
}

function insertOne(string $c, array $doc): string {
    try {
        $bulk = new MongoDB\Driver\BulkWrite;
        $id   = $bulk->insert($doc);
        mgr()->executeBulkWrite(col($c), $bulk);
        return (string) $id;
    } catch (Exception $e) { err('DB insert error: ' . $e->getMessage(), 500); }
}

function updateOne(string $c, array $filter, array $set): void {
    try {
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update($filter, ['$set' => $set]);
        mgr()->executeBulkWrite(col($c), $bulk);
    } catch (Exception $e) { err('DB update error: ' . $e->getMessage(), 500); }
}

function incrOne(string $c, array $filter, array $inc): void {
    try {
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update($filter, ['$inc' => $inc]);
        mgr()->executeBulkWrite(col($c), $bulk);
    } catch (Exception $e) {}
}

function deleteOne(string $c, array $filter): void {
    try {
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->delete($filter, ['limit' => 1]);
        mgr()->executeBulkWrite(col($c), $bulk);
    } catch (Exception $e) { err('DB delete error: ' . $e->getMessage(), 500); }
}

function newId(): MongoDB\BSON\ObjectId    { return new MongoDB\BSON\ObjectId(); }
function toId(string $id): MongoDB\BSON\ObjectId {
    try { return new MongoDB\BSON\ObjectId($id); }
    catch (Exception $e) { err('ID invalid.', 400); }
}
function now(): MongoDB\BSON\UTCDateTime   { return new MongoDB\BSON\UTCDateTime(); }

// ============================================================
// AUTH MIDDLEWARE
// ============================================================
function requireAuth(): string {
    $headers = getallheaders();
    $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!str_starts_with($auth, 'Bearer ')) err('Token lipsa.', 401);
    $uid = jwtVerify(substr($auth, 7));
    if (!$uid) err('Token invalid sau expirat.', 401);
    return $uid;
}

// ============================================================
// ROUTER
// ============================================================
$path   = '/' . trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$method = $_SERVER['REQUEST_METHOD'];
$path   = preg_replace('#/+#', '/', $path);

// ---- HEALTH ----
if ($path === '/' || $path === '/health') {
    jsonOut(['status' => 'ok', 'message' => 'OLX Clone API activ', 'php' => PHP_VERSION]);
}

// ---- AUTH ----
if ($path === '/api/auth/register' && $method === 'POST') {
    $b     = body();
    $name  = trim($b['name'] ?? '');
    $email = strtolower(trim($b['email'] ?? ''));
    $pass  = $b['password'] ?? '';
    $phone = trim($b['phone'] ?? '');
    if (!$name || !$email || !$pass) err('Toate campurile sunt obligatorii.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) err('Email invalid.');
    if (strlen($pass) < 6) err('Parola prea scurta (min 6 caractere).');
    if (findOne('users', ['email' => $email])) err('Email deja inregistrat.', 409);
    $id  = newId();
    insertOne('users', [
        '_id' => $id, 'name' => $name, 'email' => $email,
        'password' => password_hash($pass, PASSWORD_BCRYPT),
        'phone' => $phone, 'createdAt' => now(),
    ]);
    jsonOut(['token' => jwtCreate((string)$id),
             'user'  => ['id' => (string)$id, 'name' => $name, 'email' => $email, 'phone' => $phone]], 201);
}

if ($path === '/api/auth/login' && $method === 'POST') {
    $b     = body();
    $email = strtolower(trim($b['email'] ?? ''));
    $pass  = $b['password'] ?? '';
    if (!$email || !$pass) err('Email si parola sunt obligatorii.');
    $u = findOne('users', ['email' => $email]);
    if (!$u || !password_verify($pass, $u['password'])) err('Email sau parola incorecte.', 401);
    jsonOut(['token' => jwtCreate($u['id']),
             'user'  => ['id' => $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'phone' => $u['phone'] ?? '']]);
}

if ($path === '/api/auth/me' && $method === 'GET') {
    $uid = requireAuth();
    $u   = findOne('users', ['_id' => toId($uid)]);
    if (!$u) err('User negasit.', 404);
    unset($u['password']);
    jsonOut($u);
}

if ($path === '/api/auth/profile' && $method === 'PUT') {
    $uid = requireAuth();
    $b   = body();
    $upd = [];
    if (!empty($b['name']))  $upd['name']  = trim($b['name']);
    if (isset($b['phone']))  $upd['phone'] = trim($b['phone']);
    if ($upd) updateOne('users', ['_id' => toId($uid)], $upd);
    $u = findOne('users', ['_id' => toId($uid)]);
    unset($u['password']);
    jsonOut($u);
}

// ---- CATEGORIES ----
if ($path === '/api/categories' && $method === 'GET') {
    jsonOut([
        ['id'=>'electronice','name'=>'Electronice & Electrocasnice','icon'=>'📱',
         'subcategories'=>['Telefoane','Laptopuri & PC','Tablete','TV & Audio','Electrocasnice','Camere foto','Console jocuri']],
        ['id'=>'auto','name'=>'Auto, Moto & Ambarcatiuni','icon'=>'🚗',
         'subcategories'=>['Autoturisme','Motociclete','Camioane & Utilitare','Piese auto','Barci']],
        ['id'=>'imobiliare','name'=>'Imobiliare','icon'=>'🏠',
         'subcategories'=>['Apartamente vanzare','Apartamente inchiriere','Case vanzare','Case inchiriere','Terenuri']],
        ['id'=>'fashion','name'=>'Moda & Frumusete','icon'=>'👗',
         'subcategories'=>['Haine barbati','Haine femei','Haine copii','Incaltaminte','Accesorii','Cosmetice']],
        ['id'=>'casa','name'=>'Casa & Gradina','icon'=>'🛋️',
         'subcategories'=>['Mobila','Decoratiuni','Scule & Unelte','Gradina','Iluminat']],
        ['id'=>'sport','name'=>'Sport & Timp Liber','icon'=>'⚽',
         'subcategories'=>['Biciclete','Fitness','Camping','Sporturi de apa','Sporturi de iarna']],
        ['id'=>'animale','name'=>'Animale de Companie','icon'=>'🐾',
         'subcategories'=>['Caini','Pisici','Pasari','Pesti','Accesorii animale']],
        ['id'=>'copii','name'=>'Mama & Copil','icon'=>'👶',
         'subcategories'=>['Jucarii','Imbracaminte copii','Carucioare','Mobilier copii']],
        ['id'=>'servicii','name'=>'Servicii & Afaceri','icon'=>'🔧',
         'subcategories'=>['Servicii IT','Constructii','Transport','Curatenie','Meditatii']],
        ['id'=>'agro','name'=>'Agro & Industrie','icon'=>'🌾',
         'subcategories'=>['Utilaje agricole','Animale de ferma','Seminte & Plante','Lemne']],
    ]);
}

// ---- LISTINGS ----

// GET /api/listings/mine
if ($path === '/api/listings/mine' && $method === 'GET') {
    $uid  = requireAuth();
    $docs = findMany('listings',
        ['sellerId' => toId($uid)],
        ['sort' => ['createdAt' => -1]]
    );
    jsonOut($docs);
}

// GET/POST /api/listings
if ($path === '/api/listings') {
    if ($method === 'GET') {
        $filter = ['status' => 'activ'];
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = min(48, max(1, (int)($_GET['limit'] ?? 24)));

        if (!empty($_GET['category']) && $_GET['category'] !== 'all')
            $filter['category'] = $_GET['category'];
        if (!empty($_GET['city']))
            $filter['location.city'] = new MongoDB\BSON\Regex(preg_quote($_GET['city']), 'i');
        if (!empty($_GET['condition']))
            $filter['condition'] = $_GET['condition'];
        if (!empty($_GET['minPrice']) || !empty($_GET['maxPrice'])) {
            $pf = [];
            if (!empty($_GET['minPrice'])) $pf['$gte'] = (float)$_GET['minPrice'];
            if (!empty($_GET['maxPrice'])) $pf['$lte'] = (float)$_GET['maxPrice'];
            $filter['price'] = $pf;
        }
        if (!empty($_GET['search'])) {
            $rx = new MongoDB\BSON\Regex(preg_quote($_GET['search']), 'i');
            $filter['$or'] = [['title' => $rx], ['description' => $rx]];
        }

        $sortMap = [
            'newest'     => ['createdAt' => -1],
            'oldest'     => ['createdAt' =>  1],
            'price_asc'  => ['price'     =>  1],
            'price_desc' => ['price'     => -1],
        ];
        $sort = $sortMap[$_GET['sort'] ?? 'newest'] ?? ['createdAt' => -1];

        $total = countDocs('listings', $filter);
        $docs  = findMany('listings', $filter, [
            'sort'  => $sort,
            'skip'  => ($page - 1) * $limit,
            'limit' => $limit,
        ]);

        // Adauga date vanzator
        foreach ($docs as &$l) {
            if (!empty($l['sellerId'])) {
                $s = findOne('users', ['_id' => toId($l['sellerId'])]);
                $l['seller'] = $s ? ['id'=>$s['id'],'name'=>$s['name'],'phone'=>$s['phone']??''] : null;
            }
            // Scurteaza descrierea
            if (strlen($l['description'] ?? '') > 200)
                $l['description'] = mb_substr($l['description'], 0, 200) . '...';
        }

        jsonOut(['listings' => $docs, 'total' => $total,
                 'pages' => (int)ceil($total / $limit), 'currentPage' => $page]);
    }

    if ($method === 'POST') {
        $uid = requireAuth();
        $b   = body();
        $title = trim($b['title'] ?? '');
        $desc  = trim($b['description'] ?? '');
        $price = $b['price'] ?? null;
        $cat   = trim($b['category'] ?? '');
        $city  = trim($b['location']['city'] ?? '');
        if (!$title || !$desc || $price === null || !$cat || !$city)
            err('Titlu, descriere, pret, categorie si oras sunt obligatorii.');
        $id = newId();
        insertOne('listings', [
            '_id'         => $id,
            'title'       => $title,
            'description' => $desc,
            'price'       => (float)$price,
            'currency'    => $b['currency'] ?? 'RON',
            'negotiable'  => (bool)($b['negotiable'] ?? false),
            'category'    => $cat,
            'subcategory' => $b['subcategory'] ?? '',
            'images'      => $b['images'] ?? [],
            'location'    => ['city' => $city, 'county' => $b['location']['county'] ?? ''],
            'condition'   => $b['condition'] ?? 'folosit',
            'status'      => 'activ',
            'sellerId'    => toId($uid),
            'views'       => 0,
            'createdAt'   => now(),
            'updatedAt'   => now(),
        ]);
        jsonOut(['id' => (string)$id, 'message' => 'Anunt creat.'], 201);
    }
}

// GET/PUT/DELETE /api/listings/{id}
if (preg_match('#^/api/listings/([a-f0-9]{24})$#', $path, $m)) {
    $lid = $m[1];

    if ($method === 'GET') {
        $l = findOne('listings', ['_id' => toId($lid)]);
        if (!$l) err('Anuntul nu a fost gasit.', 404);
        incrOne('listings', ['_id' => toId($lid)], ['views' => 1]);
        if (!empty($l['sellerId'])) {
            $s = findOne('users', ['_id' => toId($l['sellerId'])]);
            $l['seller'] = $s ? ['id'=>$s['id'],'name'=>$s['name'],'phone'=>$s['phone']??'','email'=>$s['email'],'createdAt'=>$s['createdAt']??''] : null;
        }
        $l['views'] = ($l['views'] ?? 0) + 1;
        jsonOut($l);
    }

    if ($method === 'PUT') {
        $uid = requireAuth();
        $l   = findOne('listings', ['_id' => toId($lid)]);
        if (!$l) err('Anuntul nu exista.', 404);
        if ($l['sellerId'] !== $uid) err('Nu esti proprietarul.', 403);
        $b   = body();
        $upd = ['updatedAt' => now()];
        foreach (['title','description','price','currency','negotiable','category',
                  'subcategory','images','location','condition','status'] as $f) {
            if (array_key_exists($f, $b)) $upd[$f] = $b[$f];
        }
        updateOne('listings', ['_id' => toId($lid)], $upd);
        jsonOut(['id' => $lid, 'message' => 'Actualizat.']);
    }

    if ($method === 'DELETE') {
        $uid = requireAuth();
        $l   = findOne('listings', ['_id' => toId($lid)]);
        if (!$l) err('Anuntul nu exista.', 404);
        if ($l['sellerId'] !== $uid) err('Nu esti proprietarul.', 403);
        deleteOne('listings', ['_id' => toId($lid)]);
        jsonOut(['message' => 'Anuntul a fost sters.']);
    }
}

// 404
err('Endpoint inexistent: ' . $path, 404);
