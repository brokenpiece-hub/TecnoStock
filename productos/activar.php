<?php

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

requerir_permiso('productos', '../');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('index.php');
}

$id = entero_no_negativo($_POST['id'] ?? null);
if ($id !== null && $id > 0) {
    $stmt = $pdo->prepare('UPDATE productos SET estado = 1 WHERE id_producto = :id');
    $stmt->execute([':id' => $id]);
}

redirigir('index.php?estado=0&msg=activado');
