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

<?php if (!$login && in_array($pagina, ['inicio', 'productos', 'stock'], true)): ?>
    <?php require __DIR__ . '/aviso_stock.php'; ?>
<?php endif; ?>
</body>
</html>
