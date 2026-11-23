<?php

session_start();

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/funciones.php';

require_login();

$productos = (int) $pdo->query('SELECT COUNT(*) FROM productos WHERE estado = 1')->fetchColumn();
$stockBajo = (int) $pdo->query(
    'SELECT COUNT(*) FROM productos WHERE estado = 1 AND stock_actual < stock_minimo'
)->fetchColumn();
$movimientos = (int) $pdo->query('SELECT COUNT(*) FROM movimientos')->fetchColumn();
$ventas = (int) $pdo->query('SELECT COUNT(*) FROM ventas')->fetchColumn();
$usuarios = 0;
if (tiene_permiso('usuarios')) {
    $usuarios = (int) $pdo->query('SELECT COUNT(*) FROM usuarios WHERE estado = 1')->fetchColumn();
}

$alertas = $pdo->query(
    'SELECT codigo, nombre, stock_actual, stock_minimo
       FROM productos
      WHERE estado = 1 AND stock_actual < stock_minimo
      ORDER BY codigo
      LIMIT 5'
)->fetchAll();

$ultimosMovimientos = $pdo->query(
    'SELECT m.fecha_hora, m.tipo, m.cantidad, p.codigo
       FROM movimientos m
       JOIN productos p ON p.id_producto = m.id_producto
      ORDER BY m.fecha_hora DESC, m.id_movimiento DESC
      LIMIT 5'
)->fetchAll();

$ultimasVentas = $pdo->query(
    'SELECT v.id_venta, v.fecha_hora, v.total, v.moneda, p.codigo, d.cantidad
       FROM ventas v
       JOIN detalle_ventas d ON d.id_venta = v.id_venta
       JOIN productos p ON p.id_producto = d.id_producto
      ORDER BY v.fecha_hora DESC, v.id_venta DESC
      LIMIT 5'
)->fetchAll();

$titulo = 'TecnoStock - Inicio';
$base = '';
$pagina = 'inicio';
require __DIR__ . '/includes/header.php';
?>

<h1>Inicio</h1>
<p>Bienvenido, <strong><?= e($_SESSION['usuario_nombre']) ?></strong></p>

<?php if ($stockBajo > 0 && (tiene_permiso('productos') || tiene_permiso('stock'))): ?>
    <div class="alerta-stock" role="status">
        <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M12 3 2 20h20L12 3Z"/><path d="M12 9v5"/><circle cx="12" cy="18" r=".5"/>
        </svg>
        <span>
            <strong><?= $stockBajo ?> producto(s) con stock bajo.</strong>
            Revisa el inventario antes de registrar una venta.
        </span>
        <a class="btn btn-pequeno" href="<?= tiene_permiso('productos') ? 'productos/index.php' : 'stock/index.php' ?>">
            <?= tiene_permiso('productos') ? 'Ver productos' : 'Ver stock' ?>
        </a>
    </div>
<?php endif; ?>

<div class="metricas">
    <div class="metrica">
        <small>Productos activos</small>
        <strong><?= $productos ?></strong>
    </div>
    <div class="metrica alerta-metrica">
        <small>Productos con stock bajo</small>
        <strong><?= $stockBajo ?></strong>
    </div>
    <div class="metrica">
        <small>Movimientos</small>
        <strong><?= $movimientos ?></strong>
    </div>
    <div class="metrica exito-metrica">
        <small>Ventas registradas</small>
        <strong><?= $ventas ?></strong>
    </div>
    <?php if (tiene_permiso('usuarios')): ?>
        <div class="metrica">
            <small>Usuarios activos</small>
            <strong><?= $usuarios ?></strong>
        </div>
    <?php endif; ?>
</div>

<?php if (tiene_permiso('productos') || tiene_permiso('stock')): ?>
    <div class="card">
        <h2>Alertas de stock <small><?= $stockBajo ?> productos bajo el minimo</small></h2>
        <?php if (empty($alertas)): ?>
            <p class="mensaje">Todos los productos tienen stock suficiente.</p>
        <?php else: ?>
            <div class="tabla-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Producto</th>
                        <th>Stock actual</th>
                        <th>Stock minimo</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($alertas as $fila): ?>
                    <tr>
                        <td><?= e($fila['codigo']) ?></td>
                        <td><?= e($fila['nombre']) ?></td>
                        <td><span class="badge badge-rojo"><?= (int) $fila['stock_actual'] ?></span></td>
                        <td><?= (int) $fila['stock_minimo'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="dashboard-dos">
    <?php if (tiene_permiso('movimientos')): ?>
        <div class="card">
            <h2>Ultimos movimientos</h2>
            <?php if (empty($ultimosMovimientos)): ?>
                <p>Sin movimientos registrados.</p>
            <?php else: ?>
                <div class="tabla-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th>Cant.</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ultimosMovimientos as $fila): ?>
                        <tr>
                            <td><?= e($fila['fecha_hora']) ?></td>
                            <td><?= e($fila['codigo']) ?></td>
                            <td>
                                <span class="badge <?= $fila['tipo'] === 'entrada' ? 'badge-verde' : 'badge-gris' ?>">
                                    <?= e($fila['tipo']) ?>
                                </span>
                            </td>
                            <td><?= (int) $fila['cantidad'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (tiene_permiso('ventas')): ?>
        <div class="card">
            <h2>Ultimas ventas</h2>
            <?php if (empty($ultimasVentas)): ?>
                <p>Sin ventas registradas.</p>
            <?php else: ?>
                <div class="tabla-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>N.</th>
                            <th>Producto</th>
                            <th>Cant.</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ultimasVentas as $fila): ?>
                        <tr>
                            <td><?= (int) $fila['id_venta'] ?></td>
                            <td><?= e($fila['codigo']) ?></td>
                            <td><?= (int) $fila['cantidad'] ?></td>
                            <td><?= e($fila['moneda']) ?> <?= e($fila['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Accesos rapidos</h2>
    <p>
        <?php if (tiene_permiso('productos')): ?>
            <a class="btn" href="productos/index.php">Ver productos</a>
            <a class="btn btn-acento" href="productos/crear.php">Nuevo producto</a>
        <?php endif; ?>
        <?php if (tiene_permiso('movimientos')): ?>
            <a class="btn" href="movimientos/index.php">Movimientos</a>
            <a class="btn btn-acento" href="movimientos/crear.php">Nuevo movimiento</a>
        <?php endif; ?>
        <?php if (tiene_permiso('stock')): ?>
            <a class="btn" href="stock/index.php">Stock</a>
        <?php endif; ?>
        <?php if (tiene_permiso('ventas')): ?>
            <a class="btn" href="ventas/index.php">Ventas</a>
            <a class="btn btn-acento" href="ventas/crear.php">Nueva venta</a>
        <?php endif; ?>
        <?php if (tiene_permiso('usuarios')): ?>
            <a class="btn" href="usuarios/index.php">Usuarios y permisos</a>
        <?php endif; ?>
    </p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
