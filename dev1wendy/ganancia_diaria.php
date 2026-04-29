<?php
require_once("../com/conexion.php");

// Si no mandan fecha, usa hoy
$fecha = !empty($_GET["fecha"]) ? $_GET["fecha"] : date("Y-m-d");

// 1. Ingresos del día — suma de pagos cobrados ese día
$sqlPagos = "SELECT COALESCE(SUM(monto), 0) AS ingresos_dia
             FROM pagos
             WHERE fecha_pago = ?";
$stmtPagos = $conexion->prepare($sqlPagos);
$stmtPagos->bind_param("s", $fecha);
$stmtPagos->execute();
$ingresos = $stmtPagos->get_result()->fetch_assoc()["ingresos_dia"];

// 2. Gasto en combustible ese día — suma de costo_total de todas las cargas
$sqlComb = "SELECT COALESCE(SUM(costo_total), 0) AS gasto_combustible
            FROM combustible
            WHERE fecha = ?";
$stmtComb = $conexion->prepare($sqlComb);
$stmtComb->bind_param("s", $fecha);
$stmtComb->execute();
$gasto = $stmtComb->get_result()->fetch_assoc()["gasto_combustible"];

// 3. Ganancia real = ingresos - gasto combustible
$ganancia = $ingresos - $gasto;

echo json_encode([
    "fecha"             => $fecha,
    "ingresos_brutos"   => round($ingresos, 2),
    "gasto_combustible" => round($gasto, 2),
    "ganancia_real"     => round($ganancia, 2),
    "estado"            => $ganancia >= 0 ? "GANANCIA" : "PÉRDIDA"
]);
?>