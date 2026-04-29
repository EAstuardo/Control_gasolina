<?php
require_once("../com/conexion.php");

$data = json_decode(file_get_contents("php://input"));

// Validar campos obligatorios para evitar errores de inserción
if (
    empty($data->id_camion)   ||
    empty($data->id_usuario)  ||
    empty($data->fecha)       ||
    !isset($data->litros)     ||
    !isset($data->precio_litro) ||
    !isset($data->kilometraje)
) {
    echo json_encode(["error" => "Campos obligatorios faltantes"]);
    exit;
}

// validar si tiene litros mayor a 0
if ($data->litros <= 0) {
    echo json_encode(["error" => "Los litros deben ser mayores a 0"]);
    exit;
}
if ($data->precio_litro <= 0) {// validar si el precio por litro es mayor a 0
    echo json_encode(["error" => "El precio por litro debe ser mayor a 0"]);
    exit;
}

// validación de dato de kilometraje, no puede ser negativo
if ($data->kilometraje < 0) {
    echo json_encode(["error" => "El kilometraje no puede ser negativo"]);
    exit;
}

// Verificar que el camión exista
$checkCamion = $conexion->prepare("SELECT id_camion FROM camiones WHERE id_camion = ?");
$checkCamion->bind_param("i", $data->id_camion);
$checkCamion->execute();
if ($checkCamion->get_result()->num_rows == 0) {
    echo json_encode(["error" => "El camión no existe"]);
    exit;
}

// Verificar que el usuario exista
$checkUsuario = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = ?");
$checkUsuario->bind_param("i", $data->id_usuario);
$checkUsuario->execute();
if ($checkUsuario->get_result()->num_rows == 0) {
    echo json_encode(["error" => "El usuario no existe"]);
    exit;
}

// Calcular costo total
$costo_total = $data->litros * $data->precio_litro;

// el ripo de combustible es opcional, por defecto diesel
$tipo = !empty($data->tipo_combustible) ? $data->tipo_combustible : "DIESEL";
$observaciones = !empty($data->observaciones) ? $data->observaciones : null;

// Insertar carga de combustible
$sql = "INSERT INTO combustible 
            (id_camion, fecha, litros, kilometraje, precio_litro, costo_total, tipo_combustible, id_usuario, observaciones)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("isdiddsis",
    $data->id_camion,
    $data->fecha,
    $data->litros,
    $data->kilometraje,
    $data->precio_litro,
    $costo_total,
    $tipo,
    $data->id_usuario,
    $observaciones
);

if ($stmt->execute()) {
    echo json_encode([
        "message"      => "Carga de combustible registrada correctamente",
        "costo_total"  => $costo_total
    ]);
} else {
    echo json_encode(["error" => "Error al registrar combustible"]);
}
?>