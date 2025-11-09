<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    header("Location: ../../login/login.php");
    exit();
}

// Incluir Notificaciones
require_once __DIR__ . '/../../../controladores/Notificaciones.php';

// Manejo de alertas
$alert = $_SESSION['alert'] ?? null;
$message = $_SESSION['message'] ?? '';
unset($_SESSION['alert'], $_SESSION['message']);

// Conexión y modelos
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Nacionalidad.php';
require_once __DIR__ . '/../../../modelos/Sexo.php';
require_once __DIR__ . '/../../../modelos/Nivel.php';
require_once __DIR__ . '/../../../modelos/Curso.php';
require_once __DIR__ . '/../../../modelos/Seccion.php';
require_once __DIR__ . '/../../../modelos/Urbanismo.php';
require_once __DIR__ . '/../../../modelos/Parentesco.php';
require_once __DIR__ . '/../../../modelos/Status.php';
require_once __DIR__ . '/../../../modelos/TipoInscripcion.php';

// Instancias de los modelos
$modeloNacionalidad = new Nacionalidad($conexion);
$modeloSexo = new Sexo($conexion);
$modeloNivel = new Nivel($conexion);
$modeloCurso = new Curso($conexion);
$modeloSeccion = new Seccion($conexion);
$modeloUrbanismo = new Urbanismo($conexion);
$modeloParentesco = new Parentesco($conexion);
$statusModel = new Status($conexion);
$tipoInscripcionModel = new TipoInscripcion($conexion);

// Obtener datos sin filtro
$nacionalidades = $modeloNacionalidad->obtenerTodos();
$sexos = $modeloSexo->obtenerTodos();
$secciones = $modeloSeccion->obtenerTodos();
$urbanismos = $modeloUrbanismo->obtenerTodos();
$parentescos = $modeloParentesco->obtenerTodos();
$listaStatus = $statusModel->obtenerStatusInscripcion();
$tiposInscripcion = $tipoInscripcionModel->obtenerTodos();
?>

<head>
    <title>UECFT Araure - Nueva Inscripción</title>
    <link rel="stylesheet" href="../../../assets/css/solicitud_cupo.css">
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<?php
// Obtener datos con filtro por permisos
$niveles = $modeloNivel->obtenerNiveles($idPersona);
$cursos = $modeloCurso->obtenerCursos($idPersona);
?>



<div class="container mt-4">
    <form id="formInscripcion" data-origen="pagina">

        <!-- 🔹 BLOQUE: DATOS DE INSCRIPCIÓN -->
        <div class="card mb-3">
            <div class="card-header" style="background-color: #c90000; color: white;">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list mr-2"></i>Datos de Inscripción
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-3 mt-2">
                    <!-- Tipo de Inscripción -->
                    <div class="col-md-3">
                        <div class="form-group required-field">
                            <label for="tipoInscripcion">Tipo de Inscripción</label>
                            <select class="form-control" id="tipoInscripcion" name="idTipoInscripcion" required>
                                <option value="">Seleccione tipo</option>
                                <?php foreach ($tiposInscripcion as $tipo): ?>
                                    <option value="<?= $tipo['IdTipo_Inscripcion'] ?>">
                                        <?= htmlspecialchars($tipo['tipo_inscripcion']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Nivel -->
                    <div class="col-md-3">
                        <div class="form-group required-field">
                            <label for="nivel">Nivel</label>
                            <select class="form-control" id="nivel" name="idNivelSeleccionado" required>
                                <option value="">Seleccione un nivel</option>
                                <?php foreach ($niveles as $nivel): ?>
                                    <option value="<?= $nivel['IdNivel'] ?>">
                                        <?= htmlspecialchars($nivel['nivel']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Curso -->
                    <div class="col-md-3">
                        <div class="form-group required-field">
                            <label for="curso">Curso</label>
                            <select class="form-control" id="curso" name="idCurso" required>
                                <option value="">Seleccione un curso</option>
                                <?php foreach ($cursos as $curso): ?>
                                    <option value="<?= $curso['IdCurso'] ?>">
                                        <?= htmlspecialchars($curso['curso']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-md-3">
                        <div class="form-group required-field">
                            <label for="IdStatus" class="form-label">Status</label>
                            <select class="form-select" id="IdStatus" name="idStatus" required>
                                <option value="">Seleccione un status</option>
                                <?php foreach ($listaStatus as $status): ?>
                                    <option value="<?= htmlspecialchars($status['IdStatus']) ?>">
                                        <?= htmlspecialchars($status['status']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        
        <!-- ===================== DATOS DEL ESTUDIANTE ===================== -->
        <div class="card mb-4">
            <div class="card-header form-title" style="background-color: #c90000; color: white;" data-toggle="collapse" data-target="#datosEstudiante">
                <h5><i class="fas fa-child mr-2"></i>Datos del Estudiante</h5>
            </div>

            <div class="card-body collapse show" id="datosEstudiante">
                <div class="form-legend">
                    <i class="fas fa-asterisk"></i> Campos obligatorios
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group required-field">
                            <label for="estudianteApellidos">Apellidos</label>
                            <input type="text" class="form-control" id="estudianteApellidos" name="estudianteApellidos" 
                                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" minlength="3" maxlength="40"
                                   onkeypress="return onlyText(event)" oninput="formatearTexto2()" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group required-field">
                            <label for="estudianteNombres">Nombres</label>
                            <input type="text" class="form-control" id="estudianteNombres" name="estudianteNombres" 
                                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" minlength="3" maxlength="40"
                                   onkeypress="return onlyText(event)" oninput="formatearTexto1()" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group required-field">
                            <label for="estudianteNacionalidad">Nacionalidad</label>
                            <select class="form-control" id="estudianteNacionalidad" name="estudianteNacionalidad" required>
                                <option value="">Seleccione una nacionalidad</option>
                                <?php foreach ($nacionalidades as $nacionalidad): ?>
                                    <option value="<?= $nacionalidad['IdNacionalidad'] ?>"><?= $nacionalidad['nacionalidad'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3" id="estudianteCedulaContainer">
                        <div class="form-group required-field">
                            <label for="estudianteCedula">Cédula</label>
                            <input type="text" class="form-control" id="estudianteCedula" name="estudianteCedula"
                                   minlength="7" maxlength="8" pattern="[0-9]+" onkeypress="return onlyNumber(event)" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group required-field">
                            <label for="estudianteSexo">Sexo</label>
                            <select class="form-control" id="estudianteSexo" name="estudianteSexo" required>
                                <option value="">Seleccione un sexo</option>
                                <?php foreach ($sexos as $sexo): ?>
                                    <option value="<?= $sexo['IdSexo'] ?>"><?= $sexo['sexo'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group required-field">
                            <label for="estudianteFechaNacimiento">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="estudianteFechaNacimiento" name="estudianteFechaNacimiento" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group required-field">
                            <label for="estudianteLugarNacimiento">Lugar de Nacimiento</label>
                            <input type="text" class="form-control" id="estudianteLugarNacimiento" name="estudianteLugarNacimiento"
                                   minlength="3" maxlength="40" oninput="formatearTexto1()" required>
                        </div>
                    </div>
                    <div class="col-md-6" id="estudianteTelefonoContainer">
                        <div class="form-group required-field">
                            <label for="estudianteTelefono">Teléfono</label>
                            <input type="tel" class="form-control" id="estudianteTelefono" name="estudianteTelefono" 
                                   minlength="11" maxlength="20" pattern="[0-9]+" onkeypress="return onlyNumber2(event)" required>
                        </div>
                    </div>
                </div>

                <div class="form-group required-field">
                    <label for="estudianteCorreo">Correo Electrónico</label>
                    <input type="email" class="form-control" id="estudianteCorreo" name="estudianteCorreo" minlength="10" maxlength="50" required>
                </div>

                <div class="form-group">
                    <label>Discapacidades o condiciones especiales:</label>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="discapacidadesTable">
                            <thead>
                                <tr>
                                    <th>Tipo de Discapacidad</th>
                                    <th>Descripción</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="discapacidadesBody"></tbody>
                        </table>
                    </div>
                    <button type="button" id="btn-agregar-discapacidad" class="btn btn-sm btn-primary mt-2">
                        <i class="fas fa-plus"></i> Agregar otra discapacidad
                    </button>
                </div>
            </div>
        </div>

        <!-- ===================== DATOS DE PADRES Y REPRESENTANTE ===================== -->
        <?php
        $campos_persona = [
            'Apellidos' => 'text',
            'Nombres' => 'text',
            'Cedula' => 'text',
            'Nacionalidad' => 'select',
            'Ocupacion' => 'text',
            'Urbanismo' => 'select',
            'Direccion' => 'text',
            'TelefonoHabitacion' => 'text',
            'Celular' => 'text',
            'Correo' => 'email',
            'LugarTrabajo' => 'text'
        ];

        $labels_amistosos = [
            'Apellidos' => 'Apellidos',
            'Nombres' => 'Nombres',
            'Cedula' => 'Cédula',
            'Nacionalidad' => 'Nacionalidad',
            'Ocupacion' => 'Ocupación',
            'Urbanismo' => 'Urbanismo / Sector',
            'Direccion' => 'Dirección',
            'TelefonoHabitacion' => 'Teléfono de Habitación',
            'Celular' => 'Celular',
            'Correo' => 'Correo Electrónico',
            'LugarTrabajo' => 'Lugar de Trabajo'
        ];

        $tipos = [
            'madre' => 'Datos de la Madre',
            'padre' => 'Datos del Padre',
            'representante' => 'Datos del Representante Legal'
        ];
        ?>

        <?php foreach ($tipos as $tipo => $titulo): ?>
            <div class="card mb-4 <?= $tipo === 'representante' ? 'd-none' : '' ?>" id="seccion<?= ucfirst($tipo) ?>">
                <div class="card-header form-title" style="background-color: #c90000; color: white;">
                    <h5><i class="fas fa-user mr-2"></i><?= $titulo ?></h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <?php foreach ($campos_persona as $campo => $tipo_input): ?>
                            <div class="col-md-4 mb-3">
                                <label for="<?= $tipo . $campo ?>" class="form-label"><?= $labels_amistosos[$campo] ?></label>
                                <?php if ($campo === 'Nacionalidad'): ?>
                                    <select class="form-control" id="<?= $tipo . $campo ?>" name="<?= $tipo . $campo ?>" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($nacionalidades as $nacionalidad): ?>
                                            <option value="<?= $nacionalidad['IdNacionalidad'] ?>"><?= $nacionalidad['nacionalidad'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($campo === 'Urbanismo'): ?>
                                    <?php
                                    $inputId = $tipo . $campo . '_input';
                                    $hiddenId = $tipo . $campo;
                                    $hiddenNombre = $tipo . $campo . '_nombre';
                                    $resultadosId = $tipo . $campo . '_resultados';
                                    ?>
                                    <div class="position-relative">
                                        <input type="text" class="form-control buscador-input" id="<?= $inputId ?>" autocomplete="off" placeholder="Buscar o escribir nuevo urbanismo...">
                                        <input type="hidden" id="<?= $hiddenId ?>" name="<?= $hiddenId ?>" required>
                                        <input type="hidden" id="<?= $hiddenNombre ?>" name="<?= $hiddenNombre ?>">
                                        <div id="<?= $resultadosId ?>" class="autocomplete-results d-none"></div>
                                    </div>
                                    <script>
                                    document.addEventListener("DOMContentLoaded", function() {
                                        new BuscadorGenerico("<?= $inputId ?>", "<?= $resultadosId ?>", "urbanismo", "<?= $hiddenId ?>", "<?= $hiddenNombre ?>");
                                    });
                                    </script>
                                <?php else: ?>
                                    <input type="<?= $tipo_input ?>" class="form-control" id="<?= $tipo . $campo ?>" name="<?= $tipo . $campo ?>" required>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if ($tipo === 'madre'): ?>
                <!-- ======================================= -->
                <!-- CONTACTO DE EMERGENCIA -->
                <!-- ======================================= -->
                <div class="card mb-4">
                    <div class="card-header" style="background-color: #c90000; color: white;">
                        <h5><i class="fas fa-phone-alt mr-2"></i>Contacto de Emergencia</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group required-field">
                                    <label for="emergenciaNombre">En caso de emergencia, llamar a:</label>
                                    <input type="text" class="form-control" id="emergenciaNombre" name="emergenciaNombre"
                                        pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                        minlength="3" maxlength="40"
                                        onkeypress="return onlyText(event)"
                                        oninput="formatearTexto1()" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group required-field">
                                    <label for="emergenciaParentesco">Parentesco</label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control buscador-input" id="emergenciaParentesco_input" autocomplete="off" placeholder="Buscar o escribir nuevo parentesco...">
                                        <input type="hidden" id="emergenciaParentesco" name="emergenciaParentesco" required>
                                        <input type="hidden" id="emergenciaParentesco_nombre" name="emergenciaParentesco_nombre">
                                        <div id="emergenciaParentesco_resultados" class="autocomplete-results d-none"></div>
                                    </div>
                                    <script>
                                    document.addEventListener("DOMContentLoaded", function() {
                                        new BuscadorGenerico("emergenciaParentesco_input", "emergenciaParentesco_resultados", "parentesco", "emergenciaParentesco", "emergenciaParentesco_nombre");
                                    });
                                    </script>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group required-field">
                                    <label for="emergenciaCelular">Celular</label>
                                    <input type="tel" class="form-control" id="emergenciaCelular" name="emergenciaCelular"
                                        minlength="11" maxlength="20"
                                        pattern="[0-9]+" onkeypress="return onlyNumber2(event)" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>


            <!-- Radio para seleccionar el representante legal (después del bloque del padre) -->
            <?php if ($tipo === 'padre'): ?>
                
                <div class="card mb-4">
                        <div class="card-header" style="background-color: #c90000; color: white;">
                            <h5><i class="fas fa-user-tie mr-2"></i>Representante Legal</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>El representante legal es:</label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" id="repMadre" name="tipoRepresentante" class="custom-control-input" value="madre" checked>
                                            <label class="custom-control-label" for="repMadre">
                                                <i class="fas fa-female mr-1"></i> La Madre
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" id="repPadre" name="tipoRepresentante" class="custom-control-input" value="padre">
                                            <label class="custom-control-label" for="repPadre">
                                                <i class="fas fa-male mr-1"></i> El Padre
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" id="repOtro" name="tipoRepresentante" class="custom-control-input" value="otro">
                                            <label class="custom-control-label" for="repOtro">
                                                <i class="fas fa-user-tie mr-1"></i> Otro
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Botones para Volver y Guardar -->
        <div class="d-flex justify-content-between mt-4 mb-5">
            <a href="inscripcion.php" class="btn btn-outline-danger btn-lg">
                <i class='bx bx-arrow-back'></i> Volver a Inscripciones
            </a>
            <button type="submit" class="btn btn-danger btn-lg" id="btnEnviarFormulario">
                <i class='bx bxs-save'></i> Guardar Inscripción
            </button>
        </div>
    </form>
</div>

<?php include '../../layouts/footer.php'; ?>
<script src="../../../assets/js/solicitud_cupo.js"></script>
<script src="../../../assets/js/validacion.js"></script>
<script src="../../../assets/js/buscador_generico.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('input[name="tipoRepresentante"]');
    const seccionRepresentante = document.getElementById('seccionRepresentante');
    const camposRepresentante = seccionRepresentante ? seccionRepresentante.querySelectorAll('input, select, textarea') : [];

    function actualizarVisibilidad() {
        const seleccionado = document.querySelector('input[name="tipoRepresentante"]:checked');
        const valor = seleccionado ? seleccionado.value : 'madre';

        if (valor === 'otro') {
            // Mostrar sección de representante
            seccionRepresentante.classList.remove('d-none');

            // Marcar campos como requeridos
            camposRepresentante.forEach(campo => {
                campo.setAttribute('required', 'required');
            });
        } else {
            // Ocultar sección
            seccionRepresentante.classList.add('d-none');

            // Quitar required y limpiar valores
            camposRepresentante.forEach(campo => {
                campo.removeAttribute('required');
                if (campo.tagName === 'SELECT') {
                    campo.selectedIndex = 0;
                } else {
                    campo.value = '';
                }
            });
        }
    }

    // Escuchar cambios
    radios.forEach(radio => {
        radio.addEventListener('change', actualizarVisibilidad);
    });

    // Aplicar estado inicial
    actualizarVisibilidad();
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const selectNivel = document.getElementById("nivel");
    const selectCurso = document.getElementById("curso");
    const selectSeccion = document.getElementById("seccion");

    // Traemos todos los cursos desde PHP (vienen cargados al inicio del archivo)
    const cursosOriginales = <?= json_encode($cursos) ?>;
    const niveles = <?= json_encode($niveles) ?>;

    // === CUANDO CAMBIA EL NIVEL ===
    selectNivel.addEventListener("change", function() {
        const nivelSeleccionado = this.value.trim();
        selectCurso.innerHTML = '<option value="">Seleccione un curso</option>';

        if (nivelSeleccionado === "") {
            // Si no hay nivel seleccionado, mostramos todos los cursos
            cursosOriginales.forEach(curso => {
                const opt = document.createElement("option");
                opt.value = curso.IdCurso;
                opt.textContent = curso.curso;
                selectCurso.appendChild(opt);
            });
        } else {
            // Buscamos el nivel seleccionado
            const nivelObj = niveles.find(n => n.IdNivel == nivelSeleccionado);
            if (nivelObj) {
                // Filtramos los cursos que pertenecen a ese nivel
                const cursosFiltrados = cursosOriginales.filter(curso =>
                    parseInt(curso.IdNivel) === parseInt(nivelObj.IdNivel)
                );
                cursosFiltrados.forEach(curso => {
                    const opt = document.createElement("option");
                    opt.value = curso.IdCurso;
                    opt.textContent = curso.curso;
                    selectCurso.appendChild(opt);
                });
            }
        }

        // Limpiar secciones al cambiar nivel
        selectSeccion.selectedIndex = 0;
    });

    // === OPCIONAL: limpiar curso y sección al enviar formulario ===
    const form = document.querySelector("form");
    if (form) {
        form.addEventListener("submit", function() {
            // Previene errores si algo quedó vacío
            if (!selectNivel.value || !selectCurso.value || !selectSeccion.value) {
                Swal.fire({
                    title: "Campos incompletos",
                    text: "Debes seleccionar el nivel, curso y sección.",
                    icon: "warning",
                    confirmButtonColor: "#c90000"
                });
                event.preventDefault();
            }
        });
    }
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const selectNivel = document.getElementById("nivel");
    const cedulaContainer = document.getElementById("estudianteCedulaContainer");
    const telefonoContainer = document.getElementById("estudianteTelefonoContainer");

    // Función para mostrar u ocultar los campos según el nivel
    function actualizarCamposEstudiante() {
        const nivelSeleccionado = parseInt(selectNivel.value);

        if (nivelSeleccionado === 1) {
            // Ocultar solo cédula y teléfono
            cedulaContainer.style.display = "none";
            telefonoContainer.style.display = "none";

            // Quitar "required" para evitar errores de validación
            document.getElementById("estudianteCedula").removeAttribute("required");
            document.getElementById("estudianteTelefono").removeAttribute("required");

            // Limpiar valores si se desea
            document.getElementById("estudianteCedula").value = "";
            document.getElementById("estudianteTelefono").value = "";
        } else {
            // Mostrar cédula y teléfono
            cedulaContainer.style.display = "";
            telefonoContainer.style.display = "";

            // Restaurar "required"
            document.getElementById("estudianteCedula").setAttribute("required", "required");
            document.getElementById("estudianteTelefono").setAttribute("required", "required");
        }
    }

    // Escuchar cambios en el select de nivel
    selectNivel.addEventListener("change", actualizarCamposEstudiante);

    // Aplicar estado inicial al cargar la página
    actualizarCamposEstudiante();
});
</script>