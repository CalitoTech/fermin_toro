document.addEventListener('DOMContentLoaded', function() {
    const selectCargo = document.getElementById('cargo');
    const selectFuncion = document.getElementById('funcion');
    const selectArea = document.getElementById('area');
    const selectCargoRecibo = document.getElementById('cargo_recibo');

    selectCargo.addEventListener('change', function() {
        const idCargo = this.value;

        // Limpiar las opciones anteriores
        selectFuncion.innerHTML = '<option value="">Seleccione</option>';
        selectArea.innerHTML = '<option value="">Seleccione</option>';
        selectCargoRecibo.innerHTML = '<option value="">Seleccione</option>'; // Limpiar opciones de cargo recibo

        // Verificar que se ha seleccionado un cargo
        if (idCargo) {
            // Hacer la petición al archivo PHP para funciones y áreas
            fetch(`php/get_funciones_y_areas.php?idCargo=${idCargo}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {

                    // Ordenar funciones alfabéticamente
                    data.funciones.sort((a, b) => a.funcion.localeCompare(b.funcion));

                    // Agregar funciones al select de funciones
                    data.funciones.forEach(funcion => {
                        const option = document.createElement('option');
                        option.value = funcion.IdFuncion;
                        option.textContent = funcion.funcion;
                        selectFuncion.appendChild(option);
                    });

                    // Ordenar áreas alfabéticamente
                    data.areas.sort((a, b) => a.espacio_laboral.localeCompare(b.espacio_laboral));

                    // Agregar áreas al select de áreas
                    data.areas.forEach(area => {
                        const option = document.createElement('option');
                        option.value = area.IdArea;
                        option.textContent = area.espacio_laboral;
                        selectArea.appendChild(option);
                    });
                })
                .catch(error => console.error('Error:', error));

            // Hacer la petición al archivo PHP para los cargos de recibo
            fetch(`php/get_cargo_recibo.php?idCargo=${idCargo}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {

                    data.cargos_recibo.sort((a, b) => a.cargo_recibo.localeCompare(b.cargo_recibo));

                    // Agregar cargos de recibo al select de cargos recibo
                    data.cargos_recibo.forEach(cargoRecibo => {
                        const option = document.createElement('option');
                        option.value = cargoRecibo.IdCargo_Recibo;
                        option.textContent = cargoRecibo.cargo_recibo;
                        selectCargoRecibo.appendChild(option);
                    });
                })
                .catch(error => console.error('Error:', error));
        }
    });
});
