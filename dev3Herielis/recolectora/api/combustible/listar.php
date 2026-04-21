<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/db.php';

// Filtro opcional por camión
$idCamion = isset($_GET['id_camion']) ? (int) $_GET['id_camion'] : null;

try {
    if ($idCamion) {
        $stmt = $pdo->prepare("
            SELECT
                cb.id_carga,
                cb.id_camion,
                cb.fecha,
                cb.litros,
                cb.precio_litro,
                cb.total_pago,
                cb.proveedor,
                cb.notas,
                cb.ruta_imagen,
                cb.created_at
            FROM combustible cb
            WHERE cb.id_camion = ?
            ORDER BY cb.fecha DESC, cb.created_at DESC
        ");
        $stmt->execute([$idCamion]);
    } else {
        $stmt = $pdo->query("
            SELECT
                cb.id_carga,
                cb.id_camion,
                cb.fecha,
                cb.litros,
                cb.precio_litro,
                cb.total_pago,
                cb.proveedor,
                cb.notas,
                cb.ruta_imagen,
                cb.created_at
            FROM combustible cb
            ORDER BY cb.fecha DESC, cb.created_at DESC
        ");
    }

    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
