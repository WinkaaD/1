<?php
session_start();
require_once 'conexion.php';

// ✅ Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensaje = "";
$procedimientos = [];
$categorias = [];

// Obtener todas las categorías únicas desde la base
$catResult = $conexion->query("SELECT DISTINCT CATEGORIA FROM precios WHERE CATEGORIA IS NOT NULL AND CATEGORIA != ''");
while ($cat = $catResult->fetch_assoc()) {
    $categorias[] = $cat['CATEGORIA'];
}

// Buscar 
// Buscar por código
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['buscar_codigo'])) {
    $codigo = trim($_POST['codigo']);
    if (!empty($codigo)) {
        // Se optimiza la consulta a la base de datos
        $stmt = $conexion->prepare("SELECT * FROM precios WHERE CODIGO LIKE ?");  // Usar LIKE en lugar de = para buscar aproximado
        $stmt->bind_param("s", "%$codigo%"); // Usar % para buscar coincidencias parciales
        $stmt->execute();
        $resultadoBusqueda = $stmt->get_result();
        
        // Mostrar mensaje si no se encuentra nada
        if ($resultadoBusqueda->num_rows == 0) {
            $mensaje = "Código no encontrado.";
        }
    } else {
        $mensaje = "Ingresa solo números para el código.";
    }
}
// Buscar por glosa
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['buscar_glosa'])) {
    $glosa = trim($_POST['glosa']);
    if (!empty($glosa)) {
        // Se optimiza la consulta a la base de datos
        $stmt = $conexion->prepare("SELECT * FROM precios WHERE GLOSA LIKE ?");  // Usar LIKE en lugar de = para buscar aproximado
        $stmt->bind_param("s", "%$glosa%"); // Usar % para buscar coincidencias parciales
        $stmt->execute();
        $resultadoBusqueda = $stmt->get_result();
        
        // Mostrar mensaje si no se encuentra nada
        if ($resultadoBusqueda->num_rows == 0) {
            $mensaje = "Glosa no encontrada.";
        }
    } else {
        $mensaje = "Ingresa una glosa válida.";
    }
}

// Actualizar 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    $glosa_actualizar = strtoupper(trim($_POST["procedimiento"]));
    $nuevo_precio = str_replace(['.', ' '], '', $_POST["nuevo_precio"]);
    $prevision = strtoupper(trim($_POST["prevision"]));

    if (!ctype_digit($nuevo_precio)) {
        $mensaje = "❗ El precio debe ser un número entero.";
    } elseif (!in_array($prevision, ['FONASA', 'ISAPRE', 'PARTICULAR'])) {
        $mensaje = "❗ Previsión no válida.";
    } else {
        $sql = "UPDATE precios SET $prevision = ? WHERE CODIGO = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ds", $nuevo_precio, $glosa_actualizar);
        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Precio actualizado correctamente.";
            header('Location: adm.php');
            exit;
        } else {
            $mensaje = "❌ Error al actualizar: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Agregar nuevo procedimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $codigo = strtoupper(trim($_POST["nuevo_codigo"]));
    $glosa_nueva = trim($_POST["nueva_glosa"]);
    $categoria = trim($_POST["nueva_categoria"]);
    if (empty($categoria) && !empty($_POST['otra_categoria'])) {
        $categoria = trim($_POST['otra_categoria']);
    }

    $fonasa = str_replace(['.', ' '], '', $_POST["nuevo_fonasa"]);
    $isapre = str_replace(['.', ' '], '', $_POST["nuevo_isapre"]);
    $particular = str_replace(['.', ' '], '', $_POST["nuevo_particular"]);

    if (!$codigo || !$glosa_nueva || !$categoria) {
        $mensaje = "❗ Todos los campos son obligatorios.";
    } elseif (!ctype_digit($fonasa) || !ctype_digit($isapre) || !ctype_digit($particular)) {
        $mensaje = "❗ Todos los precios deben ser números enteros.";
    } else {
        $check = $conexion->prepare("SELECT COUNT(*) FROM precios WHERE CODIGO = ?");
        $check->bind_param("s", $codigo);
        $check->execute();
        $check->bind_result($count_codigo);
        $check->fetch();
        $check->close();

        $check2 = $conexion->prepare("SELECT COUNT(*) FROM precios WHERE BINARY GLOSA = ?");
        $check2->bind_param("s", $glosa_nueva);
        $check2->execute();
        $check2->bind_result($count_glosa);
        $check2->fetch();
        $check2->close();

        if ($count_codigo > 0) {
            $mensaje = "❗ El código ingresado ya existe.";
        } elseif ($count_glosa > 0) {
            $mensaje = "❗ La glosa ingresada ya existe.";
        } else {
            $res = $conexion->query("SELECT MAX(CAST(n_orden AS UNSIGNED)) as max_orden FROM precios");
            $row = $res->fetch_assoc();
            $ultimoOrden = intval($row['max_orden']) + 1;

            $sql = "INSERT INTO precios (CODIGO, GLOSA, CATEGORIA, FONASA, ISAPRE, PARTICULAR, n_orden)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssdddi", $codigo, $glosa_nueva, $categoria, $fonasa, $isapre, $particular, $ultimoOrden);

            if ($stmt->execute()) {
                $_SESSION['success'] = "✅ Procedimiento agregado correctamente.";
                header("Location: adm.php");
                exit;
            } else {
                $mensaje = "❌ Error al agregar: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}


// Editar categoría por n_orden
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_categoria'])) {
    $n_orden = intval($_POST['editar_n_orden'] ?? 0);
    $nueva_categoria = trim($_POST['nueva_categoria'] ?? '');

    if ($n_orden >= 0 && $nueva_categoria !== '') {
        $stmt = $conexion->prepare("UPDATE precios SET CATEGORIA = ? WHERE n_orden = ?");
        $stmt->bind_param("si", $nueva_categoria, $n_orden);
        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Categoría actualizada correctamente para n_orden $n_orden.";
            header("Location: adm.php");
            exit;
        } else {
            $mensaje = "❌ Error al actualizar la categoría: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $mensaje = "❗ Debes ingresar un número de orden válido y una nueva categoría.";
    }
}


$sql = "SELECT GLOSA, CODIGO FROM precios ORDER BY GLOSA ASC";
$result = $conexion->query($sql);
while ($row = $result->fetch_assoc()) {
    $procedimientos[] = $row;
}
// 🔴 Eliminar una glosa específica
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_glosa'])) {
    $glosaEliminar = trim($_POST['glosa_eliminar']);
    $stmt = $conexion->prepare("DELETE FROM precios WHERE BINARY GLOSA = ?");
    $stmt->bind_param("s", $glosaEliminar);
    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Glosa eliminada correctamente.";
        header("Location: adm.php");
        exit;
    } else {
        $mensaje = "❌ Error al eliminar la glosa: " . $stmt->error;
    }
    $stmt->close();
}

// 🔴 Eliminar todos los procedimientos de una categoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_categoria_completa'])) {
    $categoriaEliminar = trim($_POST['categoria_eliminar']);
    $stmt = $conexion->prepare("DELETE FROM precios WHERE BINARY CATEGORIA = ?");
    $stmt->bind_param("s", $categoriaEliminar);
    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Categoría eliminada correctamente.";
        header("Location: adm.php");
        exit;
    } else {
        $mensaje = "❌ Error al eliminar la categoría: " . $stmt->error;
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administración de Precios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
body {
    background-color: #48b4a6;
    font-family: 'Segoe UI', sans-serif;
    padding: 40px 15px;
    color: #003333;
}

.card-custom {
    background: #fff;
    border-radius: 16px;
    padding: 15px; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    margin-bottom: 30px;
}

h2 {
    font-weight: bold;
    font-size: 24px;
    color: #004d4d;
    margin-bottom: 20px;
    text-align: center;
}

h5 {
    color: #028080;
    font-weight: bold;
    margin-bottom: 20px;
}

.btn-personalizado {
    background-color: #028080;
    color: white;
    border-radius: 30px;
    padding: 10px 25px;
    font-size: 16px;
    border: none;
    transition: background-color 0.3s ease;
}

.btn-personalizado:hover {
    background-color: #016666;
}

#resultado-busqueda {  
    max-height: 200px; /* Ajusta la altura según prefieras */
    overflow-y: auto; /* Permite el scroll si los resultados exceden el tamaño */
    margin-top: 10px;
    background-color: #fff;
    padding: 15px;
    border-radius: 10px;
}

#btn-limpiar {
    margin-top: 10px; /* Ajusté el espacio superior */
}

#buscar-container {
    max-width: 400px; /* Ajusta el tamaño máximo del contenedor */
    margin: 0 auto; /* Centra el contenedor */
    padding: 15px; /* Ajusté el padding */
}

.row.mb-3 {
    margin-bottom: 10px; /* Reduje el margen entre los campos */
}

.col-md-6 {
    padding-right: 5px;
    padding-left: 5px;
}

.input-group, .row.mb-3 {
    margin-bottom: 10px;
}

input, select {
    padding: 8px; /* Ajusté el padding de los inputs y selects */
    font-size: 14px; /* Tamaño de texto ajustado para ser más compacto */
    width: 100%; /* Asegura que los inputs se ajusten correctamente */
}

h2 {
    font-weight: bold;
    font-size: 24px;
    color: #004d4d;
    margin-bottom: 15px;
    text-align: center;
}

#btn-limpiar {
    margin-top: 10px;
}

    </style>
</head>
<body>

<div class="container">
    <h2>🏥 Buscar, Actualizar y Administrar Procedimientos</h2>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success text-center"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-warning text-center"><?= htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

<div class="card-custom">
    <h5>🔍 Buscar Procedimiento</h5>

    <div class="row mb-3">
    <div class="col-md-6">
        <select id="input-prevision" class="form-control">
            <option value="" disabled selected>Previsión</option>
            <option value="FONASA">FONASA</option>
            <option value="ISAPRE">ISAPRE</option>
            <option value="PARTICULAR">PARTICULAR</option>
        </select>
    </div>
    <div class="col-md-6">
        <input type="text" id="input-codigo-glosa" class="form-control" placeholder="Código o Glosa" autocomplete="off">
    </div>
	<button type="button" id="btn-limpiar" class="btn-personalizado">Limpiar Búsqueda</button>


</div>

<div class="row mb-3">
    <div class="col-md-12">
        <div id="resultado-busqueda"></div>
    </div>
</div>

        <div class="col-md-2 d-grid">
            <!-- Mostrar los resultados de la búsqueda -->
            <div id="resultado-busqueda"></div>
        </div>
    </div>
</div>

    <!-- Actualizar Precio -->
    <div class="card-custom">
        <form method="post">
            <h5>✏️ Actualizar Precio</h5>
            <div class="mb-3">
                <select name="procedimiento" class="form-control" required>
                    <option value="">Seleccionar Procedimiento...</option>
                    <?php foreach ($procedimientos as $opc): ?>
                        <option value="<?= htmlspecialchars($opc['CODIGO']) ?>"><?= htmlspecialchars($opc['GLOSA']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <input type="text" name="nuevo_precio" class="form-control" placeholder="Nuevo Precio" required>
                </div>
                <div class="col-md-6">
                    <select name="prevision" class="form-control" required>
                        <option value="">Previsión</option>
                        <option value="FONASA">FONASA</option>
                        <option value="ISAPRE">ISAPRE</option>
                        <option value="PARTICULAR">PARTICULAR</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="actualizar" class="btn-personalizado">Actualizar</button>
        </form>
    </div>

    <!-- Agregar Procedimiento -->
    <div class="card-custom">
        <form method="post">
            <h5>➕ Agregar Procedimiento Nuevo</h5>
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" name="nuevo_codigo" class="form-control" placeholder="Código" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="nueva_glosa" class="form-control" placeholder="Glosa" required>
                </div>
                <div class="col-md-4">
                    <select name="nueva_categoria" class="form-control">
                        <option value="">Seleccionar categoría existente...</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">O escribe una nueva categoría abajo:</small>
                    <input type="text" name="otra_categoria" class="form-control mt-2" placeholder="Nueva categoría (opcional)">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" name="nuevo_fonasa" class="form-control" placeholder="Precio Fonasa">
                </div>
                <div class="col-md-4">
                    <input type="text" name="nuevo_isapre" class="form-control" placeholder="Precio Isapre">
                </div>
                <div class="col-md-4">
                    <input type="text" name="nuevo_particular" class="form-control" placeholder="Precio Particular">
                </div>
            </div>
            <button type="submit" name="agregar" class="btn-personalizado">Agregar Procedimiento</button>
        </form>
    </div>

    <!-- Importar CSV -->
    <div class="card-custom">
        <form method="POST" enctype="multipart/form-data" action="importar_detectar.php">
            <h5>📥 Importar Procedimientos desde CSV</h5>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <div class="mb-3">
                <input type="file" name="archivo" class="form-control" accept=".csv" required>
            </div>
            <button type="submit" class="btn-personalizado w-100">Importar Archivo</button>
        </form>
    </div>

    <!-- Editar Categoría -->
    <div class="card-custom mt-4">
        <form method="POST">
            <h5 class="mb-4">🛠️ Editar Categoría por Número de Orden</h5>
            <div class="row mb-3 align-items-center">
                <div class="col-md-4">
                    <input type="number" name="editar_n_orden" class="form-control form-control-lg" placeholder="Número de orden" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="nueva_categoria" class="form-control form-control-lg" placeholder="Nueva categoría" required>
                </div>
                <div class="col-md-4 d-grid">
                    <button type="submit" name="actualizar_categoria" class="btn-personalizado btn-lg rounded-pill"
                        onclick="return confirm('⚠️ ¿Estás seguro que deseas cambiar la categoría del número de orden ingresado?')">
                        Actualizar Categoría
                    </button>
                </div>
            </div>
        </form>
    </div>
<!-- 🔴 Eliminar una GLOSA específica -->
<div class="card-custom mt-4">
    <form method="POST">
        <h5 class="mb-4">❌ Eliminar Procedimiento por Glosa</h5>
        <div class="row mb-3 align-items-center">
            <div class="col-md-8">
                <input type="text" name="glosa_eliminar" class="form-control form-control-lg" placeholder="Nombre exacto de la glosa" required>
            </div>
            <div class="col-md-4 d-grid">
                <button type="submit" name="eliminar_glosa" class="btn btn-danger btn-lg rounded-pill"
                    onclick="return confirm('⚠️ ¿Estás seguro de eliminar esta glosa? Esta acción no se puede deshacer.')">
                    Eliminar Glosa
                </button>
            </div>
        </div>
    </form>
</div>

<!-- 🔴 Eliminar toda una CATEGORÍA -->
<div class="card-custom mt-4">
    <form method="POST">
        <h5 class="mb-4">❌ Eliminar Categoría Completa</h5>
        <div class="row mb-3 align-items-center">
            <div class="col-md-8">
                <input type="text" name="categoria_eliminar" class="form-control form-control-lg" placeholder="Nombre exacto de la categoría" required>
            </div>
            <div class="col-md-4 d-grid">
                <button type="submit" name="eliminar_categoria_completa" class="btn btn-danger btn-lg rounded-pill"
                    onclick="return confirm('⚠️ Esto eliminará TODOS los procedimientos de esa categoría. ¿Continuar?')">
                    Eliminar Categoría
                </button>
            </div>
        </div>
    </form>
</div>
</div>
<script>
document.getElementById("input-codigo-glosa").addEventListener("input", function () {
    const codigoGlosa = this.value.trim();
    const prevision = document.getElementById("input-prevision").value;

    // Verificar que se haya seleccionado previsión
    if (!prevision) {
        document.getElementById("resultado-busqueda").innerHTML = `<p style="color:red;">⚠️ Debes seleccionar una previsión para consultar el precio.</p>`;
        return; // Detener la búsqueda si no hay previsión seleccionada
    }

    if (codigoGlosa.length === 0) {
        document.getElementById("resultado-busqueda").innerHTML = '';
        return;
    }

    fetch(`verificar_codigo_global.php?q=${encodeURIComponent(codigoGlosa)}&prevision=${encodeURIComponent(prevision)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al buscar los datos. Intenta nuevamente.');
            }
            return response.json();
        })
        .then(data => {
            let html = '';

            if (data.existe === false) {
                html = `<p style="color:red;">${data.mensaje}</p>`;
            } else if (Array.isArray(data)) {
                html = '<table class="table table-striped"><thead><tr><th>Código</th><th>Glosa</th><th>Precio</th></tr></thead><tbody>';
                data.forEach(item => {
                    html += `<tr>
                                <td>${item.CODIGO}</td>
                                <td>${item.GLOSA}</td>
                                <td>$${parseInt(item.PRECIO).toLocaleString('es-CL')}</td>
                             </tr>`;
                });
                html += '</tbody></table>';
            }

            document.getElementById("resultado-busqueda").innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById("resultado-busqueda").innerHTML = `<p style="color:red;">Error al buscar: ${error}</p>`;
        });
});

// Agregar este código para ejecutar la búsqueda cuando se selecciona una previsión
document.getElementById("input-prevision").addEventListener("change", function() {
    const codigoGlosa = document.getElementById("input-codigo-glosa").value.trim();
    const prevision = this.value; // Obtener la previsión seleccionada

    // Verificar que el campo de código o glosa no esté vacío
    if (codigoGlosa.length > 0 && prevision) {
        // Forzar la búsqueda después de seleccionar la previsión
        fetch(`verificar_codigo_global.php?q=${encodeURIComponent(codigoGlosa)}&prevision=${encodeURIComponent(prevision)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al buscar los datos. Intenta nuevamente.');
                }
                return response.json();
            })
            .then(data => {
                let html = '';

                if (data.existe === false) {
                    html = `<p style="color:red;">${data.mensaje}</p>`;
                } else if (Array.isArray(data)) {
                    html = '<table class="table table-striped"><thead><tr><th>Código</th><th>Glosa</th><th>Precio</th></tr></thead><tbody>';
                    data.forEach(item => {
                        html += `<tr>
                                    <td>${item.CODIGO}</td>
                                    <td>${item.GLOSA}</td>
                                    <td>$${parseInt(item.PRECIO).toLocaleString('es-CL')}</td>
                                 </tr>`;
                    });
                    html += '</tbody></table>';
                }

                document.getElementById("resultado-busqueda").innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById("resultado-busqueda").innerHTML = `<p style="color:red;">Error al buscar: ${error}</p>`;
            });
    }
	// Limpiar búsqueda
document.getElementById("btn-limpiar").addEventListener("click", function() {
    // Limpiar los campos de entrada
    document.getElementById("input-codigo-glosa").value = '';
    document.getElementById("input-prevision").value = '';

    // Limpiar los resultados
    document.getElementById("resultado-busqueda").innerHTML = '';
});

});


</script>
</body>
</html>
