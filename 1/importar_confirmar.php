<?php
session_start();
require_once 'conexion.php';

// Validación CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    die('❌ CSRF token inválido o acceso indebido.');
}

$mensajes = [];
$agregados = 0;
$saltados = 0;

// Generar código único automático
function generarCodigoUnico($conexion) {
    do {
        $codigo = rand(100000, 999999);
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM precios WHERE CODIGO = ?");
        $stmt->bind_param("i", $codigo);
        $stmt->execute();
        $stmt->bind_result($existe);
        $stmt->fetch();
        $stmt->close();
    } while ($existe > 0);
    return $codigo;
}

// Recoger datos de sesión
$datos = $_SESSION['vista_previa'] ?? [];
$nuevasCategorias = $_POST['categorias'] ?? [];

if (empty($datos)) {
    die('❌ No hay datos para importar.');
}

// Iniciar transacción
$conexion->begin_transaction();
try {
    $codigosExistentes = [];
    $glosasExistentes = [];
    $q = $conexion->query("SELECT CODIGO, GLOSA FROM precios");
    while ($row = $q->fetch_assoc()) {
        $codigosExistentes[strtoupper($row['CODIGO'])] = true;
        $glosasExistentes[mb_strtolower($row['GLOSA'])] = true;
    }

    foreach ($datos as $item) {
        $codigo = $item['codigo'];
        $glosa = $item['glosa'];
        $fonasa = $item['fonasa'];
        $isapre = $item['isapre'];
        $particular = $item['particular'];
        $n_orden = $item['n_orden'];
        $categoria = $item['categoria'];

        if ($item['auto_asignar']) {
            $codigo = generarCodigoUnico($conexion);
        }

        // Asignar nueva categoría si corresponde
        if (empty($categoria) && isset($nuevasCategorias[$n_orden])) {
            $categoria = trim($nuevasCategorias[$n_orden]);
        }

        if (!$glosa || isset($codigosExistentes[$codigo]) || isset($glosasExistentes[mb_strtolower($glosa)])) {
            $saltados++;
            continue;
        }

        $stmt = $conexion->prepare("INSERT INTO precios (CODIGO, GLOSA, FONASA, ISAPRE, PARTICULAR, n_orden, CATEGORIA)
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdddis", $codigo, $glosa, $fonasa, $isapre, $particular, $n_orden, $categoria);
        $stmt->execute();
        $stmt->close();

        $codigosExistentes[$codigo] = true;
        $glosasExistentes[mb_strtolower($glosa)] = true;
        $agregados++;
    }

    $conexion->commit();
} catch (Exception $e) {
    $conexion->rollback();
    die("❌ Error durante la importación: " . $e->getMessage());
}
?>

<!-- Resultado visual -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<div class="container mt-5">
    <div class="card p-4 shadow">
        <h3>📄 Resultado de la Importación</h3>
        <?php if (!empty($_SESSION['archivo_nombre'])): ?>
            <div class="alert alert-primary">Archivo procesado: <strong><?= htmlspecialchars($_SESSION['archivo_nombre']) ?></strong></div>
        <?php endif; ?>
        <div class="alert alert-success">
            <strong>✅ Procedimientos agregados:</strong> <?= $agregados ?><br>
            <strong>⚠️ Procedimientos saltados:</strong> <?= $saltados ?>
        </div>
        <div class="text-center mt-4">
            <a href="adm.php" class="btn btn-secondary">🔙 Volver al Panel de Administración</a>
        </div>
    </div>
</div>
