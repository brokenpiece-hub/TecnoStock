<?php
if (!isset($login)) {
    $login = false;
}
if ($login): ?>
    </div>
</div>
<?php else: ?>
        </main>
    </div>
</div>
<?php endif; ?>

<?php if (!$login): ?>
    <?php if (in_array($pagina, ['inicio', 'productos', 'stock'], true)): ?>
        <?php require __DIR__ . '/aviso_stock.php'; ?>
    <?php endif; ?>
    <?php
    require_once __DIR__ . '/mensajes.php';
    $claveMensaje = $_GET['msg'] ?? '';
    if (!is_string($claveMensaje)) {
        $claveMensaje = '';
    }
    $mensajeToast = texto_mensaje($pagina ?? '', $claveMensaje);
    require __DIR__ . '/notificacion.php';
    ?>
<?php endif; ?>
</body>
</html>
