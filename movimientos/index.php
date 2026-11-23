<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

requerir_permiso('movimientos', '../');

$tipo = $_GET['tipo'] ?? '';
if (!in_array($tipo, ['entrada', 'salida'], true)) {
    $tipo = '';
}
$q = trim($_GET['q'] ?? '');

$sql = 'SELECT m.id_movimiento, m.fecha_hora, m.tipo, m.cantidad, m.observacion,
               p.codigo, p.nombre AS producto, u.nombre AS usuario
          FROM movimientos m
          JOIN productos p ON p.id_producto = m.id_producto
          JOIN usuarios u ON u.id_usuario = m.id_usuario';

$condiciones = [];
$parametros = [];
if ($tipo !== '') {
    $condiciones[] = 'm.tipo = :tipo';
    $parametros[':tipo'] = $tipo;
}
if ($q !== '') {
    $condiciones[] = '(p.codigo LIKE :q1 OR p.nombre LIKE :q2 OR m.observacion LIKE :q3 OR u.nombre LIKE :q4)';
    $busqueda = '%' . $q . '%';
    $parametros[':q1'] = $busqueda;
    $parametros[':q2'] = $busqueda;
    $parametros[':q3'] = $busqueda;
    $parametros[':q4'] = $busqueda;
}
if (!empty($condiciones)) {
    $sql .= ' WHERE ' . implode(' AND ', $condiciones);
}
$sql .= ' ORDER BY m.fecha_hora DESC, m.id_movimiento DESC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$movimientos = $stmt->fetchAll();

$mensajes = ['creado' => 'Movimiento registrado correctamente.'];
$mensaje = isset($_GET['msg']) && isset($mensajes[$_GET['msg']]) ? $mensajes[$_GET['msg']] : '';

$titulo = 'TecnoStock - Movimientos';
$base = '../';
$pagina = 'movimientos';
require __DIR__ . '/../includes/header.php';
?>

<h1>Movimientos</h1>

<?php if ($mensaje !== ''): ?>
    <p class="mensaje"><?= e($mensaje) ?></p>
<?php endif; ?>

<div class="card">
    <form class="barra-filtros" method="get" action="index.php">
        <div>
            <label for="q">Buscar movimiento</label>
            <input type="text" id="q" name="q" placeholder="Producto, observacion o usuario" value="<?= e($q) ?>">
        </div>
        <div>
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo">
                <option value="">Todos</option>
                <option value="entrada" <?= $tipo === 'entrada' ? 'selected' : '' ?>>Entradas</option>
                <option value="salida" <?= $tipo === 'salida' ? 'selected' : '' ?>>Salidas</option>
            </select>
        </div>
        <div class="filtros-acciones">
            <button type="submit">Buscar</button>
            <a class="btn btn-gris" href="index.php">Limpiar</a>
            <a class="btn btn-acento" href="crear.php">Nuevo movimiento</a>
        </div>
    </form>
</div>

<div class="card">
    <h2>Historial <small><?= count($movimientos) ?> registros</small></h2>

    <?php if (empty($movimientos)): ?>
        <p class="vacio">No hay movimientos con estos filtros.</p>
    <?php else: ?>
    <div class="tabla-scroll">
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Producto</th>
                <th>Tipo</th>
                <th>Cantidad</th>
                <th>Observacion</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($movimientos as $fila): ?>
            <tr>
                <td><?= e($fila['fecha_hora']) ?></td>
                <td><?= e($fila['codigo']) ?> - <?= e($fila['producto']) ?></td>
                <td>
                    <span class="badge <?= $fila['tipo'] === 'entrada' ? 'badge-verde' : 'badge-gris' ?>">
                        <?= $fila['tipo'] === 'entrada' ? 'Entrada' : 'Salida' ?>
                    </span>
                </td>
                <td><?= (int) $fila['cantidad'] ?></td>
                <td><?= e($fila['observacion']) ?></td>
                <td><?= e($fila['usuario']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
