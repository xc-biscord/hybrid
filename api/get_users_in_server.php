<?php
require_once __DIR__ . '/../config/config.php';
session_start();

$server_id = $_GET['server_id'] ?? null;

if (!isset($_SESSION['user_id']) || !$server_id) {
    echo json_encode(['success' => false, 'error' => 'Requête invalide']);
    exit;
}

// récupère la liste des P1 globaux
$stmt = $pdo->query("SELECT user_id FROM global_permissions WHERE permission_level = 'P1'");
$p1Users = $stmt->fetchAll(PDO::FETCH_COLUMN); // tableau d'ID

// récupère les membres du serveur
$stmt = $pdo->prepare("
    SELECT u.id, u.username, m.role
    FROM server_members m
    JOIN users u ON u.id = m.user_id
    WHERE m.server_id = ?
");
$stmt->execute([$server_id]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Override du rôle si le membre est P1 global
foreach ($users as &$u) {
    if (in_array($u['id'], $p1Users)) {
        $u['role'] = 'P1';
    }
}
unset($u); // sécurité mémoire suggérée par chatgpt

echo json_encode(['success' => true, 'users' => $users]);
