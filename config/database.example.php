<?php
declare(strict_types=1);

// Plantilla para clonar como config/database.php y ajustar segun el entorno.
$dsn     = 'mysql:host=127.0.0.1;dbname=tecnostock;charset=utf8mb4';
$usuario = 'root';       // cambiar si usa otro usuario de MySQL
$clave   = '';           // cambiar si su MariaDB tiene contraseña

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $usuario, $clave, $opciones);
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION'");
} catch (PDOException $e) {
    http_response_code(500);
    exit('Error de conexion a la base de datos.');
}
