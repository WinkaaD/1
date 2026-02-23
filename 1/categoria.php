<?php
include("conexion.php");

// Obtener la previsión desde la URL
$prevision = $_GET['prevision'] ?? 'ISAPRE';
$prevision = $_GET['prevision'] ?? 'FONASA';
$prevision = $_GET['prevision'] ?? 'PARTICULAR';
$prevision = strtoupper(trim($prevision));

// Consultar las categorías únicas existentes en la base de datos
$sql = "SELECT DISTINCT CATEGORIA FROM precios ORDER BY CATEGORIA ASC";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Categorías - <?= htmlspecialchars($prevision) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #81D1BC;
      padding: 40px;
      font-family: Arial, sans-serif;
    }
    h1 {
      text-align: center;
      margin-bottom: 40px;
    }
    .contenedor-botones {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 15px;
    }
    .btn-categoria {
      font-size: 20px;
      padding: 15px 25px;
      background-color: #028080;
      color: white;
      border: none;
      border-radius: 12px;
      text-decoration: none;
      box-shadow: 2px 2px 10px rgba(0,0,0,0.2);
      transition: background-color 0.3s ease;
    }
    .btn-categoria:hover {
      background-color: #015c5c;
    }
    .volver {
      display: block;
      margin: 30px auto 0;
      width: fit-content;
    }
  </style>
</head>
<body>

<h1>Selecciona una categoría - <?= htmlspecialchars($prevision) ?></h1>

<div class="contenedor-botones">
  <?php if ($resultado && $resultado->num_rows > 0): ?>
    <?php while ($fila = $resultado->fetch_assoc()): 
      $categoria = htmlspecialchars($fila['CATEGORIA']);
    ?>
      <a href="reis.php?categoria=<?= urlencode($categoria) ?>&prevision=<?= urlencode($prevision) ?>" class="btn-categoria"><?= $categoria ?></a>
	  <a href="refo.php?categoria=<?= urlencode($categoria) ?>&prevision=<?= urlencode($prevision) ?>" class="btn-categoria"><?= $categoria ?></a>
	  <a href="repa.php?categoria=<?= urlencode($categoria) ?>&prevision=<?= urlencode($prevision) ?>" class="btn-categoria"><?= $categoria ?></a>
    <?php endwhile; ?>
  <?php else: ?>
    <p class="text-center">No se encontraron categorías en la base de datos.</p>
  <?php endif; ?>
</div>

<a href="1.php" class="btn btn-outline-secondary volver">← Volver al inicio</a>

</body>
</html>
