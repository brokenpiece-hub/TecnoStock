<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

requerir_permiso('usuarios', '../');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('index.php');
}

$id = entero_no_negativo($_POST['id'] ?? null);
if ($id !== null && $id > 0 && $id !== (int) $_SESSION['usuario_id']) {
    $stmt = $pdo->prepare('UPDATE usuarios SET estado = 0 WHERE id_usuario = :id');
    $stmt->execute([':id' => $id]);
}

redirigir('index.php?msg=desactivado');
