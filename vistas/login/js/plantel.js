function cargarCargosRecibo() {
    const plantelSelect = document.getElementById('plantel');
    const selectedPlantelId = plantelSelect.value;
    const cargoSelect = document.getElementById('cargo');
    const selectedCargoId = cargoSelect.value; // Obtener el IdCargo seleccionado
    const codigoPlantelInput = document.getElementById('codigo_plantel');
    const cargoReciboSelect = document.getElementById('cargo_recibo');
    const codigoCargoInput = document.getElementById('codigo_cargo');

    // Limpiar el campo de código de plantel y los cargos de recibo
    codigoPlantelInput.value = '';
    cargoReciboSelect.innerHTML = '<option value="">Seleccione</option>'; // Reiniciar opciones de cargos de recibo
    codigoCargoInput.value = '';

    if (selectedPlantelId && selectedCargoId) {
        // Hacer una solicitud AJAX para obtener los cargos de recibo para el plantel y cargo seleccionados
        fetch(`php/cargar_cargos_recibo.php?plantelId=${selectedPlantelId}&cargoId=${selectedCargoId}`)
            .then(response => response.json())
            .then(data => {
                // Limpiar las opciones anteriores para evitar duplicados
                cargoReciboSelect.innerHTML = '<option value="">Seleccione</option>'; // Reiniciar opciones

                // Ordenar los cargos de recibo alfabéticamente
                data.sort((a, b) => a.cargo_recibo.localeCompare(b.cargo_recibo));

                data.forEach(cargo => {
                    const option = document.createElement('option');
                    option.value = cargo.IdCargo_Recibo;
                    option.textContent = cargo.cargo_recibo;
                    option.dataset.codigo = cargo.codigo_cargo; // Asegúrate de que esto esté presente en la respuesta
                    cargoReciboSelect.appendChild(option);
                });
                // Mostrar el código del plantel
                codigoPlantelInput.value = plantelSelect.options[plantelSelect.selectedIndex].dataset.codigo;
            })
            .catch(error => console.error('Error al cargar los cargos de recibo:', error));
    }
}

// Al seleccionar un cargo de recibo, llenar el campo de código de cargo
document.getElementById('cargo_recibo').addEventListener('change', function() {
    const selectedCargoRecibo = this.options[this.selectedIndex];
    const codigoCargoInput = document.getElementById('codigo_cargo');

    if (selectedCargoRecibo) {
        codigoCargoInput.value = selectedCargoRecibo.dataset.codigo; // Asignar el código correspondiente
    } else {
        codigoCargoInput.value = ''; // Limpiar si no hay selección
    }
});

// Agregar event listeners
document.getElementById('cargo').addEventListener('change', cargarCargosRecibo);
document.getElementById('plantel').addEventListener('change', cargarCargosRecibo);

