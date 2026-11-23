-- Importar con:
--   mysql -u root -e "SOURCE ruta/sin/espacios/tecnostock.sql"

SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION';
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS tecnostock
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tecnostock;

DROP TABLE IF EXISTS detalle_ventas;
DROP TABLE IF EXISTS ventas;
DROP TABLE IF EXISTS movimientos;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS usuario_modulos;
DROP TABLE IF EXISTS modulos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS marcas;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- Usuarios
CREATE TABLE usuarios (
  id_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre     VARCHAR(100) NOT NULL,
  correo     VARCHAR(150) NOT NULL,
  clave      VARCHAR(255) NOT NULL COMMENT 'password_hash() de PHP',
  estado     TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_correo (correo),
  CONSTRAINT chk_usuarios_estado CHECK (estado IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modulos
CREATE TABLE modulos (
  id_modulo INT UNSIGNED NOT NULL AUTO_INCREMENT,
  clave     VARCHAR(30) NOT NULL,
  nombre    VARCHAR(60) NOT NULL,
  PRIMARY KEY (id_modulo),
  UNIQUE KEY uq_modulos_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permisos por usuario
CREATE TABLE usuario_modulos (
  id_usuario INT UNSIGNED NOT NULL,
  id_modulo  INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_usuario, id_modulo),
  KEY idx_usuario_modulos_modulo (id_modulo),
  CONSTRAINT fk_um_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id_usuario),
  CONSTRAINT fk_um_modulo FOREIGN KEY (id_modulo)
    REFERENCES modulos (id_modulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categorias
CREATE TABLE categorias (
  id_categoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre       VARCHAR(80) NOT NULL,
  descripcion  VARCHAR(255) NULL,
  estado       TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_categoria),
  UNIQUE KEY uq_categoria (nombre),
  CONSTRAINT chk_categorias_estado CHECK (estado IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Productos
CREATE TABLE productos (
  id_producto   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo        VARCHAR(30) NOT NULL,
  nombre        VARCHAR(120) NOT NULL,
  descripcion   VARCHAR(255) NULL,
  precio        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  moneda        CHAR(3) NOT NULL DEFAULT 'CLP',
  stock_actual  INT UNSIGNED NOT NULL DEFAULT 0,
  stock_minimo  INT UNSIGNED NOT NULL DEFAULT 0,
  estado        TINYINT(1) NOT NULL DEFAULT 1,
  id_categoria  INT UNSIGNED NOT NULL,
  marca         VARCHAR(80) NOT NULL,
  imagen        VARCHAR(255) NULL,
  PRIMARY KEY (id_producto),
  UNIQUE KEY uq_codigo (codigo),
  KEY idx_categoria (id_categoria),
  KEY idx_marca (marca),
  CONSTRAINT fk_productos_categoria FOREIGN KEY (id_categoria)
    REFERENCES categorias (id_categoria),
  CONSTRAINT chk_productos_precio CHECK (precio >= 0),
  CONSTRAINT chk_productos_stock_actual CHECK (stock_actual >= 0),
  CONSTRAINT chk_productos_stock_minimo CHECK (stock_minimo >= 0),
  CONSTRAINT chk_productos_estado CHECK (estado IN (0,1)),
  CONSTRAINT chk_productos_moneda CHECK (moneda IN ('CLP','USD','EUR'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Movimientos
CREATE TABLE movimientos (
  id_movimiento INT UNSIGNED NOT NULL AUTO_INCREMENT,
  fecha_hora    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tipo          ENUM('entrada','salida') NOT NULL,
  cantidad      INT UNSIGNED NOT NULL,
  observacion   VARCHAR(255) NULL,
  id_producto   INT UNSIGNED NOT NULL,
  id_usuario    INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_movimiento),
  KEY idx_mov_producto (id_producto),
  CONSTRAINT fk_mov_producto FOREIGN KEY (id_producto)
    REFERENCES productos (id_producto),
  CONSTRAINT fk_mov_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id_usuario),
  CONSTRAINT chk_movimientos_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ventas
CREATE TABLE ventas (
  id_venta    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  fecha_hora  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total       DECIMAL(10,2) NOT NULL,
  moneda      CHAR(3) NOT NULL DEFAULT 'CLP',
  id_usuario  INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_venta),
  KEY idx_ventas_fecha (fecha_hora),
  CONSTRAINT fk_ventas_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id_usuario),
  CONSTRAINT chk_ventas_total CHECK (total >= 0),
  CONSTRAINT chk_ventas_moneda CHECK (moneda IN ('CLP','USD','EUR'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Detalle de ventas
CREATE TABLE detalle_ventas (
  id_detalle      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_venta        INT UNSIGNED NOT NULL,
  id_producto     INT UNSIGNED NOT NULL,
  cantidad        INT UNSIGNED NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id_detalle),
  KEY idx_detalle_venta (id_venta),
  CONSTRAINT fk_detalle_venta FOREIGN KEY (id_venta)
    REFERENCES ventas (id_venta) ON DELETE CASCADE,
  CONSTRAINT fk_detalle_producto FOREIGN KEY (id_producto)
    REFERENCES productos (id_producto),
  CONSTRAINT chk_detalle_cantidad CHECK (cantidad > 0),
  CONSTRAINT chk_detalle_precio CHECK (precio_unitario >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos semilla
-- Claves: admin/admin123, operador/operador123 y vendedor/vendedor123

INSERT INTO categorias (id_categoria, nombre, descripcion) VALUES
  (1, 'Accesorios', 'Cables, hubs y accesorios'),
  (2, 'Carga',      'Cargadores y fuentes'),
  (3, 'Audio',      'Audifonos y altavoces'),
  (4, 'Redes',      'Routers y conectividad'),
  (5, 'Proteccion', 'Fundas y protectores');

INSERT INTO usuarios (id_usuario, nombre, correo, clave) VALUES
  (1, 'Admin TecnoStock', 'admin@tecnostock.cl',
   '$2y$12$3hrmwEdBbTxPT8VzCMsfdOR07N3P1O4307YIMod8APDTalvgDmPJy'),
  (2, 'Operador Bodega', 'operador@tecnostock.cl',
   '$2y$12$rCv2bf1YbrNClOs/Ne2Nbe8qUgaj5sQ/9NQ8Pvw/RoEnHfB1NjCva'),
  (3, 'Vendedor Tienda', 'vendedor@tecnostock.cl',
   '$2y$12$nRhbayDkIsrlDD3bGiFzT.R1PMATzLw.Xvh.5U/cqk79P27.R3ixK');

INSERT INTO modulos (id_modulo, clave, nombre) VALUES
  (1, 'productos', 'Productos'),
  (2, 'movimientos', 'Movimientos'),
  (3, 'ventas', 'Ventas'),
  (4, 'usuarios', 'Usuarios'),
  (5, 'stock', 'Stock');

INSERT INTO usuario_modulos (id_usuario, id_modulo) VALUES
  (1,1), (1,2), (1,3), (1,4), (1,5),
  (2,1), (2,2), (2,3), (2,5),
  (3,3), (3,5);

INSERT INTO productos
  (id_producto, codigo, nombre, precio, moneda, stock_actual, stock_minimo, id_categoria, marca, estado)
VALUES
  (1, 'TEC-001', 'Cable USB-C 1.5 m',   4990.00, 'CLP', 25, 10, 1, 'Generico',   1),
  (2, 'TEC-002', 'Cargador GaN 65 W',  24990.00, 'CLP',  2,  5, 2, 'Xiaomi',     1),
  (3, 'TEC-003', 'Audifonos Bluetooth',39990.00, 'CLP',  9,  8, 3, 'Logitech',   1),
  (4, 'TEC-004', 'Hub USB 4 puertos',  18990.00, 'CLP',  0,  6, 1, 'Generico',   1),
  (5, 'TEC-005', 'Router WiFi 6',      89990.00, 'CLP',  6,  3, 4, 'TP-Link',    1),
  (6, 'TEC-006', 'Funda 13 pulgadas',  12990.00, 'CLP', 20, 12, 5, 'Samsung',    1),
  (7, 'TEC-007', 'Mouse inalambrico',  15990.00, 'CLP',  2,  7, 1, 'Logitech',   1),
  (8, 'TEC-008', 'Adaptador HDMI 4K',    34.99, 'USD', 14,  5, 1, 'Generico',   1),
  (9, 'TEC-009', 'Cable VGA legacy',   8990.00, 'CLP',  7,  4, 1, 'Generico',   0);

INSERT INTO movimientos (fecha_hora, tipo, cantidad, observacion, id_producto, id_usuario) VALUES
  ('2026-11-01 09:00:00','entrada',40,'Compra inicial', 1,1),
  ('2026-11-03 11:30:00','salida',  15,'Venta',         1,2),
  ('2026-11-01 09:05:00','entrada',20,'Compra inicial', 2,1),
  ('2026-11-05 16:10:00','salida',  18,'Venta',         2,2),
  ('2026-11-01 09:10:00','entrada',15,'Compra inicial', 3,1),
  ('2026-11-04 10:20:00','salida',   6,'Venta',         3,2),
  ('2026-11-01 09:15:00','entrada',12,'Compra inicial', 4,1),
  ('2026-11-06 12:00:00','salida',  12,'Venta',         4,2),
  ('2026-11-01 09:20:00','entrada', 8,'Compra inicial', 5,1),
  ('2026-11-02 14:45:00','salida',   2,'Venta',         5,2),
  ('2026-11-01 09:25:00','entrada',30,'Compra inicial', 6,1),
  ('2026-11-07 09:30:00','salida',  10,'Venta',         6,2),
  ('2026-11-01 09:30:00','entrada',10,'Compra inicial', 7,1),
  ('2026-11-08 17:05:00','salida',   8,'Venta',         7,2),
  ('2026-11-01 09:35:00','entrada',25,'Importacion',   8,1),
  ('2026-11-09 13:15:00','salida',  11,'Venta',         8,2),
  ('2026-10-20 10:00:00','entrada',10,'Stock antiguo',  9,1),
  ('2026-10-25 10:00:00','salida',   3,'Ultima venta',  9,2);

INSERT INTO ventas (id_venta, fecha_hora, total, moneda, id_usuario) VALUES
  (1, '2026-11-03 11:30:00', 74850.00, 'CLP', 2),
  (2, '2026-11-05 16:10:00',449820.00, 'CLP', 2),
  (3, '2026-11-09 13:15:00',   384.89, 'USD', 2);

INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario) VALUES
  (1, 1, 15, 4990.00),
  (2, 2, 18, 24990.00),
  (3, 8, 11,   34.99);

SELECT COUNT(*) AS tablas FROM information_schema.tables
 WHERE table_schema = 'tecnostock' AND table_type = 'BASE TABLE';

SELECT codigo, nombre, stock_actual, stock_minimo
  FROM productos
 WHERE estado = 1 AND stock_actual < stock_minimo;

SELECT COUNT(*) AS movimientos FROM movimientos;
SELECT COUNT(*) AS ventas FROM ventas;
SELECT COUNT(*) AS usuarios FROM usuarios;
SELECT COUNT(*) AS modulos FROM modulos;
