<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

// Vérifie la session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Non connecté"]);
    exit;
}

// Vérifie le paramètre
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo json_encode(["success" => false, "error" => "Paramètre user_id invalide"]);
    exit;
}

$user_id = intval($_GET['user_id']);

try {
    // JOIN entre users et profiles
    $stmt = $pdo->prepare("
        SELECT users.id, users.username, profiles.avatar_url, profiles.bio, profiles.status
        FROM users
        LEFT JOIN profiles ON users.id = profiles.user_id
        WHERE users.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(["success" => true, "user" => $user]);
    } else {
        echo json_encode(["success" => false, "error" => "Utilisateur non trouvé"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Erreur DB : " . $e->getMessage()]);
}
