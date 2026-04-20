<?php

session_start();

require_once 'auth.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT u.username, u.email, p.bio, p.avatar_url, p.status
        FROM users u
        LEFT JOIN profiles p ON u.id = p.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        echo json_encode(['success' => false, 'error' => 'Profil introuvable']);
        exit;
    }

    // vérification du rôle P1
    $stmt = $pdo->prepare("SELECT 1 FROM global_permissions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profile['is_p1'] = $stmt->fetch() !== false;

    echo json_encode(['success' => true, 'profile' => $profile]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur', 'details' => $e->getMessage()]);
}
