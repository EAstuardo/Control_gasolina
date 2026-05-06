<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/db.php';

$idCamion = isset($_GET['id_camion']) ? (int) $_GET['id_camion'] : null;

try {
    $where = $idCamion ? "WHERE cb.id_camion = $idCamion" : "";

    $stmt = $pdo->query("
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
        $where
        ORDER BY cb.fecha DESC, cb.created_at DESC
    ");

    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
