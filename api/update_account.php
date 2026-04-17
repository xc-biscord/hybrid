<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Support/Autoload.php';

use App\Support\ApiKernel;

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw === false ? '' : $raw, true);
$kernel = new ApiKernel($pdo);
$result = $kernel->accountController()->update((int) $_SESSION['user_id'], is_array($payload) ? $payload : null);

http_response_code((int) ($result['statusCode'] ?? 200));
echo json_encode($result['payload'] ?? ['success' => false, 'error' => 'Erreur serveur']);
