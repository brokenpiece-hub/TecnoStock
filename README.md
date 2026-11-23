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
| `admin@tecnostock.cl` | `admin123` | Productos, Movimientos, Ventas, Usuarios, Stock |
| `operador@tecnostock.cl` | `operador123` | Productos, Movimientos, Ventas, Stock |
| `vendedor@tecnostock.cl` | `vendedor123` | Ventas, Stock |

Los permisos se administran en **Usuarios**. Si un usuario no tiene un modulo, no aparece en el menu y tampoco puede abrirlo escribiendo la URL: cada pagina valida el permiso en PHP.

## Funcionalidades

- Inicio de sesion con usuario registrado.
- Dashboard con metricas, alertas de stock bajo, ultimos movimientos y ultimas ventas.
- Productos: registrar, listar, buscar por nombre/codigo/categoria/marca, editar (incluye nombre y codigo), desactivar y activar. El codigo sigue siendo unico: al editar no se puede usar el de otro producto. El listado tiene pestañas **Activos** e **Inactivos**: los productos desactivados se guardan en la pestaña Inactivos y se pueden devolver con el boton **Activar**, sin borrarlos.
- Marca: se escribe directamente en el formulario, no se elige de una lista.
- Filtros de productos: por categoria, marca escrita y stock critico; se pueden combinar entre si.
- Imagenes: subir JPG, PNG o WEBP (maximo 2 MB) al crear o editar un producto; tambien se pueden reemplazar o quitar.
- Movimientos: registrar entradas y salidas. El historial se puede buscar por producto, observacion o usuario y se puede combinar con el filtro por tipo.
- Ventas: registrar venta con descuento de stock y movimiento automatico. El registro se puede buscar por producto, usuario o numero de venta.
- Stock: vista de solo lectura con existencias, alertas y ultimos movimientos. Muestra la marca, permite buscar productos y filtrar por categoria y marca, y tiene un boton **Actualizar** para traer los cambios hechos en Productos. Tambien tiene pestañas **Activos** e **Inactivos** para separar los productos sin eliminarlos. No tiene botones de edicion.
- Usuarios: crear, editar, desactivar y asignar permisos por modulo.
- Diseno: paleta oscura violeta inspirada en HBO Max, con modo claro y oscuro. El boton del encabezado cambia el tema y el navegador recuerda la eleccion.

## Reglas de negocio

- El codigo de producto no se repite.
- Cada producto tiene una categoria seleccionable (incluye **Mouse**) y una marca escrita por el usuario.
- Precio, stocks y cantidades son numeros no negativos; stocks y cantidades son enteros.
- Una salida o venta no puede dejar el stock bajo cero.
- Cada movimiento guarda fecha, tipo, cantidad, producto y usuario.
- El stock se actualiza al registrar entradas, salidas y ventas.
- Toda venta guarda `ventas`, `detalle_ventas`, descuenta el stock y crea una salida en `movimientos` en una sola transaccion.
- Las imagenes se guardan en `assets/img/productos/`; en la BD solo se guarda el nombre del archivo.
- El modulo Stock solo muestra datos: no permite crear, editar ni desactivar productos.
- Los productos con movimientos se desactivan, no se eliminan.
- Desactivar un producto solo cambia su estado: sigue en la BD y aparece en la pestaña Inactivos de Productos y Stock. Activar lo devuelve a la pestaña Activos.

## Pruebas

 Casos verificados con los tres usuarios semilla:

- Login y menu segun permisos.
- Acceso directo por URL a un modulo sin permiso (denegado).
- Alta, busqueda, edicion y desactivacion de productos.
- Filtros por categoria, marca y stock critico, solos y combinados.
- Codigo duplicado y cantidades no enteras (rechazados).
- Entrada, salida y salida sin stock (rechazada).
- Venta valida, venta sin stock y venta sobre el stock (rechazadas).
- Venta del vendedor: descuenta stock y aparece como salida en Stock.
- Subida, reemplazo y eliminacion de imagen; archivos invalidos o mayores a 2 MB (rechazados).
- Vendedor ve Ventas y Stock, no ve Productos y recibe 403 si abre `productos/` por URL.
- Editar un producto y actualizar Stock: el nuevo stock y la nueva marca aparecen en el modulo Stock.
- Pestañas Activos/Inactivos en Productos y Stock: los filtros se conservan al cambiar de pestaña y desactivar o activar no borra productos.
- Creacion y edicion de usuarios con distintos permisos.
- Cambio de tema claro/oscuro y persistencia despues de recargar la pagina.
