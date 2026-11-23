<?php

/* Marcas del sistema: monograma TS para el panel y logo completo para el ingreso. */

$logos = [];

/* Isotipo TS: se usa en el sidebar y en las demas pantallas del sistema. */
$logos['isotipo'] = <<<'SVG'
<svg class="isotipo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 300" role="img" aria-label="TecnoStock">
  <defs>
    <linearGradient id="ts-iso-silver" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="#F5F5F7"/>
      <stop offset="100%" stop-color="#A5A5AE"/>
    </linearGradient>
    <linearGradient id="ts-iso-purple" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#A000FF"/>
      <stop offset="100%" stop-color="#5700B8"/>
    </linearGradient>
  </defs>

  <!-- T plateada -->
  <path fill="url(#ts-iso-silver)"
    d="M0 5 L294 5 L237 60 L186 60
       L186 195 L109 232 L117 60 L0 60 Z"/>

  <!-- S purpura -->
  <path fill="url(#ts-iso-purple)"
    d="M329 5 H488 L451 60 H329
       C302 60 300 96 330 96
       H402 C490 96 490 163 490 163
       C490 207 460 232 414 232
       H169 L222 177 H399
       C424 177 425 139 399 139
       H293 C239 139 230 119 230 97
       C230 60 266 5 329 5 Z"/>
</svg>
SVG;

/* Logo completo: solo se muestra en la pantalla de inicio de sesion. */
$logos['completo'] = <<<'SVG'
<svg class="logo-completo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600" role="img" aria-label="TecnoStock">
  <defs>
    <linearGradient id="ts-login-silver" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="#FFFFFF"/>
      <stop offset="100%" stop-color="#A0A0A9"/>
    </linearGradient>
    <linearGradient id="ts-login-purple" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#A000FF"/>
      <stop offset="100%" stop-color="#5700B8"/>
    </linearGradient>
  </defs>

  <!-- Fondo -->
  <rect width="1200" height="600" fill="#000000"/>

  <!-- Monograma TS -->
  <g transform="translate(405 45) scale(0.75)">
    <path fill="url(#ts-login-silver)"
      d="M0 5 L294 5 L237 60 L186 60
         L186 195 L109 232 L117 60 L0 60 Z"/>

    <path fill="url(#ts-login-purple)"
      d="M329 5 H488 L451 60 H329
         C302 60 300 96 330 96
         H402 C490 96 490 163 490 163
         C490 207 460 232 414 232
         H169 L222 177 H399
         C424 177 425 139 399 139
         H293 C239 139 230 119 230 97
         C230 60 266 5 329 5 Z"/>
  </g>

  <!-- Nombre -->
  <text x="600" y="475"
        text-anchor="middle"
        font-family="Arial Black, Arial, sans-serif"
        font-size="145"
        font-weight="900"
        textLength="970"
        lengthAdjust="spacingAndGlyphs">

    <tspan fill="url(#ts-login-silver)">Tecno</tspan><tspan fill="url(#ts-login-purple)">Stock</tspan>

  </text>

</svg>
SVG;
