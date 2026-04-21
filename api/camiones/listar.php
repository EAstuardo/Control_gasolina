<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/db.php';

try {
    $stmt = $pdo->query("
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
        ORDER BY c.created_at DESC
    ");

    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
