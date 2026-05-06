<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$body = json_decode(file_get_contents('php://input'), true);

if (empty($body['nombre'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'El nombre de la colonia es obligatorio']);
    exit;
}
if (!isset($body['tarifa_mensual']) || $body['tarifa_mensual'] === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'La tarifa mensual es obligatoria']);
    exit;
}

$nombre      = trim($body['nombre']);
$descripcion = isset($body['descripcion']) ? trim($body['descripcion']) : null;
$tarifa      = (float) $body['tarifa_mensual'];

if ($tarifa < 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'La tarifa no puede ser negativa']);
    exit;
}

// Verificar duplicado
$check = $pdo->prepare("SELECT id_colonia FROM colonias WHERE nombre = ?");
$check->execute([$nombre]);
if ($check->fetch()) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'mensaje' => "Ya existe una colonia con el nombre \"$nombre\""]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO colonias (nombre, descripcion, tarifa_mensual)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$nombre, $descripcion, $tarifa]);

    $id = $pdo->lastInsertId();
    echo json_encode([
        'ok'         => true,
        'mensaje'    => 'Colonia registrada correctamente',
        'id_colonia' => $id,
        'nombre'     => $nombre,
        'tarifa_mensual' => $tarifa
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
