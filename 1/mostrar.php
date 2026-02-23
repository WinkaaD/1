<?php
include("conexion.php");

// Verifica si llegaron los parámetros necesarios
$categoria = $_GET['categoria'] ?? null;
$prevision = $_GET['prevision'] ?? null;

if (!$categoria || !$prevision) {
  die("Faltan parámetros en la URL.");
}

// Consulta con parámetros seguros
$sql = "SELECT glosa, $prevision AS valor FROM tabla_precios WHERE n_orden = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $categoria);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Valores</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      padding-top: 60px;
      font-family: Arial, sans-serif;
    }

    .tabla-precios {
      max-width: 800px;
      margin: auto;
    }

    .logo {
      position: fixed;
      top: 10px;
      right: 10px;
      width: 100px;
    }

    #hora {
      font-size: 30px;
      color: white;
      background-color: #028080;
      padding: 10px 20px;
      border-radius: 15px;
      position: fixed;
      top: 10px;
      left: 50%;
      transform: translateX(-50%);
    }
  </style>
</head>
<body>

<div id="hora"></div>
<script>
  function obtenerHoraChile() {
    const fecha = new Date();
    const opciones = {
      timeZone: "America/Santiago",
      hour12: false,
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    };
    document.getElementById('hora').textContent = fecha.toLocaleTimeString('es-CL', opciones);
  }
  setInterval(obtenerHoraChile, 1000);
  obtenerHoraChile();
</script>

<img class="logo" src="logo.png" alt="Logo">

<div class="tabla-precios mt-5">
  <h2 class="mb-4 text-center">Resultados para: <strong><?php echo ucfirst($prevision); ?></strong></h2>

  <table class="table table-bordered table-hover">
    <thead class="table-success">
      <tr>
        <th>Prestación</th>
        <th>Valor</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $resultado->fetch_assoc()) { ?>
        <tr>
          <td><?php echo $row['glosa']; ?></td>
          <td>$<?php echo number_format($row['valor'], 0, ',', '.'); ?></td>
        </tr>
      <?php } ?>
    </tbody>
  </table>

  <div class="text-center mt-4">
    <a href="index.html" class="btn btn-primary">Volver al inicio</a>
  </div>
</div>

</body>
</html>
