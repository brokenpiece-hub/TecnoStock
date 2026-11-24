<?php

session_start();

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/funciones.php';

if (esta_autenticado()) {
    header('Location: dashboard.php');
    exit;
}

$error  = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave  = $_POST['clave'] ?? '';

    if ($correo === '' || $clave === '' || filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
        $error = 'Ingrese un correo valido y su contrasena.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id_usuario, nombre, correo, clave, estado, rut, cargo, foto
               FROM usuarios
              WHERE correo = :correo
              LIMIT 1'
        );
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch();

        if ($usuario !== false
            && (int) $usuario['estado'] === 1
            && password_verify($clave, $usuario['clave'])
        ) {
            session_regenerate_id(true);
            $_SESSION['usuario_id']    = (int) $usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_correo'] = $usuario['correo'];
            $_SESSION['usuario_rut']    = $usuario['rut'];
            $_SESSION['usuario_cargo']  = $usuario['cargo'];
            $_SESSION['usuario_foto']   = $usuario['foto'];
            cargar_permisos($pdo, (int) $usuario['id_usuario']);

            header('Location: dashboard.php');
            exit;
        }

        $error = 'Correo o contrasena incorrectos.';
    }
}
$titulo = 'TecnoStock - Iniciar sesion';
$base = '';
$login = true;
require __DIR__ . '/includes/header.php';
?>

<h2>Ingreso al sistema</h2>

<?php if ($error !== ''): ?>
    <p class="error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<form method="post" action="index.php">
    <label for="correo">Correo:</label>
    <input type="email" id="correo" name="correo" autocomplete="username" required value="<?= e($correo) ?>">

    <label for="clave">Contrasena:</label>
    <div class="campo-clave">
        <input type="password" id="clave" name="clave" autocomplete="current-password" required>
        <button type="button" class="ver-clave" data-clave="clave"
                aria-label="Mostrar contrasena" aria-pressed="false" title="Mostrar contrasena">
            <?= $iconos['ojo'] ?>
            <?= $iconos['ojo_tachado'] ?>
        </button>
    </div>

    <button type="submit">Ingresar</button>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
