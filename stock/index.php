<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';
require __DIR__ . '/../includes/ordenamiento.php';

requerir_permiso('stock', '../');

$q = trim($_GET['q'] ?? '');
$idCategoria = entero_no_negativo($_GET['id_categoria'] ?? null);
$marca = trim($_GET['marca'] ?? '');
$estado = $_GET['estado'] ?? '1';
if (!in_array($estado, ['1', '0'], true)) {
    $estado = '1';
}

/* Columnas ordenables de la tabla de existencias. */
$ordenes = [
    'codigo'            => 'CAST(SUBSTRING_INDEX(p.codigo, "-", -1) AS UNSIGNED)',
    'nombre'            => 'p.nombre',
    'categoria'         => 'c.nombre',
    'marca'             => 'p.marca',
    'stock_actual'      => 'p.stock_actual',
    'stock_minimo'      => 'p.stock_minimo',
    'ultimo_movimiento' => 'ultimo_movimiento',
];
$orden = $_GET['orden'] ?? '';
if (!isset($ordenes[$orden])) {
    $orden = '';
}
$direccion = $_GET['direccion'] ?? 'asc';
if (!in_array($direccion, ['asc', 'desc'], true)) {
    $direccion = 'asc';
}

/* Conteos para las pestaÃ±as, sin modificar los productos. */
$conteos = $pdo->query('SELECT estado, COUNT(*) AS total FROM productos GROUP BY estado')->fetchAll();
$totalActivos = 0;
$totalInactivos = 0;
foreach ($conteos as $conteo) {
    if ((int) $conteo['estado'] === 1) {
        $totalActivos = (int) $conteo['total'];
    } else {
        $totalInactivos = (int) $conteo['total'];
    }
}

/* Filtros compartidos entre pestaÃ±as y columnas. */
$filtros = ['estado' => $estado];
foreach (['q' => $q, 'id_categoria' => $idCategoria, 'marca' => $marca] as $clave => $valor) {
    if ($valor !== '') {
        $filtros[$clave] = $valor;
    }
}
if ($orden !== '') {
    $filtros['orden'] = $orden;
    $filtros['direccion'] = $direccion;
}

$filtrosActivos = $filtros;
$filtrosActivos['estado'] = '1';
$urlActivos = 'index.php?' . http_build_query($filtrosActivos);
$filtrosInactivos = $filtros;
$filtrosInactivos['estado'] = '0';
$urlInactivos = 'index.php?' . http_build_query($filtrosInactivos);

$sql = 'SELECT p.codigo, p.nombre, p.imagen, p.marca, c.nombre AS categoria,
               p.precio, p.moneda, p.stock_actual, p.stock_minimo, p.estado,
               (SELECT MAX(m.fecha_hora) FROM movimientos m
                 WHERE m.id_producto = p.id_producto) AS ultimo_movimiento
          FROM productos p
          JOIN categorias c ON c.id_categoria = p.id_categoria';

$condiciones = ['p.estado = :estado'];
$parametros = [':estado' => $estado];
if ($q !== '') {
    $condiciones[] = '(p.codigo LIKE :q1 OR p.nombre LIKE :q2 OR c.nombre LIKE :q3 OR p.marca LIKE :q4)';
    $busqueda = '%' . $q . '%';
    $parametros[':q1'] = $busqueda;
    $parametros[':q2'] = $busqueda;
    $parametros[':q3'] = $busqueda;
    $parametros[':q4'] = $busqueda;
}
if ($idCategoria !== null && $idCategoria > 0) {
    $condiciones[] = 'p.id_categoria = :id_categoria';
    $parametros[':id_categoria'] = $idCategoria;
}
if ($marca !== '') {
    $condiciones[] = 'p.marca LIKE :marca';
    $parametros[':marca'] = '%' . $marca . '%';
}
if (!empty($condiciones)) {
    $sql .= ' WHERE ' . implode(' AND ', $condiciones);
}
if ($orden !== '') {
    $sql .= ' ORDER BY ' . $ordenes[$orden] . ' ' . strtoupper($direccion) . ', p.codigo ASC';
} else {
    $sql .= ' ORDER BY p.estado DESC, (p.stock_actual < p.stock_minimo) DESC, p.codigo';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$productos = $stmt->fetchAll();

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias ORDER BY nombre')->fetchAll();

$stockBajo = (int) $pdo->query(
    'SELECT COUNT(*) FROM productos WHERE estado = 1 AND stock_actual < stock_minimo'
)->fetchColumn();

$movimientos = $pdo->query(
    'SELECT m.fecha_hora, m.tipo, m.cantidad, m.observacion, p.codigo, p.marca
       FROM movimientos m
       JOIN productos p ON p.id_producto = m.id_producto
      ORDER BY m.fecha_hora DESC, m.id_movimiento DESC
      LIMIT 8'
)->fetchAll();

$titulo = 'TecnoStock - Stock';
$base = '../';
$pagina = 'stock';
require __DIR__ . '/../includes/header.php';
?>

<h1>Stock</h1>
<p>Vista de solo lectura. Las cantidades se actualizan con entradas, salidas y ventas. Si editaste un producto, presiona <strong>Actualizar</strong> para ver los cambios.</p>

<nav class="tabs" aria-label="Estado de los productos">
    <a class="tab <?= $estado === '1' ? 'activo' : '' ?>" href="<?= e($urlActivos) ?>"
       <?= $estado === '1' ? 'aria-current="page"' : '' ?>>
        Activos <span class="tab-num"><?= $totalActivos ?></span>
    </a>
    <a class="tab <?= $estado === '0' ? 'activo' : '' ?>" href="<?= e($urlInactivos) ?>"
       <?= $estado === '0' ? 'aria-current="page"' : '' ?>>
        Inactivos <span class="tab-num"><?= $totalInactivos ?></span>
    </a>
</nav>

<?php if ($stockBajo > 0): ?>
    <div class="alerta-stock" role="status">
        <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M12 3 2 20h20L12 3Z"/><path d="M12 9v5"/><circle cx="12" cy="18" r=".5"/>
        </svg>
        <span><strong><?= $stockBajo ?> producto(s) con stock bajo.</strong> Avisa al operador si necesitas reponer.</span>
    </div>
<?php endif; ?>

<div class="card">
    <form class="barra-filtros" method="get" action="index.php">
        <input type="hidden" name="estado" value="<?= e($estado) ?>">
        <?php if ($orden !== ''): ?>
            <input type="hidden" name="orden" value="<?= e($orden) ?>">
            <input type="hidden" name="direccion" value="<?= e($direccion) ?>">
        <?php endif; ?>
        <div>
            <label for="q">Buscar producto</label>
            <input type="text" id="q" name="q" placeholder="Codigo, nombre, categoria o marca" value="<?= e($q) ?>">
        </div>
        <div>
            <label for="id_categoria">Categoria</label>
            <select id="id_categoria" name="id_categoria">
                <option value="">Todas</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= (int) $categoria['id_categoria'] ?>"
                        <?= (string) $categoria['id_categoria'] === (string) $idCategoria ? 'selected' : '' ?>>
                        <?= e($categoria['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="marca">Marca</label>
            <input type="text" id="marca" name="marca" placeholder="Escriba una marca" value="<?= e($marca) ?>">
        </div>
        <div class="filtros-acciones">
            <button type="submit">Buscar</button>
            <a class="btn btn-gris" href="index.php?estado=<?= e($estado) ?>">Limpiar</a>
            <button type="button" class="btn btn-acento" onclick="location.reload()">Actualizar</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="cabecera-lista">
        <h2>Existencias <small data-total>Total: <?= count($productos) ?></small></h2>
        <div class="vista-switch" role="group" aria-label="Forma de ver los productos">
            <button type="button" class="vista-btn" data-vista="lista" aria-pressed="true">Lista</button>
            <button type="button" class="vista-btn" data-vista="tarjetas" aria-pressed="false">Tarjetas</button>
        </div>
    </div>

    <div class="vista-productos" data-vista-productos data-vista="lista">
    <?php if (empty($productos)): ?>
        <?php if ($estado === '0'): ?>
            <p class="vacio">No hay productos inactivos en Stock. Los productos que desactives aparecen aqui.</p>
        <?php else: ?>
            <p class="vacio">No hay productos activos con estos filtros.</p>
        <?php endif; ?>
    <?php else: ?>
        <div class="tabla-scroll">
        <table data-buscar>
            <thead>
                <tr>
                    <th class="th-fijo">Imagen</th>
                    <?= cabecera_ordenable('codigo', 'Codigo', $orden, $direccion, $filtros) ?>
                    <?= cabecera_ordenable('nombre', 'Producto', $orden, $direccion, $filtros) ?>
                    <?= cabecera_ordenable('categoria', 'Categoria', $orden, $direccion, $filtros) ?>
                    <?= cabecera_ordenable('marca', 'Marca', $orden, $direccion, $filtros) ?>
                    <?= cabecera_ordenable('stock_actual', 'Stock actual', $orden, $direccion, $filtros) ?>
                    <?= cabecera_ordenable('stock_minimo', 'Minimo', $orden, $direccion, $filtros) ?>
                    <th class="th-fijo">Estado</th>
                    <?= cabecera_ordenable('ultimo_movimiento', 'Ultimo movimiento', $orden, $direccion, $filtros) ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($productos as $fila): ?>
                <?php
                $activo = (int) $fila['estado'] === 1;
                $bajo = (int) $fila['stock_actual'] < (int) $fila['stock_minimo'];
                $barra = barra_stock((int) $fila['stock_actual'], (int) $fila['stock_minimo']);
                ?>
                <tr data-buscar="<?= e($fila['codigo'] . ' ' . $fila['nombre'] . ' ' . $fila['categoria'] . ' ' . $fila['marca']) ?>">
                    <td class="tarjeta-imagen">
                        <?php if ($fila['imagen'] !== null && $fila['imagen'] !== ''): ?>
                            <img class="miniatura" src="<?= e(ruta_imagen($fila['imagen'], '../')) ?>"
                                 alt="Imagen de <?= e($fila['nombre']) ?>" width="56" height="56" loading="lazy">
                        <?php else: ?>
                            <span class="sin-imagen">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Sin imagen" focusable="false">
                                    <rect x="3" y="4" width="18" height="16" rx="2"/>
                                    <circle cx="8.5" cy="9.5" r="1.5"/>
                                    <path d="m4 17 5-5 4 4 3-3 4 4"/>
                                </svg>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Codigo"><?= e($fila['codigo']) ?></td>
                    <td class="tarjeta-nombre" data-label="Producto"><?= e($fila['nombre']) ?></td>
                    <td data-label="Categoria"><?= e($fila['categoria']) ?></td>
                    <td data-label="Marca"><?= e($fila['marca']) ?></td>
                    <td class="tarjeta-stock" data-label="Stock actual">
                        <?= (int) $fila['stock_actual'] ?>
                        <?php if ($bajo && $activo): ?> <span class="badge badge-rojo">Stock bajo</span><?php endif; ?>
                        <span class="barra-stock" role="img" data-porcentaje="<?= $barra['porcentaje'] ?>"
                              data-color="<?= e($barra['color']) ?>"
                              aria-label="Stock <?= e($barra['texto']) ?>, barra al <?= $barra['porcentaje'] ?>%">
                            <span class="barra-stock-relleno <?= e($barra['clase']) ?>" style="width: <?= $barra['porcentaje'] ?>%"></span>
                        </span>
                    </td>
                    <td data-label="Minimo"><?= (int) $fila['stock_minimo'] ?></td>
                    <td data-label="Estado">
                        <span class="badge <?= $activo ? 'badge-verde' : 'badge-gris' ?>">
                            <?= $activo ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td data-label="Ultimo movimiento"><?= $fila['ultimo_movimiento'] === null ? 'Sin movimientos' : e($fila['ultimo_movimiento']) ?></td>
                    <td class="tarjeta-precio solo-tarjeta" data-label="Precio"><?= e($fila['moneda']) ?> <?= e($fila['precio']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <p class="vacio buscador-vacio" hidden>No hay resultados para esta busqueda.</p>
    <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2>Ultimos movimientos</h2>
    <?php if (empty($movimientos)): ?>
        <p class="vacio">Todavia no hay movimientos registrados.</p>
    <?php else: ?>
        <div class="tabla-scroll">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Producto</th>
                    <th>Marca</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Observacion</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($movimientos as $movimiento): ?>
                <tr>
                    <td><?= e($movimiento['fecha_hora']) ?></td>
                    <td><?= e($movimiento['codigo']) ?></td>
                    <td><?= e($movimiento['marca']) ?></td>
                    <td>
                        <span class="badge <?= $movimiento['tipo'] === 'entrada' ? 'badge-verde' : 'badge-gris' ?>">
                            <?= e($movimiento['tipo']) ?>
                        </span>
                    </td>
                    <td><?= (int) $movimiento['cantidad'] ?></td>
                    <td><?= e($movimiento['observacion'] ?: '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>