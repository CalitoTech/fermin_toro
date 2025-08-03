// Función para manejar la recuperación y la configuración de datos
function fetchAndSetData(url, formData, targetElement) {
    return fetch(url, {
        method: "POST",
        body: formData,
        mode: 'cors'
    })
        .then(response => response.json())
        .then(data => {
            targetElement.innerHTML = data;
        })
        .catch(err => console.log(err));
}

// Delegar eventos a la tabla de exámenes
document.getElementById('examenBody').addEventListener('change', function(event) {
    const target = event.target;

    // Manejar el cambio en el select de tipo examen
    if (target.matches('select[name="tipo_examen[]"]')) {
        const tipo_examen = target.value;
        const cbxClasificacion = target.parentElement.nextElementSibling.querySelector('select[name="clasificacion[]"]');
        
        // Llama a la función para obtener clasificaciones
        let url = 'php/getClasificacion.php';
        let formData = new FormData();
        formData.append('IdTipo_Examen', tipo_examen);

        fetchAndSetData(url, formData, cbxClasificacion)
            .then(() => {
                const cbxExamen = cbxClasificacion.parentElement.nextElementSibling.querySelector('select[name="examen[]"]');
                cbxExamen.innerHTML = ''; // Limpiar opciones anteriores
                let defaultOption = document.createElement('option');
                defaultOption.value = ''; // Valor nulo
                defaultOption.innerHTML = "Seleccionar";
                cbxExamen.appendChild(defaultOption);
            });
    }

    // Manejar el cambio en el select de clasificación
    if (target.matches('select[name="clasificacion[]"]')) {
        const clasificacion = target.value;
        const cbxExamen = target.parentElement.nextElementSibling.querySelector('select[name="examen[]"]');
        const textareaValor = cbxExamen.parentElement.nextElementSibling.querySelector('.valor_examen');

        // Llama a la función para obtener exámenes
        let url = 'php/getExamen.php';
        let formData = new FormData();
        formData.append('IdClasificacion', clasificacion);

        fetchAndSetData(url, formData, cbxExamen)
            .then(() => {
                textareaValor.value = ''; // Limpiar el valor al cambiar la clasificación
            });
    }

    // Manejar el cambio en el select de examen
    if (target.matches('select[name="examen[]"]')) {
        const selectedOption = target.options[target.selectedIndex];
        const valor = selectedOption.getAttribute('data-valor'); // Asegúrate de que el atributo 'data-valor' esté presente
        const textareaValor = target.parentElement.nextElementSibling.querySelector('.valor_examen');
        
        if (textareaValor) {
            textareaValor.value = valor; // Rellenar el campo de valor automáticamente
        }
    }
});