<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

requerir_permiso('movimientos', '../');

/* Crear producto desde aqui solo si el usuario tambien tiene permiso de Productos. */
$puedeCrearProducto = tiene_permiso('productos');
$categorias = [];
if ($puedeCrearProducto) {
    $categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias WHERE estado = 1 ORDER BY nombre')->fetchAll();
}

$productos = $pdo->query(
    'SELECT id_producto, codigo, nombre, stock_actual
       FROM productos WHERE estado = 1 ORDER BY nombre'
)->fetchAll();

$errores = [];
$datos = [
    'tipo'           => 'entrada',
    'id_producto'    => '',
    'cantidad'       => '',
    'observacion'    => '',
    'nuevo_producto' => '0',
    'codigo'         => '',
    'nombre'         => '',
    'id_categoria'   => '',
    'marca'          => '',
    'descripcion'    => '',
    'precio'         => '',
    'moneda'         => 'CLP',
    'stock_minimo'   => '0',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($datos) as $clave) {
        $datos[$clave] = trim($_POST[$clave] ?? '');
    }

    $crearNuevo = $datos['nuevo_producto'] === '1';

    if (!in_array($datos['tipo'], ['entrada', 'salida'], true)) {
        $errores[] = 'Seleccione un tipo valido.';
    }
    if ($crearNuevo && !$puedeCrearProducto) {
        $errores[] = 'No tienes permiso para crear productos.';
    }
    if ($crearNuevo && $datos['tipo'] !== 'entrada') {
        $errores[] = 'Solo se pueden crear productos nuevos en una entrada.';
    }

    $cantidad = entero_no_negativo($datos['cantidad']);
    if ($cantidad === null || $cantidad === 0) {
        $errores[] = 'La cantidad debe ser un entero mayor que cero.';
    }

    $idProducto = null;
    $codigo = '';
    $idCategoria = null;
    $precio = null;
    $stockCritico = null;
    $archivo = ['imagen' => null, 'error' => ''];

    if ($crearNuevo) {
        /* Validaciones del producto nuevo, igual que en Productos. */
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

        if (strlen($datos['nombre']) < 2 || strlen($datos['nombre']) > 120) {
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

        if (strlen($datos['marca']) < 2 || strlen($datos['marca']) > 80) {
            $errores[] = 'La marca debe tener entre 2 y 80 caracteres.';
        }

        $precio = precio_valido($datos['precio']);
        if ($precio === null) {
            $errores[] = 'El precio debe ser un numero mayor o igual a cero.';
        }

        if (!in_array($datos['moneda'], ['CLP', 'USD', 'EUR'], true)) {
            $errores[] = 'La moneda no es valida.';
        }

        $stockCritico = entero_no_negativo($datos['stock_minimo']);
        if ($stockCritico === null) {
            $errores[] = 'El stock critico debe ser un entero mayor o igual a cero.';
        }
    } else {
        $idProducto = entero_no_negativo($datos['id_producto']);
        if ($idProducto === null || $idProducto === 0) {
            $errores[] = 'Seleccione un producto.';
        }
    }

    if ($crearNuevo && empty($errores)) {
        $archivo = procesar_imagen_producto($_FILES['imagen'] ?? null);
        if ($archivo['error'] !== '') {
            $errores[] = $archivo['error'];
        }
    }

    if (empty($errores)) {
        $pdo->beginTransaction();
        try {
            if ($crearNuevo) {
                /* El producto nace con stock 0; la entrada sera su stock inicial. */
                $insertarProducto = $pdo->prepare(
                    'INSERT INTO productos
                        (codigo, nombre, descripcion, precio, moneda, stock_actual, stock_minimo, estado, id_categoria, marca, imagen)
                     VALUES
                        (:codigo, :nombre, :descripcion, :precio, :moneda, 0, :stock_minimo, 1, :id_categoria, :marca, :imagen)'
                );
                $insertarProducto->execute([
                    ':codigo'       => $codigo,
                    ':nombre'       => $datos['nombre'],
                    ':descripcion'  => $datos['descripcion'],
                    ':precio'       => $precio,
                    ':moneda'       => $datos['moneda'],
                    ':stock_minimo' => $stockCritico,
                    ':id_categoria' => $idCategoria,
                    ':marca'        => $datos['marca'],
                    ':imagen'       => $archivo['imagen'],
                ]);
                $idProducto = (int) $pdo->lastInsertId();
            }

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
            $mensaje = $crearNuevo ? 'creado_con_producto' : 'creado';
            redirigir('index.php?msg=' . $mensaje);
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

    <form method="post" action="crear.php" enctype="multipart/form-data">
        <div class="form-grid">
            <div>
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo">
                    <option value="entrada" <?= $datos['tipo'] === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                    <option value="salida" <?= $datos['tipo'] === 'salida' ? 'selected' : '' ?>>Salida</option>
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

        <?php if ($puedeCrearProducto): ?>
            <div id="opcion-producto" class="opcion-producto <?= $datos['tipo'] === 'entrada' ? '' : 'oculto' ?>">
                <label class="check-linea" for="nuevo_producto">
                    <input type="checkbox" id="nuevo_producto" name="nuevo_producto" value="1"
                           <?= $datos['nuevo_producto'] === '1' ? 'checked' : '' ?>>
                    Crear un producto nuevo para esta entrada
                </label>
                <small class="ayuda">El producto se guarda con stock 0 y esta entrada define su stock inicial.</small>
            </div>
        <?php endif; ?>

        <div id="producto-existente">
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

        <?php if ($puedeCrearProducto): ?>
            <div id="producto-nuevo" class="bloque-nuevo-producto">
                <h2>Datos del producto nuevo</h2>
                <div class="form-grid">
                    <div>
                        <label for="codigo">Codigo</label>
                        <input type="text" id="codigo" name="codigo" value="<?= e($datos['codigo']) ?>"
                               placeholder="TEC-021" maxlength="30">
                    </div>
                    <div>
                        <label for="nombre_producto">Nombre</label>
                        <input type="text" id="nombre_producto" name="nombre" value="<?= e($datos['nombre']) ?>">
                    </div>
                    <div>
                        <label for="id_categoria">Categoria</label>
                        <select id="id_categoria" name="id_categoria">
                            <option value="">Seleccione...</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= (int) $categoria['id_categoria'] ?>"
                                    <?= (string) $categoria['id_categoria'] === $datos['id_categoria'] ? 'selected' : '' ?>>
                                    <?= e($categoria['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="marca">Marca</label>
                        <input type="text" id="marca" name="marca" maxlength="80"
                               placeholder="Escriba la marca" value="<?= e($datos['marca']) ?>">
                    </div>
                    <div>
                        <label for="descripcion">Descripcion</label>
                        <input type="text" id="descripcion" name="descripcion" value="<?= e($datos['descripcion']) ?>">
                    </div>
                    <div>
                        <label for="precio">Precio</label>
                        <input type="number" step="0.01" min="0" id="precio" name="precio" value="<?= e($datos['precio']) ?>">
                    </div>
                    <div>
                        <label for="moneda">Moneda</label>
                        <select id="moneda" name="moneda">
                            <?php foreach (['CLP', 'USD', 'EUR'] as $moneda): ?>
                                <option value="<?= $moneda ?>" <?= $datos['moneda'] === $moneda ? 'selected' : '' ?>>
                                    <?= $moneda ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="stock_minimo">Stock critico</label>
                        <input type="number" step="1" min="0" id="stock_minimo" name="stock_minimo"
                               value="<?= e($datos['stock_minimo']) ?>">
                    </div>
                    <div class="form-completo">
                        <label for="imagen">Imagen del producto (opcional)</label>
                        <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
                        <small class="ayuda">JPG, PNG o WEBP. Maximo 2 MB.</small>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <p>
            <button type="submit">Guardar movimiento</button>
            <a class="btn btn-gris" href="index.php">Cancelar</a>
        </p>
    </form>
</div>

<script>
/* Muestra el producto existente o el formulario de producto nuevo. */
(function () {
    var tipo = document.getElementById('tipo');
    var check = document.getElementById('nuevo_producto');
    var opcion = document.getElementById('opcion-producto');
    var existente = document.getElementById('producto-existente');
    var nuevo = document.getElementById('producto-nuevo');
    if (!tipo || !check || !opcion || !existente || !nuevo) {
        return;
    }

    function activar(seccion, mostrar) {
        seccion.classList.toggle('oculto', !mostrar);
        var campos = seccion.querySelectorAll('input, select, textarea');
        for (var i = 0; i < campos.length; i++) {
            campos[i].disabled = !mostrar;
        }
    }

    function actualizar() {
        var esEntrada = tipo.value === 'entrada';
        check.disabled = !esEntrada;
        opcion.classList.toggle('oculto', !esEntrada);
        var crear = esEntrada && check.checked;
        activar(existente, !crear);
        activar(nuevo, crear);
    }

    tipo.addEventListener('change', actualizar);
    check.addEventListener('change', actualizar);
    actualizar();
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
