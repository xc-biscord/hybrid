<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'error' => 'Non connecté']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];

$username = isset($data['username']) ? trim($data['username']) : null;
$email = isset($data['email']) ? trim($data['email']) : null;
$new_password = $data['password'] ?? null;
$current_password = $data['current_password'] ?? null;

if (!$username && !$email && !$new_password) {
  echo json_encode(['success' => false, 'error' => 'Aucune donnée à mettre à jour']);
  exit;
}

try {
  if ($username || $email) {
    $fields = [];
    $params = [];

    if ($username) {
      $fields[] = "username = ?";
      $params[] = $username;
    }

    if ($email) {
      $fields[] = "email = ?";
      $params[] = $email;
    }

    $params[] = $user_id;
    $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
    $stmt->execute($params);
  }

  if (!empty($new_password)) {
    if (empty($current_password)) {
      echo json_encode(['success' => false, 'error' => 'Mot de passe actuel requis']);
      exit;
    }

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($current_password, $user['password_hash'])) {
      echo json_encode(['success' => false, 'error' => 'Mot de passe actuel incorrect']);
      exit;
    }

    $hash = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$hash, $user_id]);
  }

  echo json_encode(['success' => true]);

} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => 'Erreur SQL',
    'debug' => $e->getMessage()
  ]);
}
