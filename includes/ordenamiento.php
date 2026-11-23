<?php

/* Funciones compartidas para ordenar las tablas por cabecera. */

/* Numericas y fechas empiezan de mayor a menor; el texto, en orden alfabetico. */
function direccion_inicial_orden($columna)
{
    $inicial = [
        'codigo'            => 'desc',
        'id_venta'          => 'desc',
        'precio'            => 'desc',
        'precio_unitario'   => 'desc',
        'total'             => 'desc',
        'stock_actual'      => 'desc',
        'stock_minimo'      => 'desc',
        'cantidad'          => 'desc',
        'fecha_hora'        => 'desc',
        'ultimo_movimiento' => 'desc',
    ];
    return isset($inicial[$columna]) ? $inicial[$columna] : 'asc';
}

/* Arma el enlace de una columna y invierte la direccion al hacer clic de nuevo. */
function url_ordenamiento($columna, $ordenActual, $direccionActual, $filtros)
{
    $parametros = $filtros;
    $parametros['orden'] = $columna;
    if ($ordenActual === $columna) {
        $parametros['direccion'] = $direccionActual === 'asc' ? 'desc' : 'asc';
    } else {
        $parametros['direccion'] = direccion_inicial_orden($columna);
    }
    return 'index.php?' . http_build_query($parametros);
}

/* Devuelve la cabecera clickeable con la flecha de direccion. */
function cabecera_ordenable($columna, $etiqueta, $orden, $direccion, $filtros)
{
    $activo = $orden === $columna;
    $clase = $activo ? 'ordenar activo' : 'ordenar';
    $flecha = !$activo ? '↕' : ($direccion === 'asc' ? '↑' : '↓');
    $url = e(url_ordenamiento($columna, $orden, $direccion, $filtros));

    return '<th><a class="' . $clase . '" data-ordenar href="' . $url . '">'
        . e($etiqueta) . ' <span class="flecha-orden" aria-hidden="true">' . $flecha . '</span></a></th>';
}
