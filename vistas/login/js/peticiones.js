const cbxPais = document.getElementById('pais')
cbxPais.addEventListener("change", getEstado)

const cbxEstado = document.getElementById('estado')
cbxEstado.addEventListener("change", getCiudad)

const cbxCiudad = document.getElementById('ciudad')

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

function getEstado() {
    let pais = cbxPais.value;
    let url = 'php/getEstado.php';
    let formData = new FormData();
    formData.append('IdPais', pais);

    fetchAndSetData(url, formData, cbxEstado)
        .then(() => {
            cbxCiudad.innerHTML = ''
            let defaultOption = document.createElement('option');
            defaultOption.value = 0;
            defaultOption.innerHTML = "Seleccionar";
            cbxCiudad.appendChild(defaultOption);
        })
        .catch(err => console.log(err));
}

function getCiudad() {
    let estado = cbxEstado.value;
    let url = 'php/getCiudad.php';
    let formData = new FormData();
    formData.append('IdEstado', estado);

    fetchAndSetData(url, formData, cbxCiudad)
        .catch(err => console.log(err));
}