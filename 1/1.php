<?php
include("conexion.php");

$prevision = $_GET['prevision'] ?? 'ISAPRE';
$mensaje = "";
$resultadoBusqueda = [];

// Obtener categorías
$sql = "SELECT DISTINCT CATEGORIA FROM precios ORDER BY CATEGORIA ASC";
$res = $conexion->query($sql);
$categorias = [];
if ($res && $res->num_rows > 0) {
  while ($fila = $res->fetch_assoc()) {
    $categorias[] = $fila['CATEGORIA'];
  }
}

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

// Buscar por glosa
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['buscar_glosa'])) {
  $glosa = trim($_POST['glosa']);
  if (!empty($glosa)) {
    $glosa = "%$glosa%";
    $stmt = $conexion->prepare("SELECT * FROM precios WHERE GLOSA LIKE ? LIMIT 10");
    $stmt->bind_param("s", $glosa);
    $stmt->execute();
    $resultadoBusqueda = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (empty($resultadoBusqueda)) {
      $mensaje = "No se encontraron glosas coincidentes.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Selecciona una categoría</title>
  <link rel="stylesheet" href="fondo.css">
  <style>
  
    body {
      background-color: white;
      font-family: sans-serif;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px 20px;
    }
    .hora, #fecha-actual {
      font-size: 30px;
      margin: 0;
    }
    h2 {
      margin-bottom: 10px;
      color: #333;
      text-align: center;
    }
    
    .hora, #fecha-actual {
      font-size: 15px;
      margin: 0;
	  color: #016666;
    }
    .contenedor-botones {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
      max-width: 1000px;
    }
    .btn-categoria {
      padding: 20px 30px;
      font-size: 18px;
      background-color: #016666;
      color: white;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      box-shadow: 2px 2px 8px rgba(0,0,0,0.2);
      text-decoration: none;
      transition: background-color 0.2s ease;
      width: 220px;
      text-align: center;
    }
    .btn-categoria:hover {
      background-color: #014c4c;
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
    }
    .buscador {
      margin-bottom: 30px;
      width: 100%;
      max-width: 600px;
    }
    .buscador form {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-bottom: 15px;
    }
    .buscador input[type="text"], .buscador input[type="number"] {
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
    @media (max-width: 600px) {
      .btn-categoria {
        width: 90%;
        font-size: 16px;
      }
	  #btn-limpiar {
		  padding: 10px;
		  background-color: #999;
		  color: white;
		  border: none;
		  border-radius: 8px;
		  cursor: pointer;
		  margin-top: 10px;
		}
	}
	#btn-limpiar:hover {
		background-color: #777;
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
	#btn-limpiar {
		background-color: #014c4c;
		color: white;
		border: none;
		border-radius: 8px;
		padding: 10px 14px;
		cursor: pointer;
		white-space: nowrap;
	}
	#btn-limpiar:hover {
		background-color: #012f2f;
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
	.resultado {
		margin-bottom: 10px;
		padding: 15px;
		border-radius: 8px;
		background-color: white;
		box-shadow: 0 1px 4px rgba(0,0,0,0.1);
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
	.contenedor-resultados {
		max-height: 300px;
		overflow-y: auto;
		width: 100%;
		max-width: 600px;
		margin-top: 10px;
		border-radius: 10px;
		padding: 5px;
		background-color: #f9f9f9;
		box-shadow: 0px 2px 6px rgba(0,0,0,0.1);
	}
	.contenedor-resultados {
		width: 100%;
		max-width: 600px;
		margin: 20px auto 0 auto;
		padding: 10px;
	}
	#resultados-glosa .resultado {
		background: white;
		padding: 15px;
		border-radius: 10px;
		box-shadow: 0 1px 6px rgba(0,0,0,0.1);
		margin-bottom: 15px
	}
	#resultados-glosa .resultado a {
		display: inline-block;
		margin-top: 8px;
		background-color: #016666;
		color: white;
		text-decoration: none;
		padding: 6px 10px;
		border-radius: 6px;
		font-size: 14px;
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
	.titulo-categoria 
	{color: #016666; 
	}


  </style>
</head>
<body>
  <a href="index.html" class="btn-volver">← Volver</a>
		<h2 class="titulo-categoria">Selecciona una categoría<br><?= htmlspecialchars($prevision) ?></h2>

			<div class="hora" id="hora-actual">Hora actual: --:--:--</div>
			<div id="fecha-actual">Fecha: --</div>
			
	<div class="contenedor-resultados">
</div>

  </div>
  <?php if (!empty($mensaje)): ?>
    <div class="resultado"><?= htmlspecialchars($mensaje) ?></div>
  <?php endif; ?>

  <?php foreach ($resultadoBusqueda as $fila): ?>
<div class="resultado">
  <strong>Glosa:</strong> <?= htmlspecialchars($fila['GLOSA']) ?><br>
  <strong>Categoría:</strong> <?= htmlspecialchars($fila['CATEGORIA']) ?><br>
  <?php
    $categoriaUrl = urlencode($fila['CATEGORIA']);
    $archivo = ($prevision === 'ISAPRE') ? 'reis.php' :
               (($prevision === 'PARTICULAR') ? 'repa.php' : 'refo.php');
  ?>
  <a href="<?= $archivo ?>?categoria=<?= $categoriaUrl ?>&prevision=<?= $prevision ?>">Ir a glosa</a>
</div>

<?php endforeach; ?>

  <div class="contenedor-botones"> 
    <?php foreach ($categorias as $categoria): ?>
      <?php
        $categoriaUrl = urlencode($categoria);
        $archivo = ($prevision === 'ISAPRE') ? 'reis.php' :
                   (($prevision === 'PARTICULAR') ? 'repa.php' : 'refo.php');
      ?>
      <a href="<?= $archivo ?>?categoria=<?= $categoriaUrl ?>&prevision=<?= $prevision ?>" class="btn-categoria">
        <?= htmlspecialchars($categoria) ?>
      </a>
    <?php endforeach; ?>
  </div>
  
<script>
  function actualizarHoraYFecha() {
    const ahora = new Date();
    const horas = String(ahora.getHours()).padStart(2, '0');
    const minutos = String(ahora.getMinutes()).padStart(2, '0');
    const segundos = String(ahora.getSeconds()).padStart(2, '0');

    const opcionesFecha = {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      timeZone: 'America/Santiago'
    };
	const fechaChile = ahora.toLocaleDateString('es-CL', opcionesFecha);

    document.getElementById('hora-actual').textContent = `Hora actual: ${horas}:${minutos}:${segundos}⏰`;
    document.getElementById('fecha-actual').textContent = `Fecha: ${fechaChile.charAt(0).toUpperCase() + fechaChile.slice(1)}📅`;
  }
  setInterval(actualizarHoraYFecha, 1000);
  actualizarHoraYFecha();


const inputGlosa = document.getElementById("input-glosa");
const resultadosDiv = document.getElementById("resultados-glosa");

// Reutilizable para botón e input automático
function buscarGlosa(texto) {
  resultadosDiv.innerHTML = "";

  if (texto.length < 2) {
    resultadosDiv.innerHTML = "<div class='resultado'>Ingresa al menos 2 letras.</div>";
    return;
  }

  fetch("buscar_glosa_global.php?q=" + encodeURIComponent(texto) + "&prevision=<?= urlencode($prevision) ?>")
    .then(res => res.json())
    .then(data => {
      if (data.length === 0) {
        resultadosDiv.innerHTML = "<div class='resultado'>No se encontraron resultados.</div>";
        return;
      }

      data.forEach(item => {
        const div = document.createElement("div");
        div.classList.add("resultado");
        div.innerHTML = `
          <strong>${item.GLOSA}</strong><br>
          Categoría: ${item.CATEGORIA}<br>
          <a href="${item.link}">Ver precio</a>
        `;
        resultadosDiv.appendChild(div);
      });
    });
}

// ✅ Búsqueda automática al tipear
inputGlosa.addEventListener("input", function () {
  const texto = inputGlosa.value.trim();
  buscarGlosa(texto);
});

// ✅ Búsqueda al presionar botón
document.getElementById("btn-buscar-glosa").addEventListener("click", function () {
  buscarGlosa(inputGlosa.value.trim());
});

// ✅ Botón para limpiar todo
document.getElementById('btn-limpiar').addEventListener('click', function () {
  document.querySelector('input[name="codigo"]').value = '';
  inputGlosa.value = '';
  resultadosDiv.innerHTML = '';
  inputGlosa.focus();
});
</script>

</body>
</html>
