<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';
require __DIR__ . '/../includes/ordenamiento.php';

requerir_permiso('movimientos', '../');

$tipo = $_GET['tipo'] ?? '';
if (!in_array($tipo, ['entrada', 'salida'], true)) {
    $tipo = '';
}
$q = trim($_GET['q'] ?? '');

/* Columnas ordenables del historial. */
$ordenes = [
    'fecha_hora'  => 'm.fecha_hora',
    'producto'    => 'p.nombre',
    'tipo'        => 'm.tipo',
    'cantidad'    => 'm.cantidad',
    'observacion' => 'm.observacion',
    'usuario'     => 'u.nombre',
];
$orden = $_GET['orden'] ?? '';
if (!isset($ordenes[$orden])) {
    $orden = '';
}
$direccion = $_GET['direccion'] ?? 'asc';
if (!in_array($direccion, ['asc', 'desc'], true)) {
    $direccion = 'asc';
}

$filtros = [];
foreach (['q' => $q, 'tipo' => $tipo] as $clave => $valor) {
    if ($valor !== '') {
        $filtros[$clave] = $valor;
    }
}
if ($orden !== '') {
    $filtros['orden'] = $orden;
    $filtros['direccion'] = $direccion;
}

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
if ($orden !== '') {
    $sql .= ' ORDER BY ' . $ordenes[$orden] . ' ' . strtoupper($direccion) . ', m.id_movimiento DESC LIMIT 100';
} else {
    $sql .= ' ORDER BY m.fecha_hora DESC, m.id_movimiento DESC LIMIT 100';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$movimientos = $stmt->fetchAll();

$mensajes = [
    'creado'             => 'Movimiento registrado correctamente.',
    'creado_con_producto' => 'Producto creado y entrada registrada correctamente.',
];
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
        <?php if ($orden !== ''): ?>
            <input type="hidden" name="orden" value="<?= e($orden) ?>">
            <input type="hidden" name="direccion" value="<?= e($direccion) ?>">
        <?php endif; ?>
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
    <h2>Historial <small data-total><?= count($movimientos) ?> registros</small></h2>

    <?php if (empty($movimientos)): ?>
        <p class="vacio">No hay movimientos con estos filtros.</p>
    <?php else: ?>
    <div class="tabla-scroll">
    <table data-buscar>
        <thead>
            <tr>
                <?= cabecera_ordenable('fecha_hora', 'Fecha', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('producto', 'Producto', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('tipo', 'Tipo', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('cantidad', 'Cantidad', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('observacion', 'Observacion', $orden, $direccion, $filtros) ?>
                <?= cabecera_ordenable('usuario', 'Usuario', $orden, $direccion, $filtros) ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($movimientos as $fila): ?>
            <tr data-buscar="<?= e($fila['codigo'] . ' ' . $fila['producto'] . ' ' . $fila['observacion'] . ' ' . $fila['usuario']) ?>">
                <td><?= e($fila['fecha_hora']) ?></td>
                <td data-orden-valor="<?= e($fila['producto']) ?>"><?= e($fila['codigo']) ?> - <?= e($fila['producto']) ?></td>
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
    <p class="vacio buscador-vacio" hidden>No hay resultados para esta busqueda.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
