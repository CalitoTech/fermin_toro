// Selecciona el tbody de antecedentes
const antecedentesBody = document.getElementById('antecedenteBody');

// Delegación de eventos para el cambio en los selects de antecedentes
antecedentesBody.addEventListener('change', function(event) {
    const target = event.target;

    if (target.name === 'tipo_antecedente[]') {
        getAntecedente(target);
    } else if (target.name === 'antecedente[]') {
        getExamen(target);
    }
});

function getAntecedente(select) {
    let tipo_antecedente = select.value;
    let url = 'php/getAntecedente.php';
    let formData = new FormData();
    formData.append('IdTipo_Antecedente', tipo_antecedente);

    // Obtener el select de antecedentes correspondiente
    const cbxAntecedente = select.parentElement.parentElement.querySelector('select[name="antecedente[]"]');
    
    fetchAndSetData(url, formData, cbxAntecedente)
        .then(() => {
            // Limpiar el select de examen correspondiente
            const cbxExamen = select.parentElement.parentElement.querySelector('select[name="examen[]"]');
            cbxExamen.innerHTML = '';
            let defaultOption = document.createElement('option');
            defaultOption.value = 0;
            defaultOption.innerHTML = "Seleccionar";
            cbxExamen.appendChild(defaultOption);
        })
        .catch(err => console.log(err));
}
