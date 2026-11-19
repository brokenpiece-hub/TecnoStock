<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/funciones.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('index.php');
}

$id = entero_no_negativo($_POST['id'] ?? null);
if ($id !== null && $id > 0) {
    $stmt = $pdo->prepare('UPDATE productos SET estado = 0 WHERE id_producto = :id');
    $stmt->execute([':id' => $id]);
}

redirigir('index.php?msg=desactivado');
