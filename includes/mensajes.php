<?php
/* Textos de exito de cada evento. Se usan en la notificacion flotante. */
function texto_mensaje(string $modulo, string $clave): string
{
    $mensajes = [
        'productos' => [
            'creado'      => 'Producto creado correctamente.',
            'editado'     => 'Producto actualizado correctamente.',
            'desactivado' => 'Producto desactivado correctamente.',
            'activado'    => 'Producto activado correctamente.',
        ],
        'movimientos' => [
            'creado'              => 'Movimiento registrado correctamente.',
            'creado_con_producto' => 'Producto creado y entrada registrada correctamente.',
        ],
        'ventas' => [
            'creado' => 'Venta realizada correctamente. El stock fue descontado.',
        ],
        'usuarios' => [
            'creado'      => 'Usuario creado correctamente.',
            'editado'     => 'Usuario actualizado correctamente.',
            'desactivado' => 'Usuario desactivado correctamente.',
        ],
        'perfil' => [
            'guardado'            => 'Perfil actualizado correctamente.',
            'solicitud_enviada'   => 'Solicitud de cambio de contrasena enviada al administrador.',
            'clave_aceptada'      => 'Contrasena actualizada correctamente.',
            'solicitud_rechazada' => 'Solicitud rechazada.',
        ],
    ];

    return $mensajes[$modulo][$clave] ?? '';
}
