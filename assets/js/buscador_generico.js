/**
 * Buscador Genérico
 * Maneja búsquedas de estudiantes, urbanismos y parentescos
 * con opción de crear nuevos registros si no existen
 */

class BuscadorGenerico {
    /**
     * @param {string} inputId - ID del input de búsqueda
     * @param {string} resultadosId - ID del div de resultados
     * @param {string} tipo - Tipo de búsqueda: 'estudiante', 'urbanismo', 'parentesco'
     * @param {string} hiddenIdField - ID del campo oculto donde guardar el ID seleccionado
     * @param {string} hiddenNombreField - ID del campo oculto donde guardar el nombre (para nuevos)
     * @param {Object} options - Opciones adicionales
     */
    constructor(inputId, resultadosId, tipo, hiddenIdField, hiddenNombreField = null, options = {}) {
        this.input = document.getElementById(inputId);
        this.resultados = document.getElementById(resultadosId);
        this.tipo = tipo;
        this.hiddenIdField = document.getElementById(hiddenIdField);
        this.hiddenNombreField = hiddenNombreField ? document.getElementById(hiddenNombreField) : null;
        this.options = {
            minLength: tipo === 'prefijo' ? 1 : 2, // Prefijos con 1 carácter mínimo
            delay: 300,
            placeholder: this.getPlaceholder(tipo),
            allowCreate: tipo !== 'estudiante' && tipo !== 'estudiante_regular', // Solo permitir crear en urbanismo, parentesco y prefijo
            showOnFocus: tipo !== 'estudiante' && tipo !== 'estudiante_regular', // Mostrar lista al hacer click (todos excepto estudiante)
            ...options
        };

        this.timeout = null;
        this.baseUrl = this.getBaseUrl();
        this.init();
    }

    getBaseUrl() {
        // Detectar la ruta base según la ubicación del archivo actual
        const path = window.location.pathname;

        // Si estamos en vistas/homepage (solicitud_cupo.php)
        if (path.includes('/vistas/homepage/')) {
            return '../../controladores/BuscarGeneral.php';
        }
        // Si estamos en vistas/inscripciones/inscripcion (nuevo_inscripcion.php)
        else if (path.includes('/vistas/inscripciones/')) {
            return '../../../controladores/BuscarGeneral.php';
        }
        // Si estamos en vistas/estudiantes/egreso (nuevo_egreso.php)
        else if (path.includes('/vistas/estudiantes/')) {
            return '../../../controladores/BuscarGeneral.php';
        }
        // Fallback por defecto
        else {
            return '../../../controladores/BuscarGeneral.php';
        }
    }

    getPlaceholder(tipo) {
        const placeholders = {
            'estudiante': 'Buscar por nombre, apellido o cédula...',
            'estudiante_regular': 'Buscar estudiante inscrito el año anterior...',
            'urbanismo': 'Buscar o escribir nuevo urbanismo...',
            'parentesco': 'Buscar o escribir nuevo parentesco...',
            'prefijo': 'Buscar por código (+58) o país...'
        };
        return placeholders[tipo] || 'Buscar...';
    }

    init() {
        if (!this.input || !this.resultados) {
            console.error('Buscador: Elementos no encontrados');
            return;
        }

        // Configurar placeholder
        this.input.placeholder = this.options.placeholder;

        // Eventos
        this.input.addEventListener('input', () => this.handleInput());
        this.input.addEventListener('focus', () => this.handleFocus());

        // Cerrar resultados al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!this.resultados.contains(e.target) && e.target !== this.input) {
                this.ocultarResultados();
            }
        });
    }

    handleFocus() {
        // Si debe mostrar resultados al hacer focus (urbanismo y parentesco)
        if (this.options.showOnFocus && this.input.value.trim().length === 0) {
            // Cargar los primeros 20 registros
            this.cargarPrecargados();
        } else {
            // Comportamiento normal
            this.handleInput();
        }
    }

    async cargarPrecargados() {
        try {
            let url = `${this.baseUrl}?tipo=${this.tipo}&q=&limit=20`;

            // Si es búsqueda de prefijo, agregar filtro según tipo
            if (this.tipo === 'prefijo') {
                const prefijoTipo = this.input.getAttribute('data-prefijo-tipo') || 'internacional';
                url += `&filtro=${prefijoTipo}`;
            }

            const response = await fetch(url);
            const data = await response.json();

            if (data.error) {
                console.error('Error en búsqueda:', data.error);
                return;
            }

            if (data.length > 0) {
                this.mostrarResultados(data, '');
            }
        } catch (error) {
            console.error('Error al cargar precargados:', error);
        }
    }

    handleInput() {
        clearTimeout(this.timeout);
        const texto = this.input.value.trim();

        if (texto.length < this.options.minLength) {
            this.ocultarResultados();
            this.limpiarSeleccion();
            return;
        }

        this.timeout = setTimeout(() => {
            this.buscar(texto);
        }, this.options.delay);
    }

    async buscar(texto) {
        try {
            let url = `${this.baseUrl}?tipo=${this.tipo}&q=${encodeURIComponent(texto)}`;

            // Si es búsqueda de prefijo, agregar filtro según tipo
            if (this.tipo === 'prefijo') {
                const prefijoTipo = this.input.getAttribute('data-prefijo-tipo') || 'internacional';
                url += `&filtro=${prefijoTipo}`;
            }

            const response = await fetch(url);
            const data = await response.json();

            if (data.error) {
                console.error('Error en búsqueda:', data.error);
                return;
            }

            this.mostrarResultados(data, texto);
        } catch (error) {
            console.error('Error al buscar:', error);
        }
    }

    mostrarResultados(data, textoBuscado) {
        this.resultados.innerHTML = '';

        if (data.length === 0) {
            this.resultados.innerHTML = '<div class="autocomplete-item autocomplete-no-results">No se encontraron resultados</div>';
        } else {
            // Agregar encabezado si es lista precargada
            if (this.options.showOnFocus && textoBuscado === '') {
                const header = document.createElement('div');
                header.classList.add('autocomplete-header');
                header.innerHTML = `<i class="fas fa-list mr-2"></i>Seleccione o escriba para buscar`;
                this.resultados.appendChild(header);
            }

            data.forEach(item => {
                const div = document.createElement('div');
                div.classList.add('autocomplete-item');

                // Si es un item nuevo (creado dinámicamente)
                if (item.nuevo) {
                    div.classList.add('autocomplete-item-nuevo');
                    div.innerHTML = this.renderNuevoItem(item, textoBuscado);
                } else {
                    div.innerHTML = this.renderItem(item);
                }

                div.addEventListener('click', () => this.seleccionar(item));
                this.resultados.appendChild(div);
            });
        }

        this.resultados.classList.remove('d-none');
    }

    renderItem(item) {
        switch (this.tipo) {
            case 'estudiante':
            case 'estudiante_regular':
                return `<strong>${item.apellido} ${item.nombre}</strong> - ${item.nacionalidad}-${item.cedula}`;

            case 'urbanismo':
                return `<i class="fas fa-map-marker-alt mr-2"></i>${item.urbanismo}`;

            case 'parentesco':
                return `<i class="fas fa-users mr-2"></i>${item.parentesco}`;

            case 'prefijo':
                return `<strong>${item.codigo_prefijo}</strong>`;

            case 'plantel':
                return `<i class="fas fa-school mr-2"></i>${item.plantel}`;

            default:
                return JSON.stringify(item);
        }
    }

    renderNuevoItem(item, textoBuscado) {
        switch (this.tipo) {
            case 'urbanismo':
                return `<i class="fas fa-plus-circle mr-2 text-success"></i><strong>Crear nuevo:</strong> "${item.urbanismo}"`;

            case 'parentesco':
                return `<i class="fas fa-plus-circle mr-2 text-success"></i><strong>Crear nuevo:</strong> "${item.parentesco}"`;

            case 'prefijo':
                return `<i class="fas fa-plus-circle mr-2 text-success"></i><strong>Crear nuevo:</strong> ${item.codigo_prefijo}`;

            case 'plantel':
                return `<i class="fas fa-plus-circle mr-2 text-success"></i><strong>Crear nuevo:</strong> "${item.plantel}"`;

            default:
                return `<strong>Nuevo:</strong> ${textoBuscado}`;
        }
    }

    seleccionar(item) {
        // Actualizar el input con el valor seleccionado
        if (this.tipo === 'estudiante') {
            this.input.value = `${item.apellido} ${item.nombre} (${item.nacionalidad}-${item.cedula})`;
            this.hiddenIdField.value = item.IdPersona;
        } else if (this.tipo === 'estudiante_regular') {
            this.input.value = `${item.apellido} ${item.nombre} (${item.nacionalidad}-${item.cedula})`;
            // Usar IdEstudiante que es igual a IdPersona para estudiantes
            this.hiddenIdField.value = item.IdEstudiante || item.IdPersona;
        } else if (this.tipo === 'urbanismo') {
            this.input.value = item.urbanismo;
            this.hiddenIdField.value = item.IdUrbanismo;

            // Si es nuevo, guardar también el nombre
            if (item.nuevo && this.hiddenNombreField) {
                this.hiddenNombreField.value = item.urbanismo;
            }
        } else if (this.tipo === 'parentesco') {
            this.input.value = item.parentesco;
            this.hiddenIdField.value = item.IdParentesco;

            // Si es nuevo, guardar también el nombre
            if (item.nuevo && this.hiddenNombreField) {
                this.hiddenNombreField.value = item.parentesco;
            }
        } else if (this.tipo === 'prefijo') {
            this.input.value = item.codigo_prefijo;
            this.hiddenIdField.value = item.IdPrefijo;

            // Si es nuevo, guardar código, país y max_digitos
            if (item.nuevo && this.hiddenNombreField) {
                // Guardar en formato JSON para poder extraer todos los datos
                this.hiddenNombreField.value = JSON.stringify({
                    codigo: item.codigo_prefijo,
                    pais: item.pais,
                    max_digitos: item.max_digitos
                });
            }

            // Actualizar atributo data-max-digitos del input para validación
            this.input.setAttribute('data-max-digitos', item.max_digitos);
        } else if (this.tipo === 'plantel') {
            this.input.value = item.plantel;
            this.hiddenIdField.value = item.IdPlantel;

            // Si es nuevo, guardar también el nombre
            if (item.nuevo && this.hiddenNombreField) {
                this.hiddenNombreField.value = item.plantel;
            }
        }

        this.ocultarResultados();

        // Disparar evento personalizado
        this.input.dispatchEvent(new CustomEvent('itemSeleccionado', {
            detail: item
        }));
    }

    limpiarSeleccion() {
        if (this.hiddenIdField) {
            this.hiddenIdField.value = '';
        }
        if (this.hiddenNombreField) {
            this.hiddenNombreField.value = '';
        }
    }

    ocultarResultados() {
        this.resultados.classList.add('d-none');
        this.resultados.innerHTML = '';
    }

    reset() {
        this.input.value = '';
        this.limpiarSeleccion();
        this.ocultarResultados();
    }
}

// Exportar para uso global
window.BuscadorGenerico = BuscadorGenerico;
