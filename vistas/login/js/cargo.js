document.addEventListener('DOMContentLoaded', function() {
    const tipo_pacienteSelect = document.getElementById('tipo_paciente');
    const cargoInput = document.getElementById('cargo');
    const fichaInput = document.getElementById('ficha');
    const statusInput = document.getElementById('status');
    const departamentoSelect = document.getElementById('departamento');
    const fecha_ingresoInput = document.getElementById('fecha_ingreso');
    const empresaSelect = document.getElementById('empresa');

    function updateFieldStates() {
        const tipo_pacienteText = tipo_pacienteSelect.options[tipo_pacienteSelect.selectedIndex].text;

        // Habilitar campos para "Empleado"
        if (tipo_pacienteText.includes("Empleado")) {
            cargoInput.disabled = false;
            fichaInput.disabled = false;
            statusInput.disabled = false;
            departamentoSelect.disabled = false;
            fecha_ingresoInput.disabled = false;
            empresaSelect.disabled = false;
        } else {
            // Deshabilitar campos para "Pasante" y "Obrero"
            cargoInput.disabled = true;
            fichaInput.disabled = true;
            statusInput.disabled = true;
            departamentoSelect.disabled = true;
            fecha_ingresoInput.disabled = true;
            empresaSelect.disabled = true;

            // No vaciar los campos, solo deshabilitarlos
            if (tipo_pacienteText.includes("Pasante")) {
                fecha_ingresoInput.disabled = false;
                departamentoSelect.disabled = false; // Permitir seleccionar matrícula
                statusInput.disabled = false;
            }

            if (tipo_pacienteText.includes("Obrero")) {
                cargoInput.disabled = false;
                fichaInput.disabled = false; 
                statusInput.disabled = false;
                departamentoSelect.disabled = false;
                fecha_ingresoInput.disabled = false;
                empresaSelect.disabled = false;
            }
        }
    }

    // Inicializar el estado de los campos al cargar la página
    updateFieldStates();

    // Escuchar cambios en el select de tipo_paciente
    tipo_pacienteSelect.addEventListener('change', updateFieldStates);
});
