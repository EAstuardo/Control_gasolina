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

// Datos del formulario (llega como multipart/form-data por la imagen)
$idCamion   = isset($_POST['id_camion'])    ? (int)   $_POST['id_camion']    : 0;
$fecha      = isset($_POST['fecha'])        ? trim($_POST['fecha'])          : '';
$litros     = isset($_POST['litros'])       ? (float) $_POST['litros']       : 0;
$precio     = isset($_POST['precio_litro']) ? (float) $_POST['precio_litro'] : 0;
$total      = isset($_POST['total_pago'])   ? (float) $_POST['total_pago']   : 0;
$proveedor  = isset($_POST['proveedor'])    ? trim($_POST['proveedor'])       : null;
$notas      = isset($_POST['notas'])        ? trim($_POST['notas'])           : null;

// Validaciones básicas
if (!$idCamion || !$fecha || $litros <= 0 || $precio <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Faltan campos obligatorios (camión, fecha, litros, precio)']);
    exit;
}

// Recalcular total en el servidor por seguridad
$totalCalculado = round($litros * $precio, 2);

// Verificar que el camión existe
$check = $pdo->prepare("SELECT id_camion FROM camiones WHERE id_camion = ?");
$check->execute([$idCamion]);
if (!$check->fetch()) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'mensaje' => 'Camión no encontrado']);
    exit;
}

// Manejo de imagen del recibo
$rutaImagen = null;

if (isset($_FILES['recibo']) && $_FILES['recibo']['error'] === UPLOAD_ERR_OK) {
    $file     = $_FILES['recibo'];
    $maxSize  = 5 * 1024 * 1024; // 5 MB
    $tiposOk  = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'El archivo supera los 5 MB permitidos']);
        exit;
    }

    // Verificar tipo MIME real (no confiar solo en extensión)
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeReal = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeReal, $tiposOk)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'Tipo de archivo no permitido. Usa JPG, PNG o PDF']);
        exit;
    }

    // Guardar en uploads/recibos/
    $ext      = $mimeReal === 'application/pdf' ? 'pdf' : pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombre   = 'recibo_' . $idCamion . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dirDest  = __DIR__ . '/../../uploads/recibos/';

    if (!is_dir($dirDest)) {
        mkdir($dirDest, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $dirDest . $nombre)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar el archivo']);
        exit;
    }

    $rutaImagen = 'recibos/' . $nombre;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO combustible
            (id_camion, fecha, litros, precio_litro, total_pago, proveedor, notas, ruta_imagen)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $idCamion,
        $fecha,
        $litros,
        $precio,
        $totalCalculado,
        $proveedor ?: null,
        $notas     ?: null,
        $rutaImagen,
    ]);

    echo json_encode([
        'ok'       => true,
        'mensaje'  => 'Carga registrada correctamente',
        'id_carga' => $pdo->lastInsertId(),
        'total'    => $totalCalculado,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
