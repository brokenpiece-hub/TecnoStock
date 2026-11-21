<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

require_login();

$q = trim($_GET['q'] ?? '');

$sql = 'SELECT p.id_producto, p.codigo, p.nombre, c.nombre AS categoria,
               p.precio, p.moneda, p.stock_actual, p.stock_minimo, p.estado
          FROM productos p
          JOIN categorias c ON c.id_categoria = p.id_categoria';

$parametros = [];
if ($q !== '') {
    $sql .= ' WHERE p.codigo LIKE :q1 OR p.nombre LIKE :q2 OR c.nombre LIKE :q3';
    $busqueda = '%' . $q . '%';
    $parametros = [':q1' => $busqueda, ':q2' => $busqueda, ':q3' => $busqueda];
}
$sql .= ' ORDER BY p.id_producto';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$productos = $stmt->fetchAll();

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
    <form class="barra-acciones" method="get" action="index.php">
        <div>
            <label for="q">Buscar</label>
            <input type="text" id="q" name="q" placeholder="Nombre, codigo o categoria" value="<?= e($q) ?>">
        </div>
        <button type="submit">Buscar</button>
        <a class="btn btn-gris" href="index.php">Limpiar</a>
        <a class="btn btn-verde" href="crear.php">Nuevo producto</a>
    </form>
</div>

<div class="card">
    <h2>Listado <small>Total: <?= count($productos) ?></small></h2>

    <div class="tabla-scroll">
    <table>
        <thead>
            <tr>
                <th>Codigo</th>
                <th>Nombre</th>
                <th>Categoria</th>
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
            $stockCritico = (int) $fila['stock_minimo'];
            $stockBajo    = $stockActual < $stockCritico;
            $activo       = (int) $fila['estado'] === 1;
            ?>
            <tr>
                <td><?= e($fila['codigo']) ?></td>
                <td><?= e($fila['nombre']) ?></td>
                <td><?= e($fila['categoria']) ?></td>
                <td><?= e($fila['moneda']) ?> <?= e($fila['precio']) ?></td>
                <td>
                    <?= $stockActual ?>
                    <?php if ($stockBajo): ?> <span class="badge badge-rojo">Stock bajo</span><?php endif; ?>
                </td>
                <td><?= $stockCritico ?></td>
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
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
