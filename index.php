<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'php'    => PHP_VERSION,
    'mongo'  => extension_loaded('mongodb') ? 'loaded' : 'NOT loaded',
]);
