<?php
$DB_HOST = 'localhost';
$DB_NAME = 'biscord_db_tests';
$DB_USER = 'adminweb';
$DB_PASS = 'MazdeoAchaqui';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Connexion à la base de données échouée']));
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
