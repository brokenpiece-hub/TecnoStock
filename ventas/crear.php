<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

requerir_permiso('ventas', '../');

$productos = $pdo->query(
    'SELECT id_producto, codigo, nombre, precio, moneda, stock_actual
       FROM productos WHERE estado = 1 ORDER BY nombre'
)->fetchAll();

$errores = [];
$datos = [
    'id_producto' => '',
    'cantidad'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($datos) as $clave) {
        $datos[$clave] = trim($_POST[$clave] ?? '');
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
                'SELECT nombre, precio, moneda, stock_actual FROM productos
                  WHERE id_producto = :id AND estado = 1'
            );
            $stmt->execute([':id' => $idProducto]);
            $producto = $stmt->fetch();

            if ($producto === false) {
                throw new Exception('El producto no existe o esta desactivado.');
            }

            $stock = (int) $producto['stock_actual'];

            if ($cantidad > $stock) {
                throw new Exception(
                    'No hay stock suficiente para ' . $producto['nombre'] .
                    '. Disponible: ' . $stock . '.'
                );
            }

            $precio = (float) $producto['precio'];
            $total  = number_format($precio * $cantidad, 2, '.', '');

            $pdo->prepare(
                'INSERT INTO ventas (total, moneda, id_usuario)
                 VALUES (:total, :moneda, :id_usuario)'
            )->execute([
                ':total'      => $total,
                ':moneda'     => $producto['moneda'],
                ':id_usuario' => $_SESSION['usuario_id'],
            ]);

            $idVenta = (int) $pdo->lastInsertId();

            $pdo->prepare(
                'INSERT INTO detalle_ventas
                    (id_venta, id_producto, cantidad, precio_unitario)
                 VALUES (:id_venta, :id_producto, :cantidad, :precio_unitario)'
            )->execute([
                ':id_venta'        => $idVenta,
                ':id_producto'     => $idProducto,
                ':cantidad'        => $cantidad,
                ':precio_unitario' => number_format($precio, 2, '.', ''),
            ]);

            $pdo->prepare(
                'UPDATE productos SET stock_actual = stock_actual - :cantidad
                  WHERE id_producto = :id'
            )->execute([
                ':cantidad' => $cantidad,
                ':id'       => $idProducto,
            ]);

            $pdo->prepare(
                'INSERT INTO movimientos (tipo, cantidad, observacion, id_producto, id_usuario)
                 VALUES (:tipo, :cantidad, :observacion, :id_producto, :id_usuario)'
            )->execute([
                ':tipo'        => 'salida',
                ':cantidad'    => $cantidad,
                ':observacion' => 'Venta N ' . $idVenta,
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

/* Si el formulario vuelve con error, recupera el nombre del producto elegido. */
$nombreProducto = '';
foreach ($productos as $producto) {
    if ((string) $producto['id_producto'] === $datos['id_producto']) {
        $nombreProducto = $producto['nombre'];
        break;
    }
}

$titulo = 'TecnoStock - Nueva venta';
$base = '../';
$pagina = 'ventas';
require __DIR__ . '/../includes/header.php';
?>

<h1>Nueva venta</h1>

<div class="card card-sugerencias">
    <?php if (!empty($errores)): ?>
        <ul class="error" role="alert">
            <?php foreach ($errores as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="crear.php">
        <div class="form-grid">
            <div class="campo-producto">
                <label for="buscar-producto">Producto</label>
                <input type="text" id="buscar-producto" autocomplete="off"
                       placeholder="Escriba el nombre del producto"
                       value="<?= e($nombreProducto) ?>"
                       aria-controls="lista-productos" aria-expanded="false">
                <input type="hidden" id="id_producto" name="id_producto" value="<?= e($datos['id_producto']) ?>">
                <ul id="lista-productos" class="lista-sugerencias" role="listbox" aria-label="Productos" hidden>
                    <?php foreach ($productos as $producto): ?>
                        <li role="option" aria-selected="false"
                            data-id="<?= (int) $producto['id_producto'] ?>"
                            data-nombre="<?= e($producto['nombre']) ?>"
                            data-stock="<?= (int) $producto['stock_actual'] ?>"
                            data-precio="<?= e($producto['precio']) ?>"
                            data-moneda="<?= e($producto['moneda']) ?>"><?= e($producto['nombre']) ?></li>
                    <?php endforeach; ?>
                    <li class="sin-resultados" hidden>No hay productos con ese nombre.</li>
                </ul>
            </div>
            <div>
                <label for="cantidad">Cantidad</label>
                <input type="number" step="1" min="1" id="cantidad" name="cantidad" required value="<?= e($datos['cantidad']) ?>">
            </div>
        </div>

        <p class="mensaje" id="stock-disponible" aria-live="polite">Stock disponible: seleccione un producto</p>

        <p>
            <button type="submit">Registrar venta</button>
            <a class="btn btn-gris" href="index.php">Cancelar</a>
        </p>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
