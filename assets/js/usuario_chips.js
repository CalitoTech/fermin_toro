// === Sistema de Selección de Roles con Chips ===
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('roles-search');
        const select = document.getElementById('roles');
        const chipsContainer = searchInput.parentElement;
        const options = Array.from(select.options);
        let selectedRoles = []; // Ahora esta variable se actualiza dinámicamente
        let dropdown = null;

        // Función para actualizar la lista de roles seleccionados
        function updateSelectedRoles() {
            selectedRoles = Array.from(select.selectedOptions).map(opt => Number(opt.value));
        }

        // Función para crear una etiqueta (chip)
        function createChip(rol) {
            const chip = document.createElement('div');
            chip.className = 'chip';
            chip.innerHTML = `
                ${rol.text}
                <i class="bx bx-x" onclick="removeChip(${rol.value})"></i>
            `;
            chip.onclick = () => removeChip(rol.value);
            return chip;
        }

        // Añadir rol
        function addRole(id) {
            const option = select.querySelector(`option[value="${id}"]`);
            if (option) {
                option.selected = true;
                updateSelectedRoles(); // Actualizar la lista de seleccionados
                
                const chip = createChip(option);
                chipsContainer.insertBefore(chip, searchInput);
                
                // Limpiar y cerrar el dropdown
                searchInput.value = '';
                closeDropdown();
                
                // Validar
                validateRoles();
            }
        }

        // Eliminar rol
        function removeChip(id) {
            const option = select.querySelector(`option[value="${id}"]`);
            if (option) {
                option.selected = false;
                updateSelectedRoles(); // Actualizar la lista de seleccionados
                
                const chip = chipsContainer.querySelector(`.chip i[onclick="removeChip(${id})"]`)?.parentElement;
                if (chip) chip.remove();
                
                validateRoles();
            }
        }

        // Cerrar dropdown
        function closeDropdown() {
            if (dropdown && dropdown.parentElement) {
                document.body.removeChild(dropdown);
                dropdown = null;
            }
        }

        // Validar
        function validateRoles() {
            const grupo = document.getElementById('grupo__roles');
            if (selectedRoles.length > 0) {
                grupo.classList.remove('añadir__grupo-incorrecto');
                grupo.classList.add('añadir__grupo-correcto');
                const icon = grupo.querySelector('.añadir__validacion-estado');
                icon.className = 'añadir__validacion-estado fas fa-check-circle';
                icon.style.color = '#1ed12d';
                grupo.querySelector('.añadir__input-error').classList.remove('añadir__input-error-activo');
            } else {
                grupo.classList.remove('añadir__grupo-correcto');
                grupo.classList.add('añadir__grupo-incorrecto');
                const icon = grupo.querySelector('.añadir__validacion-estado');
                icon.className = 'añadir__validacion-estado fas fa-times-circle';
                icon.style.color = '#bb2929';
                grupo.querySelector('.añadir__input-error').classList.add('añadir__input-error-activo');
            }
        }

        // Mostrar dropdown con opciones disponibles
        function showDropdown() {
            // Cerrar dropdown si ya está abierto
            if (dropdown) {
                closeDropdown();
                return;
            }

            dropdown = document.createElement('div');
            dropdown.id = 'roles-dropdown';
            dropdown.style.position = 'absolute';
            dropdown.style.top = '100%';
            dropdown.style.left = '0';
            dropdown.style.width = '100%';
            dropdown.style.backgroundColor = 'white';
            dropdown.style.border = '1px solid #ddd';
            dropdown.style.borderRadius = '6px';
            dropdown.style.zIndex = '1000';
            dropdown.style.maxHeight = '200px';
            dropdown.style.overflowY = 'auto';

            // Filtrar opciones no seleccionadas
            const availableOptions = options.filter(option => !option.selected);

            if (availableOptions.length > 0) {
                availableOptions.forEach(option => {
                    const item = document.createElement('div');
                    item.textContent = option.text;
                    item.style.padding = '8px 12px';
                    item.style.cursor = 'pointer';
                    item.style.fontSize = '0.9rem';
                    item.onmouseover = () => item.style.backgroundColor = '#f8f9fa';
                    item.onmouseout = () => item.style.backgroundColor = 'white';
                    item.onclick = (e) => {
                        e.stopPropagation();
                        addRole(Number(option.value));
                    };
                    dropdown.appendChild(item);
                });
            } else {
                const item = document.createElement('div');
                item.textContent = 'No hay más opciones disponibles';
                item.style.padding = '8px 12px';
                item.style.color = '#666';
                item.style.fontSize = '0.9rem';
                dropdown.appendChild(item);
            }

            document.body.appendChild(dropdown);

            // Posicionar correctamente
            const rect = chipsContainer.getBoundingClientRect();
            dropdown.style.top = (rect.bottom + window.scrollY) + 'px';
            dropdown.style.left = (rect.left + window.scrollX) + 'px';
            dropdown.style.width = (rect.width) + 'px';

            // Cerrar al hacer clic fuera
            const closeOnClickOutside = (e) => {
                if (!chipsContainer.contains(e.target)) {
                    closeDropdown();
                    document.removeEventListener('click', closeOnClickOutside);
                }
            };
            
            document.addEventListener('click', closeOnClickOutside);
        }

        searchInput.addEventListener('keydown', (e) => {
            // Si el campo está vacío y se presiona Backspace o Delete
            if ((e.key === 'Backspace' || e.key === 'Delete') && searchInput.value === '') {
                e.preventDefault(); // Evitar comportamiento por defecto
                
                // Obtener el último chip
                const chips = chipsContainer.querySelectorAll('.chip');
                if (chips.length > 0) {
                    const lastChip = chips[chips.length - 1];
                    const chipId = lastChip.querySelector('i').getAttribute('onclick').match(/\d+/)[0];
                    removeChip(chipId);
                }
            }
        });

        // Mostrar/ocultar dropdown al hacer clic en el input
        searchInput.addEventListener('click', (e) => {
            e.stopPropagation();
            showDropdown();
        });

        // Manejar búsqueda (opcional)
        searchInput.addEventListener('input', () => {
            if (!dropdown) return;
            
            const searchTerm = searchInput.value.toLowerCase();
            const items = dropdown.querySelectorAll('div');
            
            items.forEach(item => {
                if (item.textContent.toLowerCase().includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Enviar formulario
        document.getElementById('añadir').addEventListener('submit', function () {
            validateRoles();
        });
    });

    // Función global para eliminar chips
    function removeChip(id) {
        // Re-dispatch al manejador dentro del contexto del DOMContentLoaded
        const event = new CustomEvent('remove-chip', { detail: id });
        document.dispatchEvent(event);
    }

    // Escuchar evento global para eliminar chips
    document.addEventListener('remove-chip', (e) => {
        const id = e.detail;
        const option = document.querySelector(`#roles option[value="${id}"]`);
        if (option) {
            option.selected = false;
            const chip = document.querySelector(`.chip i[onclick="removeChip(${id})"]`)?.parentElement;
            if (chip) chip.remove();
            
            // Actualizar selectedRoles y validar
            const index = Array.from(document.querySelectorAll('#roles option[selected]')).indexOf(option);
            if (index > -1) {
                const event = new Event('DOMContentLoaded');
                document.dispatchEvent(event);
            }
        }
    });