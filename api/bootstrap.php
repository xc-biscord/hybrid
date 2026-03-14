<?php

require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json; charset=utf-8');

function jsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireMethod(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== $method) {
        jsonResponse(['success' => false, 'error' => 'Méthode non autorisée'], 405);
    }
}

function getJsonInput(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        jsonResponse(['success' => false, 'error' => 'JSON invalide'], 400);
    }

    return $data;
}

function requireAuthUserId(): int
{
    if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'error' => 'Non authentifié'], 401);
    }

    return (int) $_SESSION['user_id'];
}
