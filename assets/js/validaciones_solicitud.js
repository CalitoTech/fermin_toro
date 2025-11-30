/**
 * Sistema de Validaciones Robustas para Solicitud de Cupo
 * Validaciones en tiempo real con feedback visual
 */

class ValidadorFormulario {
    constructor(formId, basePath = '../../') {
        this.form = document.getElementById(formId);
        this.camposEditados = new Set();
        this.erroresActivos = new Map();
        this.formularioEnviado = false; // Bandera para indicar si el formulario se envió exitosamente
        this.basePath = basePath; // Ruta base para los controladores
        this.inicializar();
    }

    inicializar() {
        if (!this.form) return;

        // Rastrear campos editados
        this.form.addEventListener('input', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA') {
                this.camposEditados.add(e.target.id || e.target.name);
            }
        });

        this.configurarValidacionesCedulas();
        this.configurarValidacionesTelefonos();
        this.configurarValidacionesTexto();
        this.configurarValidacionesCorreo();
        this.configurarAlertaCierreModal();
    }

    /**
     * Verifica si hay campos editados
     */
    hayDatosEditados() {
        return this.camposEditados.size > 0;
    }

    /**
     * Configurar validaciones para campos de cédula
     */
    configurarValidacionesCedulas() {
        // Campos de cédula a validar
        const camposCedula = [
            { campo: 'estudianteCedula', nacionalidad: 'estudianteNacionalidad', label: 'Cédula del estudiante', min: 7, max: 8 },
            { campo: 'padreCedula', nacionalidad: 'padreNacionalidad', label: 'Cédula del padre', min: 7, max: 8 },
            { campo: 'madreCedula', nacionalidad: 'madreNacionalidad', label: 'Cédula de la madre', min: 7, max: 8 },
            { campo: 'representanteCedula', nacionalidad: 'representanteNacionalidad', label: 'Cédula del representante', min: 7, max: 8 },
            { campo: 'emergenciaCedula', nacionalidad: 'emergenciaNacionalidad', label: 'Cédula del contacto de emergencia', min: 7, max: 8 }
        ];

        camposCedula.forEach(config => {
            const input = document.getElementById(config.campo);
            if (!input) return;

            // Validar en tiempo real mientras escribe (solo números)
            input.addEventListener('input', (e) => {
                let valor = e.target.value;
                // Eliminar caracteres no numéricos
                valor = valor.replace(/[^0-9]/g, '');
                e.target.value = valor;
            });

            // Validar min-length al perder foco
            // IMPORTANTE: Esto solo valida el formato (longitud, números)
            // Las validaciones de duplicados y existencia las maneja solicitud_cupo.js
            input.addEventListener('blur', (e) => {
                const valor = e.target.value.trim();

                // Si está vacío
                if (!valor) {
                    // Si es requerido, mostrar error
                    if (input.hasAttribute('required')) {
                        this.mostrarError(input, `${config.label} es requerida`);
                        input.classList.remove('is-valid');
                        input.classList.add('is-invalid');
                    } else {
                        // Si NO es requerido (ej: nivel inicial), limpiar clases
                        input.classList.remove('is-invalid', 'is-valid');
                        this.limpiarError(input);
                    }
                    return;
                }

                // Si tiene contenido, validar min-length
                const minLength = parseInt(input.getAttribute('minlength')) || config.min;
                const maxLength = parseInt(input.getAttribute('maxlength')) || config.max;
                const esCedulaEscolar = maxLength === 11;
                const tipoCedula = esCedulaEscolar ? 'Cédula escolar' : 'Cédula';

                // Validar solo números
                if (!/^[0-9]+$/.test(valor)) {
                    this.mostrarError(input, 'La cédula debe contener solo números');
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    return;
                }

                // Validar longitud mínima
                if (valor.length < minLength) {
                    this.mostrarError(input, `${tipoCedula} debe tener al menos ${minLength} dígitos`);
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    return;
                }

                // Validar longitud máxima
                if (valor.length > maxLength) {
                    this.mostrarError(input, `${tipoCedula} no puede tener más de ${maxLength} dígitos`);
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    return;
                }

                // Si pasó todas las validaciones de formato, limpiar error
                // PERO NO marcar como válido - eso lo hace solicitud_cupo.js después
                // de verificar duplicados y existencia
                this.limpiarError(input);
            });
        });
    }

    /**
     * Validar cédula (longitud, formato, duplicados)
     */
    async validarCedula(config) {
        const input = document.getElementById(config.campo);
        const nacionalidadInput = document.getElementById(config.nacionalidad);

        if (!input || !nacionalidadInput) return true;

        const cedula = input.value.trim();
        const nacionalidad = nacionalidadInput.value;

        // NO limpiar estado anterior para cédulas - dejar que las validaciones
        // personalizadas manejen las clases is-valid/is-invalid
        // this.limpiarError(input);

        // Si está vacío y es requerido
        if (!cedula && input.hasAttribute('required')) {
            this.mostrarError(input, `${config.label} es requerida`);
            return false;
        }

        if (!cedula) return true; // Si está vacío y no es requerido, ok

        // Validar solo números
        if (!/^[0-9]+$/.test(cedula)) {
            this.mostrarError(input, 'La cédula debe contener solo números');
            return false;
        }

        // Obtener el maxlength y minlength actual del campo para determinar si es cédula escolar o normal
        const maxLength = parseInt(input.getAttribute('maxlength')) || config.max;
        const minLength = parseInt(input.getAttribute('minlength')) || config.min;
        const esCedulaEscolar = maxLength === 11;
        const tipoCedula = esCedulaEscolar ? 'Cédula escolar' : 'Cédula';

        // Validar longitud
        if (cedula.length < minLength) {
            this.mostrarError(input, `${tipoCedula} debe tener al menos ${minLength} dígitos`);
            return false;
        }

        if (cedula.length > maxLength) {
            this.mostrarError(input, `${tipoCedula} no puede tener más de ${maxLength} dígitos`);
            return false;
        }

        // Validar nacionalidad seleccionada
        if (!nacionalidad) {
            this.mostrarError(input, 'Debe seleccionar la nacionalidad primero');
            return false;
        }

        // NO marcar como válido aquí - dejar que las validaciones personalizadas
        // de solicitud_cupo.js lo hagan (verificación de duplicados y existencia)
        // Solo nos aseguramos de que no esté marcado como inválido
        input.classList.remove('is-invalid');

        return true;
    }

    /**
     * Configurar validaciones para campos de teléfono
     */
    configurarValidacionesTelefonos() {
        const camposTelefono = [
            { campo: 'estudianteTelefono', prefijo: 'estudianteTelefonoPrefijo', label: 'Teléfono del estudiante', min: 10, max: 10 },
            { campo: 'padreTelefonoHabitacion', prefijo: 'padreTelefonoHabitacionPrefijo', label: 'Teléfono habitación del padre', min: 7, max: 10 },
            { campo: 'padreCelular', prefijo: 'padreCelularPrefijo', label: 'Celular del padre', min: 10, max: 10 },
            { campo: 'madreTelefonoHabitacion', prefijo: 'madreTelefonoHabitacionPrefijo', label: 'Teléfono habitación de la madre', min: 7, max: 10 },
            { campo: 'madreCelular', prefijo: 'madreCelularPrefijo', label: 'Celular de la madre', min: 10, max: 10 },
            { campo: 'emergenciaCelular', prefijo: 'emergenciaCelularPrefijo', label: 'Celular de emergencia', min: 10, max: 10 },
            { campo: 'representanteTelefonoHabitacion', prefijo: 'representanteTelefonoHabitacionPrefijo', label: 'Teléfono habitación del representante', min: 7, max: 10 },
            { campo: 'representanteCelular', prefijo: 'representanteCelularPrefijo', label: 'Celular del representante', min: 10, max: 10 }
        ];

        camposTelefono.forEach(config => {
            const input = document.getElementById(config.campo);
            if (!input) return;

            // Validar en tiempo real mientras escribe
            input.addEventListener('input', (e) => {
                let valor = e.target.value;
                // Eliminar caracteres no numéricos
                valor = valor.replace(/[^0-9]/g, '');
                // Limitar longitud máxima
                if (valor.length > config.max) {
                    valor = valor.substring(0, config.max);
                }
                e.target.value = valor;
            });

            // Validar al perder foco
            input.addEventListener('blur', async (e) => {
                await this.validarTelefono(config);
            });
        });
    }

    /**
     * Validar teléfono (longitud, formato, prefijo, duplicados)
     */
    async validarTelefono(config) {
        const input = document.getElementById(config.campo);
        const prefijoHidden = document.getElementById(config.prefijo);
        const prefijoInput = document.getElementById(config.prefijo + '_input');

        if (!input) return true;

        const numero = input.value.trim();

        // Limpiar estado anterior
        this.limpiarError(input);

        // Si está vacío y es requerido
        if (!numero && input.hasAttribute('required')) {
            this.mostrarError(input, `${config.label} es requerido`);
            return false;
        }

        if (!numero) return true; // Si está vacío y no es requerido, ok

        // Validar solo números
        if (!/^[0-9]+$/.test(numero)) {
            this.mostrarError(input, 'El teléfono debe contener solo números');
            input.value = '';
            return false;
        }

        // Validar que no empiece con 0
        if (numero.startsWith('0')) {
            this.mostrarError(input, 'El número no puede comenzar con 0');
            input.value = '';
            return false;
        }

        // Validar longitud mínima
        if (numero.length < config.min) {
            this.mostrarError(input, `El teléfono debe tener al menos ${config.min} dígitos`);
            return false;
        }

        // Validar longitud máxima
        if (numero.length > config.max) {
            this.mostrarError(input, `El teléfono no puede tener más de ${config.max} dígitos`);
            return false;
        }

        // Validar que tenga prefijo seleccionado (verificar el input visible)
        const prefijoVisible = prefijoInput ? prefijoInput.value.trim() : '';
        if (!prefijoVisible || prefijoVisible === '') {
            this.mostrarError(input, 'Debe seleccionar un prefijo para el teléfono');
            return false;
        }

        // Obtener el ID del prefijo desde el campo hidden
        const idPrefijo = prefijoHidden ? prefijoHidden.value : '';
        if (!idPrefijo) {
            console.warn('No se encontró el ID del prefijo en el campo hidden');
            // Permitir continuar si no hay ID (el backend lo manejará)
        }

        // Verificar teléfono duplicado en la base de datos
        const telefonoCompleto = prefijoVisible + numero;

        // Detectar la ruta correcta según la ubicación del archivo
        let baseUrl;
        if (window.location.pathname.includes('/inscripciones/inscripcion/')) {
            baseUrl = '../../../controladores/';
        } else if (window.location.pathname.includes('/representantes/representados/')) {
            baseUrl = '../../../controladores/';
        } else if (window.location.pathname.includes('/homepage/')) {
            baseUrl = '../../controladores/';
        } else {
            baseUrl = '../../controladores/';
        }

        try {
            // IMPORTANTE: Enviar el ID del prefijo (no el código visible)
            const response = await fetch(`${baseUrl}TelefonoController.php?action=verificarTelefono&telefono=${encodeURIComponent(numero)}&prefijo=${encodeURIComponent(idPrefijo)}`);
            const data = await response.json();

            if (data.existe) {
                this.mostrarError(input, `Este teléfono ya está registrado para: ${data.persona.nombreCompleto} (${data.persona.nacionalidad}-${data.persona.cedula})`);
                input.value = '';
                input.focus();
                return false;
            }
        } catch (error) {
            console.error('Error al verificar teléfono:', error);
            // Permitir continuar si hay error de red
        }

        // Marcar como válido
        this.marcarValido(input);
        if (prefijoInput) {
            this.limpiarError(prefijoInput);
        }

        return true;
    }

    /**
     * Configurar validaciones para campos de texto (nombres, apellidos, direcciones)
     */
    configurarValidacionesTexto() {
        const camposTexto = [
            { campo: 'estudianteNombres', label: 'Nombres del estudiante', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/ },
            { campo: 'estudianteApellidos', label: 'Apellidos del estudiante', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/ },
            { campo: 'estudianteLugarNacimiento', label: 'Lugar de nacimiento', min: 3, max: 40 },
            { campo: 'padreNombres', label: 'Nombres del padre', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/ },
            { campo: 'padreApellidos', label: 'Apellidos del padre', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/ },
            { campo: 'padreOcupacion', label: 'Ocupación del padre', min: 3, max: 40 },
            { campo: 'padreDireccion', label: 'Dirección del padre', min: 3, max: 40 },
            { campo: 'padreLugarTrabajo', label: 'Lugar de trabajo del padre', min: 3, max: 40 },
            { campo: 'madreNombres', label: 'Nombres de la madre', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/ },
            { campo: 'madreApellidos', label: 'Apellidos de la madre', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/ },
            { campo: 'madreOcupacion', label: 'Ocupación de la madre', min: 3, max: 40 },
            { campo: 'madreDireccion', label: 'Dirección de la madre', min: 3, max: 40 },
            { campo: 'madreLugarTrabajo', label: 'Lugar de trabajo de la madre', min: 3, max: 40 },
            { campo: 'emergenciaNombre', label: 'Nombre contacto emergencia', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/ },
            { campo: 'representanteNombres', label: 'Nombres del representante', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/ },
            { campo: 'representanteApellidos', label: 'Apellidos del representante', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/ },
            { campo: 'representanteOcupacion', label: 'Ocupación del representante', min: 3, max: 40 },
            { campo: 'representanteDireccion', label: 'Dirección del representante', min: 3, max: 40 },
            { campo: 'representanteLugarTrabajo', label: 'Lugar de trabajo del representante', min: 3, max: 40 }
        ];

        camposTexto.forEach(config => {
            const input = document.getElementById(config.campo);
            if (!input) return;

            // Validar al perder foco
            input.addEventListener('blur', (e) => {
                this.validarCampoTexto(config);
            });
        });
    }

    /**
     * Validar campo de texto
     */
    validarCampoTexto(config) {
        const input = document.getElementById(config.campo);
        if (!input) return true;

        const valor = input.value.trim();

        // Limpiar estado anterior
        this.limpiarError(input);

        // Si está vacío y es requerido
        if (!valor && input.hasAttribute('required')) {
            this.mostrarError(input, `${config.label} es requerido`);
            return false;
        }

        if (!valor) return true; // Si está vacío y no es requerido, ok

        // Validar longitud mínima
        if (valor.length < config.min) {
            this.mostrarError(input, `${config.label} debe tener al menos ${config.min} caracteres`);
            return false;
        }

        // Validar longitud máxima
        if (valor.length > config.max) {
            this.mostrarError(input, `${config.label} no puede tener más de ${config.max} caracteres`);
            return false;
        }

        // Validar patrón si existe
        if (config.patron && !config.patron.test(valor)) {
            this.mostrarError(input, `${config.label} contiene caracteres no válidos`);
            return false;
        }

        // Marcar como válido
        this.marcarValido(input);
        return true;
    }

    /**
     * Configurar validaciones para correos electrónicos
     */
    configurarValidacionesCorreo() {
        const camposCorreo = [
            { campo: 'estudianteCorreo', label: 'Correo del estudiante' },
            { campo: 'padreCorreo', label: 'Correo del padre' },
            { campo: 'madreCorreo', label: 'Correo de la madre' },
            { campo: 'representanteCorreo', label: 'Correo del representante' }
        ];

        camposCorreo.forEach(config => {
            const input = document.getElementById(config.campo);
            if (!input) return;

            // Validar al perder foco
            input.addEventListener('blur', async (e) => {
                await this.validarCorreo(config);
            });
        });
    }

    /**
     * Validar correo electrónico
     */
    async validarCorreo(config) {
        const input = document.getElementById(config.campo);
        if (!input) return true;

        const correo = input.value.trim().toLowerCase();

        // Limpiar estado anterior
        this.limpiarError(input);

        // Si está vacío y es requerido
        if (!correo && input.hasAttribute('required')) {
            this.mostrarError(input, `${config.label} es requerido`);
            return false;
        }

        if (!correo) return true; // Si está vacío y no es requerido, ok

        // Validar formato de correo
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(correo)) {
            this.mostrarError(input, 'El formato del correo no es válido');
            return false;
        }

        // Validar longitud
        if (correo.length < 10) {
            this.mostrarError(input, 'El correo debe tener al menos 10 caracteres');
            return false;
        }

        if (correo.length > 50) {
            this.mostrarError(input, 'El correo no puede tener más de 50 caracteres');
            return false;
        }

        // Actualizar valor en lowercase
        input.value = correo;

        // Marcar como válido (la validación de duplicados ya existe en validarCorreoDuplicado)
        this.marcarValido(input);

        return true;
    }

    /**
     * Configurar alerta al intentar cerrar el modal con datos
     */
    configurarAlertaCierreModal() {
        const modal = $('#formularioModal');
        const validador = this;

        // Interceptar el cierre del modal
        modal.on('hide.bs.modal', function(e) {
            // Si el formulario se envió exitosamente, permitir cerrar sin alerta
            if (validador.formularioEnviado) {
                return;
            }

            // Si hay datos editados, preguntar antes de cerrar
            if (validador.hayDatosEditados()) {
                e.preventDefault();

                Swal.fire({
                    title: '¿Abandonar formulario?',
                    html: `
                        <div class="text-left">
                            <p>Has ingresado información en el formulario.</p>
                            <p><strong>¿Estás seguro de que deseas cerrar?</strong></p>
                            <p class="text-muted small mt-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Se perderán todos los datos ingresados.
                            </p>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#c90000',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, cerrar',
                    cancelButtonText: 'No, continuar editando',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Limpiar registro de campos editados
                        validador.camposEditados.clear();
                        validador.erroresActivos.clear();

                        // Cerrar el modal
                        modal.modal('hide');

                        // Resetear formulario
                        document.getElementById('formInscripcion').reset();
                    }
                });
            }
        });

        // Limpiar al cerrar completamente
        modal.on('hidden.bs.modal', function() {
            validador.camposEditados.clear();
            validador.erroresActivos.clear();
            validador.formularioEnviado = false; // Resetear bandera
            validador.limpiarTodosLosErrores();
        });
    }

    /**
     * Marcar formulario como enviado exitosamente
     * Esto evita que aparezca la alerta de "¿abandonar formulario?"
     */
    marcarComoEnviado() {
        this.formularioEnviado = true;
    }

    /**
     * Mostrar error en un campo
     */
    mostrarError(input, mensaje) {
        // Marcar campo como inválido
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');

        // Registrar error
        this.erroresActivos.set(input.id, mensaje);

        // Buscar o crear elemento de feedback
        let feedbackDiv = input.parentElement.querySelector('.invalid-feedback');

        if (!feedbackDiv) {
            feedbackDiv = document.createElement('div');
            feedbackDiv.className = 'invalid-feedback';
            feedbackDiv.style.display = 'block';
            input.parentElement.appendChild(feedbackDiv);
        }

        feedbackDiv.textContent = mensaje;
        feedbackDiv.style.display = 'block';
    }

    /**
     * Marcar campo como válido
     */
    marcarValido(input) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');

        // Eliminar error registrado
        this.erroresActivos.delete(input.id);

        // Ocultar mensaje de error
        const feedbackDiv = input.parentElement.querySelector('.invalid-feedback');
        if (feedbackDiv) {
            feedbackDiv.style.display = 'none';
        }
    }

    /**
     * Limpiar error de un campo
     */
    limpiarError(input) {
        // Solo remover is-invalid, preservar is-valid
        input.classList.remove('is-invalid');

        // Eliminar error registrado
        this.erroresActivos.delete(input.id);

        const feedbackDiv = input.parentElement.querySelector('.invalid-feedback');
        if (feedbackDiv) {
            feedbackDiv.style.display = 'none';
        }
    }

    /**
     * Limpiar todos los errores del formulario
     */
    limpiarTodosLosErrores() {
        this.erroresActivos.clear();

        const inputs = this.form.querySelectorAll('.is-invalid, .is-valid');
        inputs.forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
        });

        const feedbacks = this.form.querySelectorAll('.invalid-feedback');
        feedbacks.forEach(fb => {
            fb.style.display = 'none';
        });
    }

    /**
     * Verificar si hay errores activos
     */
    hayErrores() {
        return this.erroresActivos.size > 0;
    }

    /**
     * Obtener lista de errores activos
     */
    obtenerErrores() {
        return Array.from(this.erroresActivos.values());
    }

    /**
     * Validar TODO el formulario antes de enviar
     * Retorna true si TODO está válido, false si hay errores
     */
    async validarTodoElFormulario() {
        console.log('🔍 Validando TODO el formulario...');

        // Limpiar errores previos
        this.limpiarTodosLosErrores();

        let hayErrores = false;
        const erroresEncontrados = [];

        // 1. Validar campos de texto
        const camposTexto = [
            { campo: 'estudianteNombres', label: 'Nombres del estudiante', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/, requerido: true },
            { campo: 'estudianteApellidos', label: 'Apellidos del estudiante', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/, requerido: true },
            { campo: 'estudianteLugarNacimiento', label: 'Lugar de nacimiento', min: 3, max: 40, requerido: true },
            { campo: 'padreNombres', label: 'Nombres del padre', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/, requerido: true },
            { campo: 'padreApellidos', label: 'Apellidos del padre', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/, requerido: true },
            { campo: 'padreOcupacion', label: 'Ocupación del padre', min: 3, max: 40, requerido: true },
            { campo: 'padreDireccion', label: 'Dirección del padre', min: 3, max: 40, requerido: true },
            { campo: 'padreLugarTrabajo', label: 'Lugar de trabajo del padre', min: 3, max: 40, requerido: true },
            { campo: 'madreNombres', label: 'Nombres de la madre', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/, requerido: true },
            { campo: 'madreApellidos', label: 'Apellidos de la madre', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/, requerido: true },
            { campo: 'madreOcupacion', label: 'Ocupación de la madre', min: 3, max: 40, requerido: true },
            { campo: 'madreDireccion', label: 'Dirección de la madre', min: 3, max: 40, requerido: true },
            { campo: 'madreLugarTrabajo', label: 'Lugar de trabajo de la madre', min: 3, max: 40, requerido: true },
            { campo: 'emergenciaNombre', label: 'Nombre contacto emergencia', min: 3, max: 40, patron: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/, requerido: true }
        ];

        for (const config of camposTexto) {
            const input = document.getElementById(config.campo);
            if (!input || input.offsetParent === null) continue; // Skip si no existe o está oculto

            const valor = input.value.trim();

            if (config.requerido && !valor) {
                this.mostrarError(input, `${config.label} es requerido`);
                erroresEncontrados.push(`${config.label} es requerido`);
                hayErrores = true;
                continue;
            }

            if (valor) {
                if (valor.length < config.min) {
                    this.mostrarError(input, `${config.label} debe tener al menos ${config.min} caracteres`);
                    erroresEncontrados.push(`${config.label} debe tener al menos ${config.min} caracteres`);
                    hayErrores = true;
                    continue;
                }

                if (valor.length > config.max) {
                    this.mostrarError(input, `${config.label} no puede tener más de ${config.max} caracteres`);
                    erroresEncontrados.push(`${config.label} no puede tener más de ${config.max} caracteres`);
                    hayErrores = true;
                    continue;
                }

                if (config.patron && !config.patron.test(valor)) {
                    this.mostrarError(input, `${config.label} contiene caracteres no válidos`);
                    erroresEncontrados.push(`${config.label} contiene caracteres no válidos`);
                    hayErrores = true;
                    continue;
                }
            }
        }

        // 2. Validar cédulas
        const camposCedula = [
            { campo: 'estudianteCedula', nacionalidad: 'estudianteNacionalidad', label: 'Cédula del estudiante', min: 7, max: 8 },
            { campo: 'padreCedula', nacionalidad: 'padreNacionalidad', label: 'Cédula del padre', min: 7, max: 8 },
            { campo: 'madreCedula', nacionalidad: 'madreNacionalidad', label: 'Cédula de la madre', min: 7, max: 8 },
            { campo: 'emergenciaCedula', nacionalidad: 'emergenciaNacionalidad', label: 'Cédula de emergencia', min: 7, max: 8 }
        ];

        for (const config of camposCedula) {
            const input = document.getElementById(config.campo);
            if (!input || input.offsetParent === null) continue;

            const cedula = input.value.trim();
            if (!cedula) {
                if (input.hasAttribute('required')) {
                    this.mostrarError(input, `${config.label} es requerida`);
                    erroresEncontrados.push(`${config.label} es requerida`);
                    hayErrores = true;
                }
                continue;
            }

            if (!/^[0-9]+$/.test(cedula)) {
                this.mostrarError(input, 'La cédula debe contener solo números');
                erroresEncontrados.push(`${config.label} debe contener solo números`);
                hayErrores = true;
                continue;
            }

            if (cedula.length < config.min) {
                this.mostrarError(input, `La cédula debe tener al menos ${config.min} dígitos`);
                erroresEncontrados.push(`${config.label} debe tener al menos ${config.min} dígitos`);
                hayErrores = true;
                continue;
            }

            if (cedula.length > config.max) {
                this.mostrarError(input, `La cédula no puede tener más de ${config.max} dígitos`);
                erroresEncontrados.push(`${config.label} no puede tener más de ${config.max} dígitos`);
                hayErrores = true;
                continue;
            }
        }

        // 3. Validar teléfonos
        const camposTelefono = [
            { campo: 'padreTelefonoHabitacion', prefijo: 'padreTelefonoHabitacionPrefijo', label: 'Teléfono del padre', min: 7, max: 10 },
            { campo: 'padreCelular', prefijo: 'padreCelularPrefijo', label: 'Celular del padre', min: 10, max: 10 },
            { campo: 'madreTelefonoHabitacion', prefijo: 'madreTelefonoHabitacionPrefijo', label: 'Teléfono de la madre', min: 7, max: 10 },
            { campo: 'madreCelular', prefijo: 'madreCelularPrefijo', label: 'Celular de la madre', min: 10, max: 10 },
            { campo: 'emergenciaCelular', prefijo: 'emergenciaCelularPrefijo', label: 'Teléfono de emergencia', min: 10, max: 10 }
        ];

        for (const config of camposTelefono) {
            const input = document.getElementById(config.campo);
            if (!input || input.offsetParent === null) continue;

            const numero = input.value.trim();
            if (!numero) {
                if (input.hasAttribute('required')) {
                    this.mostrarError(input, `${config.label} es requerido`);
                    erroresEncontrados.push(`${config.label} es requerido`);
                    hayErrores = true;
                }
                continue;
            }

            if (!/^[0-9]+$/.test(numero)) {
                this.mostrarError(input, 'El teléfono debe contener solo números');
                erroresEncontrados.push(`${config.label} debe contener solo números`);
                hayErrores = true;
                continue;
            }

            if (numero.startsWith('0')) {
                this.mostrarError(input, 'El número no puede comenzar con 0');
                erroresEncontrados.push(`${config.label} no puede comenzar con 0`);
                hayErrores = true;
                continue;
            }

            if (numero.length < config.min) {
                this.mostrarError(input, `El teléfono debe tener al menos ${config.min} dígitos`);
                erroresEncontrados.push(`${config.label} debe tener al menos ${config.min} dígitos`);
                hayErrores = true;
                continue;
            }

            if (numero.length > config.max) {
                this.mostrarError(input, `El teléfono no puede tener más de ${config.max} dígitos`);
                erroresEncontrados.push(`${config.label} no puede tener más de ${config.max} dígitos`);
                hayErrores = true;
                continue;
            }

            // Validar prefijo
            const prefijoHidden = document.getElementById(config.prefijo);
            if (prefijoHidden && !prefijoHidden.value) {
                const prefijoInput = document.getElementById(config.prefijo + '_input');
                this.mostrarError(input, 'Debe seleccionar un prefijo para el teléfono');
                if (prefijoInput) {
                    this.mostrarError(prefijoInput, 'Seleccione un prefijo');
                }
                erroresEncontrados.push(`Prefijo de ${config.label} es requerido`);
                hayErrores = true;
            }
        }

        // 4. Validar correos
        const camposCorreo = [
            { campo: 'estudianteCorreo', label: 'Correo del estudiante' },
            { campo: 'padreCorreo', label: 'Correo del padre' },
            { campo: 'madreCorreo', label: 'Correo de la madre' }
        ];

        for (const config of camposCorreo) {
            const input = document.getElementById(config.campo);
            if (!input || input.offsetParent === null) continue;

            const correo = input.value.trim().toLowerCase();
            if (!correo) {
                if (input.hasAttribute('required')) {
                    this.mostrarError(input, `${config.label} es requerido`);
                    erroresEncontrados.push(`${config.label} es requerido`);
                    hayErrores = true;
                }
                continue;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(correo)) {
                this.mostrarError(input, 'El formato del correo no es válido');
                erroresEncontrados.push(`${config.label} no tiene un formato válido`);
                hayErrores = true;
                continue;
            }

            if (correo.length < 10) {
                this.mostrarError(input, 'El correo debe tener al menos 10 caracteres');
                erroresEncontrados.push(`${config.label} debe tener al menos 10 caracteres`);
                hayErrores = true;
                continue;
            }

            if (correo.length > 50) {
                this.mostrarError(input, 'El correo no puede tener más de 50 caracteres');
                erroresEncontrados.push(`${config.label} no puede tener más de 50 caracteres`);
                hayErrores = true;
            }
        }

        // 5. Validar selects requeridos
        const selectsRequeridos = [
            'estudianteSexo', 'estudianteNacionalidad', 'estudianteFechaNacimiento',
            'padreNacionalidad', 'padreUrbanismo',
            'madreNacionalidad', 'madreUrbanismo',
            'emergenciaNacionalidad', 'emergenciaParentesco'
        ];

        selectsRequeridos.forEach(id => {
            const select = document.getElementById(id);
            if (select && select.offsetParent !== null && select.hasAttribute('required')) {
                if (!select.value) {
                    const label = select.previousElementSibling?.textContent || id;
                    this.mostrarError(select, `Debe seleccionar una opción`);
                    erroresEncontrados.push(`${label} es requerido`);
                    hayErrores = true;
                }
            }
        });

        return {
            valido: !hayErrores,
            errores: erroresEncontrados
        };
    }
}

// Inicializar validador cuando el DOM esté listo
let validadorSolicitud = null;

document.addEventListener('DOMContentLoaded', function() {
    // Detectar la ruta base según la ubicación del HTML
    const currentPath = window.location.pathname;
    let basePath = '../../';

    // Si estamos en vistas/inscripciones/inscripcion/, necesitamos ../../../
    if (currentPath.includes('/vistas/inscripciones/inscripcion/')) {
        basePath = '../../../';
    }

    validadorSolicitud = new ValidadorFormulario('formInscripcion', basePath);
});

// Exportar para uso global
window.ValidadorFormulario = ValidadorFormulario;
window.validadorSolicitud = validadorSolicitud;
