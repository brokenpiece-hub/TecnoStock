<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

requerir_permiso('productos', '../');

$q = trim($_GET['q'] ?? '');
$idCategoria = entero_no_negativo($_GET['id_categoria'] ?? null);
$marca = trim($_GET['marca'] ?? '');
$stockCritico = $_GET['stock_critico'] ?? '';
if (!in_array($stockCritico, ['1', '0'], true)) {
    $stockCritico = '';
}

$sql = 'SELECT p.id_producto, p.codigo, p.nombre, p.imagen,
               c.nombre AS categoria, p.marca,
               p.precio, p.moneda, p.stock_actual, p.stock_minimo, p.estado
          FROM productos p
          JOIN categorias c ON c.id_categoria = p.id_categoria';

$condiciones = [];
$parametros = [];

if ($q !== '') {
    $condiciones[] = '(p.codigo LIKE :q1 OR p.nombre LIKE :q2 OR c.nombre LIKE :q3 OR p.marca LIKE :q4)';
    $busqueda = '%' . $q . '%';
    $parametros = [
        ':q1' => $busqueda,
        ':q2' => $busqueda,
        ':q3' => $busqueda,
        ':q4' => $busqueda,
    ];
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
$sql .= ' ORDER BY p.id_producto';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$productos = $stmt->fetchAll();

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias ORDER BY nombre')->fetchAll();

$mensajes = [
    'creado'      => 'Producto creado correctamente.',
    'editado'     => 'Producto actualizado correctamente.',
    'desactivado' => 'Producto desactivado.',
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

<div class="card">
    <form class="barra-filtros" method="get" action="index.php">
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
            <a class="btn btn-gris" href="index.php">Limpiar</a>
            <a class="btn btn-acento" href="crear.php">Nuevo producto</a>
        </div>
    </form>
</div>

<div class="card">
    <h2>Listado <small>Total: <?= count($productos) ?></small></h2>

    <?php if (empty($productos)): ?>
        <p class="vacio">No hay productos para mostrar con estos filtros. <a href="crear.php">Crear el primero</a>.</p>
    <?php else: ?>
    <div class="tabla-scroll">
    <table>
        <thead>
            <tr>
                <th>Imagen</th>
                <th>Codigo</th>
                <th>Nombre</th>
                <th>Categoria</th>
                <th>Marca</th>
                <th>Precio</th>
                <th>Stock actual</th>
                <th>Stock critico</th>
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
            <tr>
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
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
