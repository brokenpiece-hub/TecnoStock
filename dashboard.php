<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/funciones.php';

require_login();

$productos = (int) $pdo->query('SELECT COUNT(*) FROM productos WHERE estado = 1')->fetchColumn();
$stockBajo = (int) $pdo->query(
    'SELECT COUNT(*) FROM productos WHERE estado = 1 AND stock_actual < stock_minimo'
)->fetchColumn();
$ventas = (int) $pdo->query('SELECT COUNT(*) FROM ventas')->fetchColumn();
$movimientos = (int) $pdo->query('SELECT COUNT(*) FROM movimientos')->fetchColumn();
$titulo = 'TecnoStock - Inicio';
$base = '';
$pagina = 'inicio';
require __DIR__ . '/includes/header.php';
?>

<h1>Inicio</h1>
<p>Bienvenido, <strong><?= e($_SESSION['usuario_nombre']) ?></strong></p>

<div class="metricas">
    <div class="metrica">
        <small>Productos activos</small>
        <strong><?= $productos ?></strong>
    </div>
    <div class="metrica verde">
        <small>Productos con stock bajo</small>
        <strong><?= $stockBajo ?></strong>
    </div>
    <div class="metrica">
        <small>Ventas registradas</small>
        <strong><?= $ventas ?></strong>
    </div>
    <div class="metrica verde">
        <small>Movimientos</small>
        <strong><?= $movimientos ?></strong>
    </div>
</div>

<div class="card">
    <h2>Accesos rapidos</h2>
    <p>
        <a class="btn" href="productos/index.php">Ver productos</a>
        <a class="btn btn-verde" href="productos/crear.php">Nuevo producto</a>
        <a class="btn" href="movimientos/index.php">Movimientos</a>
        <a class="btn btn-verde" href="ventas/index.php">Ventas</a>
        <a class="btn btn-gris" href="ventas/crear.php">Nueva venta</a>
    </p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
