# Base de datos TecnoStock

Script: [`tecnostock.sql`](./tecnostock.sql). Recrea las tablas desde cero.

## Tablas

| Tabla | Campos |
|---|---|
| `usuarios` | id, nombre, correo, clave, estado |
| `modulos` | id, clave, nombre |
| `usuario_modulos` | id_usuario, id_modulo |
| `categorias` | id, nombre, descripcion, estado |
| `productos` | id, codigo, nombre, descripcion, precio, moneda, stock_actual, stock_minimo, estado, id_categoria |
| `movimientos` | id, fecha_hora, tipo, cantidad, observacion, id_producto, id_usuario |
| `ventas` | id, fecha_hora, total, moneda, id_usuario |
| `detalle_ventas` | id, id_venta, id_producto, cantidad, precio_unitario |

Relaciones: `usuario_modulos` une usuarios con modulos. `productos.id_categoria -> categorias`, `movimientos.id_producto -> productos`, `movimientos.id_usuario -> usuarios`, `detalle_ventas.id_venta -> ventas`, `detalle_ventas.id_producto -> productos`.

## Validaciones

- Codigo de producto unico.
- Correo de usuario unico.
- Precio y stocks no negativos.
- Cantidad mayor que cero en movimientos y detalle.
- Moneda: CLP, USD o EUR.
- Un usuario no puede repetir el mismo modulo.

## Usuarios y permisos

| Correo | Clave | Modulos |
|---|---|---|
| `admin@tecnostock.cl` | `admin123` | Productos, Movimientos, Ventas, Usuarios |
| `operador@tecnostock.cl` | `operador123` | Productos, Movimientos, Ventas |
| `vendedor@tecnostock.cl` | `vendedor123` | Ventas |

Inicio siempre esta disponible despues de iniciar sesion. El menu y cada pagina validan el permiso en PHP.

Hay 9 productos (1 desactivado), 18 movimientos y 3 ventas.
