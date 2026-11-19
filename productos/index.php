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
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>TecnoStock - Productos</title>
</head>
<body>
    <p><a href="../dashboard.php">Inicio</a> | <a href="crear.php">Nuevo producto</a></p>
    <h1>Productos</h1>

    <?php if ($mensaje !== ''): ?>
        <p><?= e($mensaje) ?></p>
    <?php endif; ?>

    <form method="get" action="index.php">
        <label for="q">Buscar por nombre, codigo o categoria:</label>
        <input type="text" id="q" name="q" value="<?= e($q) ?>">
        <button type="submit">Buscar</button>
        <a href="index.php">Limpiar</a>
    </form>

    <p>Total: <?= count($productos) ?></p>

    <table border="1" cellpadding="4" cellspacing="0">
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
            ?>
            <tr>
                <td><?= e($fila['codigo']) ?></td>
                <td><?= e($fila['nombre']) ?></td>
                <td><?= e($fila['categoria']) ?></td>
                <td><?= e($fila['moneda']) ?> <?= e($fila['precio']) ?></td>
                <td>
                    <?= $stockActual ?>
                    <?php if ($stockBajo): ?> <strong>Stock bajo</strong><?php endif; ?>
                </td>
                <td><?= $stockCritico ?></td>
                <td><?= (int) $fila['estado'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                <td>
                    <a href="editar.php?id=<?= (int) $fila['id_producto'] ?>">Editar</a>
                    <?php if ((int) $fila['estado'] === 1): ?>
                        <form method="post" action="desactivar.php" style="display:inline;"
                              onsubmit="return confirm('Desactivar este producto?');">
                            <input type="hidden" name="id" value="<?= (int) $fila['id_producto'] ?>">
                            <button type="submit">Desactivar</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
