# Base de datos TecnoStock (version simple)

Script: [`tecnostock.sql`](./tecnostock.sql). Recrea las tablas desde cero.

## Tablas

| Tabla | Campos principales |
|---|---|
| `usuarios` | id, nombre, correo (unico), clave (hash), estado |
| `categorias` | id, nombre (unico), descripcion, estado |
| `productos` | id, codigo (unico), nombre, descripcion, precio, **moneda**, stock_actual, stock_minimo, estado, id_categoria |
| `movimientos` | id, fecha_hora, tipo (entrada/salida), cantidad, observacion, id_producto, id_usuario |
| `ventas` | id, fecha_hora, total, moneda, id_usuario |
| `detalle_ventas` | id, id_venta, id_producto, cantidad, precio_unitario |

Relaciones: `productos.id_categoria -> categorias`, `movimientos.id_producto -> productos`, `movimientos.id_usuario -> usuarios`, `detalle_ventas.id_venta -> ventas`, `detalle_ventas.id_producto -> productos`.

## Validaciones

- Codigo de producto unico (`UNIQUE`).
- Precio y stocks no negativos (`CHECK`).
- Cantidad de movimiento y de detalle mayor que cero (`CHECK`).
- La **moneda** es un campo `CHAR(3)` con `CHECK (moneda IN ('CLP','USD','EUR'))`.
- Los stocks son `INT`: solo enteros. En el formulario se usa `type="number" step="1" min="0"` y en PHP `filter_var($valor, FILTER_VALIDATE_INT)`.

> El stock se actualiza desde PHP. Al registrar una venta se revisa el stock, se descuenta y se guarda el movimiento.

## Usuarios semilla

| Correo | Clave |
|---|---|
| `admin@tecnostock.cl` | `admin123` |
| `operador@tecnostock.cl` | `operador123` |

Hay 9 productos (1 desactivado), 18 movimientos y 3 ventas de ejemplo. Productos con stock bajo: TEC-002, TEC-004 y TEC-007.
