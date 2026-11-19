<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';

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
            'SELECT id_usuario, nombre, correo, clave, estado
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

            header('Location: dashboard.php');
            exit;
        }

        $error = 'Correo o contrasena incorrectos.';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>TecnoStock - Iniciar sesion</title>
</head>
<body>
    <h1>TecnoStock</h1>
    <h2>Iniciar sesion</h2>

    <?php if ($error !== ''): ?>
        <p style="color:red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" action="index.php">
        <p>
            <label for="correo">Correo:</label>
            <input type="email" id="correo" name="correo" required
                   value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>">
        </p>
        <p>
            <label for="clave">Contrasena:</label>
            <input type="password" id="clave" name="clave" required>
        </p>
        <p><button type="submit">Ingresar</button></p>
    </form>
</body>
</html>
