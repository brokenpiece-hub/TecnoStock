<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

requerir_permiso('productos', '../');

$id = entero_no_negativo($_GET['id'] ?? $_POST['id'] ?? null);
if ($id === null || $id === 0) {
    redirigir('index.php');
}

$stmt = $pdo->prepare(
    'SELECT id_producto, codigo, nombre, descripcion, precio, moneda,
            stock_actual, stock_minimo, id_categoria
       FROM productos
      WHERE id_producto = :id'
);
$stmt->execute([':id' => $id]);
$producto = $stmt->fetch();

if ($producto === false) {
    redirigir('index.php');
}

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias WHERE estado = 1 ORDER BY nombre')->fetchAll();

$errores = [];
$datos = [
    'descripcion'  => $producto['descripcion'] ?? '',
    'precio'       => number_format((float) $producto['precio'], 2, '.', ''),
    'moneda'       => $producto['moneda'],
    'stock_actual' => (string) $producto['stock_actual'],
    'stock_minimo' => (string) $producto['stock_minimo'],
    'id_categoria' => (string) $producto['id_categoria'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($datos) as $clave) {
        $datos[$clave] = trim($_POST[$clave] ?? '');
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

    $stockActual  = entero_no_negativo($datos['stock_actual']);
    $stockCritico = entero_no_negativo($datos['stock_minimo']);
    if ($stockActual === null) {
        $errores[] = 'El stock actual debe ser un entero mayor o igual a cero.';
    }
    if ($stockCritico === null) {
        $errores[] = 'El stock critico debe ser un entero mayor o igual a cero.';
    }

    if (empty($errores)) {
        $update = $pdo->prepare(
            'UPDATE productos
                SET descripcion = :descripcion,
                    precio = :precio,
                    moneda = :moneda,
                    stock_actual = :stock_actual,
                    stock_minimo = :stock_minimo,
                    id_categoria = :id_categoria
              WHERE id_producto = :id'
        );
        $update->execute([
            ':descripcion'  => $datos['descripcion'],
            ':precio'       => $precio,
            ':moneda'       => $datos['moneda'],
            ':stock_actual' => $stockActual,
            ':stock_minimo' => $stockCritico,
            ':id_categoria' => $idCategoria,
            ':id'           => $id,
        ]);

        redirigir('index.php?msg=editado');
    }
}
$titulo = 'TecnoStock - Editar producto';
$base = '../';
$pagina = 'productos';
require __DIR__ . '/../includes/header.php';
?>

<h1>Editar producto</h1>

<div class="card">
    <?php if (!empty($errores)): ?>
        <ul class="error">
            <?php foreach ($errores as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p><strong><?= e($producto['codigo']) ?></strong> - <?= e($producto['nombre']) ?></p>

    <form method="post" action="editar.php?id=<?= $id ?>">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="form-grid">
            <div>
                <label for="descripcion">Descripcion</label>
                <input type="text" id="descripcion" name="descripcion" value="<?= e($datos['descripcion']) ?>">
            </div>
            <div>
                <label for="precio">Precio</label>
                <input type="number" step="0.01" min="0" id="precio" name="precio" required value="<?= e($datos['precio']) ?>">
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
                <label for="id_categoria">Categoria</label>
                <select id="id_categoria" name="id_categoria" required>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= (int) $categoria['id_categoria'] ?>"
                            <?= (string) $categoria['id_categoria'] === $datos['id_categoria'] ? 'selected' : '' ?>>
                            <?= e($categoria['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="stock_actual">Stock actual</label>
                <input type="number" step="1" min="0" id="stock_actual" name="stock_actual" required value="<?= e($datos['stock_actual']) ?>">
            </div>
            <div>
                <label for="stock_minimo">Stock critico</label>
                <input type="number" step="1" min="0" id="stock_minimo" name="stock_minimo" required value="<?= e($datos['stock_minimo']) ?>">
            </div>
        </div>

        <p>
            <button type="submit">Guardar cambios</button>
            <a class="btn btn-gris" href="index.php">Cancelar</a>
        </p>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
