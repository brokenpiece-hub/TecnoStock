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
            stock_actual, stock_minimo, id_categoria, marca, imagen
       FROM productos
      WHERE id_producto = :id'
);
$stmt->execute([':id' => $id]);
$producto = $stmt->fetch();

if ($producto === false) {
    redirigir('index.php');
}

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias WHERE estado = 1 ORDER BY nombre')->fetchAll();
$imagenActual = $producto['imagen'];

$errores = [];
$datos = [
    'codigo'       => $producto['codigo'],
    'nombre'       => $producto['nombre'],
    'descripcion'  => $producto['descripcion'] ?? '',
    'precio'       => number_format((float) $producto['precio'], 2, '.', ''),
    'moneda'       => $producto['moneda'],
    'stock_actual' => (string) $producto['stock_actual'],
    'stock_minimo' => (string) $producto['stock_minimo'],
    'id_categoria' => (string) $producto['id_categoria'],
    'marca'        => $producto['marca'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($datos) as $clave) {
        $datos[$clave] = trim($_POST[$clave] ?? '');
    }

    $codigo = strtoupper($datos['codigo']);
    $datos['codigo'] = $codigo;
    if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $codigo)) {
        $errores[] = 'El codigo debe tener entre 3 y 30 caracteres (letras, numeros, guion o guion bajo).';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM productos WHERE codigo = :codigo AND id_producto <> :id');
        $stmt->execute([':codigo' => $codigo, ':id' => $id]);
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

    $stockActual  = entero_no_negativo($datos['stock_actual']);
    $stockCritico = entero_no_negativo($datos['stock_minimo']);
    if ($stockActual === null) {
        $errores[] = 'El stock actual debe ser un entero mayor o igual a cero.';
    }
    if ($stockCritico === null) {
        $errores[] = 'El stock critico debe ser un entero mayor o igual a cero.';
    }

    $archivo = ['imagen' => null, 'error' => ''];
    $quitarImagen = isset($_POST['quitar_imagen']) && $_POST['quitar_imagen'] === '1';
    if (empty($errores)) {
        $archivo = procesar_imagen_producto($_FILES['imagen'] ?? null);
        if ($archivo['error'] !== '') {
            $errores[] = $archivo['error'];
        }
    }

    if (empty($errores)) {
        $imagenFinal = $imagenActual;
        if ($quitarImagen) {
            $imagenFinal = null;
        }
        if ($archivo['imagen'] !== null) {
            $imagenFinal = $archivo['imagen'];
        }

        $update = $pdo->prepare(
            'UPDATE productos
                SET codigo = :codigo,
                    nombre = :nombre,
                    descripcion = :descripcion,
                    precio = :precio,
                    moneda = :moneda,
                    stock_actual = :stock_actual,
                    stock_minimo = :stock_minimo,
                    id_categoria = :id_categoria,
                    marca = :marca,
                    imagen = :imagen
              WHERE id_producto = :id'
        );
        $update->execute([
            ':codigo'       => $codigo,
            ':nombre'       => $datos['nombre'],
            ':descripcion'  => $datos['descripcion'],
            ':precio'       => $precio,
            ':moneda'       => $datos['moneda'],
            ':stock_actual' => $stockActual,
            ':stock_minimo' => $stockCritico,
            ':id_categoria' => $idCategoria,
            ':marca'        => $datos['marca'],
            ':imagen'       => $imagenFinal,
            ':id'           => $id,
        ]);

        if (($quitarImagen || $archivo['imagen'] !== null) && $imagenActual !== null) {
            borrar_imagen_producto($imagenActual);
        }

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
        <ul class="error" role="alert">
            <?php foreach ($errores as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="editar.php?id=<?= $id ?>" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="form-grid">
            <div>
                <label for="codigo">Codigo</label>
                <input type="text" id="codigo" name="codigo" required value="<?= e($datos['codigo']) ?>">
                <small class="ayuda">De 3 a 30 caracteres: letras, numeros, guion o guion bajo. No se puede repetir.</small>
            </div>
            <div>
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" required value="<?= e($datos['nombre']) ?>">
            </div>
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
                <label for="marca">Marca</label>
                <input type="text" id="marca" name="marca" maxlength="80" required
                       placeholder="Escriba la marca" value="<?= e($datos['marca']) ?>">
            </div>
            <div>
                <label for="stock_actual">Stock actual</label>
                <input type="number" step="1" min="0" id="stock_actual" name="stock_actual" required value="<?= e($datos['stock_actual']) ?>">
            </div>
            <div>
                <label for="stock_minimo">Stock critico</label>
                <input type="number" step="1" min="0" id="stock_minimo" name="stock_minimo" required value="<?= e($datos['stock_minimo']) ?>">
            </div>
            <div class="form-completo">
                <label for="imagen">Imagen del producto (opcional)</label>
                <?php if ($imagenActual !== null && $imagenActual !== ''): ?>
                    <p>
                        <img class="miniatura" src="<?= e(ruta_imagen($imagenActual, '../')) ?>"
                             alt="Imagen actual de <?= e($datos['nombre']) ?>" width="56" height="56">
                    </p>
                    <label class="check">
                        <input type="checkbox" name="quitar_imagen" value="1"> Quitar imagen actual
                    </label>
                <?php endif; ?>
                <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
                <small class="ayuda">JPG, PNG o WEBP. Maximo 2 MB. Si subes otra, reemplaza a la actual.</small>
            </div>
        </div>

        <p>
            <button type="submit">Guardar cambios</button>
            <a class="btn btn-gris" href="index.php">Cancelar</a>
        </p>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
