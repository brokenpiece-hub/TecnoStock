<?php
/* Aviso flotante de stock bajo. Se muestra al entrar a Inicio, Productos o Stock.
   No guarda el cierre: al volver a cargar cualquiera de esos modulos aparece de nuevo. */
$avisoStockBajo = 0;
if (isset($pdo) && function_exists('tiene_permiso') && (tiene_permiso('productos') || tiene_permiso('stock'))) {
    $avisoStockBajo = (int) $pdo->query(
        'SELECT COUNT(*) FROM productos WHERE estado = 1 AND stock_actual < stock_minimo'
    )->fetchColumn();
}

if ($avisoStockBajo > 0):
    if (($pagina ?? '') === 'stock' && tiene_permiso('stock')) {
        $destinoAviso = 'stock/index.php?estado=1&stock_critico=1';
    } elseif (tiene_permiso('productos')) {
        $destinoAviso = 'productos/index.php?estado=1&stock_critico=1';
    } else {
        $destinoAviso = 'stock/index.php?estado=1&stock_critico=1';
    }
    $textoBoton = 'Ver stock crítico';
?>
<div class="aviso-flotante" id="aviso-stock" role="status" aria-live="polite">
    <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        <path d="M12 3 2 20h20L12 3Z"/><path d="M12 9v5"/><circle cx="12" cy="18" r=".5"/>
    </svg>
    <span><strong>Stock bajo:</strong> <?= $avisoStockBajo ?> producto(s) bajo el minimo.</span>
    <a class="btn btn-pequeno" href="<?= e($base) ?><?= e($destinoAviso) ?>"><?= e($textoBoton) ?></a>
    <button type="button" class="aviso-cerrar" aria-label="Quitar aviso de stock bajo">×</button>
</div>
<?php endif; ?>
