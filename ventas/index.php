<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

requerir_permiso('ventas', '../');

$q = trim($_GET['q'] ?? '');

$sql = 'SELECT v.id_venta, v.fecha_hora, v.total, v.moneda, u.nombre AS usuario,
               p.codigo, p.nombre AS producto, d.cantidad, d.precio_unitario
          FROM ventas v
          JOIN detalle_ventas d ON d.id_venta = v.id_venta
          JOIN productos p ON p.id_producto = d.id_producto
          JOIN usuarios u ON u.id_usuario = v.id_usuario';

$parametros = [];
if ($q !== '') {
    $sql .= ' WHERE (p.codigo LIKE :q1 OR p.nombre LIKE :q2
                     OR u.nombre LIKE :q3 OR CAST(v.id_venta AS CHAR) LIKE :q4)';
    $busqueda = '%' . $q . '%';
    $parametros[':q1'] = $busqueda;
    $parametros[':q2'] = $busqueda;
    $parametros[':q3'] = $busqueda;
    $parametros[':q4'] = $busqueda;
}
$sql .= ' ORDER BY v.fecha_hora DESC, v.id_venta DESC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$ventas = $stmt->fetchAll();

$mensajes = ['creado' => 'Venta registrada. El stock fue descontado correctamente.'];
$mensaje = isset($_GET['msg']) && isset($mensajes[$_GET['msg']]) ? $mensajes[$_GET['msg']] : '';

$titulo = 'TecnoStock - Ventas';
$base = '../';
$pagina = 'ventas';
require __DIR__ . '/../includes/header.php';
?>

<h1>Ventas</h1>

<?php if ($mensaje !== ''): ?>
    <p class="mensaje"><?= e($mensaje) ?></p>
<?php endif; ?>

<div class="card">
    <form class="barra-filtros" method="get" action="index.php">
        <div>
            <label for="q">Buscar venta</label>
            <input type="text" id="q" name="q" placeholder="Producto, usuario o numero de venta" value="<?= e($q) ?>">
        </div>
        <div class="filtros-acciones">
            <button type="submit">Buscar</button>
            <a class="btn btn-gris" href="index.php">Limpiar</a>
            <a class="btn btn-acento" href="crear.php">Nueva venta</a>
        </div>
    </form>
</div>

<div class="card">
    <h2>Registro de ventas <small><?= count($ventas) ?> registros</small></h2>

    <?php if (empty($ventas)): ?>
        <p class="vacio">No hay ventas con este buscador.</p>
    <?php else: ?>
    <div class="tabla-scroll">
    <table>
        <thead>
            <tr>
                <th>N.</th>
                <th>Fecha</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio unit.</th>
                <th>Total</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($ventas as $fila): ?>
            <tr>
                <td><?= (int) $fila['id_venta'] ?></td>
                <td><?= e($fila['fecha_hora']) ?></td>
                <td><?= e($fila['codigo']) ?> - <?= e($fila['producto']) ?></td>
                <td><?= (int) $fila['cantidad'] ?></td>
                <td><?= e($fila['moneda']) ?> <?= e($fila['precio_unitario']) ?></td>
                <td><strong><?= e($fila['moneda']) ?> <?= e($fila['total']) ?></strong></td>
                <td><?= e($fila['usuario']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
