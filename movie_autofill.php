<?php
require_once 'config.php';

require_role(['admin', 'editor']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
    exit;
}

require_csrf();

$title = trim((string)($_POST['title'] ?? ''));
$year  = trim((string)($_POST['year'] ?? ''));
if ($year === '') $year = null;

// call + map
$result = omdb_autofill_movie($title, $year);

if (!($result['ok'] ?? false)) {
    http_response_code(400);
}

echo json_encode($result);

