<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

requerir_permiso('ventas', '../');

$sql = 'SELECT v.id_venta, v.fecha_hora, v.total, v.moneda, u.nombre AS usuario,
               p.codigo, p.nombre AS producto, d.cantidad, d.precio_unitario
          FROM ventas v
          JOIN detalle_ventas d ON d.id_venta = v.id_venta
          JOIN productos p ON p.id_producto = d.id_producto
          JOIN usuarios u ON u.id_usuario = v.id_usuario
         ORDER BY v.fecha_hora DESC, v.id_venta DESC
         LIMIT 100';

$ventas = $pdo->query($sql)->fetchAll();

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
    <form class="barra-acciones" method="get" action="index.php">
        <a class="btn btn-verde" href="crear.php">Nueva venta</a>
    </form>
</div>

<div class="card">
    <h2>Registro de ventas <small><?= count($ventas) ?> registros</small></h2>

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
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
