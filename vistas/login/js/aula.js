const cbxEstado = document.getElementById('cargo')
cbxEstado.addEventListener("change", getGrados)

const cbxMunicipio = document.getElementById('grado')
cbxMunicipio.addEventListener("change", getSecciones)

const cbxLocalidad = document.getElementById('seccion')

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

function getGrados() {
    let estado = cbxEstado.value;
    let url = 'php/getGrados.php';
    let formData = new FormData();
    formData.append('IdCargo', cargo);

    fetchAndSetData(url, formData, cbxMunicipio)
        .then(() => {
            cbxLocalidad.innerHTML = ''
            let defaultOption = document.createElement('option');
            defaultOption.value = 0;
            defaultOption.innerHTML = "Ninguno";
            cbxLocalidad.appendChild(defaultOption);
        })
        .catch(err => console.log(err));
}

function getSecciones() {
    let municipio = cbxMunicipio.value;
    let url = 'php/getSecciones.php';
    let formData = new FormData();
    formData.append('IdGrado', grado);

    fetchAndSetData(url, formData, cbxLocalidad)
        .catch(err => console.log(err));
}