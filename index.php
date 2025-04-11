<?php
$archivoInventario = 'inventario.json';
$archivoDespachos = 'despachos.json';

if (!file_exists($archivoInventario)) file_put_contents($archivoInventario, json_encode([]));
if (!file_exists($archivoDespachos)) file_put_contents($archivoDespachos, json_encode([]));

$productos = json_decode(file_get_contents($archivoInventario), true);
$despachos = json_decode(file_get_contents($archivoDespachos), true);

// Agregar Partida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['partida'])) {
    $productos[] = [
        'partida' => $_POST['partida'],
        'cantidad' => intval($_POST['cantidad']),
        'color' => $_POST['color'],
        'estilo' => $_POST['estilo'],
        'peso' => floatval($_POST['peso']),
        'tallas' => [
            'XS' => intval($_POST['xs']),
            'S' => intval($_POST['s']),
            'M' => intval($_POST['m']),
            'L' => intval($_POST['l']),
            'XL' => intval($_POST['xl']),
            'XXL' => intval($_POST['xxl']),
        ]
    ];
    file_put_contents($archivoInventario, json_encode($productos));
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Eliminar partida
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    if (isset($productos[$id])) {
        unset($productos[$id]);
        $productos = array_values($productos);
        file_put_contents($archivoInventario, json_encode($productos));
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Despacho
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['despacho_partida'])) {
    $id = intval($_POST['despacho_partida']);
    if (isset($productos[$id])) {
        $despacho = [
            'partida_id' => $id,
            'fecha' => date('Y-m-d H:i:s'),
            'peso_envio' => floatval($_POST['peso_envio']),
            'guia_envio' => $_POST['guia_envio'],
            'tallas' => [],
        ];

        $ok = true;
        foreach (['XS','S','M','L','XL','XXL'] as $talla) {
            $cantidad = intval($_POST["desp_$talla"]);
            if ($cantidad > $productos[$id]['tallas'][$talla]) {
                $ok = false;
                break;
            }
            $despacho['tallas'][$talla] = $cantidad;
        }

        if ($ok) {
            foreach ($despacho['tallas'] as $talla => $cantidad) {
                $productos[$id]['tallas'][$talla] -= $cantidad;
                $productos[$id]['cantidad'] -= $cantidad;
            }

            $despachos[] = $despacho;
            file_put_contents($archivoInventario, json_encode($productos));
            file_put_contents($archivoDespachos, json_encode($despachos));
        } else {
            echo "<script>alert('¡Cantidad mayor al stock disponible en alguna talla!');</script>";
        }

        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario y Despachos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 1200px;
        }
        .card-header {
            background-color: #004085;
            color: #fff;
            font-weight: bold;
            border-radius: 10px 10px 0 0;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .table {
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
        }
        .table th {
            background-color: #004085;
            color: #fff;
            font-weight: bold;
        }
        .table-striped tbody tr:nth-child(odd) {
            background-color: #f8f9fa;
        }
        .btn-primary, .btn-warning, .btn-success, .btn-danger {
            border-radius: 30px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover, .btn-warning:hover, .btn-danger:hover, .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }
        .badge-info {
            background-color: #17a2b8;
        }
        .form-control {
            border-radius: 8px;
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.1);
        }
        .logo {
            max-width: 250px;
            height: auto;
        }
        .input-talla {
            width: 60px;
            height: 40px;
            text-align: center;
            font-size: 1.2rem;
            padding: 5px;
            border-radius: 8px;
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.1);
        }
        .input-guia {
            width: 50%;
            border-radius: 8px;
            padding: 10px;
        }
        .table-responsive {
            margin-top: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container py-4">
    <!-- LOGO -->
    <div class="text-center mb-4">
        <img src="logo.jpg" alt="Logo" class="logo">
    </div>

    <h2 class="text-center mb-5 text-primary"><i class="fas fa-boxes"></i> Sistema de Inventario y Despachos</h2>

    <!-- AGREGAR PARTIDA -->
    <div class="card mb-4">
        <div class="card-header">Agregar Nueva Partida</div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-4"><label>Partida</label><input name="partida" class="form-control" required></div>
                <div class="col-md-4"><label>Cantidad Total</label><input type="number" name="cantidad" class="form-control" required></div>
                <div class="col-md-4"><label>Peso (kg)</label><input type="number" step="0.01" name="peso" class="form-control" required></div>
                <div class="col-md-6"><label>Color</label><input name="color" class="form-control" required></div>
                <div class="col-md-6"><label>Estilo</label><input name="estilo" class="form-control" required></div>
                <div class="col-12"><label>Tallas</label>
                    <div class="d-flex justify-content-between">
                        <?php foreach (['xs', 's', 'm', 'l', 'xl', 'xxl'] as $t): ?>
                            <div class="col-2 text-center">
                                <input type="number" name="<?= $t ?>" class="form-control input-talla" value="0" min="0">
                                <small class="text-uppercase"><?= strtoupper($t) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-12 text-end"><button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Agregar</button></div>
            </form>
        </div>
    </div>

    <!-- DESPACHO -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">Despachar Prendas</div>
        <div class="card-body">
            <form method="post" class="row g-3" id="form-despacho">
                <div class="col-md-4">
                    <label>Seleccionar Partida</label>
                    <select name="despacho_partida" id="partidaSelect" class="form-select" required onchange="mostrarDetalles()">
                        <option disabled selected value="">-- Seleccionar --</option>
                        <?php foreach ($productos as $i => $p): ?>
                            <option value="<?= $i ?>" data-color="<?= htmlspecialchars($p['color']) ?>" data-estilo="<?= htmlspecialchars($p['estilo']) ?>">
                                #<?= $i + 1 ?> - <?= htmlspecialchars($p['partida']) ?> (<?= $p['cantidad'] ?> disponibles)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Color</label>
                    <input type="text" id="colorInput" class="form-control" disabled>
                </div>
                <div class="col-md-4">
                    <label>Estilo</label>
                    <input type="text" id="estiloInput" class="form-control" disabled>
                </div>
                <div class="col-md-3">
                    <label>Peso del paquete (kg)</label>
                    <input type="number" step="0.01" name="peso_envio" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Número de guía de envío</label>
                    <input type="text" name="guia_envio" class="form-control input-guia" required>
                </div>
                <div class="col-md-4"><label>Prendas por Talla</label>
                    <div class="d-flex justify-content-between">
                        <?php foreach (['XS','S','M','L','XL','XXL'] as $t): ?>
                            <div class="col-2 text-center">
                                <input type="number" name="desp_<?= $t ?>" class="form-control input-talla" value="0" min="0">
                                <small><?= $t ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-truck"></i> Realizar Despacho</button>
                </div>
            </form>
        </div>
    </div>

    <!-- INVENTARIO -->
    <h4>Inventario Actual</h4>
    <input type="text" class="form-control mb-4" id="searchInventario" placeholder="Buscar por número de partida..." oninput="filterInventario()">
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-striped align-middle" id="tablaInventario">
            <thead class="table-dark">
                <tr><th>#</th><th>Partida</th><th>Cantidad</th><th>Color</th><th>Estilo</th><th>Peso</th><th>Tallas</th><th>Acción</th></tr>
            </thead>
            <tbody>
            <?php foreach ($productos as $i => $p): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($p['partida']) ?></td>
                    <td><?= $p['cantidad'] ?></td>
                    <td><?= htmlspecialchars($p['color']) ?></td>
                    <td><?= htmlspecialchars($p['estilo']) ?></td>
                    <td><?= $p['peso'] ?> kg</td>
                    <td>
                        <?php foreach ($p['tallas'] as $t => $v): ?>
                            <span class="badge bg-info"><?= $t ?>: <?= $v ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td><a href="?eliminar=<?= $i ?>" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Eliminar</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- HISTORIAL DESPACHOS -->
    <h4>Historial de Despachos</h4>
    <input type="text" class="form-control mb-4" id="searchDespachos" placeholder="Buscar por número de partida..." oninput="filterDespachos()">
    <div class="table-responsive mb-5">
        <table class="table table-sm table-bordered table-striped">
            <thead class="table-dark">
                <tr><th>#</th><th>Partida</th><th>Color</th><th>Estilo</th><th>Fecha</th><th>Peso</th><th>Guía de Envío</th><th>Detalles de Tallas</th></tr>
            </thead>
            <tbody id="historialDespachos">
            <?php foreach ($despachos as $i => $d): 
                $p = isset($productos[$d['partida_id']]) ? $productos[$d['partida_id']] : null;
            ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= isset($p['partida']) ? htmlspecialchars($p['partida']) : 'N/A' ?></td>
                    <td><?= isset($p['color']) ? htmlspecialchars($p['color']) : '-' ?></td>
                    <td><?= isset($p['estilo']) ? htmlspecialchars($p['estilo']) : '-' ?></td>
                    <td><?= $d['fecha'] ?></td>
                    <td><?= isset($d['peso_envio']) ? $d['peso_envio'] . ' kg' : 'N/A' ?></td>
                    <td><?= isset($d['guia_envio']) ? $d['guia_envio'] : 'N/A' ?></td>
                    <td>
                        <div class="tallas">
                            <?php if (isset($d['tallas']) && is_array($d['tallas'])): ?>
                                <?php foreach ($d['tallas'] as $t => $c): ?>
                                    <div class="col-2 text-center"><?= $t ?>: <?= $c ?></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-2 text-center">-</div>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function mostrarDetalles() {
    var select = document.getElementById('partidaSelect');
    var selectedOption = select.options[select.selectedIndex];
    document.getElementById('colorInput').value = selectedOption.dataset.color;
    document.getElementById('estiloInput').value = selectedOption.dataset.estilo;
}

function filterInventario() {
    var input = document.getElementById('searchInventario').value.toUpperCase();
    var table = document.getElementById('tablaInventario');
    var rows = table.getElementsByTagName('tr');
    
    for (var i = 1; i < rows.length; i++) {
        var partida = rows[i].getElementsByTagName('td')[1];
        if (partida) {
            var partidaText = partida.textContent || partida.innerText;
            if (partidaText.toUpperCase().indexOf(input) > -1) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
}

function filterDespachos() {
    var input = document.getElementById('searchDespachos').value.toUpperCase();
    var table = document.getElementById('historialDespachos');
    var rows = table.getElementsByTagName('tr');
    
    for (var i = 1; i < rows.length; i++) {
        var partida = rows[i].getElementsByTagName('td')[1];
        if (partida) {
            var partidaText = partida.textContent || partida.innerText;
            if (partidaText.toUpperCase().indexOf(input) > -1) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
}
</script>

</body>
</html>
