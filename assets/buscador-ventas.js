/* Buscador de productos en Nueva venta: muestra solo nombres y guarda el id. */
(function () {
    var input = document.getElementById('buscar-producto');
    if (!input) {
        return;
    }

    var idOculto = document.getElementById('id_producto');
    var lista = document.getElementById('lista-productos');
    var opciones = Array.prototype.slice.call(lista.querySelectorAll('li[role="option"]'));
    var sinResultados = lista.querySelector('.sin-resultados');
    var textoStock = document.getElementById('stock-disponible');
    var cantidad = document.getElementById('cantidad');
    var formulario = input.closest('form');
    var activo = -1;

    /* Quita acentos y mayusculas para comparar nombres. */
    function normalizar(texto) {
        return texto
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    /* Devuelve solo las opciones que no estan ocultas. */
    function visibles() {
        return opciones.filter(function (opcion) {
            return !opcion.hidden;
        });
    }

    /* Muestra la lista de productos. */
    function abrir() {
        lista.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    /* Oculta la lista de productos. */
    function cerrar() {
        lista.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        activo = -1;
    }

    /* Deja una opcion marcada como elegida. */
    function resaltar(opcion) {
        opciones.forEach(function (otra) {
            otra.classList.remove('activo');
            otra.setAttribute('aria-selected', 'false');
        });
        if (!opcion) {
            return;
        }
        opcion.classList.add('activo');
        opcion.setAttribute('aria-selected', 'true');
    }

    /* Muestra solo los nombres que coinciden con lo escrito. */
    function filtrar() {
        var busqueda = normalizar(input.value);
        var total = 0;

        opciones.forEach(function (opcion) {
            var mostrar = busqueda === '' || normalizar(opcion.dataset.nombre).indexOf(busqueda) !== -1;
            opcion.hidden = !mostrar;
            if (mostrar) {
                total++;
            }
        });

        sinResultados.hidden = total !== 0;
        resaltar(null);
        abrir();
    }

    /* Quita el producto elegido y limpia el stock mostrado. */
    function limpiarSeleccion() {
        idOculto.value = '';
        textoStock.textContent = 'Stock disponible: seleccione un producto';
        cantidad.max = '';
        resaltar(null);
    }

    /* Guarda el producto elegido y muestra su stock y precio. */
    function seleccionar(opcion) {
        idOculto.value = opcion.dataset.id;
        input.value = opcion.dataset.nombre;
        textoStock.textContent = 'Stock disponible: ' + opcion.dataset.stock +
            ' | Precio: ' + opcion.dataset.moneda + ' ' + opcion.dataset.precio;
        cantidad.max = opcion.dataset.stock;
        resaltar(opcion);
        cerrar();
    }

    /* Al enfocar el campo, muestra la lista. */
    input.addEventListener('focus', function () {
        filtrar();
    });

    /* Al escribir, limpia la eleccion y filtra. */
    input.addEventListener('input', function () {
        limpiarSeleccion();
        filtrar();
    });

    /* Al salir del campo, oculta la lista. */
    input.addEventListener('blur', function () {
        cerrar();
    });

    /* Al bajar el boton, elige el producto sin perder el campo. */
    lista.addEventListener('mousedown', function (evento) {
        var opcion = evento.target.closest('li[role="option"]');
        if (opcion) {
            evento.preventDefault();
            seleccionar(opcion);
        }
    });

    /* Al hacer clic, elige el producto. */
    lista.addEventListener('click', function (evento) {
        var opcion = evento.target.closest('li[role="option"]');
        if (opcion) {
            seleccionar(opcion);
        }
    });

    /* Con flechas recorre la lista, Enter elige y Escape cierra. */
    input.addEventListener('keydown', function (evento) {
        var opcionesVisibles = visibles();

        if (evento.key === 'ArrowDown' || evento.key === 'ArrowUp') {
            evento.preventDefault();
            if (opcionesVisibles.length === 0) {
                return;
            }
            if (evento.key === 'ArrowDown') {
                activo = (activo + 1) % opcionesVisibles.length;
            } else {
                activo = activo <= 0 ? opcionesVisibles.length - 1 : activo - 1;
            }
            resaltar(opcionesVisibles[activo]);
        } else if (evento.key === 'Enter') {
            if (!lista.hidden && activo >= 0 && opcionesVisibles[activo]) {
                evento.preventDefault();
                seleccionar(opcionesVisibles[activo]);
            }
        } else if (evento.key === 'Escape') {
            cerrar();
        }
    });

    /* No deja enviar el formulario si no se eligio producto. */
    formulario.addEventListener('submit', function (evento) {
        if (idOculto.value === '') {
            evento.preventDefault();
            textoStock.textContent = 'Seleccione un producto de la lista.';
            input.focus();
            abrir();
        }
    });

    /* Si el formulario volvio con error, recupera el producto ya elegido. */
    if (idOculto.value !== '') {
        for (var i = 0; i < opciones.length; i++) {
            if (opciones[i].dataset.id === idOculto.value) {
                input.value = opciones[i].dataset.nombre;
                textoStock.textContent = 'Stock disponible: ' + opciones[i].dataset.stock +
                    ' | Precio: ' + opciones[i].dataset.moneda + ' ' + opciones[i].dataset.precio;
                cantidad.max = opciones[i].dataset.stock;
                resaltar(opciones[i]);
                break;
            }
        }
    }
})();
