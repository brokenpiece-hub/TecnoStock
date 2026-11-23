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

/* Datos para la barra de stock de las tarjetas.
   La referencia es siempre 50: con 50 o mas la barra queda llena y desde ahi baja. */
function barra_stock($actual, $minimo): array
{
    $actual = max(0, (int) $actual);
    $minimo = max(0, (int) $minimo);

    $objetivo = 50;
    $porcentaje = (int) round(($actual / $objetivo) * 100);
    $porcentaje = max(0, min(100, $porcentaje));

    /* En critico o por debajo la barra nunca se ve llena. */
    if ($actual <= $minimo) {
        $porcentaje = min($porcentaje, 95);
    }

    if ($actual < $minimo) {
        $color = 'rojo';
        $clase = 'barra-roja';
        $texto = 'bajo el stock critico';
    } elseif ($actual === $minimo) {
        $color = 'amarillo';
        $clase = 'barra-amarilla';
        $texto = 'en el stock critico';
    } else {
        $color = 'verde';
        $clase = 'barra-verde';
        $texto = 'sobre el stock critico';
    }

    return [
        'porcentaje' => $porcentaje,
        'color'      => $color,
        'clase'      => $clase,
        'texto'      => $texto,
    ];
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

function ruta_imagen($archivo, $base = ''): string
{
    if ($archivo === null || $archivo === '') {
        return '';
    }

    return $base . 'assets/img/productos/' . rawurlencode($archivo);
}

function procesar_imagen_producto($archivo): array
{
    if (!is_array($archivo) || !isset($archivo['error']) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return ['imagen' => null, 'error' => ''];
    }

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return ['imagen' => null, 'error' => 'No se pudo subir la imagen.'];
    }

    if ($archivo['size'] > 2 * 1024 * 1024) {
        return ['imagen' => null, 'error' => 'La imagen debe pesar maximo 2 MB.'];
    }

    $info = @getimagesize($archivo['tmp_name']);
    $extensiones = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    if ($info === false || !isset($extensiones[$info[2]])) {
        return ['imagen' => null, 'error' => 'La imagen debe ser JPG, PNG o WEBP.'];
    }

    $directorio = __DIR__ . '/../assets/img/productos';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    $nombre = 'prod_' . bin2hex(random_bytes(8)) . '.' . $extensiones[$info[2]];
    if (!move_uploaded_file($archivo['tmp_name'], $directorio . '/' . $nombre)) {
        return ['imagen' => null, 'error' => 'No se pudo guardar la imagen.'];
    }

    return ['imagen' => $nombre, 'error' => ''];
}

function borrar_imagen_producto($archivo): void
{
    if ($archivo === null || $archivo === '') {
        return;
    }

    $ruta = __DIR__ . '/../assets/img/productos/' . basename($archivo);
    if (is_file($ruta)) {
        unlink($ruta);
    }
}

/* RUT de usuario: se guarda sin puntos y con la K en mayuscula. */
function rut_normalizado($valor): string
{
    $rut = strtoupper(str_replace(['.', ' '], '', trim((string) $valor)));
    return preg_match('/^\d{7,8}-[\dK]$/', $rut) ? $rut : '';
}

function rut_valido($valor): bool
{
    return rut_normalizado($valor) !== '';
}

function rut_disponible($pdo, string $rut, int $idExcluido = 0): bool
{
    if ($rut === '') {
        return true;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE rut = :rut AND id_usuario <> :id');
    $stmt->execute([':rut' => $rut, ':id' => $idExcluido]);
    return (int) $stmt->fetchColumn() === 0;
}

function ruta_imagen_usuario($archivo, string $base = ''): string
{
    if ($archivo === null || $archivo === '') {
        return '';
    }

    return $base . 'assets/img/usuarios/' . rawurlencode($archivo);
}

/* Foto de perfil: JPG, PNG o WEBP, maximo 2 MB. */
function procesar_imagen_usuario($archivo): array
{
    if (!is_array($archivo) || !isset($archivo['error']) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return ['foto' => null, 'error' => ''];
    }

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return ['foto' => null, 'error' => 'No se pudo subir la foto.'];
    }

    if ($archivo['size'] > 2 * 1024 * 1024) {
        return ['foto' => null, 'error' => 'La foto debe pesar maximo 2 MB.'];
    }

    $info = @getimagesize($archivo['tmp_name']);
    $extensiones = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    if ($info === false || !isset($extensiones[$info[2]])) {
        return ['foto' => null, 'error' => 'La foto debe ser JPG, PNG o WEBP.'];
    }

    $directorio = __DIR__ . '/../assets/img/usuarios';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    $nombre = 'usr_' . bin2hex(random_bytes(8)) . '.' . $extensiones[$info[2]];
    if (!move_uploaded_file($archivo['tmp_name'], $directorio . '/' . $nombre)) {
        return ['foto' => null, 'error' => 'No se pudo guardar la foto.'];
    }

    return ['foto' => $nombre, 'error' => ''];
}

function borrar_imagen_usuario($archivo): void
{
    if ($archivo === null || $archivo === '') {
        return;
    }

    $ruta = __DIR__ . '/../assets/img/usuarios/' . basename($archivo);
    if (is_file($ruta)) {
        unlink($ruta);
    }
}
