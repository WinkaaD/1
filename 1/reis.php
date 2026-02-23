<?php
include("conexion.php");

$prevision = $_GET['prevision'] ?? 'ISAPRE';
$categoria = $_GET['categoria'] ?? '';
$categoria = trim($categoria);

$codigoBuscado = ''; 
// Buscar por código
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['buscar_codigo'])) {
  $codigo = trim($_POST['codigo']);
  if (!empty($codigo)) {
    $stmt = $conexion->prepare("SELECT * FROM precios WHERE CODIGO = ?");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $resultadoBusqueda = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (empty($resultadoBusqueda)) {
      $mensaje = "Código no encontrado.";
    }
  } else {
    $mensaje = "Ingresa solo números para el código.";
  }
}


$sql = "SELECT CODIGO, GLOSA, `$prevision` AS PRECIO FROM precios WHERE CATEGORIA = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $categoria);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($categoria) ?> - ISAPRE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: white;
      padding: 40px;
      font-family: Arial, sans-serif;
    }
    .hora, #fecha-actual {
      font-size: 40px;
      margin: 0;
    }
    h1 {
      text-align: center;
      margin-bottom: 30px;
    }
    table {
      margin: auto;
      width: 90%;
    }
    th {
      background-color: #005BAA;
      color: white;
    }
	.btn-volver {
		position: absolute;
		left: 20px;
		top: 20px;
		padding: 10px 15px;
		background-color: #016666;
		color: white;
		border-radius: 8px;
		text-decoration: none;
		font-weight: bold;
		transition: background-color 0.2s ease;
	}
	.btn-volver:hover {
		background-color: #014c4c;
	}
    #hora {
      font-size: 40px;
      background-color: #016666;
      color: white;
      text-align: center;
      padding: 10px 20px;
      border-radius: 15px;
      position: fixed;
      top: 1px;
      left: 50%;
      transform: translateX(-50%);
	  margin-bottom: 80px;
    }
	.buscador {
		margin-bottom: 30px;
		width: 100%;
		max-width: 600px;
		}
	.fila-glosa {
		width: 100%;
		max-width: 600px;
		margin-bottom: 10px;
	}
	.contenedor-glosa {
		display: flex;
		gap: 10px;
		margin-top: 5px;
	}
	#input-glosa {
		flex-grow: 1;
		padding: 10px;
		font-size: 16px;
		border-radius: 8px;
		border: none;
		}
	.buscador input[type="text"] {
		padding: 10px;
		font-size: 16px;
		border-radius: 8px;
		border: none;
	}
	.buscador button {
		padding: 10px;
		background-color: #014c4c;
		color: white;
		border: none;
		border-radius: 8px;
		cursor: pointer;
	}
	#btn-limpiar {
		white-space: nowrap;
	}
	#btn-limpiar:hover {
		background-color: #012f2f;
	}
	.resultado {
		background: #fff;
		padding: 15px;
		border-radius: 10px;
		margin-bottom: 20px;
		width: 100%;
		max-width: 600px;
		box-shadow: 0px 0px 10px rgba(0,0,0,0.15);
	}
	.resultado a {
		margin-top: 5px;
		display: inline-block;
		color: white;
		background-color: #016666;
		padding: 6px 10px;
		border-radius: 6px;
		text-decoration: none;
	}
	.buscador-contenedor {
		display: flex;
		flex-direction: column;
		align-items: center;
		margin-top: 30px;
	}
	.buscador {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 10px;
	}
	.buscador form,
	.fila-glosa {
		width: 100%;
		max-width: 600px;
	}
	.contenedor-glosa {
		display: flex;
		justify-content: center;
		gap: 10px;
	}
	.buscador form {
		display: flex;
		gap: 10px;
		justify-content: center;
		}
	.buscador form input {
		flex: 1;
	}
	.buscador-contenedor {
		display: flex;
		flex-direction: column;
		align-items: center;
		margin-top: 30px;
	}
	.grupo-busqueda {
		display: flex;
		flex-direction: column;
		align-items: center;
		margin-bottom: 15px;
		width: 100%;
		max-width: 600px;
	}
	.fila {
		display: flex;
		gap: 10px;
		width: 100%;
	}
	.fila input {
		flex-grow: 1;
	}
	input[type="text"],
	input[type="number"],
	select {
		border: 1px solid #000 !important;  /* siempre borde negro */
		border-radius: 6px;
		padding: 10px;
		font-size: 16px;
		width: 100%;
		box-sizing: border-box;
		outline: none;
	}
	.fila-filtrada {
		background-color: #e9fce9 !important;
	}
	.input-estandar {
		width: 100%;
		padding: 10px;
		font-size: 16px;
		border-radius: 8px;
		border: 1px solid #000;
		box-sizing: border-box;
	}
	.btn-buscar {
		padding: 10px 14px;
		background-color: #014c4c;
		color: white;border: none;
		border-radius: 8px;
		cursor: pointer;
		white-space: nowrap;
	}
	.btn-buscar:hover {
		background-color: #012f2f;
	}



  </style>
</head>
<body>

<div style="text-align: center; margin-top: 40px;">
	<a href="index.html" class="btn-volver">← Volver</a>
</div>
<div id="mensaje-codigo" style="margin-top: 10px; color: red; font-weight: bold;"></div>

<div id="hora"></div>
</div>
  </div>

<script>
  function actualizarHora() {
    const fecha = new Date();
    const opciones = {
      timeZone: "America/Santiago",
      hour12: false,
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    };
    const horaChile = fecha.toLocaleTimeString('es-CL', opciones);
    document.getElementById('hora').textContent = horaChile;
	}
	setInterval(actualizarHora, 1000);
		actualizarHora();
</script>

<h1><?= htmlspecialchars($categoria) ?> - Precios ISAPRE</h1>

<?php if ($resultado && $resultado->num_rows > 0): ?>
  <table class="table table-bordered table-striped">
    <thead>
	<tr>
        <th>Seleccionar</th>
        <th>Código</th>
        <th>Glosa</th>
        <th>Precio (Isapre)</th>
      </tr>
    </thead>
    <tbody>
<?php echo "<p style='color:red;'>Buscando: $codigoBuscado</p>"; ?>

<?php while ($fila = $resultado->fetch_assoc()): 
  $precio = (float)str_replace('.', '', $fila['PRECIO']);
  $resaltar = ((string)$fila['CODIGO'] === (string)$codigoBuscado) ? 'style="background-color: #d0f0d0;"' : '';
echo "<!-- debug: fila={$fila['CODIGO']} buscado={$codigoBuscado} -->";

?>
<tr <?= $resaltar ?>>

          <td class="text-center">
            <input type="checkbox" class="exam-checkbox"
                   data-codigo="<?= $fila['CODIGO'] ?>"
                   data-glosa="<?= htmlspecialchars($fila['GLOSA']) ?>"
                   value="<?= $precio ?>">
          </td>
          <td><?= htmlspecialchars($fila['CODIGO']) ?></td>
          <td><?= htmlspecialchars($fila['GLOSA']) ?></td>
          <td>$<?= number_format($precio, 0, ',', '.') ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <div class="d-flex justify-content-between align-items-center mt-3 px-3">
    <h5>Total acumulado: <span id="total-global" class="text-success fw-bold">$0</span></h5>
    <button class="btn btn-outline-danger btn-sm" onclick="vaciarSeleccion()">Vaciar Selección</button>
  </div>

  <div class="cotizacion-box" id="detalle-cotizacion" style="display:none; max-width: 800px; margin: 40px auto 0; background: #fff; border: 1px solid #ccc; border-radius: 10px; padding: 20px;">
    <h5 class="text-center mb-3">Estás cotizando:</h5>
    <ul id="lista-cotizacion" class="list-group mb-3"></ul>
    <h6 class="text-end">Total: <span id="detalle-total" class="text-primary fw-bold">$0</span></h6>
  </div>

<?php else: ?>
  <div class="alert alert-warning text-center">
    No se encontraron resultados en esta categoría de Isapre.
  </div>
<?php endif; ?>

<script>

const tipoPrevision = "ISAPRE"; 

window.addEventListener('DOMContentLoaded', () => {
  const claves = ["FONASA", "ISAPRE", "PARTICULAR"];

  claves.forEach(p => {
    if (p !== tipoPrevision) {
      localStorage.removeItem(`seleccionados_${p}`);
      localStorage.removeItem(`total_${p}`);
    }
  });

  iniciarScriptCotizacion();
});

function iniciarScriptCotizacion() {
  const claveSeleccion = `seleccionados_${tipoPrevision}`;
  const claveTotal = `total_${tipoPrevision}`;

  const checkboxes = document.querySelectorAll('.exam-checkbox');
  const totalGlobalDisplay = document.getElementById('total-global');
  const listaCotizacion = document.getElementById('lista-cotizacion');
  const detalleCotizacion = document.getElementById('detalle-cotizacion');
  const detalleTotal = document.getElementById('detalle-total');

  let seleccionados = JSON.parse(localStorage.getItem(claveSeleccion)) || [];

  checkboxes.forEach(cb => {
    const yaSeleccionado = seleccionados.find(item => item.codigo === cb.dataset.codigo);
    if (yaSeleccionado) cb.checked = true;
  });

  checkboxes.forEach(cb => {
    cb.addEventListener('change', () => {
      let lista = JSON.parse(localStorage.getItem(claveSeleccion)) || [];
      const codigo = cb.dataset.codigo;
      const glosa = cb.dataset.glosa;
      const precio = parseFloat(cb.value);

      if (cb.checked) {
        if (!lista.find(item => item.codigo === codigo)) {
          lista.push({ codigo, glosa, precio });
        }
      } else {
        lista = lista.filter(item => item.codigo !== codigo);
      }

      localStorage.setItem(claveSeleccion, JSON.stringify(lista));
      actualizarVista();
    });
  });

  function vaciarSeleccion() {
    if (confirm("¿Estás seguro de vaciar toda la selección?")) {
      localStorage.removeItem(claveSeleccion);
      localStorage.removeItem(claveTotal);
      seleccionados = [];
      checkboxes.forEach(cb => cb.checked = false);
      actualizarVista();
    }
  }
  window.vaciarSeleccion = vaciarSeleccion;

  function actualizarVista() {
    let lista = JSON.parse(localStorage.getItem(claveSeleccion)) || [];
    let total = lista.reduce((acc, item) => acc + item.precio, 0);

    if (totalGlobalDisplay) totalGlobalDisplay.textContent = '$' + total.toLocaleString('es-CL');
    if (detalleTotal) detalleTotal.textContent = '$' + total.toLocaleString('es-CL');

    if (lista.length > 0) {
      if (detalleCotizacion) detalleCotizacion.style.display = 'block';
      if (listaCotizacion) {
        listaCotizacion.innerHTML = '';
        lista.forEach(item => {
          const li = document.createElement('li');
          li.className = 'list-group-item';
          li.textContent = `${item.codigo} - ${item.glosa}: $${item.precio.toLocaleString('es-CL')}`;
          listaCotizacion.appendChild(li);
        });
      }
    } else {
      if (detalleCotizacion) detalleCotizacion.style.display = 'none';
    }

    localStorage.setItem(claveTotal, total);
  }

  actualizarVista();
}
</script>

<script>
document.getElementById("input-glosa").addEventListener("input", function () {
  const texto = this.value.trim().toLowerCase();
  const filas = document.querySelectorAll("table tbody tr");

  filas.forEach(fila => {
    const glosa = fila.children[2]?.textContent.toLowerCase() || "";
    const codigo = fila.children[1]?.textContent.toLowerCase() || "";

    if (glosa.includes(texto) || codigo.includes(texto)) {
      fila.style.display = "";
      fila.classList.add("fila-filtrada");
    } else {
      fila.style.display = "none";
      fila.classList.remove("fila-filtrada");
    }
  });
});

document.getElementById("btn-limpiar").addEventListener("click", function () {
  const input = document.getElementById("input-glosa");
  input.value = "";
  input.focus();

  const filas = document.querySelectorAll("table tbody tr");
  filas.forEach(fila => {
    fila.style.display = "";
    fila.classList.remove("fila-filtrada");
  });
});
</script>
<script>
const inputCodigo = document.getElementById("input-codigo");
const divResultadoCodigo = document.getElementById("resultado-codigo");

inputCodigo.addEventListener("input", function () {
  const codigo = this.value.trim();
  if (codigo.length === 0) {
    divResultadoCodigo.innerHTML = '';
    return;
  }

  fetch(`buscar_codigo_global.php?codigo=${encodeURIComponent(codigo)}&prevision=<?= urlencode($prevision) ?>&categoria=<?= urlencode($categoria) ?>`)
    .then(res => res.json())
    .then(data => {
      if (!data.existe) {
        divResultadoCodigo.innerHTML = `<p style="color:red;">El código no existe en la base de datos.</p>`;
        return;
      }

      if (data.misma_categoria) {
        const filas = document.querySelectorAll("table tbody tr");
        let encontrado = false;

        filas.forEach(fila => {
          const valor = fila.children[1]?.textContent.trim();
          if (valor === codigo) {
            fila.style.display = '';
            fila.classList.add("fila-filtrada");
            encontrado = true;
          } else {
            fila.style.display = "none";
            fila.classList.remove("fila-filtrada");
          }
        });

        if (!encontrado) {
          divResultadoCodigo.innerHTML = `<p style="color:red;">Código encontrado pero no visible en esta tabla.</p>`;
        } else {
          divResultadoCodigo.innerHTML = '';
        }
      } else {
        divResultadoCodigo.innerHTML = `
          <p style="color:red;">El código pertenece a la categoría: <strong>${data.categoria}</strong></p>
          <div class="resultado">
            <strong>Glosa:</strong> ${data.glosa}<br>
            <strong>Categoría:</strong> ${data.categoria}<br>
            <a href="${data.link}">Ir a glosa</a>
          </div>
        `;
      }
    });
});
</script>

<script>
document.querySelector('input[name="codigo"]').addEventListener("input", function () {
  const texto = this.value.trim().toLowerCase();
  const filas = document.querySelectorAll("table tbody tr");

  filas.forEach(fila => {
    const codigo = fila.children[1]?.textContent.toLowerCase() || "";
    if (codigo.includes(texto)) {
      fila.style.display = "";
      fila.classList.add("fila-filtrada");
    } else {
      fila.style.display = "none";
      fila.classList.remove("fila-filtrada");
    }
  });
});
</script>
<script>
document.getElementById("input-codigo").addEventListener("input", function () {
  const texto = this.value.trim().toLowerCase();
  const filas = document.querySelectorAll("table tbody tr");

  filas.forEach(fila => {
    const codigo = fila.children[1]?.textContent.toLowerCase() || "";

    if (codigo.includes(texto)) {
      fila.style.display = "";
      fila.classList.add("fila-filtrada");
    } else {
      fila.style.display = "none";
      fila.classList.remove("fila-filtrada");
    }
  });
});
</script>
<script>
document.getElementById("input-codigo").addEventListener("input", function () {
  const codigo = this.value.trim();
  const filas = document.querySelectorAll("table tbody tr");
  let encontradoEnCategoria = false;
  let mensajeDiv = document.getElementById("mensaje-codigo");

  // Limpiar mensaje
  mensajeDiv.textContent = "";

  if (codigo.length < 1) {
    filas.forEach(fila => fila.style.display = "");
    return;
  }

  filas.forEach(fila => {
    const codFila = fila.children[1]?.textContent.trim();
    if (codFila === codigo) {
      fila.style.display = "";
      fila.classList.add("fila-filtrada");
      encontradoEnCategoria = true;
    } else {
      fila.style.display = "none";
      fila.classList.remove("fila-filtrada");
    }
  });

  if (!encontradoEnCategoria) {
    // Si no está en esta categoría, consultar si existe globalmente
    fetch("verificar_codigo_global.php?codigo=" + encodeURIComponent(codigo))
      .then(res => res.json())
      .then(data => {
        if (data.existe) {
          mensajeDiv.textContent = "El código pertenece a la categoría: " + data.categoria;
        } else {
          mensajeDiv.textContent = "Código no encontrado en la base de datos.";
        }
      });
  }
});
</script>

</body>
</html>
