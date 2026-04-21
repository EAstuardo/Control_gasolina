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

// Validaciones
$requeridos = ['numero_placa', 'marca', 'modelo', 'anio', 'id_colonia'];
foreach ($requeridos as $campo) {
    if (empty($body[$campo])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => "El campo '$campo' es obligatorio"]);
        exit;
    }
}

$placa     = strtoupper(trim($body['numero_placa']));
$marca     = trim($body['marca']);
$modelo    = trim($body['modelo']);
$anio      = (int) $body['anio'];
$capacidad = isset($body['capacidad_kg']) && $body['capacidad_kg'] !== '' ? (float) $body['capacidad_kg'] : null;
$estado    = $body['estado'] ?? 'ACTIVO';
$colonia   = (int) $body['id_colonia'];

// Validar estado permitido
$estadosValidos = ['ACTIVO', 'INACTIVO', 'MANTENIMIENTO'];
if (!in_array($estado, $estadosValidos)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Estado no válido']);
    exit;
}

// Verificar placa duplicada
$check = $pdo->prepare("SELECT id_camion FROM camiones WHERE numero_placa = ?");
$check->execute([$placa]);
if ($check->fetch()) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'mensaje' => "La placa $placa ya está registrada"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO camiones (numero_placa, marca, modelo, anio, capacidad_kg, estado, id_colonia)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$placa, $marca, $modelo, $anio, $capacidad, $estado, $colonia]);

    echo json_encode([
        'ok'        => true,
        'mensaje'   => 'Camión registrado correctamente',
        'id_camion' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
