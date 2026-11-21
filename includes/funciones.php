<?php
declare(strict_types=1);

function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function redirigir(string $ruta): void
{
    header('Location: ' . $ruta);
    exit;
}

function entero_no_negativo($valor): ?int
{
    if (!is_scalar($valor)) {
        return null;
    }

    $texto = trim((string) $valor);
    if ($texto === '' || filter_var($texto, FILTER_VALIDATE_INT) === false) {
        return null;
    }

    $numero = (int) $texto;
    return $numero >= 0 ? $numero : null;
}

function precio_valido($valor): ?string
{
    if (!is_scalar($valor)) {
        return null;
    }

    $texto = trim((string) $valor);
    if ($texto === '' || !is_numeric($texto)) {
        return null;
    }

    $numero = (float) $texto;
    if ($numero < 0) {
        return null;
    }

    return number_format($numero, 2, '.', '');
}
