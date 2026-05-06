<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/db.php';

try {
    $stmt = $pdo->query("
        SELECT id_colonia, nombre, descripcion, tarifa_mensual, activo
        FROM colonias
        WHERE activo = 1
        ORDER BY nombre ASC
    ");
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
