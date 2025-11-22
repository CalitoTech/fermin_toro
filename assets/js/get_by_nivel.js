document.addEventListener('DOMContentLoaded', function() {
    const nivelSelect = document.getElementById('nivel');
    const cursoSelect = document.getElementById('curso');
    const aulaSelect = document.getElementById('aula');

    // Función para cargar cursos por nivel
    function cargarCursos(nivelId) {
        if (!nivelId) return;
        
        fetch(`../../../controladores/CursoController.php?action=getByNivel&nivelId=${nivelId}`)
            .then(response => response.json())
            .then(data => {
                const cursoActual = cursoSelect.value;
                cursoSelect.innerHTML = '<option value="">Seleccione un curso</option>';
                
                data.forEach(curso => {
                    const selected = curso.IdCurso == cursoActual ? 'selected' : '';
                    cursoSelect.innerHTML += `<option value="${curso.IdCurso}" ${selected}>${curso.curso}</option>`;
                });
            });
    }

    // Función para cargar aulas por nivel
    function cargarAulas(nivelId) {
        if (!nivelId) return;
        
        fetch(`../../../controladores/AulaController.php?action=getByNivel&nivelId=${nivelId}`)
            .then(response => response.json())
            .then(data => {
                const aulaActual = aulaSelect.value;
                aulaSelect.innerHTML = '<option value="">Sin aula asignada</option>';
                
                data.forEach(aula => {
                    const selected = aula.IdAula == aulaActual ? 'selected' : '';
                    aulaSelect.innerHTML += `<option value="${aula.IdAula}" ${selected}>${aula.aula}</option>`;
                });
            });
    }

    // Al cambiar el nivel
    nivelSelect.addEventListener('change', function() {
        const nivelId = this.value;
        cargarCursos(nivelId);
        cargarAulas(nivelId);
    });

    // Si hay un nivel seleccionado al cargar (modo edición)
    if (nivelSelect.value) {
        cargarCursos(nivelSelect.value);
        cargarAulas(nivelSelect.value);
    }
});