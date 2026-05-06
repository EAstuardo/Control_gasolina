<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$id) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'ID requerido']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            cb.id_combustible           AS id_carga,
            cb.id_camion,
            cb.fecha,
            cb.litros,
            ROUND(cb.costo_total / NULLIF(cb.litros, 0), 2) AS precio_litro,
            cb.costo_total              AS total_pago,
            cb.tipo_combustible         AS proveedor,
            cb.observaciones            AS notas,
            NULL                        AS ruta_imagen,
            cb.created_at,
            cam.numero_placa,
            cam.marca,
            cam.modelo
        FROM combustible cb
        LEFT JOIN camiones cam ON cam.id_camion = cb.id_camion
        WHERE cb.id_combustible = ?
    ");
    $stmt->execute([$id]);
    $carga = $stmt->fetch();

    if (!$carga) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'mensaje' => 'Registro no encontrado']);
        exit;
    }

    echo json_encode($carga);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
