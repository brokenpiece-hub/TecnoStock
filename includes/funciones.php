<?php

function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function redirigir(string $ruta): void
{
    header('Location: ' . $ruta);
    exit;
}

function entero_no_negativo($valor): ?int
{
    if (!is_scalar($valor)) {
        return null;
    }

    $texto = trim((string) $valor);
    if ($texto === '' || filter_var($texto, FILTER_VALIDATE_INT) === false) {
        return null;
    }

    $numero = (int) $texto;
    return $numero >= 0 ? $numero : null;
}

function precio_valido($valor): ?string
{
    if (!is_scalar($valor)) {
        return null;
    }

    $texto = trim((string) $valor);
    if ($texto === '' || !is_numeric($texto)) {
        return null;
    }

    $numero = (float) $texto;
    if ($numero < 0) {
        return null;
    }

    return number_format($numero, 2, '.', '');
}

function modulos_disponibles($pdo): array
{
    return $pdo->query('SELECT id_modulo, clave, nombre FROM modulos ORDER BY id_modulo')->fetchAll();
}

function permisos_de_usuario($pdo, $idUsuario): array
{
    $stmt = $pdo->prepare('SELECT id_modulo FROM usuario_modulos WHERE id_usuario = :id');
    $stmt->execute([':id' => $idUsuario]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function guardar_permisos($pdo, $idUsuario, $modulos): void
{
    $pdo->prepare('DELETE FROM usuario_modulos WHERE id_usuario = :id')->execute([':id' => $idUsuario]);

    $stmt = $pdo->prepare(
        'INSERT INTO usuario_modulos (id_usuario, id_modulo) VALUES (:id, :modulo)'
    );
    foreach ($modulos as $idModulo) {
        $stmt->execute([
            ':id'     => $idUsuario,
            ':modulo' => (int) $idModulo,
        ]);
    }
}
