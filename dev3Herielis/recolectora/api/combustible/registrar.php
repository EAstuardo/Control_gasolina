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

// Acepta tanto form-data como JSON
$idCamion  = isset($_POST['id_camion'])    ? (int)   $_POST['id_camion']    : 0;
$fecha     = isset($_POST['fecha'])        ? trim($_POST['fecha'])           : '';
$litros    = isset($_POST['litros'])       ? (float) $_POST['litros']        : 0;
$precio    = isset($_POST['precio_litro']) ? (float) $_POST['precio_litro']  : 0;
$proveedor = isset($_POST['proveedor'])    ? trim($_POST['proveedor'])        : '';
$notas     = isset($_POST['notas'])        ? trim($_POST['notas'])            : '';
$tipo      = isset($_POST['tipo_combustible']) ? strtoupper(trim($_POST['tipo_combustible'])) : 'DIESEL';

if (!$idCamion || !$fecha || $litros <= 0 || $precio <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Faltan campos obligatorios (camión, fecha, litros, precio)']);
    exit;
}

// Validar tipo
if (!in_array($tipo, ['DIESEL', 'GASOLINA'])) $tipo = 'DIESEL';

// Calcular costo total
$costoTotal = round($litros * $precio, 2);

// Verificar que el camión existe
$check = $pdo->prepare("SELECT id_camion FROM camiones WHERE id_camion = ?");
$check->execute([$idCamion]);
if (!$check->fetch()) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'mensaje' => 'Camión no encontrado']);
    exit;
}

// Observaciones: combina proveedor y notas
$observaciones = trim(($proveedor ? "Proveedor: $proveedor. " : '') . $notas) ?: null;

// id_usuario = 1 por defecto (sin sistema de login aún)
$idUsuario = 1;

// Verificar que existe usuario 1, si no insertar uno temporal
$ucheck = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = 1");
$ucheck->execute();
if (!$ucheck->fetch()) {
    $pdo->exec("INSERT INTO usuarios (id_usuario, nombre, email, password, rol) VALUES (1, 'Admin', 'admin@recolectora.gt', 'temporal', 'ADMIN')");
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO combustible (id_camion, fecha, litros, costo_total, tipo_combustible, id_usuario, observaciones)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$idCamion, $fecha, $litros, $costoTotal, $tipo, $idUsuario, $observaciones]);

    echo json_encode([
        'ok'       => true,
        'mensaje'  => 'Carga registrada correctamente',
        'id_carga' => $pdo->lastInsertId(),
        'total'    => $costoTotal,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
