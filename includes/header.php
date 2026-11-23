<?php
/* Evita que el navegador muestre paginas guardadas con datos viejos. */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');

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

$iconos = [
    'logo'         => '<svg class="icono logo-icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="M3 7v10l9 5 9-5V7"/><path d="M12 12v10"/></svg>',
    'inicio'       => '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9v11h14V9"/><path d="M9 20v-6h6v6"/></svg>',
    'productos'    => '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="M3 7v10l9 5 9-5V7"/><path d="M12 12v10"/></svg>',
    'nuevo'        => '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>',
    'movimientos'  => '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 8h14"/><path d="m15 5 3 3-3 3"/><path d="M20 16H6"/><path d="m9 13-3 3 3 3"/></svg>',
    'stock'        => '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 16 9 5 9-5"/></svg>',
    'ventas'       => '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 6h2l2 10h10l2-7H6"/><circle cx="9" cy="19" r="1"/><circle cx="17" cy="19" r="1"/></svg>',
    'usuarios'     => '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-3 3-5 6-5s6 2 6 5"/><path d="M17 8a3 3 0 0 1 0 6"/><path d="M18 15c2 .5 3 2 3 4"/></svg>',
    'sol'          => '<svg class="icono tema-icono-claro" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"/></svg>',
    'luna'         => '<svg class="icono tema-icono-oscuro" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5Z"/></svg>',
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?></title>
    <script>
        /* Define el tema antes de cargar el CSS para evitar el parpadeo. */
        (function () {
            var guardado = null;
            try {
                guardado = localStorage.getItem('tecnostock-tema');
            } catch (error) {
                // Si falla, se usa el modo oscuro.
            }
            document.documentElement.dataset.tema = guardado === 'claro' ? 'claro' : 'oscuro';
            document.documentElement.style.colorScheme = guardado === 'claro' ? 'light' : 'dark';
        })();
    </script>
    <link rel="stylesheet" href="<?= e($base) ?>assets/estilos.css?v=5">
    <script src="<?= e($base) ?>assets/tema.js?v=1" defer></script>
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
<a class="skip-link" href="#contenido-principal">Saltar al contenido</a>
<div class="layout">
    <aside class="sidebar">
        <div class="logo">
            <?= $iconos['logo'] ?>
            <span class="logo-texto">TecnoStock <small>ERP de inventario</small></span>
        </div>

        <nav class="nav" aria-label="Modulos del sistema">
            <a href="<?= e($base) ?>dashboard.php" class="<?= $pagina === 'inicio' ? 'activo' : '' ?>" <?= $pagina === 'inicio' ? 'aria-current="page"' : '' ?>>
                <?= $iconos['inicio'] ?><span>Inicio</span>
            </a>
            <?php if (tiene_permiso('productos')): ?>
                <a href="<?= e($base) ?>productos/index.php" class="<?= $pagina === 'productos' ? 'activo' : '' ?>" <?= $pagina === 'productos' ? 'aria-current="page"' : '' ?>>
                    <?= $iconos['productos'] ?><span>Productos</span>
                </a>
                <a href="<?= e($base) ?>productos/crear.php" class="<?= $pagina === 'nuevo' ? 'activo' : '' ?>" <?= $pagina === 'nuevo' ? 'aria-current="page"' : '' ?>>
                    <?= $iconos['nuevo'] ?><span>Nuevo producto</span>
                </a>
            <?php endif; ?>
            <?php if (tiene_permiso('movimientos')): ?>
                <a href="<?= e($base) ?>movimientos/index.php" class="<?= $pagina === 'movimientos' ? 'activo' : '' ?>" <?= $pagina === 'movimientos' ? 'aria-current="page"' : '' ?>>
                    <?= $iconos['movimientos'] ?><span>Movimientos</span>
                </a>
            <?php endif; ?>
            <?php if (tiene_permiso('stock')): ?>
                <a href="<?= e($base) ?>stock/index.php" class="<?= $pagina === 'stock' ? 'activo' : '' ?>" <?= $pagina === 'stock' ? 'aria-current="page"' : '' ?>>
                    <?= $iconos['stock'] ?><span>Stock</span>
                </a>
            <?php endif; ?>
            <?php if (tiene_permiso('ventas')): ?>
                <a href="<?= e($base) ?>ventas/index.php" class="<?= $pagina === 'ventas' ? 'activo' : '' ?>" <?= $pagina === 'ventas' ? 'aria-current="page"' : '' ?>>
                    <?= $iconos['ventas'] ?><span>Ventas</span>
                </a>
            <?php endif; ?>
            <?php if (tiene_permiso('usuarios')): ?>
                <a href="<?= e($base) ?>usuarios/index.php" class="<?= $pagina === 'usuarios' ? 'activo' : '' ?>" <?= $pagina === 'usuarios' ? 'aria-current="page"' : '' ?>>
                    <?= $iconos['usuarios'] ?><span>Usuarios</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-pie">Sistema de inventario<br>Versión MVP</div>
    </aside>

    <div class="principal">
        <header class="barra-superior">
            <span class="barra-titulo"><?= e($titulo) ?></span>
            <button type="button" class="tema-toggle" id="tema-toggle"
                    aria-label="Cambiar a modo claro" aria-pressed="true">
                <?= $iconos['sol'] ?>
                <?= $iconos['luna'] ?>
                <span class="tema-texto">Modo claro</span>
            </button>
            <span class="barra-usuario"><?= e($_SESSION['usuario_nombre'] ?? '') ?></span>
            <a href="<?= e($base) ?>logout.php">Cerrar sesión</a>
        </header>

        <main class="contenido" id="contenido-principal" tabindex="-1">
<?php endif; ?>
