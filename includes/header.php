<?php
if (!isset($titulo)) {
    $titulo = 'TecnoStock';
}
if (!isset($base)) {
    $base = '';
}
if (!isset($pagina)) {
    $pagina = '';
}
if (!isset($login)) {
    $login = false;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?></title>
    <link rel="stylesheet" href="<?= e($base) ?>assets/estilos.css">
</head>
<body class="<?= $login ? 'login-body' : 'app-body' ?>">

<?php if ($login): ?>
<div class="login-box">
    <div class="login-marca">
        <h1>TecnoStock</h1>
        <p>Sistema de control de inventario</p>
    </div>
    <div class="login-form">
<?php else: ?>
<div class="layout">
    <aside class="sidebar">
        <div class="logo">
            <span class="logo-texto">TecnoStock <small>ERP de inventario</small></span>
        </div>

        <nav class="nav">
            <a href="<?= e($base) ?>dashboard.php" class="<?= $pagina === 'inicio' ? 'activo' : '' ?>">Inicio</a>
            <?php if (tiene_permiso('productos')): ?>
                <a href="<?= e($base) ?>productos/index.php" class="<?= $pagina === 'productos' ? 'activo' : '' ?>">Productos</a>
                <a href="<?= e($base) ?>productos/crear.php" class="<?= $pagina === 'nuevo' ? 'activo' : '' ?>">Nuevo producto</a>
            <?php endif; ?>
            <?php if (tiene_permiso('movimientos')): ?>
                <a href="<?= e($base) ?>movimientos/index.php" class="<?= $pagina === 'movimientos' ? 'activo' : '' ?>">Movimientos</a>
            <?php endif; ?>
            <?php if (tiene_permiso('ventas')): ?>
                <a href="<?= e($base) ?>ventas/index.php" class="<?= $pagina === 'ventas' ? 'activo' : '' ?>">Ventas</a>
            <?php endif; ?>
            <?php if (tiene_permiso('usuarios')): ?>
                <a href="<?= e($base) ?>usuarios/index.php" class="<?= $pagina === 'usuarios' ? 'activo' : '' ?>">Usuarios</a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-pie">Sistema de inventario<br>Versión MVP</div>
    </aside>

    <div class="principal">
        <header class="barra-superior">
            <span class="barra-titulo"><?= e($titulo) ?></span>
            <span class="barra-usuario"><?= e($_SESSION['usuario_nombre'] ?? '') ?></span>
            <a href="<?= e($base) ?>logout.php">Cerrar sesión</a>
        </header>

        <main class="contenido">
<?php endif; ?>
