<?php
declare(strict_types=1);

function esta_autenticado(): bool
{
    return isset($_SESSION['usuario_id'], $_SESSION['usuario_nombre']);
}

function require_login(): void
{
    if (!esta_autenticado()) {
        header('Location: /tecnostock/index.php');
        exit;
    }
}
