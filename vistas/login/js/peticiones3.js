const cbxTipo_Examen = document.getElementById('tipo_examen')
cbxTipo_Examen.addEventListener("change", getClasificacion)

const cbxClasificacion = document.getElementById('clasificacion')
cbxClasificacion.addEventListener("change", getExamen)

const cbxExamen = document.getElementById('examen')

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

function getClasificacion() {
    let tipo_examen = cbxTipo_Examen.value;
    let url = 'php/getClasificacion.php';
    let formData = new FormData();
    formData.append('IdTipo_Examen', tipo_examen);

    fetchAndSetData(url, formData, cbxClasificacion)
        .then(() => {
            cbxExamen.innerHTML = ''
            let defaultOption = document.createElement('option');
            defaultOption.value = 0;
            defaultOption.innerHTML = "Seleccionar";
            cbxExamen.appendChild(defaultOption);
        })
        .catch(err => console.log(err));
}

function getExamen() {
    let clasificacion = cbxClasificacion.value;
    let url = 'php/getExamen.php';
    let formData = new FormData();
    formData.append('IdClasificacion', clasificacion);

    fetchAndSetData(url, formData, cbxExamen)
        .catch(err => console.log(err));
}