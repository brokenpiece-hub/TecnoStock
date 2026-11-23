<?php

session_start();

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/funciones.php';

require_login();

$id = (int) $_SESSION['usuario_id'];
$esAdmin = tiene_permiso('usuarios');

$stmt = $pdo->prepare(
    'SELECT id_usuario, nombre, correo, rut, cargo, foto FROM usuarios WHERE id_usuario = :id'
);
$stmt->execute([':id' => $id]);
$usuario = $stmt->fetch();

if ($usuario === false) {
    session_destroy();
    redirigir('index.php');
}

$datos = [
    'nombre' => $usuario['nombre'],
    'rut'    => $usuario['rut'] ?? '',
    'cargo'  => $usuario['cargo'] ?? '',
];
$errores = [];
$accion = $_POST['accion'] ?? '';

/* El administrador acepta o rechaza solicitudes de otros usuarios. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $esAdmin && in_array($accion, ['aceptar', 'rechazar'], true)) {
    $idSolicitud = entero_no_negativo($_POST['id_solicitud'] ?? null);
    $solicitud = null;

    if ($idSolicitud !== null && $idSolicitud !== 0) {
        $stmt = $pdo->prepare(
            'SELECT id_usuario, clave_hash, estado FROM solicitudes_clave WHERE id_solicitud = :id'
        );
        $stmt->execute([':id' => $idSolicitud]);
        $solicitud = $stmt->fetch();
    }

    if ($solicitud === false || $solicitud === null || $solicitud['estado'] !== 'pendiente') {
        $errores[] = 'La solicitud ya fue revisada o no existe.';
    } else {
        $estadoNuevo = $accion === 'aceptar' ? 'aprobada' : 'rechazada';
        $pdo->beginTransaction();
        try {
            if ($accion === 'aceptar') {
                $pdo->prepare('UPDATE usuarios SET clave = :clave WHERE id_usuario = :id')
                    ->execute([':clave' => $solicitud['clave_hash'], ':id' => $solicitud['id_usuario']]);
            }
            $pdo->prepare('UPDATE solicitudes_clave SET estado = :estado WHERE id_solicitud = :id')
                ->execute([':estado' => $estadoNuevo, ':id' => $idSolicitud]);
            $pdo->commit();
            redirigir('perfil.php?msg=' . ($accion === 'aceptar' ? 'clave_aceptada' : 'solicitud_rechazada'));
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = 'No se pudo actualizar la solicitud.';
        }
    }
}

/* Guardar nombre, RUT, cargo y foto del perfil. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'guardar') {
    $datos['nombre'] = trim($_POST['nombre'] ?? '');
    $rut = rut_normalizado($_POST['rut'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $quitarFoto = isset($_POST['quitar_foto']);
    $fotoNueva = procesar_imagen_usuario($_FILES['foto'] ?? null);

    if (strlen($datos['nombre']) < 2 || strlen($datos['nombre']) > 100) {
        $errores[] = 'El nombre debe tener entre 2 y 100 caracteres.';
    }

    if (trim((string) ($_POST['rut'] ?? '')) !== '') {
        if ($rut === '') {
            $errores[] = 'El RUT debe tener un formato como 12.345.678-9.';
        } elseif (!rut_disponible($pdo, $rut, $id)) {
            $errores[] = 'Ese RUT ya esta registrado.';
        }
    } else {
        $rut = null;
    }

    if ($cargo !== '' && strlen($cargo) > 80) {
        $errores[] = 'El cargo debe tener maximo 80 caracteres.';
    }

    if ($fotoNueva['error'] !== '') {
        $errores[] = $fotoNueva['error'];
    }

    if (empty($errores)) {
        $fotoFinal = $usuario['foto'];
        if ($fotoNueva['foto'] !== null) {
            $fotoFinal = $fotoNueva['foto'];
        } elseif ($quitarFoto) {
            $fotoFinal = null;
        }

        $stmt = $pdo->prepare(
            'UPDATE usuarios SET nombre = :nombre, rut = :rut, cargo = :cargo, foto = :foto
              WHERE id_usuario = :id'
        );
        $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':rut'    => $rut,
            ':cargo'  => $cargo !== '' ? $cargo : null,
            ':foto'   => $fotoFinal,
            ':id'     => $id,
        ]);

        if ($fotoNueva['foto'] !== null && $usuario['foto'] !== null) {
            borrar_imagen_usuario($usuario['foto']);
        } elseif ($quitarFoto && $fotoNueva['foto'] === null) {
            borrar_imagen_usuario($usuario['foto']);
        }

        $_SESSION['usuario_nombre'] = $datos['nombre'];
        $_SESSION['usuario_foto']   = $fotoFinal;
        redirigir('perfil.php?msg=guardado');
    }

    $datos['rut'] = $_POST['rut'] ?? '';
    $datos['cargo'] = $cargo;
}

/* El usuario propone una clave nueva; queda pendiente de aprobacion. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'solicitar_clave') {
    $clave = $_POST['clave_nueva'] ?? '';
    $confirmar = $_POST['clave_confirmar'] ?? '';

    if (strlen($clave) < 6) {
        $errores[] = 'La contrasena debe tener al menos 6 caracteres.';
    }
    if ($clave !== $confirmar) {
        $errores[] = 'Las contrasenas no coinciden.';
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare(
            'INSERT INTO solicitudes_clave (id_usuario, clave_hash) VALUES (:id, :clave)'
        );
        $stmt->execute([
            ':id'    => $id,
            ':clave' => password_hash($clave, PASSWORD_DEFAULT),
        ]);
        redirigir('perfil.php?msg=solicitud_enviada');
    }
}

/* Estado de la ultima solicitud del usuario. */
$stmt = $pdo->prepare(
    'SELECT estado, fecha_hora FROM solicitudes_clave
      WHERE id_usuario = :id ORDER BY id_solicitud DESC LIMIT 1'
);
$stmt->execute([':id' => $id]);
$miSolicitud = $stmt->fetch();

$pendientes = [];
if ($esAdmin) {
    $pendientes = $pdo->query(
        'SELECT s.id_solicitud, s.fecha_hora, u.nombre, u.rut, u.cargo
           FROM solicitudes_clave s
           JOIN usuarios u ON u.id_usuario = s.id_usuario
          WHERE s.estado = "pendiente"
          ORDER BY s.fecha_hora DESC'
    )->fetchAll();
}

$titulo = 'TecnoStock - Mi perfil';
$base = '';
$pagina = 'perfil';
require __DIR__ . '/includes/header.php';
?>

<h1>Mi perfil</h1>

<div class="perfil-layout">
    <div class="card perfil-datos">
        <?php if (!empty($errores)): ?>
            <ul class="error" role="alert">
                <?php foreach ($errores as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="perfil-cabecera">
            <?php if (!empty($usuario['foto'])): ?>
                <img class="avatar avatar-grande" src="<?= e(ruta_imagen_usuario($usuario['foto'])) ?>" alt="Foto de perfil">
            <?php else: ?>
                <span class="avatar avatar-grande avatar-inicial"><?= e(strtoupper(substr($datos['nombre'], 0, 1))) ?></span>
            <?php endif; ?>
            <div>
                <h2><?= e($datos['nombre']) ?></h2>
                <p><?= e($usuario['correo']) ?></p>
            </div>
        </div>

        <form method="post" action="perfil.php" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="guardar">

            <div class="form-grid">
                <div>
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" required maxlength="100" value="<?= e($datos['nombre']) ?>">
                </div>
                <div>
                    <label for="rut">RUT</label>
                    <input type="text" id="rut" name="rut" maxlength="20" placeholder="12.345.678-9" value="<?= e($datos['rut']) ?>">
                </div>
                <div>
                    <label for="cargo">Cargo</label>
                    <input type="text" id="cargo" name="cargo" maxlength="80" placeholder="Escriba su cargo" value="<?= e($datos['cargo']) ?>">
                </div>
                <div class="form-completo">
                    <label for="foto">Foto de perfil</label>
                    <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
                    <small class="ayuda">JPG, PNG o WEBP. Maximo 2 MB.</small>
                </div>
            </div>

            <?php if (!empty($usuario['foto'])): ?>
                <label class="check-linea" for="quitar_foto">
                    <input type="checkbox" id="quitar_foto" name="quitar_foto" value="1">
                    Quitar la foto actual
                </label>
            <?php endif; ?>

            <p><button type="submit">Guardar perfil</button></p>
        </form>
    </div>

    <div class="card perfil-clave">
        <h2>Cambiar contrasena</h2>
        <p class="ayuda">La solicitud debe ser aprobada por un administrador.</p>

        <?php if ($miSolicitud !== false): ?>
            <p class="mensaje">
                Ultima solicitud: <strong><?= e($miSolicitud['estado']) ?></strong>
                (<?= e($miSolicitud['fecha_hora']) ?>)
            </p>
        <?php endif; ?>

        <form method="post" action="perfil.php">
            <input type="hidden" name="accion" value="solicitar_clave">
            <label for="clave_nueva">Nueva contrasena</label>
            <input type="password" id="clave_nueva" name="clave_nueva" minlength="6" required autocomplete="new-password">

            <label for="clave_confirmar">Repetir contrasena</label>
            <input type="password" id="clave_confirmar" name="clave_confirmar" minlength="6" required autocomplete="new-password">

            <p><button type="submit">Enviar solicitud</button></p>
        </form>
    </div>

    <?php if ($esAdmin): ?>
        <div class="card perfil-solicitudes">
            <h2>Solicitudes de contrasena <small><?= count($pendientes) ?> pendientes</small></h2>

            <?php if (empty($pendientes)): ?>
                <p class="vacio">No hay solicitudes pendientes.</p>
            <?php else: ?>
                <div class="lista-solicitudes">
                    <?php foreach ($pendientes as $solicitud): ?>
                        <div class="solicitud">
                            <div class="solicitud-datos">
                                <strong><?= e($solicitud['nombre']) ?></strong>
                                <small>
                                    <?= e($solicitud['rut'] ?: 'Sin RUT') ?>
                                    <?php if (!empty($solicitud['cargo'])): ?> · <?= e($solicitud['cargo']) ?><?php endif; ?>
                                    · <?= e($solicitud['fecha_hora']) ?>
                                </small>
                            </div>
                            <div class="solicitud-botones">
                                <form method="post" action="perfil.php">
                                    <input type="hidden" name="accion" value="aceptar">
                                    <input type="hidden" name="id_solicitud" value="<?= (int) $solicitud['id_solicitud'] ?>">
                                    <button type="submit" class="btn-ticket" title="Aceptar">
                                        <span aria-hidden="true">✓</span> Aceptar
                                    </button>
                                </form>
                                <form method="post" action="perfil.php">
                                    <input type="hidden" name="accion" value="rechazar">
                                    <input type="hidden" name="id_solicitud" value="<?= (int) $solicitud['id_solicitud'] ?>">
                                    <button type="submit" class="btn-equis" title="Rechazar">
                                        <span aria-hidden="true">✗</span> Rechazar
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
