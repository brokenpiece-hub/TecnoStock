<?php

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

function cargar_permisos($pdo, $idUsuario): void
{
    $stmt = $pdo->prepare(
        'SELECT m.clave
           FROM modulos m
           JOIN usuario_modulos um ON um.id_modulo = m.id_modulo
          WHERE um.id_usuario = :id'
    );
    $stmt->execute([':id' => $idUsuario]);
    $_SESSION['permisos'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function tiene_permiso($clave): bool
{
    if (!isset($_SESSION['permisos']) && esta_autenticado() && isset($GLOBALS['pdo'])) {
        cargar_permisos($GLOBALS['pdo'], (int) $_SESSION['usuario_id']);
    }

    return isset($_SESSION['permisos']) && in_array($clave, $_SESSION['permisos'], true);
}

function requerir_permiso($clave, $base = ''): void
{
    require_login();

    if (tiene_permiso($clave)) {
        return;
    }

    http_response_code(403);
    $titulo = 'TecnoStock - Acceso denegado';
    $pagina = '';
    require __DIR__ . '/sin_permiso.php';
    exit;
}
