function filtrarEmpleados() {
    const input = document.getElementById('empleado').value.toLowerCase();
    const resultadosDiv = document.getElementById('resultados');
    resultadosDiv.innerHTML = ''; // Limpiar resultados anteriores

    if (input.length > 0) {
        const resultadosFiltrados = empleados.filter(empleado => 
            empleado.nombre.toLowerCase().includes(input) || 
            empleado.apellido.toLowerCase().includes(input) || 
            empleado.cedula.includes(input)
        );

        resultadosFiltrados.forEach(empleado => {
            const div = document.createElement('div');
            div.textContent = `${empleado.nombre} ${empleado.apellido} - ${empleado.cedula}`;
            div.setAttribute('data-id', empleado.IdPersona);
            div.classList.add('resultado');
            div.onclick = function() {
                document.getElementById('empleado').value = `${empleado.nombre} ${empleado.apellido} - ${empleado.cedula}`;
                document.getElementById('IdPersona').value = empleado.IdPersona; // Guardar el ID en un campo oculto
                resultadosDiv.innerHTML = ''; // Limpiar resultados
            };
            resultadosDiv.appendChild(div);
        });
    }
}

document.querySelector('form').onsubmit = function(event) {
    const idPersona = document.getElementById('IdPersona').value;
    if (!idPersona) {
        alert('Por favor, selecciona un empleado de la lista.');
        event.preventDefault(); // Evitar el envío del formulario
    }
};

// Función para validar la fecha de ingreso
function validarFechaIngreso() {
const idPersona = document.getElementById('IdPersona').value;
const fechaIngresoInput = document.getElementById('fecha_ingreso');

const empleadoSeleccionado = empleados.find(emp => emp.IdPersona == idPersona);
if (empleadoSeleccionado) {
    const fechaNacimiento = new Date(empleadoSeleccionado.fecha_nacimiento);
    
    // Calcular fechas límite
    const fechaMinima = new Date(fechaNacimiento);
    fechaMinima.setFullYear(fechaMinima.getFullYear() + 18); // 18 años
    const fechaMaxima = new Date(fechaNacimiento);
    fechaMaxima.setFullYear(fechaMaxima.getFullYear() + 100); // 100 años

    const fechaIngreso = new Date(fechaIngresoInput.value);
    const fechaActual = new Date(); // Fecha actual

    // Validaciones
    if (fechaIngreso < fechaMinima) {
        alert("La fecha de ingreso debe ser mayor a 18 años desde la fecha de nacimiento.");
        fechaIngresoInput.value = ""; // Limpiar el campo
        return false;
    }

    if (fechaIngreso > fechaMaxima) {
        alert("La fecha de ingreso no puede exceder los 100 años desde la fecha de nacimiento.");
        fechaIngresoInput.value = ""; // Limpiar el campo
        return false;
    }

    if (fechaIngreso > fechaActual) {
        alert("La fecha de ingreso no puede ser una fecha futura.");
        fechaIngresoInput.value = ""; // Limpiar el campo
        return false;
    }
}
return true;
}

// Agregar el evento de validación al campo de fecha de ingreso
document.getElementById('fecha_ingreso').addEventListener('change', validarFechaIngreso);


document.querySelector('form').onsubmit = function(event) {
const isValid = validarFechaIngreso();
// Otras validaciones...
if (!isValid) {
    event.preventDefault(); // Evitar el envío si la validación falla
}
};