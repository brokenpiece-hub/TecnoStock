-- ============================================================================
-- TecnoStock - Base de datos del MVP de inventario
-- MariaDB 10.11+ / MySQL 8+ | PHP 8 + PDO
--
-- Ejecutar desde mysql.exe (root sin clave en XAMPP):
--   SOURCE "C:/Users/bang_/Desktop/Proycto U/tecnostock/sql/tecnostock.sql"
--
-- El script es REPRODUCIBLE: recrea la base desde cero (borra las tablas).
-- Incluye: PK/FK, UNIQUE, CHECK, triggers de integridad, procedimiento
-- transaccional, vistas de consulta y datos semilla con stock bajo.
-- ============================================================================

-- Modo estricto: evita que un decimal se redondee o un negativo se convierta
-- silenciosamente en 0 dentro de columnas INT UNSIGNED. Se aplica a GLOBAL
-- (todas las conexiones nuevas, incluida la de PHP/PDO) y a esta sesión.
-- También queda registrado en C:\xampp\mysql\bin\my.ini para sobrevivir reinicios.
SET GLOBAL sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION';
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION';
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS tecnostock
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE tecnostock;

DROP VIEW      IF EXISTS v_movimientos_detalle;
DROP VIEW      IF EXISTS v_productos_stock_bajo;
DROP VIEW      IF EXISTS v_productos_activos;
DROP PROCEDURE IF EXISTS sp_registrar_movimiento;
DROP TABLE     IF EXISTS movimientos;
DROP TABLE     IF EXISTS productos;
DROP TABLE     IF EXISTS categorias;
DROP TABLE     IF EXISTS monedas;
DROP TABLE     IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. Catálogos
-- ----------------------------------------------------------------------------

CREATE TABLE usuarios (
  id_usuario     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre         VARCHAR(100) NOT NULL,
  correo         VARCHAR(150) NOT NULL,
  clave          VARCHAR(255) NOT NULL COMMENT 'password_hash() de PHP (bcrypt)',
  estado         TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1=activo, 0=desactivado',
  fecha_creacion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_acceso  DATETIME     NULL DEFAULT NULL,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_usuarios_correo (correo),
  CONSTRAINT chk_usuarios_nombre CHECK (CHAR_LENGTH(TRIM(nombre)) >= 2),
  CONSTRAINT chk_usuarios_correo CHECK (correo REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+[.][A-Za-z]{2,}$'),
  CONSTRAINT chk_usuarios_clave  CHECK (CHAR_LENGTH(clave) >= 20),
  CONSTRAINT chk_usuarios_estado CHECK (estado IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE monedas (
  codigo     CHAR(3)         NOT NULL COMMENT 'ISO 4217: CLP, USD, EUR',
  nombre     VARCHAR(50)     NOT NULL,
  simbolo    VARCHAR(10)     NOT NULL,
  decimales  TINYINT UNSIGNED NOT NULL DEFAULT 2,
  PRIMARY KEY (codigo),
  CONSTRAINT chk_monedas_codigo CHECK (codigo REGEXP '^[A-Z]{3}$'),
  CONSTRAINT chk_monedas_decimales CHECK (decimales BETWEEN 0 AND 4)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categorias (
  id_categoria   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre         VARCHAR(80)  NOT NULL,
  descripcion    VARCHAR(255) NULL,
  estado         TINYINT(1)   NOT NULL DEFAULT 1,
  fecha_creacion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_categoria),
  UNIQUE KEY uq_categorias_nombre (nombre),
  CONSTRAINT chk_categorias_nombre CHECK (CHAR_LENGTH(TRIM(nombre)) >= 2),
  CONSTRAINT chk_categorias_estado CHECK (estado IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Productos
--    - codigo UNIQUE  -> regla "el código no puede repetirse"
--    - stock_actual / stock_minimo DECIMAL(12,3) + CHECK (x = FLOOR(x)):
--      se almacenan como enteros y la BD RECHAZA 3.5 (un INT lo redondearía a 4
--      antes de poder validarlo, incluso con sql_mode estricto).
--    - precio DECIMAL(14,2) + CHECK -> nunca negativo, sin errores de coma flotante
--    - moneda_codigo FK -> campo "currency" normalizado (ISO 4217)
--    - baja lógica: estado = 0, nunca DELETE cuando hay movimientos
-- ----------------------------------------------------------------------------

CREATE TABLE productos (
  id_producto         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo              VARCHAR(30)  NOT NULL,
  nombre              VARCHAR(120) NOT NULL,
  descripcion         TEXT NULL,
  precio              DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  moneda_codigo       CHAR(3)      NOT NULL DEFAULT 'CLP',
  stock_actual        DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  stock_minimo        DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  estado              TINYINT(1)   NOT NULL DEFAULT 1,
  id_categoria        INT UNSIGNED NOT NULL,
  fecha_creacion      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fecha_desactivacion DATETIME     NULL,
  motivo_desactivacion VARCHAR(255) NULL,
  PRIMARY KEY (id_producto),
  UNIQUE KEY uq_productos_codigo (codigo),
  KEY idx_productos_nombre   (nombre),
  KEY idx_productos_categoria (id_categoria),
  KEY idx_productos_estado    (estado),
  KEY idx_productos_stock     (stock_actual, stock_minimo),
  CONSTRAINT fk_productos_categoria FOREIGN KEY (id_categoria)
    REFERENCES categorias (id_categoria) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_productos_moneda FOREIGN KEY (moneda_codigo)
    REFERENCES monedas (codigo) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_productos_codigo CHECK (UPPER(codigo) REGEXP '^[A-Z0-9][A-Z0-9_-]{2,29}$'),
  CONSTRAINT chk_productos_nombre CHECK (CHAR_LENGTH(TRIM(nombre)) >= 2),
  CONSTRAINT chk_productos_precio CHECK (precio >= 0),
  CONSTRAINT chk_productos_stock_actual CHECK (stock_actual >= 0 AND stock_actual = FLOOR(stock_actual)),
  CONSTRAINT chk_productos_stock_minimo CHECK (stock_minimo >= 0 AND stock_minimo = FLOOR(stock_minimo)),
  CONSTRAINT chk_productos_estado CHECK (estado IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Movimientos (historial inmutable)
--    - fecha_hora, tipo, cantidad, producto y usuario son OBLIGATORIOS
--    - cantidad > 0 y entera (CHECK con FLOOR), salida nunca deja stock negativo
-- ----------------------------------------------------------------------------

CREATE TABLE movimientos (
  id_movimiento INT UNSIGNED NOT NULL AUTO_INCREMENT,
  fecha_hora    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tipo          ENUM('entrada','salida') NOT NULL,
  cantidad      DECIMAL(12,3) NOT NULL,
  observacion   VARCHAR(500) NULL,
  id_producto   INT UNSIGNED NOT NULL,
  id_usuario    INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_movimiento),
  KEY idx_movimientos_producto (id_producto),
  KEY idx_movimientos_usuario  (id_usuario),
  KEY idx_movimientos_fecha    (fecha_hora),
  KEY idx_movimientos_tipo     (tipo),
  CONSTRAINT fk_movimientos_producto FOREIGN KEY (id_producto)
    REFERENCES productos (id_producto) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_movimientos_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_movimientos_cantidad CHECK (cantidad > 0 AND cantidad = FLOOR(cantidad))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Triggers de integridad
--    El stock NO se calcula a mano: se actualiza solo al insertar un movimiento.
--    FOR UPDATE bloquea la fila del producto para evitar carreras concurrentes.
-- ----------------------------------------------------------------------------

DELIMITER //

CREATE TRIGGER trg_productos_bi BEFORE INSERT ON productos FOR EACH ROW
BEGIN
  SET NEW.codigo = UPPER(TRIM(NEW.codigo));
  SET NEW.nombre = TRIM(NEW.nombre);
END//

CREATE TRIGGER trg_productos_bu BEFORE UPDATE ON productos FOR EACH ROW
BEGIN
  SET NEW.codigo = UPPER(TRIM(NEW.codigo));
  SET NEW.nombre = TRIM(NEW.nombre);

  IF NEW.estado = 0 AND OLD.estado = 1 AND NEW.fecha_desactivacion IS NULL THEN
    SET NEW.fecha_desactivacion = CURRENT_TIMESTAMP;
  END IF;

  IF NEW.estado = 1 AND OLD.estado = 0 THEN
    SET NEW.fecha_desactivacion = NULL;
    SET NEW.motivo_desactivacion = NULL;
  END IF;
END//

CREATE TRIGGER trg_movimientos_bi BEFORE INSERT ON movimientos FOR EACH ROW
BEGIN
  DECLARE v_estado TINYINT(1);
  DECLARE v_stock  DECIMAL(12,3);

  IF NEW.tipo NOT IN ('entrada','salida') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tipo de movimiento inválido';
  END IF;

  IF NEW.cantidad IS NULL OR NEW.cantidad <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cantidad debe ser un entero positivo';
  END IF;

  SET NEW.observacion = NULLIF(TRIM(NEW.observacion), '');

  SELECT estado, stock_actual INTO v_estado, v_stock
    FROM productos
   WHERE id_producto = NEW.id_producto
   FOR UPDATE;

  IF v_estado IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El producto indicado no existe';
  END IF;

  IF v_estado = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se pueden registrar movimientos en un producto desactivado';
  END IF;

  IF NEW.tipo = 'salida' AND NEW.cantidad > v_stock THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente: la salida no puede dejar stock negativo';
  END IF;
END//

CREATE TRIGGER trg_movimientos_ai AFTER INSERT ON movimientos FOR EACH ROW
BEGIN
  UPDATE productos
     SET stock_actual = CASE
           WHEN NEW.tipo = 'entrada' THEN stock_actual + NEW.cantidad
           ELSE stock_actual - NEW.cantidad
         END
   WHERE id_producto = NEW.id_producto;
END//

CREATE TRIGGER trg_movimientos_bu BEFORE UPDATE ON movimientos FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Los movimientos no se editan; registre uno compensatorio';
END//

CREATE TRIGGER trg_movimientos_bd BEFORE DELETE ON movimientos FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Los movimientos no se eliminan: son el historial de auditoría';
END//

-- Procedimiento de entrada única para movimientos: transacción + rollback.
-- PHP lo invoca con una consulta preparada (PDO::prepare / execute).
CREATE PROCEDURE sp_registrar_movimiento(
  IN p_tipo        VARCHAR(10),
  IN p_cantidad    DECIMAL(12,3),
  IN p_id_producto INT UNSIGNED,
  IN p_id_usuario  INT UNSIGNED,
  IN p_observacion VARCHAR(500)
)
BEGIN
  DECLARE v_error VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    GET DIAGNOSTICS CONDITION 1 v_error = MESSAGE_TEXT;
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error;
  END;

  START TRANSACTION;
    INSERT INTO movimientos (tipo, cantidad, id_producto, id_usuario, observacion)
    VALUES (p_tipo, p_cantidad, p_id_producto, p_id_usuario, p_observacion);
  COMMIT;
END//

DELIMITER ;

-- ----------------------------------------------------------------------------
-- 5. Vistas para las consultas del MVP
-- ----------------------------------------------------------------------------

CREATE VIEW v_productos_activos AS
SELECT p.id_producto,
       p.codigo,
       p.nombre,
       p.descripcion,
       p.precio,
       p.moneda_codigo,
       m.simbolo AS moneda_simbolo,
       CAST(p.stock_actual AS UNSIGNED) AS stock_actual,
       CAST(p.stock_minimo AS UNSIGNED) AS stock_minimo,
       p.id_categoria,
       c.nombre AS categoria,
       p.fecha_actualizacion,
       CASE WHEN p.stock_actual < p.stock_minimo THEN 1 ELSE 0 END AS stock_bajo
  FROM productos p
  JOIN categorias c ON c.id_categoria = p.id_categoria
  JOIN monedas m ON m.codigo = p.moneda_codigo
 WHERE p.estado = 1;

CREATE VIEW v_productos_stock_bajo AS
SELECT id_producto,
       codigo,
       nombre,
       stock_actual,
       stock_minimo,
       CAST((stock_minimo - stock_actual) AS UNSIGNED) AS faltante,
       categoria,
       moneda_codigo,
       precio
  FROM v_productos_activos
 WHERE stock_bajo = 1
 ORDER BY faltante DESC;

CREATE VIEW v_movimientos_detalle AS
SELECT mv.id_movimiento,
       mv.fecha_hora,
       mv.tipo,
       CAST(mv.cantidad AS UNSIGNED) AS cantidad,
       mv.observacion,
       p.codigo,
       p.nombre AS producto,
       u.nombre AS usuario_responsable
  FROM movimientos mv
  JOIN productos p ON p.id_producto = mv.id_producto
  JOIN usuarios u ON u.id_usuario = mv.id_usuario;

-- ----------------------------------------------------------------------------
-- 6. Datos semilla
--    Claves: admin/admin123 y operador/operador123 (generadas con password_hash)
--    Los productos se crean con stock 0 y se alimentan con movimientos reales:
--    así se demuestra que el trigger actualiza el stock automáticamente.
-- ----------------------------------------------------------------------------

INSERT INTO monedas (codigo, nombre, simbolo, decimales) VALUES
  ('CLP', 'Peso chileno',        '$',    0),
  ('USD', 'Dólar estadounidense','US$', 2),
  ('EUR', 'Euro',                '€',   2);

INSERT INTO categorias (id_categoria, nombre, descripcion) VALUES
  (1, 'Accesorios',  'Cables, hubs y accesorios generales'),
  (2, 'Carga',       'Cargadores y fuentes de poder'),
  (3, 'Audio',       'Audífonos y altavoces'),
  (4, 'Redes',       'Routers, switches y conectividad'),
  (5, 'Protección',  'Fundas y protectores'),
  (6, 'Legacy',      'Productos discontinuados (solo consulta)');

UPDATE categorias SET estado = 0 WHERE id_categoria = 6;

INSERT INTO usuarios (id_usuario, nombre, correo, clave) VALUES
  (1, 'Admin TecnoStock', 'admin@tecnostock.cl',
   '$2y$12$3hrmwEdBbTxPT8VzCMsfdOR07N3P1O4307YIMod8APDTalvgDmPJy'),
  (2, 'Operador Bodega',  'operador@tecnostock.cl',
   '$2y$12$rCv2bf1YbrNClOs/Ne2Nbe8qUgaj5sQ/9NQ8Pvw/RoEnHfB1NjCva');

INSERT INTO productos
  (id_producto, codigo, nombre, descripcion, precio, moneda_codigo,
   stock_actual, stock_minimo, id_categoria)
VALUES
  (1, 'TEC-001', 'Cable USB-C 1.5 m',
   'Cable trenzado de carga rápida, 1.5 metros', 4990.00, 'CLP', 0, 10, 1),
  (2, 'TEC-002', 'Cargador GaN 65 W',
   'Cargador compacto de 65 W con dos puertos', 24990.00, 'CLP', 0, 5, 2),
  (3, 'TEC-003', 'Audífonos Bluetooth Pro',
   'Audífonos inalámbricos con cancelación de ruido', 39990.00, 'CLP', 0, 8, 3),
  (4, 'TEC-004', 'Hub USB 4 puertos',
   'Hub USB 3.0 con 4 puertos y alimentación externa', 18990.00, 'CLP', 0, 6, 1),
  (5, 'TEC-005', 'Router WiFi 6',
   'Router inalámbrico WiFi 6 doble banda', 89990.00, 'CLP', 0, 3, 4),
  (6, 'TEC-006', 'Funda protectora 13"',
   'Funda rígida para notebook de 13 pulgadas', 12990.00, 'CLP', 0, 12, 5),
  (7, 'TEC-007', 'Mouse inalámbrico',
   'Mouse óptico inalámbrico con receptor USB', 15990.00, 'CLP', 0, 7, 1),
  (8, 'TEC-008', 'Adaptador HDMI 4K',
   'Adaptador HDMI a USB-C con soporte 4K', 34.99, 'USD', 0, 5, 1),
  (9, 'TEC-009', 'Cable VGA legacy',
   'Producto discontinuado por el proveedor', 8990.00, 'CLP', 0, 4, 6);

-- Entradas y salidas semilla (el trigger trg_movimientos_ai calcula el stock).
INSERT INTO movimientos (fecha_hora, tipo, cantidad, observacion, id_producto, id_usuario) VALUES
  ('2026-11-01 09:00:00', 'entrada', 40, 'Compra inicial',        1, 1),
  ('2026-11-03 11:30:00', 'salida',  15, 'Venta muestra',         1, 2),
  ('2026-11-01 09:05:00', 'entrada', 20, 'Compra inicial',        2, 1),
  ('2026-11-05 16:10:00', 'salida',  18, 'Venta por mayor',       2, 2),
  ('2026-11-01 09:10:00', 'entrada', 15, 'Compra inicial',        3, 1),
  ('2026-11-04 10:20:00', 'salida',   6, 'Venta tienda',          3, 2),
  ('2026-11-01 09:15:00', 'entrada', 12, 'Compra inicial',        4, 1),
  ('2026-11-06 12:00:00', 'salida',  12, 'Venta tienda',          4, 2),
  ('2026-11-01 09:20:00', 'entrada',  8, 'Compra inicial',        5, 1),
  ('2026-11-02 14:45:00', 'salida',   2, 'Venta tienda',          5, 2),
  ('2026-11-01 09:25:00', 'entrada', 30, 'Compra inicial',        6, 1),
  ('2026-11-07 09:30:00', 'salida',  10, 'Venta tienda',          6, 2),
  ('2026-11-01 09:30:00', 'entrada', 10, 'Compra inicial',        7, 1),
  ('2026-11-08 17:05:00', 'salida',   8, 'Venta tienda',          7, 2),
  ('2026-11-01 09:35:00', 'entrada', 25, 'Importación',           8, 1),
  ('2026-11-09 13:15:00', 'salida',  11, 'Venta online',          8, 2),
  ('2026-10-20 10:00:00', 'entrada', 10, 'Stock antiguo',         9, 1),
  ('2026-10-25 10:00:00', 'salida',   3, 'Última venta',          9, 2);

-- Baja lógica del producto Legacy (tenía movimientos: NO se elimina físicamente).
UPDATE productos
   SET estado = 0,
       fecha_desactivacion = '2026-11-10 10:00:00',
       motivo_desactivacion = 'Discontinuado por el proveedor'
 WHERE id_producto = 9;

-- ----------------------------------------------------------------------------
-- 7. Verificación rápida
-- ----------------------------------------------------------------------------

SELECT 'Tablas creadas' AS resultado,
       COUNT(*) AS total
  FROM information_schema.tables
 WHERE table_schema = 'tecnostock' AND table_type = 'BASE TABLE';

SELECT id_producto, codigo, nombre, stock_actual, stock_minimo, estado
  FROM productos
 ORDER BY id_producto;

SELECT codigo, nombre, stock_actual, stock_minimo, faltante
  FROM v_productos_stock_bajo;

SELECT COUNT(*) AS total_movimientos FROM movimientos;
