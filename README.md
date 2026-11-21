# TecnoStock

Sistema web de inventario para accesorios tecnologicos. Permite registrar productos, controlar entradas y salidas, registrar ventas y administrar usuarios con permisos por modulo.

## Requisitos

- XAMPP (Apache, PHP 8 y MySQL/MariaDB)
- Navegador web

## Instalacion

1. Importar `sql/tecnostock.sql` en MySQL o phpMyAdmin.
2. Copiar `config/database.example.php` como `config/database.php` y ajustar usuario/clave si hace falta.
3. Iniciar Apache y MySQL.
4. Abrir `http://localhost/tecnostock/index.php`.

## Usuarios

| Correo | Clave | Modulos permitidos |
|---|---|---|
| `admin@tecnostock.cl` | `admin123` | Productos, Movimientos, Ventas, Usuarios |
| `operador@tecnostock.cl` | `operador123` | Productos, Movimientos, Ventas |
| `vendedor@tecnostock.cl` | `vendedor123` | Ventas |

Los permisos se administran en **Usuarios**. Si un usuario no tiene un modulo, no aparece en el menu y tampoco puede abrirlo escribiendo la URL: cada pagina valida el permiso en PHP.

## Funcionalidades

- Inicio de sesion con usuario registrado.
- Dashboard con metricas, alertas de stock bajo, ultimos movimientos y ultimas ventas.
- Productos: registrar, listar, buscar por nombre/codigo/categoria, editar y desactivar.
- Movimientos: registrar entradas y salidas.
- Ventas: registrar venta con descuento de stock y movimiento automatico.
- Usuarios: crear, editar, desactivar y asignar permisos por modulo.

## Reglas de negocio

- El codigo de producto no se repite.
- Precio, stocks y cantidades son numeros no negativos; stocks y cantidades son enteros.
- Una salida o venta no puede dejar el stock bajo cero.
- Cada movimiento guarda fecha, tipo, cantidad, producto y usuario.
- El stock se actualiza al registrar entradas, salidas y ventas.
- Los productos con movimientos se desactivan, no se eliminan.

## Pruebas

 Casos verificados con los tres usuarios semilla:

- Login y menu segun permisos.
- Acceso directo por URL a un modulo sin permiso (denegado).
- Alta, busqueda, edicion y desactivacion de productos.
- Codigo duplicado y cantidades no enteras (rechazados).
- Entrada, salida y salida sin stock (rechazada).
- Venta valida, venta sin stock y venta sobre el stock (rechazadas).
- Creacion y edicion de usuarios con distintos permisos.
