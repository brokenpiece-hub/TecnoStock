<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

require_login();

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
                  WHERE id_producto = :id AND estado = 1 FOR UPDATE'
            );
            $stmt->execute([':id' => $idProducto]);
            $producto = $stmt->fetch();

            if ($producto === false) {
                throw new Exception('El producto no existe o esta desactivado.');
            }

            $stock = (int) $producto['stock_actual'];

            // Revisa que haya stock suficiente
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

$titulo = 'TecnoStock - Nueva venta';
$base = '../';
$pagina = 'ventas';
require __DIR__ . '/../includes/header.php';
?>

<h1>Nueva venta</h1>

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
                <label for="id_producto">Producto</label>
                <select id="id_producto" name="id_producto" required
                        onchange="mostrarStock()" onfocus="mostrarStock()">
                    <option value="">Seleccione...</option>
                    <?php foreach ($productos as $producto): ?>
                        <option value="<?= (int) $producto['id_producto'] ?>"
                                data-stock="<?= (int) $producto['stock_actual'] ?>"
                                data-precio="<?= e($producto['precio']) ?>"
                                data-moneda="<?= e($producto['moneda']) ?>"
                            <?= (string) $producto['id_producto'] === $datos['id_producto'] ? 'selected' : '' ?>>
                            <?= e($producto['codigo']) ?> - <?= e($producto['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="cantidad">Cantidad</label>
                <input type="number" step="1" min="1" id="cantidad" name="cantidad" required value="<?= e($datos['cantidad']) ?>">
            </div>
        </div>

        <p class="mensaje" id="stock-disponible">Stock disponible: seleccione un producto</p>

        <p>
            <button type="submit">Registrar venta</button>
            <a class="btn btn-gris" href="index.php">Cancelar</a>
        </p>
    </form>
</div>

<script>
function mostrarStock() {
    var select = document.getElementById('id_producto');
    var opcion = select.options[select.selectedIndex];
    var texto = document.getElementById('stock-disponible');
    var cantidad = document.getElementById('cantidad');

    if (!opcion || !opcion.getAttribute('data-stock')) {
        texto.textContent = 'Stock disponible: seleccione un producto';
        cantidad.max = '';
        return;
    }

    var stock = opcion.getAttribute('data-stock');
    texto.textContent = 'Stock disponible: ' + stock +
                        ' | Precio: ' + opcion.getAttribute('data-moneda') +
                        ' ' + opcion.getAttribute('data-precio');
    cantidad.max = stock;
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
