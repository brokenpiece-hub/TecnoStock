<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

requerir_permiso('usuarios', '../');

$sql = 'SELECT u.id_usuario, u.nombre, u.correo, u.estado, u.rut, u.cargo, u.foto,
               GROUP_CONCAT(m.nombre ORDER BY m.id_modulo SEPARATOR ", ") AS modulos
          FROM usuarios u
          LEFT JOIN usuario_modulos um ON um.id_usuario = u.id_usuario
          LEFT JOIN modulos m ON m.id_modulo = um.id_modulo
         GROUP BY u.id_usuario, u.nombre, u.correo, u.estado, u.rut, u.cargo, u.foto
         ORDER BY u.id_usuario';

$usuarios = $pdo->query($sql)->fetchAll();

$titulo = 'TecnoStock - Usuarios';
$base = '../';
$pagina = 'usuarios';
require __DIR__ . '/../includes/header.php';
?>

<h1>Usuarios</h1>

<div class="card">
    <form class="barra-acciones" method="get" action="index.php">
        <a class="btn btn-acento" href="crear.php">Nuevo usuario</a>
        <a class="btn" href="../perfil.php">Mi perfil</a>
    </form>
</div>

<div class="card">
    <h2>Listado <small><?= count($usuarios) ?> registros</small></h2>

    <div class="tabla-scroll">
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>RUT</th>
                <th>Cargo</th>
                <th>Correo</th>
                <th>Modulos permitidos</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $fila): ?>
            <tr>
                <td>
                    <?php if (!empty($fila['foto'])): ?>
                        <img class="avatar avatar-tabla" src="<?= e('../assets/img/usuarios/' . rawurlencode($fila['foto'])) ?>" alt="">
                    <?php endif; ?>
                    <?= e($fila['nombre']) ?>
                </td>
                <td><?= e($fila['rut'] ?: '—') ?></td>
                <td><?= e($fila['cargo'] ?: '—') ?></td>
                <td><?= e($fila['correo']) ?></td>
                <td><?= e($fila['modulos'] ?: 'Sin accesos') ?></td>
                <td>
                    <span class="badge <?= (int) $fila['estado'] === 1 ? 'badge-verde' : 'badge-gris' ?>">
                        <?= (int) $fila['estado'] === 1 ? 'Activo' : 'Inactivo' ?>
                    </span>
                </td>
                <td>
                    <div class="acciones">
                        <a class="btn btn-pequeno" href="editar.php?id=<?= (int) $fila['id_usuario'] ?>">Editar</a>
                        <?php if ((int) $fila['estado'] === 1 && (int) $fila['id_usuario'] !== (int) $_SESSION['usuario_id']): ?>
                            <form method="post" action="desactivar.php"
                                  onsubmit="return confirm('Desactivar este usuario?');">
                                <input type="hidden" name="id" value="<?= (int) $fila['id_usuario'] ?>">
                                <button type="submit" class="btn-gris btn-pequeno">Desactivar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
