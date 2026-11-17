# Base de datos TecnoStock

Script reproducible: [`tecnostock.sql`](./tecnostock.sql)

## Ejecución

Con Apache/MariaDB de XAMPP iniciados:

```powershell
& 'C:\xampp\mysql\bin\mysql.exe' --default-character-set=utf8mb4 -u root `
  -e 'SOURCE C:/Users/bang_/AppData/Local/Temp/opencode/tecnostock.sql'
```

Si la ruta tiene espacios, copiar el script primero a una ruta sin espacios
(el cliente `mysql.exe` no maneja bien `SOURCE` con rutas con espacios).

También se puede importar desde phpMyAdmin: **Importar → seleccionar el archivo**.

> El script **recrea las tablas** (drop + create). No ejecutar sobre datos reales.

## Usuarios semilla

| Correo | Clave | Nombre |
|---|---|---|
| `admin@tecnostock.cl` | `admin123` | Admin TecnoStock |
| `operador@tecnostock.cl` | `operador123` | Operador Bodega |

Las claves están guardadas con `password_hash()` (bcrypt, `$2y$12$...`).

## Modelo

| Tabla | Propósito | Validaciones clave |
|---|---|---|
| `usuarios` | Login y responsable de movimientos | correo `UNIQUE` + formato, clave con hash, estado 0/1 |
| `monedas` | Catálogo ISO 4217 (CLP/USD/EUR) | código `CHAR(3)` uppercase, FK desde productos |
| `categorias` | Catálogo de categorías | nombre `UNIQUE`, estado 0/1 |
| `productos` | Catálogo e inventario | código `UNIQUE`, precio ≥ 0, stock entero ≥ 0, `stock_bajo = stock_actual < stock_minimo`, baja lógica |
| `movimientos` | Historial de entradas/salidas | cantidad entera > 0, tipo ENUM, FK producto/usuario, inmutable |

## Distribución de responsabilidades

- **Restricciones declarativas** (`UNIQUE`, `FK`, `CHECK`): integridad básica.
- **Triggers**:
  - `trg_productos_bi/bu`: normalizan código y nombre; registran fecha/motivo de desactivación.
  - `trg_movimientos_bi`: bloquea movimientos sobre productos desactivados y salidas que dejen stock negativo; usa `SELECT ... FOR UPDATE` para evitar carreras.
  - `trg_movimientos_ai`: **actualiza el stock automáticamente**.
  - `trg_movimientos_bu/bd`: impiden editar/borrar el historial (auditabilidad).
- **Procedimiento** `sp_registrar_movimiento(...)`: transacción única con `ROLLBACK` ante error. PHP lo llamará con `PDO::prepare()`.
- **Vistas**: `v_productos_activos`, `v_productos_stock_bajo` (alerta) y `v_movimientos_detalle`.

## Enteros y modo estricto

`stock_actual`, `stock_minimo` y `cantidad` son `DECIMAL(12,3)` con
`CHECK (x = FLOOR(x))`. Esto rechaza `3.5` **en la base de datos**: un `INT`
redondearía el valor a `4` antes de poder validarlo, incluso con `sql_mode`
estricto. PHP debe usar además `filter_var($valor, FILTER_VALIDATE_INT)` y el
formulario `type="number" step="1" min="0"`.

El modo estricto también quedó configurado en `C:\xampp\mysql\bin\my.ini` y se
aplica con `SET GLOBAL` dentro del script.

## Casos de prueba ejecutados

| Caso | Resultado esperado | Verificado |
|---|---|---|
| Código de producto duplicado | Error 1062 | ✅ |
| Precio negativo | Error de `CHECK` | ✅ |
| Stock con decimales (`3.5`) | Error de `CHECK` | ✅ |
| Stock negativo (`-1`) | Error de `CHECK` | ✅ |
| Moneda inexistente | Error de FK | ✅ |
| Correo con formato inválido | Error de `CHECK` | ✅ |
| Salida mayor al stock | Error 45000 + rollback, stock intacto | ✅ |
| Cantidad `0` o decimal en movimiento | Error 45000 / `CHECK` | ✅ |
| Movimiento sobre producto desactivado | Error 45000 | ✅ |
| Editar o borrar un movimiento | Error 45000 | ✅ |
| Entrada + salida válidas por procedimiento | Stock 25 → 28 → 25 | ✅ |
| Productos con stock bajo | 3 alertas (TEC-002, TEC-004, TEC-007) | ✅ |
