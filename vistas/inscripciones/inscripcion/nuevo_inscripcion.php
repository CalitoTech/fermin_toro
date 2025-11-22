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
require_once __DIR__ . '/../../../modelos/TipoTrabajador.php';
require_once __DIR__ . '/../../../modelos/Plantel.php';
require_once __DIR__ . '/../../../modelos/FechaEscolar.php';

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
$modeloTipoTrabajador = new TipoTrabajador($conexion);
$modeloPlantel = new Plantel($conexion);
$fechaEscolarModel = new FechaEscolar($conexion);

// Obtener datos sin filtro
$nacionalidades = $modeloNacionalidad->obtenerConNombresLargos();
$sexos = $modeloSexo->obtenerTodos();
$secciones = $modeloSeccion->obtenerTodos();
$urbanismos = $modeloUrbanismo->obtenerTodos();
$parentescos = $modeloParentesco->obtenerTodos();
$listaStatus = $statusModel->obtenerStatusInscripcion();
$tiposInscripcion = $tipoInscripcionModel->obtenerTodos();
$tiposTrabajador = $modeloTipoTrabajador->obtenerTodos();
$planteles = $modeloPlantel->obtenerTodos();
$añoEscolarActivo = $fechaEscolarModel->obtenerActivo();
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



<div class="container mt-4" style="min-height: 80vh;">
    <form id="formInscripcion" data-origen="pagina" method="POST">
        <!-- Hidden fields para formulario de estudiante regular -->
        <input type="hidden" name="IdEstudiante" id="IdEstudiante" value="">
        <input type="hidden" name="IdFechaEscolar" id="IdFechaEscolar" value="<?= $añoEscolarActivo['IdFecha_Escolar'] ?? '' ?>">
        <input type="hidden" name="IdCurso" id="IdCurso" value="">
        <input type="hidden" name="idTipoInscripcion" id="idTipoInscripcion" value="1">
        <input type="hidden" name="origen" id="origen" value="administrativo">

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

                    <!-- Contenedor para Nuevo Ingreso -->
                    <div id="nuevoIngresoContainer" class="col-12" style="display: none;">
                        <div class="row g-3">
                            <!-- Nivel -->
                            <div class="col-md-4">
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
                            <div class="col-md-4">
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
                            <div class="col-md-4">
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

                    <!-- Contenedor para Estudiante Regular (Prosecución) -->
                    <div id="regularContainer" class="col-12" style="display: none;">
                        <div class="row g-3">
                            <!-- Buscador de Estudiante -->
                            <div class="col-12">
                                <div class="form-group required-field">
                                    <label for="buscadorEstudiante">
                                        <i class='bx bx-search'></i> Buscar Estudiante
                                    </label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control buscador-input" id="buscadorEstudiante"
                                               autocomplete="off" placeholder="Escriba cédula, nombre o apellido del estudiante...">
                                        <div id="resultadosBusqueda" class="autocomplete-results d-none"></div>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Busque al estudiante que desea inscribir
                                    </small>
                                </div>
                            </div>

                            <!-- Información del estudiante (se muestra después de seleccionar) -->
                            <div id="infoEstudianteContainer" class="col-12" style="display: none;">
                                <div class="alert alert-info">
                                    <h6><i class='bx bx-user'></i> Estudiante Seleccionado</h6>
                                    <p class="mb-1"><strong>Nombre:</strong> <span id="infoEstudianteNombre"></span></p>
                                    <p class="mb-1"><strong>Curso Actual:</strong> <span id="infoCursoActual"></span></p>
                                </div>

                                <!-- Pregunta si repite curso -->
                                <div class="form-group required-field mb-3">
                                    <label>¿El estudiante repite el curso actual?</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="custom-control custom-radio">
                                                <input type="radio" id="repiteNo" name="repiteCurso" class="custom-control-input" value="0" checked>
                                                <label class="custom-control-label" for="repiteNo">
                                                    No, pasa al siguiente curso
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="custom-control custom-radio">
                                                <input type="radio" id="repiteSi" name="repiteCurso" class="custom-control-input" value="1">
                                                <label class="custom-control-label" for="repiteSi">
                                                    Sí, repite el curso actual
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Curso (readonly) -->
                                <div class="form-group mb-3">
                                    <label for="cursoRegular">
                                        <i class='bx bxs-graduation'></i> Curso
                                        <span class="badge badge-secondary">Solo lectura</span>
                                    </label>
                                    <input type="text" class="form-control" id="cursoRegular" readonly
                                           style="background-color: #e9ecef; cursor: not-allowed;">
                                </div>

                                <!-- Sección (editable) -->
                                <div class="form-group required-field mb-3">
                                    <label for="seccionRegular">
                                        <i class='bx bx-group'></i> Sección
                                        <span style="color: #dc3545;"></span>
                                    </label>
                                    <select name="IdCursoSeccion" id="seccionRegular" class="form-control" required>
                                        <option value="">Seleccione una sección</option>
                                    </select>
                                    <small class="text-muted">Puede cambiar la sección si lo desea</small>
                                </div>

                                <!-- Status (solo Pendiente de pago e Inscrito) -->
                                <div class="form-group required-field mb-3">
                                    <label for="statusRegular">
                                        <i class='bx bx-info-circle'></i> Status
                                        <span style="color: #dc3545;"></span>
                                    </label>
                                    <select name="idStatus" id="statusRegular" class="form-control" required>
                                        <option value="">Seleccione un status</option>
                                        <?php foreach ($listaStatus as $status): ?>
                                            <?php if ($status['IdStatus'] == 10 || $status['IdStatus'] == 11): ?>
                                                <option value="<?= htmlspecialchars($status['IdStatus']) ?>">
                                                    <?= htmlspecialchars($status['status']) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
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
                                   onkeypress="return onlyText(event)" oninput="formatearTexto2()" placeholder="Ej: Pérez González" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group required-field">
                            <label for="estudianteNombres">Nombres</label>
                            <input type="text" class="form-control" id="estudianteNombres" name="estudianteNombres"
                                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" minlength="3" maxlength="40"
                                   onkeypress="return onlyText(event)" oninput="formatearTexto1()" placeholder="Ej: Juan Carlos" required>
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
                                    <option value="<?= $nacionalidad['IdNacionalidad'] ?>"><?= $nacionalidad['nombre_largo'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3" id="estudianteCedulaContainer">
                        <div class="form-group required-field">
                            <label for="estudianteCedula">Cédula</label>
                            <input type="text" class="form-control" id="estudianteCedula" name="estudianteCedula"
                                   minlength="7" maxlength="8" pattern="[0-9]+" onkeypress="return onlyNumber(event)" placeholder="Ej: 12345678" required>
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
                                   minlength="3" maxlength="40" oninput="formatearTexto1()" placeholder="Ej: Araure, Portuguesa" required>
                        </div>
                    </div>
                    <div class="col-md-6" id="estudianteTelefonoContainer">
                        <div class="form-group required-field">
                            <label for="estudianteTelefono">Teléfono</label>
                            <div class="input-group">
                                <!-- Prefix selector -->
                                <div class="position-relative" style="max-width: 100px;">
                                    <input type="text" class="form-control buscador-input text-center fw-bold prefijo-telefono"
                                           id="estudianteTelefonoPrefijo_input" maxlength="4" data-prefijo-tipo="internacional"
                                           onkeypress="return /[0-9+]/.test(event.key)"
                                           oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                                           style="border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: none; background: #f8f9fa; color: #c90000;">
                                    <input type="hidden" id="estudianteTelefonoPrefijo" name="estudianteTelefonoPrefijo" required>
                                    <input type="hidden" id="estudianteTelefonoPrefijo_nombre" name="estudianteTelefonoPrefijo_nombre">
                                    <div id="estudianteTelefonoPrefijo_resultados" class="autocomplete-results d-none"></div>
                                </div>

                                <!-- Phone number input -->
                                <input type="tel" class="form-control" id="estudianteTelefono" name="estudianteTelefono"
                                       minlength="10" maxlength="10" pattern="[0-9]+" onkeypress="return onlyNumber2(event)" placeholder="4121234567" required
                                       style="border-top-left-radius: 0; border-bottom-left-radius: 0;">

                                <!-- Phone icon -->
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            </div>

                            <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                const buscador = new BuscadorGenerico(
                                    "estudianteTelefonoPrefijo_input",
                                    "estudianteTelefonoPrefijo_resultados",
                                    "prefijo",
                                    "estudianteTelefonoPrefijo",
                                    "estudianteTelefonoPrefijo_nombre"
                                );

                                // Establecer valor por defecto
                                const inputPrefijo = document.getElementById("estudianteTelefonoPrefijo_input");
                                inputPrefijo.value = "+58";

                                // Formatear prefijo: evitar que se borre el +
                                inputPrefijo.addEventListener("input", function(e) {
                                    let valor = this.value;
                                    if (!valor.startsWith("+")) {
                                        this.value = "+" + valor.replace(/\+/g, "");
                                    }
                                    if (valor.indexOf("+") > 0) {
                                        this.value = "+" + valor.replace(/\+/g, "");
                                    }
                                });
                                inputPrefijo.addEventListener("keydown", function(e) {
                                    if (this.value === "+" && (e.key === "Backspace" || e.key === "Delete")) {
                                        e.preventDefault();
                                    }
                                });

                                // Buscar el ID del prefijo por defecto
                                const baseUrl = buscador.baseUrl;
                                fetch(`${baseUrl}?tipo=prefijo&q=%2B58&filtro=internacional`)
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data && data.length > 0) {
                                            const prefijoEncontrado = data.find(p => p.codigo_prefijo === "+58");
                                            if (prefijoEncontrado) {
                                                document.getElementById("estudianteTelefonoPrefijo").value = prefijoEncontrado.IdPrefijo;
                                            }
                                        }
                                    })
                                    .catch(err => console.error("Error al cargar prefijo por defecto:", err));
                            });
                            </script>
                        </div>
                    </div>
                </div>

                <div class="form-group required-field">
                    <label for="estudianteCorreo">Correo Electrónico</label>
                    <input type="email" class="form-control" id="estudianteCorreo" name="estudianteCorreo" minlength="10" maxlength="50" placeholder="Ej: estudiante@correo.com" required>
                </div>

                <div class="form-group required-field">
                    <label for="estudiantePlantel">Plantel donde cursó el último año escolar</label>
                    <div class="position-relative">
                        <input type="text" class="form-control buscador-input" id="estudiantePlantel_input" autocomplete="off" placeholder="Buscar o escribir nuevo plantel...">
                        <input type="hidden" id="estudiantePlantel" name="estudiantePlantel" required>
                        <input type="hidden" id="estudiantePlantel_nombre" name="estudiantePlantel_nombre">
                        <div id="estudiantePlantel_resultados" class="autocomplete-results d-none"></div>
                    </div>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Busque o escriba el nombre del plantel educativo
                    </small>
                    <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        new BuscadorGenerico(
                            "estudiantePlantel_input",
                            "estudiantePlantel_resultados",
                            "plantel",
                            "estudiantePlantel",
                            "estudiantePlantel_nombre"
                        );
                    });
                    </script>
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
                                            <option value="<?= $nacionalidad['IdNacionalidad'] ?>"><?= $nacionalidad['nombre_largo'] ?></option>
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
                                <?php elseif ($campo === 'TelefonoHabitacion' || $campo === 'Celular'): ?>
                                    <?php
                                    $prefijoInputId = $tipo . $campo . 'Prefijo_input';
                                    $prefijoHiddenId = $tipo . $campo . 'Prefijo';
                                    $prefijoHiddenNombre = $tipo . $campo . 'Prefijo_nombre';
                                    $prefijoResultadosId = $tipo . $campo . 'Prefijo_resultados';
                                    $telefonoId = $tipo . $campo;

                                    // Configuración según tipo de teléfono
                                    $prefijoTipo = ($campo === 'TelefonoHabitacion') ? 'fijo' : 'internacional';
                                    $prefijoDefault = ($campo === 'TelefonoHabitacion') ? '0255' : '+58';
                                    $minLength = ($campo === 'TelefonoHabitacion') ? '7' : '10';
                                    $maxLength = ($campo === 'TelefonoHabitacion') ? '7' : '10';
                                    ?>
                                    <div class="input-group">
                                        <!-- Prefix selector -->
                                        <div class="position-relative" style="max-width: 100px;">
                                            <input type="text" class="form-control buscador-input text-center fw-bold prefijo-telefono"
                                                   id="<?= $prefijoInputId ?>" maxlength="4" data-prefijo-tipo="<?= $prefijoTipo ?>"
                                                   onkeypress="return /[0-9+]/.test(event.key)"
                                                   oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                                                   style="border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: none; background: #f8f9fa; color: #c90000;">
                                            <input type="hidden" id="<?= $prefijoHiddenId ?>" name="<?= $prefijoHiddenId ?>" required>
                                            <input type="hidden" id="<?= $prefijoHiddenNombre ?>" name="<?= $prefijoHiddenNombre ?>">
                                            <div id="<?= $prefijoResultadosId ?>" class="autocomplete-results d-none"></div>
                                        </div>

                                        <!-- Phone number input -->
                                        <input type="tel" class="form-control" id="<?= $telefonoId ?>" name="<?= $telefonoId ?>"
                                               minlength="<?= $minLength ?>" maxlength="<?= $maxLength ?>" pattern="[0-9]+" onkeypress="return onlyNumber2(event)" required
                                               style="border-top-left-radius: 0; border-bottom-left-radius: 0;">

                                        <!-- Phone icon -->
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    </div>

                                    <script>
                                    document.addEventListener("DOMContentLoaded", function() {
                                        const buscador = new BuscadorGenerico(
                                            "<?= $prefijoInputId ?>",
                                            "<?= $prefijoResultadosId ?>",
                                            "prefijo",
                                            "<?= $prefijoHiddenId ?>",
                                            "<?= $prefijoHiddenNombre ?>"
                                        );

                                        // Establecer valor por defecto
                                        const inputPrefijo = document.getElementById("<?= $prefijoInputId ?>");
                                        inputPrefijo.value = "<?= $prefijoDefault ?>";

                                        // Formatear prefijo para internacionales: evitar que se borre el +
                                        <?php if ($prefijoTipo === 'internacional'): ?>
                                        inputPrefijo.addEventListener("input", function(e) {
                                            let valor = this.value;
                                            if (!valor.startsWith("+")) {
                                                this.value = "+" + valor.replace(/\+/g, "");
                                            }
                                            if (valor.indexOf("+") > 0) {
                                                this.value = "+" + valor.replace(/\+/g, "");
                                            }
                                        });
                                        inputPrefijo.addEventListener("keydown", function(e) {
                                            if (this.value === "+" && (e.key === "Backspace" || e.key === "Delete")) {
                                                e.preventDefault();
                                            }
                                        });
                                        <?php endif; ?>

                                        // Buscar el ID del prefijo por defecto
                                        const baseUrl = buscador.baseUrl;
                                        const prefijoEncoded = encodeURIComponent("<?= $prefijoDefault ?>");
                                        fetch(`${baseUrl}?tipo=prefijo&q=${prefijoEncoded}&filtro=<?= $prefijoTipo ?>`)
                                            .then(res => res.json())
                                            .then(data => {
                                                if (data && data.length > 0) {
                                                    const prefijoEncontrado = data.find(p => p.codigo_prefijo === "<?= $prefijoDefault ?>");
                                                    if (prefijoEncontrado) {
                                                        document.getElementById("<?= $prefijoHiddenId ?>").value = prefijoEncontrado.IdPrefijo;
                                                    }
                                                }
                                            })
                                            .catch(err => console.error("Error al cargar prefijo por defecto:", err));
                                    });
                                    </script>
                                <?php else: ?>
                                    <input type="<?= $tipo_input ?>" class="form-control" id="<?= $tipo . $campo ?>" name="<?= $tipo . $campo ?>" required>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Campo Tipo de Trabajador -->
                    <div class="form-group required-field mt-3">
                        <label>Tipo de Trabajador</label>
                        <div class="row">
                            <?php foreach ($tiposTrabajador as $tipoTrab): ?>
                                <div class="col-md-6">
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="<?= $tipo ?>TipoTrabajador_<?= $tipoTrab['IdTipoTrabajador'] ?>"
                                               name="<?= $tipo ?>TipoTrabajador"
                                               class="custom-control-input"
                                               value="<?= $tipoTrab['IdTipoTrabajador'] ?>" required>
                                        <label class="custom-control-label" for="<?= $tipo ?>TipoTrabajador_<?= $tipoTrab['IdTipoTrabajador'] ?>">
                                            <?= htmlspecialchars($tipoTrab['tipo_trabajador']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
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
                                    <div class="input-group">
                                        <!-- Prefix selector -->
                                        <div class="position-relative" style="max-width: 100px;">
                                            <input type="text" class="form-control buscador-input text-center fw-bold prefijo-telefono"
                                                   id="emergenciaCelularPrefijo_input" maxlength="4" data-prefijo-tipo="internacional"
                                                   onkeypress="return /[0-9+]/.test(event.key)"
                                                   oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                                                   style="border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: none; background: #f8f9fa; color: #c90000;">
                                            <input type="hidden" id="emergenciaCelularPrefijo" name="emergenciaCelularPrefijo" required>
                                            <input type="hidden" id="emergenciaCelularPrefijo_nombre" name="emergenciaCelularPrefijo_nombre">
                                            <div id="emergenciaCelularPrefijo_resultados" class="autocomplete-results d-none"></div>
                                        </div>

                                        <!-- Phone number input -->
                                        <input type="tel" class="form-control" id="emergenciaCelular" name="emergenciaCelular"
                                               minlength="10" maxlength="10" pattern="[0-9]+" onkeypress="return onlyNumber2(event)" required
                                               style="border-top-left-radius: 0; border-bottom-left-radius: 0;">

                                        <!-- Phone icon -->
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    </div>

                                    <script>
                                    document.addEventListener("DOMContentLoaded", function() {
                                        const buscador = new BuscadorGenerico(
                                            "emergenciaCelularPrefijo_input",
                                            "emergenciaCelularPrefijo_resultados",
                                            "prefijo",
                                            "emergenciaCelularPrefijo",
                                            "emergenciaCelularPrefijo_nombre"
                                        );

                                        // Establecer valor por defecto
                                        const inputPrefijo = document.getElementById("emergenciaCelularPrefijo_input");
                                        inputPrefijo.value = "+58";

                                        // Formatear prefijo: evitar que se borre el +
                                        inputPrefijo.addEventListener("input", function(e) {
                                            let valor = this.value;
                                            if (!valor.startsWith("+")) {
                                                this.value = "+" + valor.replace(/\+/g, "");
                                            }
                                            if (valor.indexOf("+") > 0) {
                                                this.value = "+" + valor.replace(/\+/g, "");
                                            }
                                        });
                                        inputPrefijo.addEventListener("keydown", function(e) {
                                            if (this.value === "+" && (e.key === "Backspace" || e.key === "Delete")) {
                                                e.preventDefault();
                                            }
                                        });

                                        // Buscar el ID del prefijo por defecto
                                        const baseUrl = buscador.baseUrl;
                                        fetch(`${baseUrl}?tipo=prefijo&q=%2B58&filtro=internacional`)
                                            .then(res => res.json())
                                            .then(data => {
                                                if (data && data.length > 0) {
                                                    const prefijoEncontrado = data.find(p => p.codigo_prefijo === "+58");
                                                    if (prefijoEncontrado) {
                                                        document.getElementById("emergenciaCelularPrefijo").value = prefijoEncontrado.IdPrefijo;
                                                    }
                                                }
                                            })
                                            .catch(err => console.error("Error al cargar prefijo por defecto:", err));
                                    });
                                    </script>
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
        <div class="d-flex justify-content-between mt-4 mb-5" id="botonesFormulario">
            <a href="inscripcion.php" class="btn btn-outline-danger btn-lg">
                <i class='bx bx-arrow-back'></i> Volver a Inscripciones
            </a>
            <button type="submit" class="btn btn-danger btn-lg" id="btnEnviarFormulario" style="display: none;">
                <i class='bx bxs-save'></i> Guardar Inscripción
            </button>
        </div>
    </form>
</div>

<?php include '../../layouts/footer.php'; ?>

<?php if ($alert): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: '<?= $alert === 'success' ? 'success' : 'error' ?>',
        title: '<?= $alert === 'success' ? '¡Éxito!' : 'Error' ?>',
        text: '<?= addslashes($message) ?>',
        confirmButtonColor: '<?= $alert === 'success' ? '#28a745' : '#c90000' ?>',
        confirmButtonText: 'Entendido'
    });
});
</script>
<?php endif; ?>

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
        form.addEventListener("submit", function(event) {
            let errores = [];

            // Validar nivel, curso y sección
            if (!selectNivel.value || !selectCurso.value || !selectSeccion.value) {
                errores.push('Debes seleccionar el nivel, curso y sección');
            }

            // Validar fecha de nacimiento (6-18 años)
            const fechaNacimiento = document.getElementById('estudianteFechaNacimiento').value;
            if (fechaNacimiento) {
                const hoy = new Date();
                const fechaNac = new Date(fechaNacimiento);
                let edad = hoy.getFullYear() - fechaNac.getFullYear();
                const mes = hoy.getMonth() - fechaNac.getMonth();

                if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNac.getDate())) {
                    edad--;
                }

                if (edad < 6 || edad > 18) {
                    errores.push('La edad del estudiante debe estar entre 6 y 18 años');
                    document.getElementById('estudianteFechaNacimiento').classList.add('is-invalid');
                }
            }

            // Validar prefijos de teléfono
            const validarPrefijo = (prefijoId, telefonoId, nombre) => {
                const telefono = document.getElementById(telefonoId);
                const prefijo = document.getElementById(prefijoId);

                if (telefono && telefono.value && prefijo && !prefijo.value) {
                    errores.push(`${nombre} es obligatorio cuando se ingresa un número de teléfono`);
                    const prefijoInput = document.getElementById(prefijoId + '_input');
                    if (prefijoInput) {
                        prefijoInput.classList.add('is-invalid');
                    }
                }
            };

            // Validar prefijo del teléfono del estudiante
            const nivelSeleccionado = parseInt(selectNivel.value);
            if (nivelSeleccionado !== 1) {
                validarPrefijo('estudianteTelefonoPrefijo', 'estudianteTelefono', 'El prefijo del teléfono del estudiante');
            }

            // Validar prefijos de padre, madre y emergencia
            validarPrefijo('padreTelefonoHabitacionPrefijo', 'padreTelefonoHabitacion', 'El prefijo del teléfono de habitación del padre');
            validarPrefijo('padreCelularPrefijo', 'padreCelular', 'El prefijo del celular del padre');
            validarPrefijo('madreTelefonoHabitacionPrefijo', 'madreTelefonoHabitacion', 'El prefijo del teléfono de habitación de la madre');
            validarPrefijo('madreCelularPrefijo', 'madreCelular', 'El prefijo del celular de la madre');
            validarPrefijo('emergenciaCelularPrefijo', 'emergenciaCelular', 'El prefijo del teléfono de emergencia');

            // Validar prefijos del representante si es "otro"
            const tipoRepSeleccionado = document.querySelector('input[name="tipoRepresentante"]:checked');
            if (tipoRepSeleccionado && tipoRepSeleccionado.value === 'otro') {
                validarPrefijo('representanteTelefonoHabitacionPrefijo', 'representanteTelefonoHabitacion', 'El prefijo del teléfono de habitación del representante');
                validarPrefijo('representanteCelularPrefijo', 'representanteCelular', 'El prefijo del celular del representante');
            }

            // Validar cédulas duplicadas
            const cedulas = {};
            const cedulasParaValidar = [
                { id: 'estudianteCedula', nacionalidadId: 'estudianteNacionalidad', nombre: 'Estudiante' },
                { id: 'padreCedula', nacionalidadId: 'padreNacionalidad', nombre: 'Padre' },
                { id: 'madreCedula', nacionalidadId: 'madreNacionalidad', nombre: 'Madre' }
            ];

            if (tipoRepSeleccionado && tipoRepSeleccionado.value === 'otro') {
                cedulasParaValidar.push({
                    id: 'representanteCedula',
                    nacionalidadId: 'representanteNacionalidad',
                    nombre: 'Representante Legal'
                });
            }

            cedulasParaValidar.forEach(persona => {
                const cedula = document.getElementById(persona.id)?.value;
                const nacionalidad = document.getElementById(persona.nacionalidadId)?.value;

                if (cedula && nacionalidad) {
                    const cedulaCompleta = nacionalidad + '-' + cedula;

                    if (cedulas[cedulaCompleta]) {
                        errores.push(`La cédula ${cedulaCompleta} está duplicada (${cedulas[cedulaCompleta]} y ${persona.nombre})`);
                        document.getElementById(persona.id).classList.add('is-invalid');
                    } else {
                        cedulas[cedulaCompleta] = persona.nombre;
                    }
                }
            });

            // Mostrar errores si existen
            if (errores.length > 0) {
                event.preventDefault();
                Swal.fire({
                    title: "Datos incompletos",
                    html: '<ul style="text-align: left;">' + errores.map(e => '<li>' + e + '</li>').join('') + '</ul>',
                    icon: "warning",
                    confirmButtonColor: "#c90000"
                });
                return;
            }

            // Validar acceso de representantes
            const cedulasRepresentantes = [];

            if (document.getElementById('padreCedula').value && document.getElementById('padreNacionalidad').value) {
                cedulasRepresentantes.push({
                    cedula: document.getElementById('padreCedula').value,
                    nacionalidad: document.getElementById('padreNacionalidad').value,
                    nombre: 'Padre'
                });
            }

            if (document.getElementById('madreCedula').value && document.getElementById('madreNacionalidad').value) {
                cedulasRepresentantes.push({
                    cedula: document.getElementById('madreCedula').value,
                    nacionalidad: document.getElementById('madreNacionalidad').value,
                    nombre: 'Madre'
                });
            }

            if (tipoRepSeleccionado && tipoRepSeleccionado.value === 'otro' &&
                document.getElementById('representanteCedula').value &&
                document.getElementById('representanteNacionalidad').value) {
                cedulasRepresentantes.push({
                    cedula: document.getElementById('representanteCedula').value,
                    nacionalidad: document.getElementById('representanteNacionalidad').value,
                    nombre: 'Representante Legal'
                });
            }

            // Si hay representantes para validar
            if (cedulasRepresentantes.length > 0) {
                event.preventDefault();

                fetch('../../controladores/PersonaController.php?action=verificarAccesoRepresentantes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(cedulasRepresentantes)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.representantesConAcceso.length > 0) {
                        const nombres = data.representantesConAcceso.map(r => r.nombre).join(', ');
                        const plural = data.representantesConAcceso.length > 1;

                        Swal.fire({
                            icon: 'info',
                            title: 'Acceso al sistema detectado',
                            html: `
                                <div style="text-align: left;">
                                    <p><strong>${plural ? 'Los representantes' : 'El representante'} ${nombres} ${plural ? 'tienen' : 'tiene'} acceso al sistema.</strong></p>
                                    <p>Por favor, ${plural ? 'que inicien' : 'que inicie'} sesión en ${plural ? 'sus cuentas' : 'su cuenta'} y realicen la solicitud de inscripción desde allí.</p>
                                    <p class="text-muted small mt-3">
                                        <i class="fas fa-info-circle"></i>
                                        ${plural ? 'Ellos pueden' : 'Puede'} acceder al sistema desde la página de inicio y gestionar la inscripción directamente.
                                    </p>
                                </div>
                            `,
                            confirmButtonColor: '#c90000',
                            confirmButtonText: 'Entendido',
                            showCloseButton: true
                        });
                    } else {
                        // Si no hay representantes con acceso, enviar el formulario
                        event.target.submit();
                    }
                })
                .catch(error => {
                    console.error('Error al verificar acceso:', error);
                    // Si hay error, permitir envío
                    event.target.submit();
                });
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

    // Configurar restricciones de fecha de nacimiento (6-18 años)
    const fechaNacimientoInput = document.getElementById('estudianteFechaNacimiento');
    if (fechaNacimientoInput) {
        const hoy = new Date();

        // Fecha máxima: hace 6 años
        const fechaMax = new Date(hoy.getFullYear() - 6, hoy.getMonth(), hoy.getDate());

        // Fecha mínima: hace 18 años
        const fechaMin = new Date(hoy.getFullYear() - 18, hoy.getMonth(), hoy.getDate());

        // Formatear fechas a YYYY-MM-DD
        const formatoFecha = (fecha) => {
            const year = fecha.getFullYear();
            const month = String(fecha.getMonth() + 1).padStart(2, '0');
            const day = String(fecha.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        fechaNacimientoInput.setAttribute('min', formatoFecha(fechaMin));
        fechaNacimientoInput.setAttribute('max', formatoFecha(fechaMax));

        // Validar cuando se cambia el valor
        fechaNacimientoInput.addEventListener('blur', function() {
            const valorSeleccionado = this.value;
            if (!valorSeleccionado) return;

            const fechaSeleccionada = new Date(valorSeleccionado + 'T00:00:00');
            const hoy = new Date();

            // Calcular edad
            let edad = hoy.getFullYear() - fechaSeleccionada.getFullYear();
            const mes = hoy.getMonth() - fechaSeleccionada.getMonth();

            if (mes < 0 || (mes === 0 && hoy.getDate() < fechaSeleccionada.getDate())) {
                edad--;
            }

            // Validar rango de edad
            if (edad < 6 || edad > 18) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Edad no válida',
                    html: `La fecha de nacimiento seleccionada no es válida.<br><br>
                           <strong>El estudiante debe tener entre 6 y 18 años.</strong><br>
                           <small class="text-muted">Edad calculada: ${edad} años</small>`,
                    confirmButtonColor: '#c90000',
                    confirmButtonText: 'Entendido'
                });
                this.value = '';
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }

    // === MANEJO DINÁMICO DEL TIPO DE INSCRIPCIÓN ===
    const tipoInscripcionSelect = document.getElementById('tipoInscripcion');
    const nuevoIngresoContainer = document.getElementById('nuevoIngresoContainer');
    const regularContainer = document.getElementById('regularContainer');

    // Ocultar TODO el formulario inicialmente (excepto el primer card de "Datos de Inscripción")
    document.querySelectorAll('.card.mb-4, .card.mb-3').forEach((card, index) => {
        // El primer card es "Datos de Inscripción" (index 0), mantenerlo visible
        // Todos los demás se ocultan (estudiante, madre, padre, representante, contacto emergencia)
        if (index > 0) {
            card.style.display = 'none';
        }
    });

    tipoInscripcionSelect.addEventListener('change', function() {
        const tipoSeleccionado = parseInt(this.value);
        const form = document.getElementById('formInscripcion');
        const btnGuardar = document.getElementById('btnEnviarFormulario');

        // Actualizar hidden field
        document.getElementById('idTipoInscripcion').value = tipoSeleccionado;

        // Limpiar formularios
        if (document.getElementById('buscadorEstudiante')) {
            document.getElementById('buscadorEstudiante').value = '';
        }
        document.getElementById('IdEstudiante').value = '';
        if (document.getElementById('infoEstudianteContainer')) {
            document.getElementById('infoEstudianteContainer').style.display = 'none';
        }

        if (tipoSeleccionado === 1) {
            // Nuevo Ingreso - Mostrar formulario completo tradicional
            nuevoIngresoContainer.style.display = 'flex';
            regularContainer.style.display = 'none';
            btnGuardar.style.display = 'inline-block'; // Mostrar botón de guardar

            // Cambiar action del formulario al endpoint tradicional
            // form.action = '../../../controladores/InscripcionController.php?action=guardarInscripcion';
            // form.method = 'POST';

            // Mostrar formulario completo de estudiante y padres (todos los cards excepto el de inscripción que ya está visible)
            document.querySelectorAll('.card.mb-4, .card.mb-3').forEach((card, index) => {
                if (index > 0) card.style.display = 'block';
            });

            // Hacer required los campos del formulario tradicional
            const nivelSelect = document.getElementById('nivel');
            const cursoSelect = document.getElementById('curso');
            const statusSelect = document.getElementById('IdStatus');
            if (nivelSelect) nivelSelect.setAttribute('required', 'required');
            if (cursoSelect) cursoSelect.setAttribute('required', 'required');
            if (statusSelect) statusSelect.setAttribute('required', 'required');

            // Remover required de campos de estudiante regular
            const seccionRegular = document.getElementById('seccionRegular');
            const statusRegular = document.getElementById('statusRegular');
            if (seccionRegular) seccionRegular.removeAttribute('required');
            if (statusRegular) statusRegular.removeAttribute('required');

        } else if (tipoSeleccionado === 2) {
            // Estudiante Regular (Prosecución) - Mostrar buscador simple
            nuevoIngresoContainer.style.display = 'none';
            regularContainer.style.display = 'flex';
            btnGuardar.style.display = 'inline-block'; // Mostrar botón de guardar

            // Cambiar action del formulario al endpoint de renovación
            form.action = '../../../controladores/representantes/procesar_renovacion.php';
            form.method = 'POST';

            // Ocultar formulario completo
            document.querySelectorAll('.card.mb-4').forEach((card, index) => {
                if (index > 0) card.style.display = 'none';
            });

            // Remover required del formulario tradicional
            const nivelSelect = document.getElementById('nivel');
            const cursoSelect = document.getElementById('curso');
            const statusSelect = document.getElementById('IdStatus');
            if (nivelSelect) nivelSelect.removeAttribute('required');
            if (cursoSelect) cursoSelect.removeAttribute('required');
            if (statusSelect) statusSelect.removeAttribute('required');

            // Hacer required los campos de estudiante regular
            const seccionRegular = document.getElementById('seccionRegular');
            const statusRegular = document.getElementById('statusRegular');
            if (seccionRegular) seccionRegular.setAttribute('required', 'required');
            if (statusRegular) statusRegular.setAttribute('required', 'required');

        } else {
            // Sin selección - Ocultar todo excepto el card de "Datos de Inscripción"
            nuevoIngresoContainer.style.display = 'none';
            regularContainer.style.display = 'none';
            btnGuardar.style.display = 'none'; // Ocultar botón de guardar
            form.action = '';

            document.querySelectorAll('.card.mb-4, .card.mb-3').forEach((card, index) => {
                if (index > 0) card.style.display = 'none';
            });
        }
    });

    // === INTERCEPTAR SUBMIT PARA TIPO REGULAR ===
    // Este listener se ejecuta ANTES que el de solicitud_cupo.js
    document.getElementById('btnEnviarFormulario').addEventListener('click', function(e) {
        const tipoInscripcion = parseInt(document.getElementById('idTipoInscripcion').value || 1);

        // Si es inscripción regular (tipo 2), validar y enviar directamente
        if (tipoInscripcion === 2) {
            e.preventDefault();
            e.stopImmediatePropagation(); // Detener otros listeners

            // Validaciones simples para tipo regular
            const idEstudiante = document.getElementById('IdEstudiante').value;
            const seccionRegular = document.getElementById('seccionRegular').value;
            const statusRegular = document.getElementById('statusRegular').value;

            if (!idEstudiante) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Datos incompletos',
                    text: 'Debe seleccionar un estudiante',
                    confirmButtonColor: '#c90000'
                });
                return;
            }

            if (!seccionRegular) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Datos incompletos',
                    text: 'Debe seleccionar una sección',
                    confirmButtonColor: '#c90000'
                });
                return;
            }

            if (!statusRegular) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Datos incompletos',
                    text: 'Debe seleccionar un status',
                    confirmButtonColor: '#c90000'
                });
                return;
            }

            // Si todas las validaciones pasan, enviar el formulario
            document.getElementById('formInscripcion').submit();
        }
        // Si es tipo 1 (nuevo ingreso), dejar que solicitud_cupo.js maneje la validación
    }, true); // useCapture = true para ejecutarse primero

    // === BUSCADOR DE ESTUDIANTE PARA PROSECUCIÓN ===
    // Usa tipo 'estudiante_regular' que solo muestra estudiantes con inscripción "Inscrito" del año anterior
    if (document.getElementById('buscadorEstudiante')) {
        const buscadorEstudiante = new BuscadorGenerico(
            'buscadorEstudiante',
            'resultadosBusqueda',
            'estudiante_regular',
            'IdEstudiante'
        );

        const inputBuscador = document.getElementById('buscadorEstudiante');
        inputBuscador.addEventListener('itemSeleccionado', async function(e) {
            // Usar IdEstudiante si está disponible, sino usar IdPersona
            const idEstudiante = e.detail?.IdEstudiante || e.detail?.IdPersona;
            if (idEstudiante) {

                try {
                    // Obtener información del curso siguiente
                    const response = await fetch(`../../../controladores/PersonaController.php?action=obtenerCursoSiguiente&idEstudiante=${idEstudiante}`);
                    const data = await response.json();

                    if (!data.success) {
                        if (data.graduado) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Estudiante Graduado',
                                text: data.mensaje || 'Este estudiante ya completó todos los cursos disponibles',
                                confirmButtonColor: '#c90000'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.error || 'No se pudo obtener la información del estudiante',
                                confirmButtonColor: '#c90000'
                            });
                        }
                        return;
                    }

                    // Guardar datos en variables globales
                    window.estudianteData = data;

                    // Mostrar información del estudiante
                    document.getElementById('infoEstudianteNombre').textContent =
                        `${data.estudiante.apellido} ${data.estudiante.nombre} (${data.estudiante.nacionalidad}-${data.estudiante.cedula})`;
                    document.getElementById('infoCursoActual').textContent =
                        `${data.cursoActual.curso} - Sección ${data.cursoActual.seccion}`;

                    // Mostrar el contenedor de información
                    document.getElementById('infoEstudianteContainer').style.display = 'block';

                    // Configurar curso por defecto (siguiente)
                    actualizarCursoYSecciones(false);

                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error al obtener la información del estudiante',
                        confirmButtonColor: '#c90000'
                    });
                }
            }
        });

        // Manejar cambio en radio de repite curso
        const radioRepite = document.querySelectorAll('input[name="repiteCurso"]');
        if (radioRepite.length > 0) {
            radioRepite.forEach(radio => {
                radio.addEventListener('change', function() {
                    const repite = this.value === '1';
                    actualizarCursoYSecciones(repite);
                });
            });
        }

        function actualizarCursoYSecciones(repite) {
            if (!window.estudianteData) return;

            const data = window.estudianteData;
            const cursoRegularInput = document.getElementById('cursoRegular');
            const seccionSelect = document.getElementById('seccionRegular');
            const idCursoHidden = document.getElementById('IdCurso');

            if (repite) {
                // Mostrar curso actual
                cursoRegularInput.value = data.cursoActual.curso;
                idCursoHidden.value = data.cursoActual.IdCurso;

                // Cargar secciones del curso actual
                cargarSeccionesCurso(data.cursoActual.IdCurso, data.cursoActual.IdSeccion);
            } else {
                // Mostrar curso siguiente
                cursoRegularInput.value = data.cursoSiguiente.curso;
                idCursoHidden.value = data.cursoSiguiente.IdCurso;

                // Cargar secciones del curso siguiente
                const seccionesSiguiente = data.secciones || [];
                seccionSelect.innerHTML = '<option value="">Seleccione una sección</option>';
                seccionesSiguiente.forEach(seccion => {
                    const option = document.createElement('option');
                    option.value = seccion.IdCurso_Seccion;
                    option.textContent = seccion.seccion;
                    if (seccion.IdSeccion == data.seccionPorDefecto) {
                        option.selected = true;
                    }
                    seccionSelect.appendChild(option);
                });
            }
        }

        async function cargarSeccionesCurso(idCurso, idSeccionActual) {
            try {
                const response = await fetch(`../../../controladores/BuscarGeneral.php?tipo=secciones_curso&idCurso=${idCurso}`);
                const secciones = await response.json();

                const seccionSelect = document.getElementById('seccionRegular');
                seccionSelect.innerHTML = '<option value="">Seleccione una sección</option>';

                secciones.forEach(seccion => {
                    const option = document.createElement('option');
                    option.value = seccion.IdCurso_Seccion;
                    option.textContent = seccion.seccion;
                    if (seccion.IdSeccion == idSeccionActual) {
                        option.selected = true;
                    }
                    seccionSelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error al cargar secciones:', error);
            }
        }
    }
});
</script>