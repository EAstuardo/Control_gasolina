<?php
require_once("../com/conexion.php");

if (empty($_GET["id_camion"])) {
    echo json_encode(["error" => "id_camion es obligatorio"]);
    exit;
}

$id_camion = intval($_GET["id_camion"]);

// Verificar que el camión exista
$checkCamion = $conexion->prepare(
    "SELECT id_camion, numero_placa, marca, modelo 
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

// Calcular estadísticas de consumo
$sql = "SELECT 
            COUNT(*)                AS total_cargas,
            SUM(litros)             AS total_litros,
            AVG(litros)             AS promedio_litros_por_carga,
            SUM(costo_total)        AS gasto_total_combustible,
            AVG(costo_total)        AS gasto_promedio_por_carga,
            AVG(precio_litro)       AS precio_promedio_litro,
            MAX(kilometraje)        AS kilometraje_actual,
            MIN(kilometraje)        AS kilometraje_inicial
        FROM combustible 
        WHERE id_camion = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_camion);
$stmt->execute();
$datos = $stmt->get_result()->fetch_assoc();

if ($datos["total_cargas"] == 0) {
    echo json_encode(["mensaje" => "No hay cargas registradas para este camión"]);
    exit;
}

// Calcular km recorridos totales
$km_recorridos = $datos["kilometraje_actual"] - $datos["kilometraje_inicial"];
$km_por_litro = $datos["total_litros"] > 0 // evitar división por cero
    ? round($km_recorridos / $datos["total_litros"], 2)
    : 0;

echo json_encode([
    "camion"                    => $camion,
    "total_cargas"              => (int)$datos["total_cargas"],
    "total_litros"              => round($datos["total_litros"], 2),
    "promedio_litros_por_carga" => round($datos["promedio_litros_por_carga"], 2),
    "gasto_total_combustible"   => round($datos["gasto_total_combustible"], 2),
    "gasto_promedio_por_carga"  => round($datos["gasto_promedio_por_carga"], 2),
    "precio_promedio_litro"     => round($datos["precio_promedio_litro"], 2),
    "km_recorridos_total"       => $km_recorridos,
    "eficiencia_km_por_litro"   => $km_por_litro
]);
?>