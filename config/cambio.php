<?php

/* Tipo de cambio para pasar pesos chilenos a dolares.
   Valor de Google Finance del 23-11-2026: 1 USD = 1022.65 CLP.
   Cambia este numero cuando quieras usar otro valor. */
const CLP_POR_USD = 1022.65;

/* Convierte un monto en CLP a USD. */
function clp_a_usd(float $monto): float
{
    return $monto / CLP_POR_USD;
}

/* Suma los totales y los pasa a USD (solo convierte CLP). */
function total_en_usd(array $totales): float
{
    $total = 0.0;
    foreach ($totales as $fila) {
        if ($fila['moneda'] === 'CLP') {
            $total += clp_a_usd((float) $fila['total']);
        } elseif ($fila['moneda'] === 'USD') {
            $total += (float) $fila['total'];
        }
    }
    return $total;
}
