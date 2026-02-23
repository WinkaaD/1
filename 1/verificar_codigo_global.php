<?php
include("conexion.php");
header('Content-Type: application/json');

// Obtener parámetros de la URL
$q = trim($_GET['q'] ?? '');
$prevision = strtoupper(trim($_GET['prevision'] ?? ''));

// Validar campos mínimos
if (empty($q) || !in_array($prevision, ['FONASA', 'ISAPRE', 'PARTICULAR'])) {
    echo json_encode([]);
    exit;
}

// Preparar consulta general (por glosa o código)
$stmt = $conexion->prepare("SELECT CODIGO, GLOSA, CATEGORIA, $prevision AS PRECIO FROM precios WHERE GLOSA LIKE ? OR CODIGO LIKE ?");
$like = "%$q%";
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$result = $stmt->get_result();

$datos = [];
while ($row = $result->fetch_assoc()) {
    $datos[] = [
        'CODIGO' => $row['CODIGO'],
        'GLOSA' => $row['GLOSA'],
        'CATEGORIA' => $row['CATEGORIA'],
        'PRECIO' => (float)$row['PRECIO']
    ];
}

// Si no se encontró nada, retornar mensaje opcional
if (empty($datos)) {
    echo json_encode([
        'existe' => false,
        'mensaje' => '❌ No se encontraron resultados.'
    ]);
    exit;
}

echo json_encode($datos);
exit;
?>

