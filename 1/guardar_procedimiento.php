<?php
include("conexion.php");

$q = $_GET['q'] ?? '';  // El código o glosa que el usuario está buscando
$prevision = $_GET['prevision'] ?? '';

// Consulta a la base de datos con LIKE para coincidencias parciales
$sql = "SELECT CODIGO, GLOSA, $prevision AS PRECIO FROM precios WHERE (GLOSA LIKE ? OR CODIGO LIKE ?) AND CATEGORIA = ?";
$stmt = $conexion->prepare($sql);
$searchTerm = "%$q%";
$stmt->bind_param("sss", $searchTerm, $searchTerm, $prevision);
$stmt->execute();
$result = $stmt->get_result();

$datos = [];
while ($row = $result->fetch_assoc()) {
    $datos[] = [
        'CODIGO' => $row['CODIGO'],
        'GLOSA' => $row['GLOSA'],
        'PRECIO' => (float)$row['PRECIO']
    ];
}

// Retornar los resultados como JSON
echo json_encode($datos);
