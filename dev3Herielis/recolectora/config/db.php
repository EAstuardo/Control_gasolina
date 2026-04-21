<?php
// Configuración de conexión a MySQL
$host     = 'localhost';
$dbname   = 'recolectora_db';
$user     = 'root';
$password = '';          // En XAMPP por defecto está vacío
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'mensaje' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}
