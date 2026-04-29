<?php
require_once("../com/conexion.php");

$data = json_decode(file_get_contents("php://input"));

// Validar campos obligatorios
if (
    empty($data->numero_placa) ||
    empty($data->marca)        ||
    empty($data->modelo)       ||
    empty($data->anio)         ||
    empty($data->id_colonia)
) {
    echo json_encode(["error" => "Campos obligatorios faltantes"]);
    exit;
}

// Verificar que la colonia exista
$checkColonia = $conexion->prepare("SELECT id_colonia FROM colonias WHERE id_colonia = ?");
$checkColonia->bind_param("i", $data->id_colonia);
$checkColonia->execute();
if ($checkColonia->get_result()->num_rows == 0) {
    echo json_encode(["error" => "La colonia no existe"]);
    exit;
}

// Verificar que la placa no esté repetida
$checkPlaca = $conexion->prepare("SELECT id_camion FROM camiones WHERE numero_placa = ?");
$checkPlaca->bind_param("s", $data->numero_placa);
$checkPlaca->execute();
if ($checkPlaca->get_result()->num_rows > 0) {
    echo json_encode(["error" => "Ya existe un camión con esa placa"]);
    exit;
}

// Insertar camión
$sql = "INSERT INTO camiones (numero_placa, marca, modelo, anio, capacidad_kg, id_colonia)
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("sssidi",
    $data->numero_placa,
    $data->marca,
    $data->modelo,
    $data->anio,
    $data->capacidad_kg,  // opcional, puede venir vacío
    $data->id_colonia
);

if ($stmt->execute()) {
    echo json_encode([
        "message"   => "Camión creado correctamente",
        "id_camion" => $conexion->insert_id
    ]);
} else {
    echo json_encode(["error" => "Error al crear camión"]);
}
?>