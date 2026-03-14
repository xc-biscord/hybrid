<?php

require_once __DIR__ . '/../config/config.php';
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}
?>