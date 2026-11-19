<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';

require_login();

$productos = (int) $pdo->query('SELECT COUNT(*) FROM productos WHERE estado = 1')->fetchColumn();
$stockBajo = (int) $pdo->query(
    'SELECT COUNT(*) FROM productos WHERE estado = 1 AND stock_actual < stock_minimo'
)->fetchColumn();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>TecnoStock - Inicio</title>
</head>
<body>
    <h1>TecnoStock</h1>
    <p>Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre'], ENT_QUOTES, 'UTF-8') ?></p>

    <p>Productos activos: <?= $productos ?></p>
    <p>Productos con stock bajo: <?= $stockBajo ?></p>

    <p>
        <a href="productos/index.php">Ver productos</a> |
        <a href="productos/crear.php">Nuevo producto</a> |
        <a href="logout.php">Cerrar sesion</a>
    </p>
</body>
</html>
