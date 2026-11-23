<?php

/* Agrega las columnas y la tabla nuevas sin borrar datos ya guardados. */

function columna_existe($pdo, string $tabla, string $columna): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla AND COLUMN_NAME = :columna'
    );
    $stmt->execute([':tabla' => $tabla, ':columna' => $columna]);
    return (int) $stmt->fetchColumn() > 0;
}

function indice_existe($pdo, string $tabla, string $indice): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla AND INDEX_NAME = :indice'
    );
    $stmt->execute([':tabla' => $tabla, ':indice' => $indice]);
    return (int) $stmt->fetchColumn() > 0;
}

function asegurar_esquema_perfil($pdo): void
{
    if (!columna_existe($pdo, 'usuarios', 'rut')) {
        $pdo->exec('ALTER TABLE usuarios ADD COLUMN rut VARCHAR(20) NULL AFTER nombre');
    }
    if (!columna_existe($pdo, 'usuarios', 'cargo')) {
        $pdo->exec('ALTER TABLE usuarios ADD COLUMN cargo VARCHAR(80) NULL AFTER rut');
    }
    if (!columna_existe($pdo, 'usuarios', 'foto')) {
        $pdo->exec('ALTER TABLE usuarios ADD COLUMN foto VARCHAR(255) NULL AFTER cargo');
    }
    if (!indice_existe($pdo, 'usuarios', 'uq_usuarios_rut')) {
        $pdo->exec('ALTER TABLE usuarios ADD UNIQUE KEY uq_usuarios_rut (rut)');
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS solicitudes_clave (
            id_solicitud INT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_usuario   INT UNSIGNED NOT NULL,
            clave_hash   VARCHAR(255) NOT NULL,
            fecha_hora   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            estado       ENUM("pendiente", "aprobada", "rechazada") NOT NULL DEFAULT "pendiente",
            PRIMARY KEY (id_solicitud),
            KEY idx_solicitudes_usuario (id_usuario),
            CONSTRAINT fk_solicitudes_usuario FOREIGN KEY (id_usuario)
                REFERENCES usuarios (id_usuario)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}
