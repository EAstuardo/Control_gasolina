<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$id) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'ID de camión requerido']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            c.id_camion,
            c.numero_placa,
            c.marca,
            c.modelo,
            c.anio,
            c.capacidad_kg,
            c.estado,
            c.id_colonia,
            c.created_at,
            col.nombre AS colonia_nombre
        FROM camiones c
        LEFT JOIN colonias col ON col.id_colonia = c.id_colonia
        WHERE c.id_camion = ?
    ");
    $stmt->execute([$id]);
    $camion = $stmt->fetch();

    if (!$camion) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'mensaje' => 'Camión no encontrado']);
        exit;
    }

    echo json_encode($camion);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
