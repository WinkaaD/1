<?php
require_once 'conexion.php'; // Ajusta el path si está en otra carpeta

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=procedimientos_exportados.csv');

$output = fopen('php://output', 'w');

// Encabezados
fputcsv($output, ['N_ORDEN', 'GLOSA', 'PRECIO_FONASA', 'PRECIO_ISAPRE', 'PRECIO_PARTICULAR', 'CATEGORIA']);

// Traer los datos desde la base
$stmt = $conn->query("SELECT N_ORDEN, GLOSA, FONASA, ISAPRE, PARTICULAR, CATEGORIA FROM procedimientos");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
