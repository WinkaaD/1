<?php
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    die('❌ CSRF token inválido o acceso indebido.');
}

$mensajes = [];
$agregados = 0;
$saltados = 0;

if (isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo']['tmp_name'];
    $nombreArchivo = $_FILES['archivo']['name'];

    if (!in_array(mime_content_type($archivo), ['text/plain', 'text/csv', 'application/vnd.ms-excel'])) {
        $mensajes[] = ['tipo' => 'danger', 'texto' => "❌ Tipo de archivo inválido. Solo CSV permitido."];
    } else {
        $codigosExistentes = [];
        $glosasExistentes = [];

        $query = $conexion->query("SELECT CODIGO, GLOSA FROM precios");
        while ($row = $query->fetch_assoc()) {
            $codigosExistentes[strtoupper($row['CODIGO'])] = $row['GLOSA'];
            $glosasExistentes[mb_strtolower($row['GLOSA'])] = $row['CODIGO'];
        }

        $nOrdenToCategoria = [];
        $q = $conexion->query("SELECT DISTINCT n_orden, CATEGORIA FROM precios WHERE CATEGORIA IS NOT NULL");
        while ($row = $q->fetch_assoc()) {
            $nOrdenToCategoria[intval($row['n_orden'])] = $row['CATEGORIA'];
        }

        $codigosUsados = array_keys($codigosExistentes);
        $ultimoCodigo = max(array_map('intval', preg_grep('/^\d+$/', $codigosUsados))) ?: 0;

        if (($handle = fopen($archivo, 'r')) !== false) {
            $conexion->begin_transaction();
            try {
                $primeraFila = true;
                while (($datos = fgetcsv($handle, 1000, ',')) !== false) {
                    if ($primeraFila) {
                        $primeraFila = false;
                        continue;
                    }

                    if (count($datos) < 6) {
                        $mensajes[] = ['tipo' => 'warning', 'texto' => "⚠️ Fila incompleta. Saltada."];
                        $saltados++;
                        continue;
                    }

                    $codigo = strtoupper(trim($datos[0] ?? ''));
                    $glosa = trim($datos[1] ?? '');
                    $fonasa = intval(str_replace(['.', ',', ' '], '', $datos[2] ?? '0'));
                    $isapre = intval(str_replace(['.', ',', ' '], '', $datos[3] ?? '0'));
                    $particular = intval(str_replace(['.', ',', ' '], '', $datos[4] ?? '0'));
                    $n_orden = intval($datos[5] ?? 0);

                    // Código duplicado
                    if (isset($codigosExistentes[$codigo])) {
                        $glosaExistente = $codigosExistentes[$codigo];
                        $mensajes[] = ['tipo' => 'warning', 'texto' => "⚠️ Código $codigo ya existe. Pertenece a: '$glosaExistente'."];
                        $saltados++;
                        continue;
                    }

                    // Glosa duplicada
                    if (isset($glosasExistentes[mb_strtolower($glosa)])) {
                        $codigoExistente = $glosasExistentes[mb_strtolower($glosa)];
                        $mensajes[] = ['tipo' => 'warning', 'texto' => "⚠️ Glosa '$glosa' ya existe. Pertenece al código: $codigoExistente."];
                        $saltados++;
                        continue;
                    }

                    // Código vacío: generar automático
                    if (empty($codigo)) {
                        do {
                            $ultimoCodigo++;
                        } while (in_array((string)$ultimoCodigo, $codigosUsados));

                        $codigo = (string)$ultimoCodigo;
                    }

                    // Determinar categoría
                    $categoria = $nOrdenToCategoria[$n_orden] ?? "Sin categoría";

                    $stmt = $conexion->prepare("INSERT INTO precios (CODIGO, GLOSA, FONASA, ISAPRE, PARTICULAR, n_orden, CATEGORIA) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssdddis", $codigo, $glosa, $fonasa, $isapre, $particular, $n_orden, $categoria);
                    $stmt->execute();
                    $stmt->close();

                    $codigosExistentes[$codigo] = $glosa;
                    $glosasExistentes[mb_strtolower($glosa)] = $codigo;
                    $agregados++;
                }

                fclose($handle);
                $conexion->commit();
                $_SESSION['success'] = "✅ Se importaron $agregados procedimientos. $saltados fueron omitidos.";
                header("Location: adm.php");
                exit;
            } catch (Exception $e) {
                $conexion->rollback();
                die("❌ Error en la importación: " . $e->getMessage());
            }
        } else {
            $mensajes[] = ['tipo' => 'danger', 'texto' => "❌ No se pudo abrir el archivo."];
        }
    }
} else {
    $mensajes[] = ['tipo' => 'danger', 'texto' => "❌ No se recibió ningún archivo."];
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="container mt-5">
    <div class="card p-4 shadow">
        <h3>📄 Resultado de la Importación</h3>
        <?php if (!empty($nombreArchivo)): ?>
            <div class="alert alert-primary">Archivo procesado: <strong><?= htmlspecialchars($nombreArchivo) ?></strong></div>
        <?php endif; ?>
        <div class="alert alert-success mt-2">
            <strong>✅ Procedimientos agregados:</strong> <?= $agregados ?><br>
            <strong>⚠️ Procedimientos saltados:</strong> <?= $saltados ?>
        </div>
        <?php foreach ($mensajes as $msg): ?>
            <div class="alert alert-<?= htmlspecialchars($msg['tipo']) ?>"><?= $msg['texto'] ?></div>
        <?php endforeach; ?>
        <div class="text-center mt-4">
            <a href="adm.php" class="btn btn-secondary">🔙 Volver al Panel de Administración</a>
        </div>
    </div>
</div>
