<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

requerir_permiso('usuarios', '../');

$modulos = modulos_disponibles($pdo);
$idsValidos = array_map('intval', array_column($modulos, 'id_modulo'));

$errores = [];
$datos = [
    'nombre'  => '',
    'correo'  => '',
    'clave'   => '',
    'estado'  => '1',
    'modulos' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['nombre'] = trim($_POST['nombre'] ?? '');
    $datos['correo'] = trim($_POST['correo'] ?? '');
    $datos['clave']  = $_POST['clave'] ?? '';
    $datos['estado'] = trim($_POST['estado'] ?? '1');

    $sel = $_POST['modulos'] ?? [];
    $datos['modulos'] = is_array($sel) ? array_map('intval', $sel) : [];

    if (strlen($datos['nombre']) < 2 || strlen($datos['nombre']) > 100) {
        $errores[] = 'El nombre debe tener entre 2 y 100 caracteres.';
    }

    if (filter_var($datos['correo'], FILTER_VALIDATE_EMAIL) === false) {
        $errores[] = 'Ingrese un correo valido.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE correo = :correo');
        $stmt->execute([':correo' => $datos['correo']]);
        if ((int) $stmt->fetchColumn() > 0) {
            $errores[] = 'Ese correo ya esta registrado.';
        }
    }

    if (strlen($datos['clave']) < 6) {
        $errores[] = 'La contrasena debe tener al menos 6 caracteres.';
    }

    if (!in_array($datos['estado'], ['0', '1'], true)) {
        $errores[] = 'El estado no es valido.';
    }

    $datos['modulos'] = array_values(array_intersect($datos['modulos'], $idsValidos));
    if (empty($datos['modulos'])) {
        $errores[] = 'Seleccione al menos un modulo.';
    }

    if (empty($errores)) {
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare(
                'INSERT INTO usuarios (nombre, correo, clave, estado)
                 VALUES (:nombre, :correo, :clave, :estado)'
            );
            $insert->execute([
                ':nombre' => $datos['nombre'],
                ':correo' => $datos['correo'],
                ':clave'  => password_hash($datos['clave'], PASSWORD_DEFAULT),
                ':estado' => (int) $datos['estado'],
            ]);

            guardar_permisos($pdo, (int) $pdo->lastInsertId(), $datos['modulos']);
            $pdo->commit();
            redirigir('index.php?msg=creado');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = 'No se pudo guardar el usuario.';
        }
    }
}

$titulo = 'TecnoStock - Nuevo usuario';
$base = '../';
$pagina = 'usuarios';
require __DIR__ . '/../includes/header.php';
?>

<h1>Nuevo usuario</h1>

<div class="card">
    <?php if (!empty($errores)): ?>
        <ul class="error">
            <?php foreach ($errores as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="crear.php">
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
                <label for="clave">Contrasena</label>
                <input type="password" id="clave" name="clave" minlength="6" required>
            </div>
            <div>
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="1" <?= $datos['estado'] === '1' ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= $datos['estado'] === '0' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
        </div>

        <label>Modulos permitidos</label>
        <?php foreach ($modulos as $modulo): ?>
            <label class="check">
                <input type="checkbox" name="modulos[]" value="<?= (int) $modulo['id_modulo'] ?>"
                    <?= in_array((int) $modulo['id_modulo'], $datos['modulos'], true) ? 'checked' : '' ?>>
                <?= e($modulo['nombre']) ?>
            </label>
        <?php endforeach; ?>

        <p>
            <button type="submit">Guardar</button>
            <a class="btn btn-gris" href="index.php">Cancelar</a>
        </p>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
