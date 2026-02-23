<?php
session_start();
require_once 'conexion.php';

// Validación CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    die('❌ CSRF token inválido o acceso indebido.');
}

$vistaPrevia = [];
$nuevosOrdenes = [];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo']['tmp_name'];
    $nombreArchivo = $_FILES['archivo']['name'];

    if (!in_array(mime_content_type($archivo), ['text/plain', 'text/csv', 'application/vnd.ms-excel'])) {
        die('❌ Tipo de archivo inválido.');
    }

    // Cargar datos actuales
    $codigosExistentes = [];
    $glosasExistentes = [];
    $nOrdenToCategoria = [];

    $q = $conexion->query("SELECT CODIGO, GLOSA, n_orden, CATEGORIA FROM precios");
    while ($row = $q->fetch_assoc()) {
        $codigosExistentes[strtoupper($row['CODIGO'])] = true;
        $glosasExistentes[mb_strtolower($row['GLOSA'])] = true;
        $nOrdenToCategoria[intval($row['n_orden'])] = $row['CATEGORIA'];
    }

    $primera = true;
    if (($handle = fopen($archivo, 'r')) !== false) {
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            if ($primera) { $primera = false; continue; }
            if (count($data) < 6) continue;

            $codigo = trim($data[0]);
            $glosa = trim($data[1]);
            $fonasa = str_replace(['.', ',', ' '], '', $data[2] ?? '0');
            $isapre = str_replace(['.', ',', ' '], '', $data[3] ?? '0');
            $particular = str_replace(['.', ',', ' '], '', $data[4] ?? '0');
            $n_orden = intval($data[5]);
            $categoria_csv = trim($data[6] ?? '');

            $codigo = $codigo === '' ? null : strtoupper($codigo);
            $auto_asignar = $codigo === null;

            $duplicado_codigo = !$auto_asignar && isset($codigosExistentes[$codigo]);
            $duplicado_glosa = isset($glosasExistentes[mb_strtolower($glosa)]);

            if (!ctype_digit($fonasa) || !ctype_digit($isapre) || !ctype_digit($particular)) {
                $errores[] = "❌ Error en línea con glosa '$glosa': precios deben ser numéricos.";
                continue;
            }

            if (!$glosa || preg_match('/^[\s]*$/', $glosa)) {
                $errores[] = "❌ Glosa vacía o inválida en línea con código '$codigo'.";
                continue;
            }

            if ($duplicado_codigo) {
                $errores[] = "❌ Código duplicado '$codigo'.";
                continue;
            }

            if ($duplicado_glosa) {
                $errores[] = "❌ Glosa duplicada '$glosa'.";
                continue;
            }

            if (isset($nOrdenToCategoria[$n_orden])) {
                // Ya existe ese n_orden
                if ($categoria_csv !== '' && $categoria_csv !== $nOrdenToCategoria[$n_orden]) {
                    $errores[] = "❌ Conflicto: n_orden $n_orden ya está asociado a categoría '{$nOrdenToCategoria[$n_orden]}', no a '$categoria_csv'.";
                    continue;
                }
                $categoria = $nOrdenToCategoria[$n_orden];
            } else {
                // n_orden nuevo
                if ($categoria_csv !== '') {
                    $nuevosOrdenes[$n_orden] = $categoria_csv;
                    $categoria = $categoria_csv;
                } else {
                    $nuevosOrdenes[$n_orden] = '';
                    $categoria = ''; // Se llenará después
                }
            }

            $vistaPrevia[] = [
                'codigo' => $codigo,
                'glosa' => $glosa,
                'fonasa' => intval($fonasa),
                'isapre' => intval($isapre),
                'particular' => intval($particular),
                'n_orden' => $n_orden,
                'categoria' => $categoria,
                'auto_asignar' => $auto_asignar
            ];
        }
        fclose($handle);
    }

    if (!empty($errores)) {
        echo "<div class='alert alert-danger'><strong>Errores encontrados:</strong><ul>";
        foreach ($errores as $e) {
            echo "<li>" . htmlspecialchars($e) . "</li>";
        }
        echo "</ul></div>";
        exit;
    }

    $_SESSION['vista_previa'] = $vistaPrevia;
    $_SESSION['nuevos_ordenes'] = $nuevosOrdenes;
    $_SESSION['archivo_nombre'] = $nombreArchivo;
} else {
    echo "<div class='alert alert-danger'>❌ No se recibió ningún archivo válido.</div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Importación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
    <h2 class="mb-4">✅ Archivo procesado correctamente</h2>
    <p>Se encontraron <?= count($vistaPrevia) ?> procedimientos válidos.</p>

    <?php if (!empty($nuevosOrdenes)): ?>
        <div class="alert alert-warning">
            <strong>Se detectaron n_orden nuevos. Por favor, ingresa sus categorías antes de confirmar:</strong>
        </div>
        <form method="POST" action="importar_confirmar.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <table class="table table-bordered">
                <thead>
                    <tr><th>n_orden</th><th>Categoría</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($nuevosOrdenes as $orden => $cat): ?>
                        <tr>
                            <td><?= $orden ?></td>
                            <td><input type="text" name="categorias[<?= $orden ?>]" value="<?= htmlspecialchars($cat) ?>" class="form-control" required></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-success">✅ Confirmar Importación</button>
        </form>
    <?php else: ?>
        <form method="POST" action="importar_confirmar.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <button type="submit" class="btn btn-success">✅ Confirmar Importación</button>
        </form>
    <?php endif; ?>
</body>
</html>
