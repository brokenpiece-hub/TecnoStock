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

    function normalizar(texto) {
        return texto
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function visibles() {
        return opciones.filter(function (opcion) {
            return !opcion.hidden;
        });
    }

    function abrir() {
        lista.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function cerrar() {
        lista.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        activo = -1;
    }

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

    function limpiarSeleccion() {
        idOculto.value = '';
        textoStock.textContent = 'Stock disponible: seleccione un producto';
        cantidad.max = '';
        resaltar(null);
    }

    function seleccionar(opcion) {
        idOculto.value = opcion.dataset.id;
        input.value = opcion.dataset.nombre;
        textoStock.textContent = 'Stock disponible: ' + opcion.dataset.stock +
            ' | Precio: ' + opcion.dataset.moneda + ' ' + opcion.dataset.precio;
        cantidad.max = opcion.dataset.stock;
        resaltar(opcion);
        cerrar();
    }

    input.addEventListener('focus', function () {
        filtrar();
    });

    input.addEventListener('input', function () {
        limpiarSeleccion();
        filtrar();
    });

    input.addEventListener('blur', function () {
        cerrar();
    });

    lista.addEventListener('mousedown', function (evento) {
        var opcion = evento.target.closest('li[role="option"]');
        if (opcion) {
            evento.preventDefault();
            seleccionar(opcion);
        }
    });

    lista.addEventListener('click', function (evento) {
        var opcion = evento.target.closest('li[role="option"]');
        if (opcion) {
            seleccionar(opcion);
        }
    });

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
