<?php
include("conexion.php");

$q = $_GET['q'] ?? '';
$prevision = $_GET['prevision'] ?? 'ISAPRE';

header('Content-Type: application/json');

if (strlen($q) < 2) {
  echo json_encode([]);
  exit;
}

$q = "%$q%";
$stmt = $conexion->prepare("SELECT GLOSA, CATEGORIA FROM precios WHERE GLOSA LIKE ? OR CODIGO LIKE ? LIMIT 20");
$stmt->bind_param("ss", $q, $q);
$stmt->execute();
$result = $stmt->get_result();

$datos = [];

while ($row = $result->fetch_assoc()) {
  $archivo = ($prevision === 'ISAPRE') ? 'reis.php' :
             (($prevision === 'PARTICULAR') ? 'repa.php' : 'refo.php');
  $categoriaURL = urlencode($row['CATEGORIA']);
  $link = "$archivo?categoria=$categoriaURL&prevision=$prevision";

  $datos[] = [
    'GLOSA' => $row['GLOSA'],
    'CATEGORIA' => $row['CATEGORIA'],
    'link' => $link
  ];
}

echo json_encode($datos);
