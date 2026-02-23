<?php
$conexion = new mysqli("localhost", "root", "", "precios3");

// Verificamos la conexión
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Ahora sí puedes hacer la consulta:
$resultado = $conexion->query("SELECT * FROM precios");
?>
