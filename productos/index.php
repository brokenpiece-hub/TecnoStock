<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';
require __DIR__ . '/../includes/ordenamiento.php';

requerir_permiso('productos', '../');

$q = trim($_GET['q'] ?? '');
$idCategoria = entero_no_negativo($_GET['id_categoria'] ?? null);
$marca = trim($_GET['marca'] ?? '');
$stockCritico = $_GET['stock_critico'] ?? '';
if (!in_array($stockCritico, ['1', '0'], true)) {
    $stockCritico = '';
}
$estado = $_GET['estado'] ?? '1';
if (!in_array($estado, ['1', '0'], true)) {
    $estado = '1';
}

/* Conteos para las pestañas: no borra ni oculta productos, solo los separa. */
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

/* Columnas que se pueden ordenar y su equivalente en la consulta. */
$ordenes = [
    'codigo'       => 'CAST(SUBSTRING_INDEX(p.codigo, "-", -1) AS UNSIGNED)',
    'nombre'       => 'p.nombre',
    'categoria'    => 'c.nombre',
    'marca'        => 'p.marca',
    'precio'       => 'p.precio',
    'stock_actual' => 'p.stock_actual',
    'stock_minimo' => 'p.stock_minimo',
];
$orden = $_GET['orden'] ?? '';
if (!isset($ordenes[$orden])) {
    $orden = '';
}
$direccion = $_GET['direccion'] ?? 'asc';
if (!in_array($direccion, ['asc', 'desc'], true)) {
    $direccion = 'asc';
}

/* Los filtros se comparten entre pestañas y columnas para no perderlos. */
$filtros = ['estado' => $estado];
foreach (['q' => $q, 'id_categoria' => $idCategoria, 'marca' => $marca, 'stock_critico' => $stockCritico] as $clave => $valor) {
    if ($valor !== null && $valor !== '') {
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

$sql = 'SELECT p.id_producto, p.codigo, p.nombre, p.imagen,
               c.nombre AS categoria, p.marca,
               p.precio, p.moneda, p.stock_actual, p.stock_minimo, p.estado
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
if ($stockCritico === '1') {
    $condiciones[] = 'p.stock_actual < p.stock_minimo';
} elseif ($stockCritico === '0') {
    $condiciones[] = 'p.stock_actual >= p.stock_minimo';
}

if (!empty($condiciones)) {
    $sql .= ' WHERE ' . implode(' AND ', $condiciones);
}
if ($orden !== '') {
    $sql .= ' ORDER BY ' . $ordenes[$orden] . ' ' . strtoupper($direccion) . ', p.id_producto ASC';
} else {
    $sql .= ' ORDER BY p.id_producto';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$productos = $stmt->fetchAll();

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias ORDER BY nombre')->fetchAll();

$mensajes = [
    'creado'      => 'Producto creado correctamente.',
    'editado'     => 'Producto actualizado correctamente.',
    'desactivado' => 'Producto desactivado.',
    'activado'    => 'Producto activado.',
];
$mensaje = isset($_GET['msg']) && isset($mensajes[$_GET['msg']]) ? $mensajes[$_GET['msg']] : '';
$titulo = 'TecnoStock - Productos';
$base = '../';
$pagina = 'productos';
require __DIR__ . '/../includes/header.php';
?>

<h1>Productos</h1>

<?php if ($mensaje !== ''): ?>
    <p class="mensaje"><?= e($mensaje) ?></p>
<?php endif; ?>

<nav class="tabs" aria-label="Estado de los productos">
    <a class="tab <?= $estado === '1' ? 'activo' : '' ?>" href="<?= e($urlActivos) ?>"
       <?= $estado === '1' ? 'aria-current="page"' : '' ?>>
        Activos <span class="tab-num"><?= $totalActivos ?></span>
    </a>
    <a class="tab <?= $estado === '0' ? 'activo' : '' ?>" href="<?= e($urlInactivos) ?>"
       <?= $estado === '0' ? 'aria-current="page"' : '' ?>>
        Inactivos <span class="tab-num"><?= $totalInactivos ?></span>
    </a>
    <a class="btn btn-acento btn-pequeno tab-accion" href="crear.php">Nuevo producto</a>
</nav>

<div class="card">
    <form class="barra-filtros" method="get" action="index.php">
        <input type="hidden" name="estado" value="<?= e($estado) ?>">
        <?php if ($orden !== ''): ?>
            <input type="hidden" name="orden" value="<?= e($orden) ?>">
            <input type="hidden" name="direccion" value="<?= e($direccion) ?>">
        <?php endif; ?>
        <div>
            <label for="q">Buscar</label>
            <input type="text" id="q" name="q" placeholder="Nombre, codigo, categoria o marca" value="<?= e($q) ?>">
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
        <div>
            <label for="stock_critico">Stock critico</label>
            <select id="stock_critico" name="stock_critico">
                <option value="" <?= $stockCritico === '' ? 'selected' : '' ?>>Todos</option>
                <option value="1" <?= $stockCritico === '1' ? 'selected' : '' ?>>Solo stock critico</option>
                <option value="0" <?= $stockCritico === '0' ? 'selected' : '' ?>>Sin stock critico</option>
            </select>
        </div>
        <div class="filtros-acciones">
            <button type="submit">Buscar</button>
            <a class="btn btn-gris" href="index.php?estado=<?= e($estado) ?>">Limpiar</a>
        </div>
    </form>
</div>

<div class="card">
    <h2>Listado <small data-total>Total: <?= count($productos) ?></small></h2>

    <?php if (empty($productos)): ?>
        <?php if ($estado === '0'): ?>
            <p class="vacio">No hay productos inactivos. Los productos que desactives se guardan aqui.</p>
        <?php else: ?>
            <p class="vacio">No hay productos activos con estos filtros. <a href="crear.php">Crear el primero</a>.</p>
        <?php endif; ?>
    <?php else: ?>
    <div class="tabla-scroll">
    <table data-buscar>
        <thead>
            <tr>
                <th>Imagen</th>
                <?= cabecera_ordenable('codigo', 'Codigo', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('nombre', 'Nombre', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('categoria', 'Categoria', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('marca', 'Marca', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('precio', 'Precio', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('stock_actual', 'Stock actual', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('stock_minimo', 'Stock critico', $orden, $direccion, $filtros) ?>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($productos as $fila): ?>
            <?php
            $stockActual  = (int) $fila['stock_actual'];
            $stockCriticoFila = (int) $fila['stock_minimo'];
            $stockBajo    = $stockActual < $stockCriticoFila;
            $activo       = (int) $fila['estado'] === 1;
            ?>
            <tr data-buscar="<?= e($fila['codigo'] . ' ' . $fila['nombre'] . ' ' . $fila['categoria'] . ' ' . $fila['marca']) ?>">
                <td>
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
                <td><?= e($fila['codigo']) ?></td>
                <td><?= e($fila['nombre']) ?></td>
                <td><?= e($fila['categoria']) ?></td>
                <td><?= e($fila['marca']) ?></td>
                <td><?= e($fila['moneda']) ?> <?= e($fila['precio']) ?></td>
                <td>
                    <?= $stockActual ?>
                    <?php if ($stockBajo): ?> <span class="badge badge-rojo">Stock bajo</span><?php endif; ?>
                </td>
                <td><?= $stockCriticoFila ?></td>
                <td>
                    <span class="badge <?= $activo ? 'badge-verde' : 'badge-gris' ?>">
                        <?= $activo ? 'Activo' : 'Inactivo' ?>
                    </span>
                </td>
                <td class="acciones">
                    <a class="btn btn-pequeno" href="editar.php?id=<?= (int) $fila['id_producto'] ?>">Editar</a>
                    <?php if ($activo): ?>
                        <form method="post" action="desactivar.php"
                              onsubmit="return confirm('Desactivar este producto?');">
                            <input type="hidden" name="id" value="<?= (int) $fila['id_producto'] ?>">
                            <button class="btn btn-gris btn-pequeno" type="submit">Desactivar</button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="activar.php"
                              onsubmit="return confirm('Activar este producto?');">
                            <input type="hidden" name="id" value="<?= (int) $fila['id_producto'] ?>">
                            <button class="btn btn-acento btn-pequeno" type="submit">Activar</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <p class="vacio buscador-vacio" hidden>No hay resultados para esta busqueda.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
