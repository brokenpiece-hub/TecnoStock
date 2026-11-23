<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

requerir_permiso('productos', '../');

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
    'marca'        => '',
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

    $stockActual = entero_no_negativo($datos['stock_actual']);
    $stockCritico = entero_no_negativo($datos['stock_minimo']);
    if ($stockActual === null) {
        $errores[] = 'El stock actual debe ser un entero mayor o igual a cero.';
    }
    if ($stockCritico === null) {
        $errores[] = 'El stock critico debe ser un entero mayor o igual a cero.';
    }

    $archivo = ['imagen' => null, 'error' => ''];
    if (empty($errores)) {
        $archivo = procesar_imagen_producto($_FILES['imagen'] ?? null);
        if ($archivo['error'] !== '') {
            $errores[] = $archivo['error'];
        }
    }

    if (empty($errores)) {
        $insert = $pdo->prepare(
            'INSERT INTO productos
                (codigo, nombre, descripcion, precio, moneda, stock_actual, stock_minimo, estado, id_categoria, marca, imagen)
             VALUES
                (:codigo, :nombre, :descripcion, :precio, :moneda, :stock_actual, :stock_minimo, 1, :id_categoria, :marca, :imagen)'
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
            ':marca'        => $datos['marca'],
            ':imagen'       => $archivo['imagen'],
        ]);

        redirigir('index.php?msg=creado');
    }
}
$titulo = 'TecnoStock - Nuevo producto';
$base = '../';
$pagina = 'nuevo';
require __DIR__ . '/../includes/header.php';
?>

<h1>Nuevo producto</h1>

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
                <label for="codigo">Codigo</label>
                <input type="text" id="codigo" name="codigo" required value="<?= e($datos['codigo']) ?>">
            </div>
            <div>
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" required value="<?= e($datos['nombre']) ?>">
            </div>
            <div>
                <label for="id_categoria">Categoria</label>
                <select id="id_categoria" name="id_categoria" required>
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
                <input type="text" id="marca" name="marca" maxlength="80" required
                       placeholder="Escriba la marca" value="<?= e($datos['marca']) ?>">
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
                <label for="stock_actual">Stock actual</label>
                <input type="number" step="1" min="0" id="stock_actual" name="stock_actual" required value="<?= e($datos['stock_actual']) ?>">
            </div>
            <div>
                <label for="stock_minimo">Stock critico</label>
                <input type="number" step="1" min="0" id="stock_minimo" name="stock_minimo" required value="<?= e($datos['stock_minimo']) ?>">
            </div>
            <div class="form-completo">
                <label for="imagen">Imagen del producto (opcional)</label>
                <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
                <small class="ayuda">JPG, PNG o WEBP. Maximo 2 MB.</small>
            </div>
        </div>

        <p>
            <button type="submit">Guardar</button>
            <a class="btn btn-gris" href="index.php">Cancelar</a>
        </p>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
