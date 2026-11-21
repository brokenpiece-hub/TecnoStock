<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

requerir_permiso('usuarios', '../');

$id = entero_no_negativo($_GET['id'] ?? $_POST['id'] ?? null);
if ($id === null || $id === 0) {
    redirigir('index.php');
}

$stmt = $pdo->prepare('SELECT id_usuario, nombre, correo, clave, estado FROM usuarios WHERE id_usuario = :id');
$stmt->execute([':id' => $id]);
$usuario = $stmt->fetch();

if ($usuario === false) {
    redirigir('index.php');
}

$modulos = modulos_disponibles($pdo);
$idsValidos = array_map('intval', array_column($modulos, 'id_modulo'));
$permisosActuales = permisos_de_usuario($pdo, $id);
$esPropio = $id === (int) $_SESSION['usuario_id'];

$idModuloUsuarios = 0;
foreach ($modulos as $modulo) {
    if ($modulo['clave'] === 'usuarios') {
        $idModuloUsuarios = (int) $modulo['id_modulo'];
    }
}

$errores = [];
$datos = [
    'nombre'  => $usuario['nombre'],
    'correo'  => $usuario['correo'],
    'estado'  => (string) $usuario['estado'],
    'modulos' => $permisosActuales,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['nombre'] = trim($_POST['nombre'] ?? '');
    $datos['correo'] = trim($_POST['correo'] ?? '');
    $datos['estado'] = trim($_POST['estado'] ?? '1');
    $claveNueva = $_POST['clave'] ?? '';

    $sel = $_POST['modulos'] ?? [];
    $datos['modulos'] = is_array($sel) ? array_map('intval', $sel) : [];

    if ($esPropio) {
        $datos['estado'] = '1';
        $datos['modulos'][] = $idModuloUsuarios;
    }

    if (strlen($datos['nombre']) < 2 || strlen($datos['nombre']) > 100) {
        $errores[] = 'El nombre debe tener entre 2 y 100 caracteres.';
    }

    if (filter_var($datos['correo'], FILTER_VALIDATE_EMAIL) === false) {
        $errores[] = 'Ingrese un correo valido.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE correo = :correo AND id_usuario <> :id');
        $stmt->execute([':correo' => $datos['correo'], ':id' => $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            $errores[] = 'Ese correo ya esta registrado.';
        }
    }

    if ($claveNueva !== '' && strlen($claveNueva) < 6) {
        $errores[] = 'La contrasena debe tener al menos 6 caracteres.';
    }

    if (!in_array($datos['estado'], ['0', '1'], true)) {
        $errores[] = 'El estado no es valido.';
    }

    $datos['modulos'] = array_values(array_unique(array_intersect($datos['modulos'], $idsValidos)));
    if (empty($datos['modulos'])) {
        $errores[] = 'Seleccione al menos un modulo.';
    }

    if (empty($errores)) {
        $pdo->beginTransaction();
        try {
            if ($claveNueva !== '') {
                $update = $pdo->prepare(
                    'UPDATE usuarios
                        SET nombre = :nombre, correo = :correo, clave = :clave, estado = :estado
                      WHERE id_usuario = :id'
                );
                $update->execute([
                    ':nombre' => $datos['nombre'],
                    ':correo' => $datos['correo'],
                    ':clave'  => password_hash($claveNueva, PASSWORD_DEFAULT),
                    ':estado' => (int) $datos['estado'],
                    ':id'     => $id,
                ]);
            } else {
                $update = $pdo->prepare(
                    'UPDATE usuarios
                        SET nombre = :nombre, correo = :correo, estado = :estado
                      WHERE id_usuario = :id'
                );
                $update->execute([
                    ':nombre' => $datos['nombre'],
                    ':correo' => $datos['correo'],
                    ':estado' => (int) $datos['estado'],
                    ':id'     => $id,
                ]);
            }

            guardar_permisos($pdo, $id, $datos['modulos']);
            $pdo->commit();

            if ($esPropio) {
                cargar_permisos($pdo, $id);
            }

            redirigir('index.php?msg=editado');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = 'No se pudo guardar el usuario.';
        }
    }
}

$titulo = 'TecnoStock - Editar usuario';
$base = '../';
$pagina = 'usuarios';
require __DIR__ . '/../includes/header.php';
?>

<h1>Editar usuario</h1>

<div class="card">
    <?php if (!empty($errores)): ?>
        <ul class="error">
            <?php foreach ($errores as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($esPropio): ?>
        <p class="mensaje">Estas editando tu propia cuenta. No puedes desactivarte ni quitar tu acceso a Usuarios.</p>
    <?php endif; ?>

    <form method="post" action="editar.php?id=<?= $id ?>">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="form-grid">
            <div>
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" required value="<?= e($datos['nombre']) ?>">
            </div>
            <div>
                <label for="correo">Correo</label>
                <input type="email" id="correo" name="correo" required value="<?= e($datos['correo']) ?>">
            </div>
            <div>
                <label for="clave">Nueva contrasena (dejar vacio para mantener)</label>
                <input type="password" id="clave" name="clave" minlength="6">
            </div>
            <div>
                <label for="estado">Estado</label>
                <select id="estado" name="estado" <?= $esPropio ? 'disabled' : '' ?>>
                    <option value="1" <?= $datos['estado'] === '1' ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= $datos['estado'] === '0' ? 'selected' : '' ?>>Inactivo</option>
                </select>
                <?php if ($esPropio): ?>
                    <input type="hidden" name="estado" value="1">
                <?php endif; ?>
            </div>
        </div>

        <label>Modulos permitidos</label>
        <?php foreach ($modulos as $modulo): ?>
            <?php $idModulo = (int) $modulo['id_modulo']; ?>
            <label class="check">
                <input type="checkbox" name="modulos[]" value="<?= $idModulo ?>"
                    <?= in_array($idModulo, $datos['modulos'], true) ? 'checked' : '' ?>
                    <?= $esPropio && $idModulo === $idModuloUsuarios ? 'disabled checked' : '' ?>>
                <?= e($modulo['nombre']) ?>
            </label>
        <?php endforeach; ?>
        <?php if ($esPropio && $idModuloUsuarios > 0): ?>
            <input type="hidden" name="modulos[]" value="<?= $idModuloUsuarios ?>">
        <?php endif; ?>

        <p>
            <button type="submit">Guardar cambios</button>
            <a class="btn btn-gris" href="index.php">Cancelar</a>
        </p>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
