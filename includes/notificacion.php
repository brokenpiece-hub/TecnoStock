<?php
/* Notificacion flotante de exito. Aparece al volver de crear, editar, etc. */
if (empty($mensajeToast)) {
    return;
}
?>
<div class="notificacion-flotante" id="notificacion-flotante" role="status" aria-live="polite">
    <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        <circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>
    </svg>
    <span><strong>Listo.</strong> <?= e($mensajeToast) ?></span>
    <button type="button" class="notificacion-cerrar" aria-label="Cerrar notificacion">×</button>
</div>
