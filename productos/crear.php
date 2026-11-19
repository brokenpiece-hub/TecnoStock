<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

require_login();

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias WHERE estado = 1 ORDER BY nombre')->fetchAll();

$errores = [];
$datos = [
    'codigo'       => '',
    'nombre'       => '',
    'descripcion'  => '',
    'precio'       => '',
    'moneda'       => 'CLP',
    'stock_actual' => '0',
    'stock_minimo' => '0',
    'id_categoria' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($datos) as $clave) {
        $datos[$clave] = trim($_POST[$clave] ?? '');
    }

    $codigo = strtoupper($datos['codigo']);
    if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $codigo)) {
        $errores[] = 'El codigo debe tener entre 3 y 30 caracteres (letras, numeros, guion o guion bajo).';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM productos WHERE codigo = :codigo');
        $stmt->execute([':codigo' => $codigo]);
        if ((int) $stmt->fetchColumn() > 0) {
            $errores[] = 'Ese codigo ya esta registrado.';
        }
    }

    if (mb_strlen($datos['nombre']) < 2 || mb_strlen($datos['nombre']) > 120) {
        $errores[] = 'El nombre debe tener entre 2 y 120 caracteres.';
    }

    $idCategoria = entero_no_negativo($datos['id_categoria']);
    if ($idCategoria === null || $idCategoria === 0) {
        $errores[] = 'Seleccione una categoria.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM categorias WHERE id_categoria = :id AND estado = 1');
        $stmt->execute([':id' => $idCategoria]);
        if ((int) $stmt->fetchColumn() === 0) {
            $errores[] = 'La categoria no es valida.';
        }
    }

    $precio = precio_valido($datos['precio']);
    if ($precio === null) {
        $errores[] = 'El precio debe ser un numero mayor o igual a cero.';
    }

    if (!in_array($datos['moneda'], ['CLP', 'USD', 'EUR'], true)) {
        $errores[] = 'La moneda no es valida.';
    }

    $stockActual = entero_no_negativo($datos['stock_actual']);
    $stockCritico = entero_no_negativo($datos['stock_minimo']);
    if ($stockActual === null) {
        $errores[] = 'El stock actual debe ser un entero mayor o igual a cero.';
    }
    if ($stockCritico === null) {
        $errores[] = 'El stock critico debe ser un entero mayor o igual a cero.';
    }

    if (empty($errores)) {
        $insert = $pdo->prepare(
            'INSERT INTO productos
                (codigo, nombre, descripcion, precio, moneda, stock_actual, stock_minimo, estado, id_categoria)
             VALUES
                (:codigo, :nombre, :descripcion, :precio, :moneda, :stock_actual, :stock_minimo, 1, :id_categoria)'
        );
        $insert->execute([
            ':codigo'       => $codigo,
            ':nombre'       => $datos['nombre'],
            ':descripcion'  => $datos['descripcion'],
            ':precio'       => $precio,
            ':moneda'       => $datos['moneda'],
            ':stock_actual' => $stockActual,
            ':stock_minimo' => $stockCritico,
            ':id_categoria' => $idCategoria,
        ]);

        redirigir('index.php?msg=creado');
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>TecnoStock - Nuevo producto</title>
</head>
<body>
    <p><a href="index.php">Volver a productos</a></p>
    <h1>Nuevo producto</h1>

    <?php if (!empty($errores)): ?>
        <ul style="color:red;">
            <?php foreach ($errores as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="crear.php">
        <p><label>Codigo: <input type="text" name="codigo" required value="<?= e($datos['codigo']) ?>"></label></p>
        <p><label>Nombre: <input type="text" name="nombre" required value="<?= e($datos['nombre']) ?>"></label></p>
        <p>
            <label>Categoria:
                <select name="id_categoria" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= (int) $categoria['id_categoria'] ?>"
                            <?= (string) $categoria['id_categoria'] === $datos['id_categoria'] ? 'selected' : '' ?>>
                            <?= e($categoria['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </p>
        <p><label>Descripcion: <input type="text" name="descripcion" value="<?= e($datos['descripcion']) ?>"></label></p>
        <p><label>Precio: <input type="number" step="0.01" min="0" name="precio" required value="<?= e($datos['precio']) ?>"></label></p>
        <p>
            <label>Moneda:
                <select name="moneda">
                    <?php foreach (['CLP', 'USD', 'EUR'] as $moneda): ?>
                        <option value="<?= $moneda ?>" <?= $datos['moneda'] === $moneda ? 'selected' : '' ?>>
                            <?= $moneda ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </p>
        <p><label>Stock actual: <input type="number" step="1" min="0" name="stock_actual" required value="<?= e($datos['stock_actual']) ?>"></label></p>
        <p><label>Stock critico: <input type="number" step="1" min="0" name="stock_minimo" required value="<?= e($datos['stock_minimo']) ?>"></label></p>
        <p><button type="submit">Guardar</button></p>
    </form>
</body>
</html>
