<?php
include("conexion.php");

$result = $conexion->query("SHOW COLUMNS FROM precios");

echo "<h2>Columnas de la tabla 'precios'</h2><ul>";
while ($columna = $result->fetch_assoc()) {
    echo "<li>" . $columna['Field'] . "</li>";
}
echo "</ul>";
?>
