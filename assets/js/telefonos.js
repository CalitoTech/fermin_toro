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
                    <input type="text" 
                        class="form-control añadir__input numero-telefono" 
                        name="telefonos[${telefonoCount}][numero]"
                        placeholder="Ej: 04141234567"
                        maxlength="15"
                        style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
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

        // Inicializar
        actualizarBotones();
    });