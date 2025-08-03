document.getElementById('asistenciaForm').addEventListener('submit', function(event) {
    const rows = document.querySelectorAll('.asistencia-row');
    let hasError = false;

    rows.forEach(row => {
        const idPersonaInput = row.querySelector('.lista').value;
        const fechaInicio = row.querySelector('input[name="fecha_inicio_semana"]').value;

        if (idPersonaInput) {
            // Aquí se puede hacer una llamada AJAX para verificar si existe
            fetch(`php/verificar_registro.php?idPersona=${idPersonaInput}&fechaInicio=${fechaInicio}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        hasError = true;
                        row.style.border = "2px solid red"; // Cambiar borde a rojo
                        alert("Esta persona ya se encuentra registrada para la fecha seleccionada");
                    }
                });
        }
    });

    if (hasError) {
        event.preventDefault(); // Evitar el envío del formulario
    }
});