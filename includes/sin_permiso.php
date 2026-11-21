<?php
require __DIR__ . '/header.php';
?>

<h1>Acceso denegado</h1>

<div class="card">
    <p>No tienes permiso para ver este modulo.</p>
    <p>Si necesitas acceso, pideselo al administrador del sistema.</p>
    <p>
        <a class="btn" href="<?= e($base) ?>dashboard.php">Volver al inicio</a>
    </p>
</div>

<?php require __DIR__ . '/footer.php'; ?>
