<?php
require_once("../com/conexion.php");

//url php?
if (empty($_GET["id_camion"])) {
    echo json_encode(["error" => "id_camion es obligatorio"]);
    exit;
}

$id_camion = intval($_GET["id_camion"]);

// Verificar que el camión exista y traer sus datos
$checkCamion = $conexion->prepare(
    "SELECT id_camion, numero_placa, marca, modelo, estado 
     FROM camiones WHERE id_camion = ?"
);
$checkCamion->bind_param("i", $id_camion);
$checkCamion->execute();
$resCamion = $checkCamion->get_result();

if ($resCamion->num_rows == 0) {
    echo json_encode(["error" => "El camión no existe"]);
    exit;
}

$camion = $resCamion->fetch_assoc();

// Obtener historial de cargas de ese camión
$sql = "SELECT id_combustible, fecha, litros, kilometraje, precio_litro, 
               costo_total, tipo_combustible, observaciones
        FROM combustible 
        WHERE id_camion = ? 
        ORDER BY fecha DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_camion);
$stmt->execute();
$resultado = $stmt->get_result();

$cargas = [];
while ($row = $resultado->fetch_assoc()) {
    $cargas[] = $row;
}

echo json_encode([
    "camion"       => $camion,
    "total_cargas" => count($cargas),
    "cargas"       => $cargas
]);
?>