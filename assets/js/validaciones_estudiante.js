/**
 * Validaciones en tiempo real para formulario de estudiante
 * Sistema de validación similar a solicitud_cupo.js
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-editar-estudiante');
    if (!form) return;

    // 🔹 UTILIDADES DE VALIDACIÓN
    const Validaciones = {
        // Solo letras y espacios (incluyendo acentos y ñ)
        soloLetras: (valor) => /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(valor),

        // Solo números
        soloNumeros: (valor) => /^[0-9]+$/.test(valor),

        // Email válido
        emailValido: (valor) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor),

        // Rango de longitud
        longitudValida: (valor, min, max) => {
            const len = valor.length;
            return len >= min && len <= max;
        },

        // Cédula válida (7-8 dígitos para normal, 10-11 para escolar)
        cedulaValida: (valor, esEscolar) => {
            if (!Validaciones.soloNumeros(valor)) return false;
            if (esEscolar) {
                return Validaciones.longitudValida(valor, 10, 11);
            } else {
                return Validaciones.longitudValida(valor, 7, 8);
            }
        },

        // Teléfono válido (10 dígitos)
        telefonoValido: (valor) => {
            return Validaciones.soloNumeros(valor) && valor.length === 10;
        }
    };

    // 🔹 REMOVER TODOS LOS ATRIBUTOS HTML5 DE VALIDACIÓN
    form.querySelectorAll('input, select, textarea').forEach(input => {
        input.removeAttribute('required');
        input.removeAttribute('pattern');
        input.removeAttribute('minlength');
        input.removeAttribute('maxlength');
        input.removeAttribute('min');
        input.removeAttribute('max');
    });

    // 🔹 FUNCIONES DE MENSAJE DE ERROR (estilo solicitud_cupo.js)
    function mostrarError(input, mensaje) {
        const formGroup = input.closest('.form-group') || input.closest('.mb-3') || input.parentElement;
        if (!formGroup) return;

        // Remover error anterior
        limpiarError(input);

        // Agregar nuevo error
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');

        const errorDiv = document.createElement('small');
        errorDiv.className = 'text-danger d-block mt-1';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i>' + mensaje;

        // Insertar después del input
        input.parentNode.insertBefore(errorDiv, input.nextSibling);
    }

    function limpiarError(input) {
        const formGroup = input.closest('.form-group') || input.closest('.mb-3') || input.parentElement;

        // Remover mensajes de error
        const errores = formGroup?.querySelectorAll('.text-danger');
        errores?.forEach(error => {
            if (error.querySelector('.fa-exclamation-circle')) {
                error.remove();
            }
        });

        input.classList.remove('is-invalid', 'is-valid');
    }

    function marcarValido(input) {
        limpiarError(input);
        input.classList.add('is-valid');
        input.classList.remove('is-invalid');
    }

    // 🔹 FORMATEAR CAMPOS AUTOMÁTICAMENTE (como en solicitud_cupo.js)
    function formatearSoloNumeros(input) {
        input.value = input.value.replace(/[^0-9]/g, '');
    }

    function formatearSoloLetras(input) {
        // Permitir letras, espacios, acentos y ñ
        input.value = input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
    }

    // 🔹 VALIDADORES ESPECÍFICOS
    function validarNombre(input) {
        const valor = input.value.trim();

        if (!valor) {
            mostrarError(input, 'El nombre es obligatorio');
            return false;
        }

        if (!Validaciones.soloLetras(valor)) {
            mostrarError(input, 'El nombre solo puede contener letras');
            return false;
        }

        if (!Validaciones.longitudValida(valor, 3, 40)) {
            mostrarError(input, 'El nombre debe tener entre 3 y 40 caracteres');
            return false;
        }

        marcarValido(input);
        return true;
    }

    function validarApellido(input) {
        const valor = input.value.trim();

        if (!valor) {
            mostrarError(input, 'El apellido es obligatorio');
            return false;
        }

        if (!Validaciones.soloLetras(valor)) {
            mostrarError(input, 'El apellido solo puede contener letras');
            return false;
        }

        if (!Validaciones.longitudValida(valor, 3, 40)) {
            mostrarError(input, 'El apellido debe tener entre 3 y 40 caracteres');
            return false;
        }

        marcarValido(input);
        return true;
    }

    function validarCedula(input) {
        const valor = input.value.trim();

        if (!valor) {
            limpiarError(input);
            return true; // Cédula es opcional
        }

        // Determinar si es cédula escolar basado en datos del formulario
        const fechaNacimiento = form.querySelector('[name="fecha_nacimiento"]')?.value;
        let esEscolar = false;

        if (fechaNacimiento) {
            const fecha = new Date(fechaNacimiento);
            const hoy = new Date();
            const edad = hoy.getFullYear() - fecha.getFullYear();
            esEscolar = edad < 10;
        }

        if (!Validaciones.soloNumeros(valor)) {
            mostrarError(input, 'La cédula solo puede contener números');
            return false;
        }

        if (esEscolar) {
            if (!Validaciones.longitudValida(valor, 10, 11)) {
                mostrarError(input, 'La cédula escolar debe tener entre 10 y 11 dígitos');
                return false;
            }
        } else {
            if (!Validaciones.longitudValida(valor, 7, 8)) {
                mostrarError(input, 'La cédula debe tener entre 7 y 8 dígitos');
                return false;
            }
        }

        marcarValido(input);
        return true;
    }

    function validarCorreo(input) {
        const valor = input.value.trim();

        if (!valor) {
            limpiarError(input);
            return true; // Correo es opcional
        }

        if (!Validaciones.emailValido(valor)) {
            mostrarError(input, 'Ingrese un correo electrónico válido (ej: usuario@correo.com)');
            return false;
        }

        if (!Validaciones.longitudValida(valor, 5, 100)) {
            mostrarError(input, 'El correo debe tener entre 5 y 100 caracteres');
            return false;
        }

        marcarValido(input);
        return true;
    }

    function validarTelefono(input) {
        const valor = input.value.trim();

        if (!valor) {
            limpiarError(input);
            return true; // Teléfono es opcional
        }

        if (!Validaciones.soloNumeros(valor)) {
            mostrarError(input, 'El teléfono solo puede contener números');
            return false;
        }

        if (valor.length !== 10) {
            mostrarError(input, 'El teléfono debe tener exactamente 10 dígitos');
            return false;
        }

        marcarValido(input);
        return true;
    }

    function validarDireccion(input) {
        const valor = input.value.trim();

        if (!valor) {
            limpiarError(input);
            return true; // Dirección es opcional
        }

        if (!Validaciones.longitudValida(valor, 10, 250)) {
            mostrarError(input, 'La dirección debe tener entre 10 y 250 caracteres');
            return false;
        }

        marcarValido(input);
        return true;
    }

    function validarFechaNacimiento(input) {
        const valor = input.value.trim();

        if (!valor) {
            mostrarError(input, 'La fecha de nacimiento es obligatoria');
            return false;
        }

        const fecha = new Date(valor);
        const hoy = new Date();
        const edad = Math.floor((hoy - fecha) / (365.25 * 24 * 60 * 60 * 1000));

        if (edad < 3 || edad > 25) {
            mostrarError(input, 'El estudiante debe tener entre 3 y 25 años');
            return false;
        }

        marcarValido(input);
        return true;
    }

    // 🔹 ASIGNAR VALIDADORES Y FORMATEADORES A CAMPOS
    const validadores = {
        'nombre': validarNombre,
        'apellido': validarApellido,
        'cedula': validarCedula,
        'correo': validarCorreo,
        'direccion': validarDireccion,
        'fecha_nacimiento': validarFechaNacimiento
    };

    // Asignar eventos blur para validación en tiempo real
    Object.keys(validadores).forEach(fieldName => {
        const input = form.querySelector(`[name="${fieldName}"]`);
        if (input) {
            // Validar con blur
            input.addEventListener('blur', function() {
                validadores[fieldName](this);
            });

            // Formatear mientras escribe (input event)
            input.addEventListener('input', function() {
                // Limpiar errores mientras escribe
                if (this.classList.contains('is-invalid') || this.classList.contains('is-valid')) {
                    limpiarError(this);
                }

                // Formatear según el tipo de campo
                if (fieldName === 'nombre' || fieldName === 'apellido') {
                    formatearSoloLetras(this);
                } else if (fieldName === 'cedula') {
                    formatearSoloNumeros(this);
                }
            });
        }
    });

    // Validar teléfonos dinámicos
    form.addEventListener('blur', function(e) {
        if (e.target.matches('[name="phone_numero[]"]')) {
            validarTelefono(e.target);
        }
    }, true);

    form.addEventListener('input', function(e) {
        if (e.target.matches('[name="phone_numero[]"]')) {
            formatearSoloNumeros(e.target);
            if (e.target.classList.contains('is-invalid') || e.target.classList.contains('is-valid')) {
                limpiarError(e.target);
            }
        }
    }, true);

    // 🔹 VALIDACIÓN AL ENVIAR FORMULARIO
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let formularioValido = true;
        let camposFaltantes = [];

        // Validar todos los campos obligatorios
        Object.keys(validadores).forEach(fieldName => {
            const input = form.querySelector(`[name="${fieldName}"]`);
            if (input) {
                const esValido = validadores[fieldName](input);
                if (!esValido) {
                    formularioValido = false;
                    // Obtener el label y limpiar los dos puntos
                    let labelText = input.closest('.form-group')?.querySelector('label')?.textContent || fieldName;
                    labelText = labelText.replace(':', '').trim();
                    camposFaltantes.push(labelText);
                }
            }
        });

        // Validar teléfonos
        const telefonos = form.querySelectorAll('[name="phone_numero[]"]');
        telefonos.forEach(tel => {
            if (tel.value.trim() && !validarTelefono(tel)) {
                formularioValido = false;
                camposFaltantes.push('Teléfono');
            }
        });

        if (!formularioValido) {
            // Scroll al primer error
            const primerError = form.querySelector('.is-invalid');
            if (primerError) {
                primerError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                primerError.focus();
            }

            // Mostrar alerta con SweetAlert2 si está disponible
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Datos incompletos o incorrectos',
                    html: '<strong>Por favor corrija los siguientes campos:</strong><br><ul class="text-left mt-2" style="list-style-position: inside;">' +
                          camposFaltantes.map(campo => `<li>${campo}</li>`).join('') +
                          '</ul>',
                    confirmButtonColor: '#c90000',
                    confirmButtonText: 'Entendido'
                });
            } else {
                alert('Por favor, corrija los errores en el formulario antes de continuar.');
            }
        } else {
            // Si todo está válido, enviar el formulario
            form.submit();
        }
    });
});
