// Validaciones básicas de formulario
function validarFormularioHorario(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('.hora-picker');
    
    inputs.forEach(input => {
        input.style.borderColor = ''; // Resetear estilo
        
        try {
            const timeMoment = moment(input.value, 'h:mm A');
            const hour = timeMoment.hours();
            
            // Validar rango horario
            if (hour < 7 || hour >= 16) {
                isValid = false;
                input.style.borderColor = '#c90000';
                Swal.fire({
                    title: 'Hora fuera de rango',
                    text: `La hora ${input.value} no está permitida (7AM - 4PM)`,
                    icon: 'error',
                    confirmButtonColor: '#c90000'
                });
            }
        } catch (e) {
            isValid = false;
            input.style.borderColor = '#c90000';
            Swal.fire({
                title: 'Formato inválido',
                text: 'Por favor ingrese una hora válida',
                icon: 'error',
                confirmButtonColor: '#c90000'
            });
        }
    });
    
    return isValid;
}

// Configurar evento submit genérico
function configurarValidacionFormulario() {
    document.querySelectorAll('form.necesita-validacion-horario').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validarFormularioHorario(this)) {
                e.preventDefault();
            }
        });
    });
}