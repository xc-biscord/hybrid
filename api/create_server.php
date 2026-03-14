<?php
session_start();
header('Content-Type: application/json');

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

require_once __DIR__ . '/../config/config.php';

// Récupère les données JSON envoyées
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['nom']) || empty(trim($data['nom']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Nom de serveur requis']);
    exit;
}

$name = trim($data['nom']);
$user_id = $_SESSION['user_id'];

try {
    // Création du serveur
    $stmt = $pdo->prepare("INSERT INTO servers (name, owner_id) VALUES (?, ?)");
    $stmt->execute([$name, $user_id]);

    $server_id = $pdo->lastInsertId();

    // Ajoute le créateur comme membre avec rôle 'P2' (admin du serveur)
    $stmt2 = $pdo->prepare("INSERT INTO server_members (server_id, user_id, role) VALUES (?, ?, 'P2')");
    $stmt2->execute([$server_id, $user_id]);

    echo json_encode([
        'success' => true,
        'server_id' => $server_id
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur serveur : ' . $e->getMessage()
    ]);
}
