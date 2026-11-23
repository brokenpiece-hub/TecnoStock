<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

requerir_permiso('movimientos', '../');

$productos = $pdo->query(
    'SELECT id_producto, codigo, nombre, stock_actual
       FROM productos WHERE estado = 1 ORDER BY nombre'
)->fetchAll();

$errores = [];
$datos = [
    'tipo'        => 'entrada',
    'id_producto' => '',
    'cantidad'    => '',
    'observacion' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($datos) as $clave) {
        $datos[$clave] = trim($_POST[$clave] ?? '');
    }

    if (!in_array($datos['tipo'], ['entrada', 'salida'], true)) {
        $errores[] = 'Seleccione un tipo valido.';
    }

    $idProducto = entero_no_negativo($datos['id_producto']);
    $cantidad   = entero_no_negativo($datos['cantidad']);

    if ($idProducto === null || $idProducto === 0) {
        $errores[] = 'Seleccione un producto.';
    }
    if ($cantidad === null || $cantidad === 0) {
        $errores[] = 'La cantidad debe ser un entero mayor que cero.';
    }

    if (empty($errores)) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'SELECT stock_actual, nombre FROM productos
                  WHERE id_producto = :id AND estado = 1'
            );
            $stmt->execute([':id' => $idProducto]);
            $producto = $stmt->fetch();

            if ($producto === false) {
                throw new Exception('El producto no existe o esta desactivado.');
            }

            $stock = (int) $producto['stock_actual'];
            if ($datos['tipo'] === 'salida' && $cantidad > $stock) {
                throw new Exception('Stock insuficiente. Disponible: ' . $stock . '.');
            }

            if ($datos['tipo'] === 'entrada') {
                $sqlStock = 'UPDATE productos SET stock_actual = stock_actual + :cantidad
                              WHERE id_producto = :id';
            } else {
                $sqlStock = 'UPDATE productos SET stock_actual = stock_actual - :cantidad
                              WHERE id_producto = :id';
            }
            $pdo->prepare($sqlStock)->execute([
                ':cantidad' => $cantidad,
                ':id'       => $idProducto,
            ]);

            $pdo->prepare(
                'INSERT INTO movimientos (tipo, cantidad, observacion, id_producto, id_usuario)
                 VALUES (:tipo, :cantidad, :observacion, :id_producto, :id_usuario)'
            )->execute([
                ':tipo'        => $datos['tipo'],
                ':cantidad'    => $cantidad,
                ':observacion' => $datos['observacion'],
                ':id_producto' => $idProducto,
                ':id_usuario'  => $_SESSION['usuario_id'],
            ]);

            $pdo->commit();
            redirigir('index.php?msg=creado');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = $e->getMessage();
        }
    }
}

$titulo = 'TecnoStock - Nuevo movimiento';
$base = '../';
$pagina = 'movimientos';
require __DIR__ . '/../includes/header.php';
?>

<h1>Nuevo movimiento</h1>

<div class="card">
    <?php if (!empty($errores)): ?>
        <ul class="error" role="alert">
            <?php foreach ($errores as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="crear.php">
        <div class="form-grid">
            <div>
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo">
                    <option value="entrada" <?= $datos['tipo'] === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                    <option value="salida" <?= $datos['tipo'] === 'salida' ? 'selected' : '' ?>>Salida</option>
                </select>
            </div>
            <div>
                <label for="id_producto">Producto</label>
                <select id="id_producto" name="id_producto" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($productos as $producto): ?>
                        <option value="<?= (int) $producto['id_producto'] ?>"
                            <?= (string) $producto['id_producto'] === $datos['id_producto'] ? 'selected' : '' ?>>
                            <?= e($producto['codigo']) ?> - <?= e($producto['nombre']) ?> (<?= (int) $producto['stock_actual'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="cantidad">Cantidad</label>
                <input type="number" step="1" min="1" id="cantidad" name="cantidad" required value="<?= e($datos['cantidad']) ?>">
            </div>
            <div>
                <label for="observacion">Observacion</label>
                <input type="text" id="observacion" name="observacion" value="<?= e($datos['observacion']) ?>">
            </div>
        </div>

        <p>
            <button type="submit">Guardar movimiento</button>
            <a class="btn btn-gris" href="index.php">Cancelar</a>
        </p>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
