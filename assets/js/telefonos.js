// Manejo de teléfonos dinámicos
    document.addEventListener('DOMContentLoaded', function() {
        const telefonosContainer = document.getElementById('telefonos-container');
        let telefonoCount = 1;

        // Agregar nuevo teléfono
        function agregarTelefono() {
            const nuevoTelefono = document.createElement('div');
            nuevoTelefono.className = 'telefono-item mb-3';
            nuevoTelefono.innerHTML = `
                <div class="input-group">
                    <select class="form-select añadir__input tipo-telefono" name="telefonos[${telefonoCount}][tipo]"
                            style="max-width: 150px; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                        ${document.querySelector('.tipo-telefono').innerHTML}
                    </select>
                    <div class="position-relative" style="max-width: 90px;">
                        <input type="text" class="form-control buscador-input text-center fw-bold prefijo-telefono prefijo-input"
                               data-index="${telefonoCount}" maxlength="4" data-prefijo-tipo="internacional"
                               onkeypress="return /[0-9+]/.test(event.key)"
                               oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                               style="border-radius: 0; border-right: none; background: #f8f9fa; color: #c90000;">
                        <input type="hidden" class="prefijo-hidden" name="telefonos[${telefonoCount}][prefijo]">
                        <input type="hidden" class="prefijo-nombre-hidden" name="telefonos[${telefonoCount}][prefijo_nombre]">
                        <div class="prefijo-resultados autocomplete-results d-none"></div>
                    </div>
                    <input type="text"
                        class="form-control añadir__input numero-telefono"
                        name="telefonos[${telefonoCount}][numero]"
                        placeholder="Ej: 4141234567"
                        minlength="10"
                        maxlength="10"
                        pattern="^[0-9]+"
                        onkeypress="return onlyNumber(event)"
                        style="border-top-left-radius: 0; border-bottom-left-radius: 0; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                    <button type="button" class="btn btn-outline-danger btn-eliminar-telefono">
                        <i class='bx bx-trash'></i>
                    </button>
                    <button type="button" class="btn btn-outline-success btn-agregar-telefono">
                        <i class='bx bx-plus'></i>
                    </button>
                </div>
                <p class="añadir__input-error">El teléfono debe ser válido</p>
            `;
            telefonosContainer.appendChild(nuevoTelefono);

            // Inicializar el buscador de prefijo para el nuevo campo
            inicializarBuscadorPrefijo(telefonoCount);

            telefonoCount++;
            actualizarBotones();
        }

        // Eliminar teléfono
        function eliminarTelefono(elemento) {
            if (document.querySelectorAll('.telefono-item').length > 1) {
                elemento.remove();
                actualizarBotones();
                // Reindexar los nombres de los campos
                reindexarTelefonos();
            }
        }

        // Reindexar los nombres de los campos
        function reindexarTelefonos() {
            document.querySelectorAll('.telefono-item').forEach((item, index) => {
                item.querySelector('.tipo-telefono').name = `telefonos[${index}][tipo]`;
                item.querySelector('.numero-telefono').name = `telefonos[${index}][numero]`;
                item.querySelector('.prefijo-hidden').name = `telefonos[${index}][prefijo]`;
                item.querySelector('.prefijo-nombre-hidden').name = `telefonos[${index}][prefijo_nombre]`;
                item.querySelector('.prefijo-input').setAttribute('data-index', index);
            });
            telefonoCount = document.querySelectorAll('.telefono-item').length;
        }

        // Actualizar visibilidad de botones
        function actualizarBotones() {
            const items = document.querySelectorAll('.telefono-item');
            items.forEach((item, index) => {
                const btnEliminar = item.querySelector('.btn-eliminar-telefono');
                const btnAgregar = item.querySelector('.btn-agregar-telefono');
                
                btnEliminar.style.display = index === 0 && items.length === 1 ? 'none' : 'block';
                btnAgregar.style.display = index === items.length - 1 ? 'block' : 'none';
            });
        }

        // Event listeners
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-agregar-telefono') || 
                e.target.closest('.btn-agregar-telefono')) {
                agregarTelefono();
            }
            
            if (e.target.classList.contains('btn-eliminar-telefono') || 
                e.target.closest('.btn-eliminar-telefono')) {
                eliminarTelefono(e.target.closest('.telefono-item'));
            }
        });

        // Validación básica de teléfono
        document.addEventListener('blur', function(e) {
            if (e.target.classList.contains('numero-telefono')) {
                const telefono = e.target.value.trim();
                const grupo = e.target.closest('.telefono-item');
                
                if (telefono && !/^[0-9+]{7,15}$/.test(telefono)) {
                    grupo.classList.add('añadir__grupo-incorrecto');
                } else {
                    grupo.classList.remove('añadir__grupo-incorrecto');
                }
            }
        }, true);

        // Función para inicializar el buscador de prefijos
        function inicializarBuscadorPrefijo(index) {
            const item = document.querySelectorAll('.telefono-item')[index];
            if (!item) return;

            const prefijoInput = item.querySelector('.prefijo-input');
            const prefijoHidden = item.querySelector('.prefijo-hidden');
            const prefijoNombreHidden = item.querySelector('.prefijo-nombre-hidden');
            const prefijoResultados = item.querySelector('.prefijo-resultados');

            // Crear un ID único para este buscador
            const uniqueId = `prefijo_${index}_${Date.now()}`;
            prefijoInput.id = uniqueId + '_input';
            prefijoHidden.id = uniqueId;
            prefijoNombreHidden.id = uniqueId + '_nombre';
            prefijoResultados.id = uniqueId + '_resultados';

            // Inicializar BuscadorGenerico
            const buscador = new BuscadorGenerico(
                prefijoInput.id,
                prefijoResultados.id,
                'prefijo',
                prefijoHidden.id,
                prefijoNombreHidden.id
            );

            // Establecer valor por defecto
            prefijoInput.value = '+58';

            // Formatear prefijo: evitar que se borre el +
            prefijoInput.addEventListener('input', function(e) {
                let valor = this.value;
                if (!valor.startsWith('+')) {
                    this.value = '+' + valor.replace(/\+/g, '');
                }
                if (valor.indexOf('+') > 0) {
                    this.value = '+' + valor.replace(/\+/g, '');
                }
            });
            prefijoInput.addEventListener('keydown', function(e) {
                if (this.value === '+' && (e.key === 'Backspace' || e.key === 'Delete')) {
                    e.preventDefault();
                }
            });

            // Buscar el ID del prefijo por defecto
            const baseUrl = buscador.baseUrl;
            fetch(`${baseUrl}?tipo=prefijo&q=%2B58&filtro=internacional`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const prefijoEncontrado = data.find(p => p.codigo_prefijo === '+58');
                        if (prefijoEncontrado) {
                            prefijoHidden.value = prefijoEncontrado.IdPrefijo;
                        }
                    }
                })
                .catch(err => console.error('Error al cargar prefijo por defecto:', err));
        }

        // Inicializar el primer prefijo
        inicializarBuscadorPrefijo(0);

        // Inicializar
        actualizarBotones();
    });