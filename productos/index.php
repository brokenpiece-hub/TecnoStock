<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';
require __DIR__ . '/../includes/ordenamiento.php';

requerir_permiso('productos', '../');

/* Iconos de las acciones en la esquina de cada tarjeta. */
$iconoEditar = '<svg class="icono-accion" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/></svg>';
$iconoApagado = '<svg class="icono-accion" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 2v10"/><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/></svg>';

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

/* Conteos para las pestaÃ±as: no borra ni oculta productos, solo los separa. */
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

/* Los filtros se comparten entre pestaÃ±as y columnas para no perderlos. */
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
    <div class="cabecera-lista">
        <h2>Listado <small data-total>Total: <?= count($productos) ?></small></h2>
        <div class="vista-switch" role="group" aria-label="Forma de ver los productos">
            <button type="button" class="vista-btn" data-vista="lista" aria-pressed="true">Lista</button>
            <button type="button" class="vista-btn" data-vista="tarjetas" aria-pressed="false">Tarjetas</button>
        </div>
    </div>

    <div class="vista-productos" data-vista-productos data-acciones data-vista="lista">
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
                <th class="th-fijo">Imagen</th>
                <?= cabecera_ordenable('codigo', 'Codigo', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('nombre', 'Nombre', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('categoria', 'Categoria', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('marca', 'Marca', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('precio', 'Precio', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('stock_actual', 'Stock actual', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('stock_minimo', 'Stock critico', $orden, $direccion, $filtros) ?>
                <th class="th-fijo">Estado</th>
                <th class="th-fijo">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($productos as $fila): ?>
            <?php
            $stockActual  = (int) $fila['stock_actual'];
            $stockCriticoFila = (int) $fila['stock_minimo'];
            $stockBajo    = $stockActual < $stockCriticoFila;
            $activo       = (int) $fila['estado'] === 1;
            $barra        = barra_stock($stockActual, $stockCriticoFila);
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
                <td class="tarjeta-nombre" data-label="Nombre"><?= e($fila['nombre']) ?></td>
                <td data-label="Categoria"><?= e($fila['categoria']) ?></td>
                <td data-label="Marca"><?= e($fila['marca']) ?></td>
                <td class="tarjeta-precio" data-label="Precio"><?= e($fila['moneda']) ?> <?= e($fila['precio']) ?></td>
                <td class="tarjeta-stock" data-label="Stock actual">
                    <?= $stockActual ?>
                    <?php if ($stockBajo): ?> <span class="badge badge-rojo">Stock bajo</span><?php endif; ?>
                    <span class="barra-stock" role="img" data-porcentaje="<?= $barra['porcentaje'] ?>"
                          data-color="<?= e($barra['color']) ?>"
                          aria-label="Stock <?= e($barra['texto']) ?>, barra al <?= $barra['porcentaje'] ?>%">
                        <span class="barra-stock-relleno <?= e($barra['clase']) ?>" style="width: <?= $barra['porcentaje'] ?>%"></span>
                    </span>
                </td>
                <td data-label="Stock critico"><?= $stockCriticoFila ?></td>
                <td data-label="Estado">
                    <span class="badge <?= $activo ? 'badge-verde' : 'badge-gris' ?>">
                        <?= $activo ? 'Activo' : 'Inactivo' ?>
                    </span>
                </td>
                <td class="acciones" data-label="Acciones">
                    <a class="btn btn-pequeno" href="editar.php?id=<?= (int) $fila['id_producto'] ?>"
                       title="Editar" aria-label="Editar">
                        <?= $iconoEditar ?><span class="texto-accion">Editar</span>
                    </a>
                    <?php if ($activo): ?>
                        <form method="post" action="desactivar.php"
                              onsubmit="return confirm('Desactivar este producto?');">
                            <input type="hidden" name="id" value="<?= (int) $fila['id_producto'] ?>">
                            <button class="btn btn-gris btn-pequeno" type="submit" title="Desactivar" aria-label="Desactivar">
                                <?= $iconoApagado ?><span class="texto-accion">Desactivar</span>
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="activar.php"
                              onsubmit="return confirm('Activar este producto?');">
                            <input type="hidden" name="id" value="<?= (int) $fila['id_producto'] ?>">
                            <button class="btn btn-acento btn-pequeno accion-activar" type="submit" title="Activar" aria-label="Activar">
                                <?= $iconoApagado ?><span class="texto-accion">Activar</span>
                            </button>
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
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>