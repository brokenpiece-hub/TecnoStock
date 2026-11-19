# Base de datos TecnoStock (version simple)

Script: [`tecnostock.sql`](./tecnostock.sql). Recrea las tablas desde cero.

## Tablas

| Tabla | Campos principales |
|---|---|
| `usuarios` | id, nombre, correo (unico), clave (hash), estado |
| `categorias` | id, nombre (unico), descripcion, estado |
| `productos` | id, codigo (unico), nombre, descripcion, precio, **moneda**, stock_actual, stock_minimo, estado, id_categoria |
| `movimientos` | id, fecha_hora, tipo (entrada/salida), cantidad, observacion, id_producto, id_usuario |

Relaciones: `productos.id_categoria -> categorias`, `movimientos.id_producto -> productos`, `movimientos.id_usuario -> usuarios`.

## Validaciones

- Codigo de producto unico (`UNIQUE`).
- Precio y stocks no negativos (`CHECK`).
- Cantidad de movimiento mayor que cero (`CHECK`).
- La **moneda** es un campo `CHAR(3)` con `CHECK (moneda IN ('CLP','USD','EUR'))`.
- Los stocks son `INT`: solo enteros. En el formulario se usa `type="number" step="1" min="0"` y en PHP `filter_var($valor, FILTER_VALIDATE_INT)`.

> El stock se actualizara desde PHP al registrar entradas/salidas (siguiente fase). La base guarda los datos y sus restricciones basicas.

## Usuarios semilla

| Correo | Clave |
|---|---|
| `admin@tecnostock.cl` | `admin123` |
| `operador@tecnostock.cl` | `operador123` |

Hay 9 productos (1 desactivado) y 18 movimientos. Productos con stock bajo: TEC-002, TEC-004 y TEC-007.
