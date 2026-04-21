<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$id) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'ID de carga requerido']);
    exit;
}

try {
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
        WHERE cb.id_carga = ?
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
