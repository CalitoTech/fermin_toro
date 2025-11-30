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

// ========================================
// CONFIGURACIÓN DE TIPOS DE INSCRIPCIÓN
// ========================================
// Cambiar a true para mostrar el tipo "Estudiante Regular" en el select
$MOSTRAR_TIPO_REGULAR = false;

// IDs de tipos de inscripción (referencia)
// 1 = Nuevo Ingreso
// 2 = Estudiante Regular (Prosecución)
// 3 = Reinscripción
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

// Incluir funciones auxiliares para renderizar formularios (igual que solicitud_cupo.php)
require_once __DIR__ . '/../../homepage/includes/form_persona_fields.php';

// Preparar opciones para los selects
$data_options = [
    'nacionalidades' => $nacionalidades,
    'urbanismos' => $urbanismos,
    'parentescos' => $parentescos,
    'tiposTrabajador' => $tiposTrabajador
];
?>

<div class="container mt-4" style="min-height: 80vh;">
    <form id="formInscripcion" data-origen="pagina" method="POST">
        <!-- Hidden fields para formulario -->
        <input type="hidden" name="IdEstudiante" id="IdEstudiante" value="">
        <input type="hidden" name="IdFechaEscolar" id="IdFechaEscolar" value="<?= $añoEscolarActivo['IdFecha_Escolar'] ?? '' ?>">
        <input type="hidden" name="IdCurso" id="IdCurso" value="">
        <input type="hidden" name="idTipoInscripcion" id="idTipoInscripcion" value="1">
        <input type="hidden" name="origen" id="origen" value="administrativo">
        <input type="hidden" name="esReinscripcion" id="esReinscripcion" value="0">
        <input type="hidden" id="idCursoSeleccionado" name="idCurso">
        <input type="hidden" id="idNivelSeleccionado" name="idNivelSeleccionado">

        <!-- BLOQUE: DATOS DE INSCRIPCIÓN -->
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
                                    <?php
                                    // Ocultar "Estudiante Regular" (ID=2) si $MOSTRAR_TIPO_REGULAR es false
                                    if (!$MOSTRAR_TIPO_REGULAR && $tipo['IdTipo_Inscripcion'] == 2) {
                                        continue;
                                    }
                                    ?>
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

                    <!-- Contenedor para Reinscripción -->
                    <div id="reinscripcionContainer" class="col-12" style="display: none;">
                        <div class="row g-3">
                            <!-- Buscador de Estudiante para Reinscripción -->
                            <div class="col-12">
                                <div class="form-group required-field">
                                    <label for="buscadorEstudianteReinscripcion">
                                        <i class='bx bx-search'></i> Buscar Estudiante
                                    </label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control buscador-input" id="buscadorEstudianteReinscripcion"
                                               autocomplete="off" placeholder="Escriba cédula, nombre o apellido del estudiante...">
                                        <input type="hidden" id="IdEstudianteReinscripcion">
                                        <div id="resultadosBusquedaReinscripcion" class="autocomplete-results d-none"></div>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Busque al estudiante que desea reinscribir
                                    </small>
                                </div>
                            </div>

                            <!-- Información del estudiante para reinscripción -->
                            <div id="infoEstudianteReinscripcionContainer" class="col-12" style="display: none;">
                                <div class="alert alert-warning">
                                    <h6><i class='bx bx-user'></i> Estudiante Seleccionado</h6>
                                    <p class="mb-1"><strong>Nombre:</strong> <span id="infoEstudianteReinscripcionNombre"></span></p>
                                    <p class="mb-1"><strong>Última inscripción:</strong> <span id="infoUltimaInscripcion"></span></p>
                                </div>

                                <!-- Curso (seleccionable) -->
                                <div class="form-group required-field mb-3">
                                    <label for="cursoReinscripcion">
                                        <i class='bx bxs-graduation'></i> Curso
                                    </label>
                                    <select id="cursoReinscripcion" class="form-control" required>
                                        <option value="">Seleccione un curso</option>
                                    </select>
                                    <small class="text-muted">Solo se muestran cursos iguales o superiores al último cursado</small>
                                </div>

                                <!-- Status (todos los disponibles) -->
                                <div class="form-group required-field mb-3">
                                    <label for="statusReinscripcion">
                                        <i class='bx bx-info-circle'></i> Status
                                    </label>
                                    <select id="statusReinscripcion" class="form-control" required>
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
            </div>
        </div>

        <!-- ===================== DATOS DEL ESTUDIANTE (Igual que solicitud_cupo.php) ===================== -->
        <div class="card mb-4" id="seccionDatosEstudiante" style="display: none;">
            <div class="card-header form-title" style="background-color: #c90000; color: white;" data-toggle="collapse" data-target="#datosEstudiante">
                <h5><i class="fas fa-child mr-2"></i>Datos del Estudiante</h5>
            </div>

            <div class="card-body collapse show" id="datosEstudiante">
                <!-- Leyenda de campos obligatorios -->
                <div class="form-legend">
                    <i class="fas fa-asterisk"></i> Campos obligatorios
                </div>

                <!-- PRIMERA FILA: Sexo, Fecha Nacimiento, Nacionalidad, Cédula -->
                <div class="row">
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
                            <label for="estudianteFechaNacimiento">Fecha Nacimiento</label>
                            <input type="date" class="form-control" id="estudianteFechaNacimiento" name="estudianteFechaNacimiento" required>
                        </div>
                    </div>
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
                            minlength="7" maxlength="8"
                            pattern="[0-9]+" onkeypress="return onlyNumber(event)" readonly required>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Primero ingrese la fecha de nacimiento
                            </small>
                        </div>
                    </div>
                </div>

                <!-- SEGUNDA FILA: Apellidos, Nombres -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group required-field">
                            <label for="estudianteApellidos">Apellidos</label>
                            <input type="text" class="form-control" id="estudianteApellidos" name="estudianteApellidos"
                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                            minlength="3" maxlength="40"
                            onkeypress="return onlyText(event)"
                            oninput="formatearTexto2(this)" placeholder="Ej: Rodríguez Gómez" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group required-field">
                            <label for="estudianteNombres">Nombres</label>
                            <input type="text" class="form-control" id="estudianteNombres" name="estudianteNombres"
                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                            minlength="3" maxlength="40"
                            onkeypress="return onlyText(event)"
                            oninput="formatearTexto1(this)" placeholder="Ej: Juan Carlos" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group required-field">
                            <label for="estudianteLugarNacimiento">Lugar de Nacimiento</label>
                            <input type="text" class="form-control" id="estudianteLugarNacimiento" name="estudianteLugarNacimiento"
                            minlength="3" maxlength="40"
                            oninput="formatearTexto1()" placeholder="Ej: Araure, Portuguesa" required>
                        </div>
                    </div>
                    <div class="col-md-6" id="estudianteTelefonoContainer" style="display: none;">
                        <div class="form-group">
                            <label for="estudianteTelefono">Teléfono <small class="text-muted">(Opcional)</small></label>
                            <div class="input-group">
                                <!-- Prefix selector -->
                                <div class="position-relative" style="max-width: 90px;">
                                    <input type="text" class="form-control buscador-input text-center fw-bold prefijo-telefono"
                                           id="estudianteTelefonoPrefijo_input" maxlength="4" data-prefijo-tipo="internacional"
                                           onkeypress="return /[0-9+]/.test(event.key)"
                                           oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                                           style="border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: none; background: #f8f9fa; color: #c90000;"
                                           value="+58">
                                    <input type="hidden" id="estudianteTelefonoPrefijo" name="estudianteTelefonoPrefijo">
                                    <input type="hidden" id="estudianteTelefonoPrefijo_nombre" name="estudianteTelefonoPrefijo_nombre">
                                    <div id="estudianteTelefonoPrefijo_resultados" class="autocomplete-results d-none"></div>
                                </div>

                                <!-- Phone number input -->
                                <input type="tel" class="form-control" id="estudianteTelefono" name="estudianteTelefono"
                                       minlength="10" maxlength="10"
                                       pattern="[0-9]+" onkeypress="return onlyNumber(event)"
                                       placeholder="4121234567"
                                       style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group required-field">
                    <label for="estudianteCorreo">Correo electrónico</label>
                    <input type="email" class="form-control" id="estudianteCorreo" name="estudianteCorreo"
                    minlength="10" maxlength="50" placeholder="Ej: estudiante@correo.com" required>
                </div>

                <div class="form-group required-field" id="estudiantePlantelContainer">
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
                            <tbody id="discapacidadesBody">
                                <!-- Fila inicial se genera automáticamente -->
                            </tbody>
                        </table>
                    </div>
                    <button type="button" id="btn-agregar-discapacidad" class="btn btn-sm btn-primary mt-2">
                        <i class="fas fa-plus"></i> Agregar otra discapacidad
                    </button>
                </div>
            </div>
        </div>

        <!-- ===================== DATOS DE LA MADRE (usando helper igual que solicitud_cupo.php) ===================== -->
        <div id="seccionMadreContainer" style="display: none;">
            <?php
            // Renderizar bloque de la Madre (con contacto de emergencia) - con 'show' para que esté desplegado
            renderizarBloquePersona('madre', 'Datos de la Madre', 'fa-female', 'datosMadre', 'Madre', $data_options, 'show', true);
            ?>
        </div>

        <!-- ===================== DATOS DEL PADRE (usando helper igual que solicitud_cupo.php) ===================== -->
        <div id="seccionPadreContainer" style="display: none;">
            <?php
            // Renderizar bloque del Padre - con 'show' para que esté desplegado
            renderizarBloquePersona('padre', 'Datos del Padre', 'fa-male', 'datosPadre', 'Padre', $data_options, 'show', false);
            ?>
        </div>

        <!-- Información de representante automático -->
        <div id="repAutoInfo" class="representante-auto" style="display: none;">
            <i class="fas fa-info-circle mr-2"></i>
            Se usará <span id="repSeleccionado">la madre</span> como representante legal
        </div>

        <!-- Bloque de selección de representante legal -->
        <div class="card mb-4" id="seccionRepresentanteSelector" style="display: none;">
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

        <!-- ===================== DATOS DEL REPRESENTANTE LEGAL (se muestra si selecciona "otro") ===================== -->
        <div id="seccionRepresentante" style="display: none;">
            <?php
            // Renderizar bloque del Representante Legal (con Parentesco como buscador)
            echo '<div class="card mb-4">';
            echo '<div class="card-header" style="background-color: #c90000; color: white;">';
            echo '<h5><i class="fas fa-user-tie mr-2"></i>Datos del Representante Legal</h5>';
            echo '</div>';
            echo '<div class="card-body">';

            // Renderizar campos organizados por filas
            $fila_actual = [];
            $cols_actuales = 0;

            foreach ($campos_persona as $nombre_campo => $config) {
                $fila_actual[] = ['nombre' => $nombre_campo, 'config' => $config];
                $cols_actuales += $config['col'];

                // Si completamos 12 columnas o es el último campo, renderizamos la fila
                if ($cols_actuales >= 12 || $nombre_campo === array_key_last($campos_persona)) {
                    echo '<div class="row">';
                    foreach ($fila_actual as $item) {
                        // Para el representante, el Parentesco debe ser un buscador
                        if ($item['nombre'] === 'Parentesco') {
                            $id = 'representanteParentesco';
                            $name = 'representanteParentesco';
                            $inputId = $id . '_input';
                            $hiddenId = $id;
                            $hiddenNombre = $id . '_nombre';
                            $resultadosId = $id . '_resultados';

                            echo '<div class="col-md-6">';
                            echo '<div class="form-group required-field">';
                            echo '<label for="' . $id . '">Parentesco</label>';
                            echo '<div class="position-relative">';
                            echo '<input type="text" class="form-control buscador-input" id="' . $inputId . '" autocomplete="off" placeholder="Buscar o escribir nuevo parentesco...">';
                            echo '<input type="hidden" id="' . $hiddenId . '" name="' . $name . '" required>';
                            echo '<input type="hidden" id="' . $hiddenNombre . '" name="' . $name . '_nombre">';
                            echo '<div id="' . $resultadosId . '" class="autocomplete-results d-none"></div>';
                            echo '</div>';
                            echo '<script>
                            document.addEventListener("DOMContentLoaded", function() {
                                new BuscadorGenerico("' . $inputId . '", "' . $resultadosId . '", "parentesco", "' . $hiddenId . '", "' . $hiddenNombre . '");
                            });
                            </script>';
                            echo '</div>';
                            echo '</div>';
                        } else {
                            renderizarCampoPersona('representante', $item['nombre'], $item['config'], $data_options, '');
                        }
                    }
                    echo '</div>';

                    // Resetear para la siguiente fila
                    $fila_actual = [];
                    $cols_actuales = 0;
                }
            }

            echo '</div>';
            echo '</div>';
            ?>
        </div>

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

<script src="../../../assets/js/buscador_generico.js"></script>
<script src="../../../assets/js/validaciones_solicitud.js?v=8"></script>
<script src="../../../assets/js/solicitud_cupo.js?v=18"></script>
<script src="../../../assets/js/validacion.js?v=4"></script>

<script>
// Variable global para el nivel seleccionado (necesaria para validaciones)
let nivelSeleccionadoGlobal = 0;

document.addEventListener('DOMContentLoaded', function () {
    // === Inicializar buscadores ===

    // Buscador de plantel
    new BuscadorGenerico(
        "estudiantePlantel_input",
        "estudiantePlantel_resultados",
        "plantel",
        "estudiantePlantel",
        "estudiantePlantel_nombre"
    );

    // Buscador de prefijo para teléfono de estudiante
    new BuscadorGenerico(
        "estudianteTelefonoPrefijo_input",
        "estudianteTelefonoPrefijo_resultados",
        "prefijo",
        "estudianteTelefonoPrefijo",
        "estudianteTelefonoPrefijo_nombre"
    );

    // === Manejo de visibilidad de sección representante ===
    const radios = document.querySelectorAll('input[name="tipoRepresentante"]');
    const seccionRepresentante = document.getElementById('seccionRepresentante');
    const camposRepresentante = seccionRepresentante ? seccionRepresentante.querySelectorAll('input, select, textarea') : [];
    const repInfo = document.getElementById('repAutoInfo');

    function actualizarVisibilidadRepresentante() {
        const seleccionado = document.querySelector('input[name="tipoRepresentante"]:checked');
        const valor = seleccionado ? seleccionado.value : 'madre';

        if (valor === 'otro') {
            // Mostrar sección de representante
            seccionRepresentante.style.display = 'block';
            if (repInfo) repInfo.style.display = 'none';

            // Marcar campos como requeridos
            camposRepresentante.forEach(campo => {
                campo.setAttribute('required', 'required');
            });
        } else {
            // Ocultar sección
            seccionRepresentante.style.display = 'none';
            if (repInfo) {
                repInfo.style.display = 'block';
                document.getElementById('repSeleccionado').textContent = valor === 'padre' ? 'el padre' : 'la madre';
            }

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
        radio.addEventListener('change', actualizarVisibilidadRepresentante);
    });

    // === Manejo de filtro de cursos por nivel ===
    const selectNivel = document.getElementById("nivel");
    const selectCurso = document.getElementById("curso");

    // Traemos todos los cursos desde PHP
    const cursosOriginales = <?= json_encode($cursos) ?>;
    const niveles = <?= json_encode($niveles) ?>;

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

        // Actualizar campo oculto de nivel
        document.getElementById('idNivelSeleccionado').value = nivelSeleccionado;

        // Actualizar variable global para validaciones
        nivelSeleccionadoGlobal = parseInt(nivelSeleccionado) || 0;

    });

    // === Función para ocultar/mostrar cédula, teléfono y plantel según el curso ===
    function actualizarCamposSegunCurso(idCurso) {
        const cedulaContainer = document.getElementById('estudianteCedulaContainer');
        const telefonoContainer = document.getElementById('estudianteTelefonoContainer');
        const cedulaInput = document.getElementById('estudianteCedula');
        const telefonoInput = document.getElementById('estudianteTelefono');
        const plantelContainer = document.getElementById('estudiantePlantelContainer');
        const plantelInput = document.getElementById('estudiantePlantel');
        const plantelInputVisible = document.getElementById('estudiantePlantel_input');
        const plantelNombre = document.getElementById('estudiantePlantel_nombre');

        if (parseInt(idCurso) === 1) {
            // Primer curso (Primer Nivel): MOSTRAR cédula pero hacerla OPCIONAL
            if (cedulaContainer) {
                cedulaContainer.style.display = '';
                const $cedulaFormGroup = cedulaContainer.querySelector('.form-group');
                if ($cedulaFormGroup) {
                    $cedulaFormGroup.classList.remove('required-field');
                }

                const $cedulaLabel = document.querySelector('label[for="estudianteCedula"]');
                if ($cedulaLabel && !$cedulaLabel.querySelector('.text-muted')) {
                    $cedulaLabel.innerHTML += ' <small class="text-muted">(Opcional)</small>';
                }

                if (cedulaInput) {
                    cedulaInput.setAttribute('data-opcional-nivel-inicial', 'true');
                    cedulaInput.removeAttribute('required');
                    cedulaInput.value = '';
                    cedulaInput.setAttribute('readonly', true);
                }
            }

            // Ocultar teléfono para primer nivel
            if (telefonoContainer) {
                telefonoContainer.style.display = 'none';
                if (telefonoInput) {
                    telefonoInput.removeAttribute('required');
                    telefonoInput.value = '';
                }
            }

            // Ocultar plantel para primer nivel
            if (plantelContainer) {
                plantelContainer.style.display = 'none';
                if (plantelInput) {
                    plantelInput.removeAttribute('required');
                    plantelInput.value = '1'; // IdPlantel = 1 para U.E.C "Fermín Toro"
                }
                if (plantelNombre) plantelNombre.value = 'U.E.C "Fermín Toro"';
                if (plantelInputVisible) plantelInputVisible.value = 'U.E.C "Fermín Toro"';
            }
        } else if (idCurso) {
            // Otros cursos: Mostrar cédula (readonly hasta fecha) y plantel
            if (cedulaContainer) {
                cedulaContainer.style.display = '';
                const $cedulaFormGroup = cedulaContainer.querySelector('.form-group');
                if ($cedulaFormGroup) {
                    $cedulaFormGroup.classList.add('required-field');
                }

                const $cedulaLabel = document.querySelector('label[for="estudianteCedula"]');
                if ($cedulaLabel) {
                    const optionalText = $cedulaLabel.querySelector('.text-muted');
                    if (optionalText) {
                        optionalText.remove();
                    }
                    // Restaurar solo "Cédula" sin el opcional
                    $cedulaLabel.childNodes[0].textContent = 'Cédula';
                }

                if (cedulaInput) {
                    cedulaInput.removeAttribute('data-opcional-nivel-inicial');
                    cedulaInput.setAttribute('required', 'required');
                    cedulaInput.setAttribute('readonly', true);
                    // Restaurar mensaje de ayuda
                    const cedulaHelpText = cedulaInput.nextElementSibling;
                    if (cedulaHelpText) cedulaHelpText.style.display = 'block';
                }
            }
            // Teléfono se maneja por la edad en el blur de fecha de nacimiento
            if (plantelContainer) {
                plantelContainer.style.display = '';
                if (plantelInput) {
                    plantelInput.setAttribute('required', 'required');
                    plantelInput.value = '';
                }
                if (plantelNombre) plantelNombre.value = '';
                if (plantelInputVisible) plantelInputVisible.value = '';
            }
        }
    }

    // Escuchar cambios en el select de curso
    selectCurso.addEventListener("change", function() {
        const cursoSeleccionado = this.value;
        document.getElementById('idCursoSeleccionado').value = cursoSeleccionado;
        actualizarCamposSegunCurso(cursoSeleccionado);
    });

    // === Configurar restricciones de fecha de nacimiento (6-18 años) ===
    const fechaNacimientoInput = document.getElementById('estudianteFechaNacimiento');
    if (fechaNacimientoInput) {
        const hoy = new Date();
        const fechaMax = new Date(hoy.getFullYear() - 6, hoy.getMonth(), hoy.getDate());
        const fechaMin = new Date(hoy.getFullYear() - 18, hoy.getMonth(), hoy.getDate());

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
            if (!valorSeleccionado) {
                // Si borra la fecha, volver a bloquear cédula y ocultar teléfono
                const cedulaInput = document.getElementById('estudianteCedula');
                const telefonoContainer = document.getElementById('estudianteTelefonoContainer');
                const telefonoInput = document.getElementById('estudianteTelefono');
                const cedulaHelpText = cedulaInput?.nextElementSibling;

                if (cedulaInput) {
                    cedulaInput.setAttribute('readonly', true);
                    cedulaInput.value = '';
                    if (cedulaHelpText) {
                        cedulaHelpText.style.display = 'block';
                    }
                }

                if (telefonoContainer) {
                    telefonoContainer.style.display = 'none';
                    if (telefonoInput) {
                        telefonoInput.value = '';
                        telefonoInput.removeAttribute('required');
                    }
                }
                return;
            }

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

                // Bloquear cédula y ocultar teléfono si la edad no es válida
                const cedulaInput = document.getElementById('estudianteCedula');
                const telefonoContainer = document.getElementById('estudianteTelefonoContainer');
                const telefonoInput = document.getElementById('estudianteTelefono');

                if (cedulaInput) {
                    cedulaInput.setAttribute('readonly', true);
                    cedulaInput.value = '';
                }

                if (telefonoContainer) {
                    telefonoContainer.style.display = 'none';
                    if (telefonoInput) {
                        telefonoInput.value = '';
                        telefonoInput.removeAttribute('required');
                    }
                }
            } else {
                this.classList.remove('is-invalid');

                // Verificar si es primer curso (IdCurso === 1, que es "Primer Nivel")
                const cursoActual = parseInt(document.getElementById('curso')?.value || 0);
                const esPrimerCurso = cursoActual === 1;

                // Habilitar campo de cédula
                const cedulaContainer = document.getElementById('estudianteCedulaContainer');
                const cedulaInput = document.getElementById('estudianteCedula');
                const cedulaLabel = document.querySelector('label[for="estudianteCedula"]');
                const cedulaHelpText = cedulaInput?.nextElementSibling;

                if (cedulaInput && cedulaContainer) {
                    cedulaContainer.style.display = '';
                    cedulaInput.removeAttribute('readonly');

                    // Ocultar el mensaje de ayuda
                    if (cedulaHelpText) {
                        cedulaHelpText.style.display = 'none';
                    }

                    // Ajustar label y maxlength según la edad
                    if (cedulaLabel) {
                        if (edad < 10) {
                            // Menores de 10 años: Cédula escolar con maxlength 11, minlength 10
                            const labelText = 'Cédula escolar';
                            cedulaLabel.childNodes[0].textContent = labelText;
                            cedulaInput.setAttribute('maxlength', '11');
                            cedulaInput.setAttribute('minlength', '10');
                        } else {
                            // 10 años o más: Cédula normal con maxlength 8, minlength 7
                            const labelText = 'Cédula';
                            cedulaLabel.childNodes[0].textContent = labelText;
                            cedulaInput.setAttribute('maxlength', '8');
                            cedulaInput.setAttribute('minlength', '7');
                        }

                        // IMPORTANTE: Si es primer curso, preservar el "(Opcional)"
                        if (esPrimerCurso && !cedulaLabel.querySelector('.text-muted')) {
                            cedulaLabel.innerHTML += ' <small class="text-muted">(Opcional)</small>';
                        }
                    }
                }

                // Manejar visibilidad del campo teléfono según edad SOLO si NO es primer curso
                const telefonoContainer = document.getElementById('estudianteTelefonoContainer');
                const telefonoInput = document.getElementById('estudianteTelefono');

                if (!esPrimerCurso && telefonoContainer && telefonoInput) {
                    if (edad < 10) {
                        // Menores de 10 años: ocultar teléfono
                        telefonoContainer.style.display = 'none';
                        telefonoInput.value = '';
                        telefonoInput.removeAttribute('required');

                        // Limpiar también el prefijo
                        const prefijoInput = document.getElementById('estudianteTelefonoPrefijo');
                        const prefijoInputVisible = document.getElementById('estudianteTelefonoPrefijo_input');
                        if (prefijoInput) prefijoInput.value = '';
                        if (prefijoInputVisible) prefijoInputVisible.value = '+58';
                    } else {
                        // 10 años o más: mostrar teléfono como opcional
                        telefonoContainer.style.display = 'block';
                        telefonoInput.removeAttribute('required');
                    }
                }
            }
        });

        // === FUNCIÓN PARA PRESERVAR LABEL "(Opcional)" EN CÉDULA ===
        function preservarLabelOpcionalCedula() {
            const $cedula = document.getElementById('estudianteCedula');
            if ($cedula && $cedula.getAttribute('data-opcional-nivel-inicial') === 'true') {
                const $cedulaLabel = document.querySelector('label[for="estudianteCedula"]');
                const $formGroup = document.getElementById('estudianteCedulaContainer')?.querySelector('.form-group');

                if ($cedulaLabel && !$cedulaLabel.querySelector('.text-muted')) {
                    $cedulaLabel.innerHTML += ' <small class="text-muted">(Opcional)</small>';
                }
                if ($formGroup) {
                    $formGroup.classList.remove('required-field');
                }
                $cedula.removeAttribute('required');
            }
        }

        // MutationObserver para detectar cambios en el DOM
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' || mutation.type === 'characterData') {
                    preservarLabelOpcionalCedula();
                }
            });
        });

        // Observar cambios en el label de cédula
        const cedulaLabelElement = document.querySelector('label[for="estudianteCedula"]');
        if (cedulaLabelElement) {
            observer.observe(cedulaLabelElement, {
                childList: true,
                characterData: true,
                subtree: true
            });
        }

        // Event listeners para preservar el label cuando cambia la fecha
        if (fechaNacimientoInput) {
            fechaNacimientoInput.addEventListener('change', preservarLabelOpcionalCedula);
            fechaNacimientoInput.addEventListener('focus', preservarLabelOpcionalCedula);
        }
    }

    // === MANEJO DINÁMICO DEL TIPO DE INSCRIPCIÓN ===
    const tipoInscripcionSelect = document.getElementById('tipoInscripcion');
    const nuevoIngresoContainer = document.getElementById('nuevoIngresoContainer');
    const regularContainer = document.getElementById('regularContainer');
    const reinscripcionContainer = document.getElementById('reinscripcionContainer');

    // Contenedores del formulario completo
    const seccionDatosEstudiante = document.getElementById('seccionDatosEstudiante');
    const seccionMadreContainer = document.getElementById('seccionMadreContainer');
    const seccionPadreContainer = document.getElementById('seccionPadreContainer');
    const seccionRepresentanteSelector = document.getElementById('seccionRepresentanteSelector');

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
        if (document.getElementById('buscadorEstudianteReinscripcion')) {
            document.getElementById('buscadorEstudianteReinscripcion').value = '';
        }
        document.getElementById('IdEstudiante').value = '';
        if (document.getElementById('infoEstudianteContainer')) {
            document.getElementById('infoEstudianteContainer').style.display = 'none';
        }
        if (document.getElementById('infoEstudianteReinscripcionContainer')) {
            document.getElementById('infoEstudianteReinscripcionContainer').style.display = 'none';
        }

        // Función auxiliar para ocultar todos los contenedores de tipo
        function ocultarTodosContenedores() {
            nuevoIngresoContainer.style.display = 'none';
            regularContainer.style.display = 'none';
            if (reinscripcionContainer) reinscripcionContainer.style.display = 'none';
        }

        if (tipoSeleccionado === 1) {
            // Nuevo Ingreso - Mostrar formulario completo tradicional
            ocultarTodosContenedores();
            nuevoIngresoContainer.style.display = 'flex';
            btnGuardar.style.display = 'inline-block';

            // Mostrar formulario completo
            seccionDatosEstudiante.style.display = 'block';
            seccionMadreContainer.style.display = 'block';
            seccionPadreContainer.style.display = 'block';
            seccionRepresentanteSelector.style.display = 'block';
            if (repInfo) repInfo.style.display = 'block';

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

            // Aplicar estado inicial del representante
            actualizarVisibilidadRepresentante();

        } else if (tipoSeleccionado === 2) {
            // Estudiante Regular (Prosecución) - Mostrar buscador simple
            ocultarTodosContenedores();
            regularContainer.style.display = 'flex';
            btnGuardar.style.display = 'inline-block';

            // Cambiar action del formulario al endpoint de renovación
            form.action = '../../../controladores/representantes/procesar_renovacion.php';
            form.method = 'POST';

            // Ocultar formulario completo
            seccionDatosEstudiante.style.display = 'none';
            seccionMadreContainer.style.display = 'none';
            seccionPadreContainer.style.display = 'none';
            seccionRepresentanteSelector.style.display = 'none';
            seccionRepresentante.style.display = 'none';
            if (repInfo) repInfo.style.display = 'none';

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

            // Remover required de reinscripción
            const cursoReinscripcion = document.getElementById('cursoReinscripcion');
            const statusReinscripcion = document.getElementById('statusReinscripcion');
            if (cursoReinscripcion) cursoReinscripcion.removeAttribute('required');
            if (statusReinscripcion) statusReinscripcion.removeAttribute('required');

        } else if (tipoSeleccionado === 3) {
            // Reinscripción - Mostrar buscador y selección de curso
            ocultarTodosContenedores();
            reinscripcionContainer.style.display = 'flex';
            btnGuardar.style.display = 'inline-block';

            // Cambiar action del formulario al endpoint de renovación (mismo que regular)
            form.action = '../../../controladores/representantes/procesar_renovacion.php';
            form.method = 'POST';

            // Ocultar formulario completo
            seccionDatosEstudiante.style.display = 'none';
            seccionMadreContainer.style.display = 'none';
            seccionPadreContainer.style.display = 'none';
            seccionRepresentanteSelector.style.display = 'none';
            seccionRepresentante.style.display = 'none';
            if (repInfo) repInfo.style.display = 'none';

            // Remover required del formulario tradicional y regular
            const nivelSelect = document.getElementById('nivel');
            const cursoSelect = document.getElementById('curso');
            const statusSelect = document.getElementById('IdStatus');
            if (nivelSelect) nivelSelect.removeAttribute('required');
            if (cursoSelect) cursoSelect.removeAttribute('required');
            if (statusSelect) statusSelect.removeAttribute('required');

            const seccionRegular = document.getElementById('seccionRegular');
            const statusRegular = document.getElementById('statusRegular');
            if (seccionRegular) seccionRegular.removeAttribute('required');
            if (statusRegular) statusRegular.removeAttribute('required');

            // Hacer required los campos de reinscripción
            const cursoReinscripcion = document.getElementById('cursoReinscripcion');
            const statusReinscripcion = document.getElementById('statusReinscripcion');
            if (cursoReinscripcion) cursoReinscripcion.setAttribute('required', 'required');
            if (statusReinscripcion) statusReinscripcion.setAttribute('required', 'required');

        } else {
            // Sin selección - Ocultar todo
            ocultarTodosContenedores();
            btnGuardar.style.display = 'none';
            form.action = '';

            seccionDatosEstudiante.style.display = 'none';
            seccionMadreContainer.style.display = 'none';
            seccionPadreContainer.style.display = 'none';
            seccionRepresentanteSelector.style.display = 'none';
            seccionRepresentante.style.display = 'none';
            if (repInfo) repInfo.style.display = 'none';
        }
    });

    // === INTERCEPTAR SUBMIT PARA TIPO REGULAR Y REINSCRIPCIÓN ===
    document.getElementById('btnEnviarFormulario').addEventListener('click', function(e) {
        const tipoInscripcion = parseInt(document.getElementById('idTipoInscripcion').value || 1);

        // Si es inscripción regular (tipo 2), validar y enviar directamente
        if (tipoInscripcion === 2) {
            e.preventDefault();
            e.stopImmediatePropagation();

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
        // Si es reinscripción (tipo 3), validar y enviar
        else if (tipoInscripcion === 3) {
            e.preventDefault();
            e.stopImmediatePropagation();

            const idEstudiante = document.getElementById('IdEstudianteReinscripcion').value;
            const cursoReinscripcion = document.getElementById('cursoReinscripcion').value;
            const statusReinscripcion = document.getElementById('statusReinscripcion').value;

            if (!idEstudiante) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Datos incompletos',
                    text: 'Debe seleccionar un estudiante',
                    confirmButtonColor: '#c90000'
                });
                return;
            }

            if (!cursoReinscripcion) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Datos incompletos',
                    text: 'Debe seleccionar un curso',
                    confirmButtonColor: '#c90000'
                });
                return;
            }

            if (!statusReinscripcion) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Datos incompletos',
                    text: 'Debe seleccionar un status',
                    confirmButtonColor: '#c90000'
                });
                return;
            }

            // Copiar valores de reinscripción a los campos principales del formulario
            document.getElementById('IdEstudiante').value = idEstudiante;
            document.getElementById('IdCurso').value = cursoReinscripcion;
            document.getElementById('esReinscripcion').value = '1';

            // Crear campo temporal para el status si no existe uno con name
            let statusInput = document.querySelector('input[name="idStatus"]');
            if (!statusInput) {
                statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'idStatus';
                document.getElementById('formInscripcion').appendChild(statusInput);
            }
            statusInput.value = statusReinscripcion;

            // Si todas las validaciones pasan, enviar el formulario
            document.getElementById('formInscripcion').submit();
        }
        // Si es tipo 1 (nuevo ingreso), dejar que solicitud_cupo.js maneje la validación
    }, true);

    // === BUSCADOR DE ESTUDIANTE PARA PROSECUCIÓN ===
    if (document.getElementById('buscadorEstudiante')) {
        const buscadorEstudiante = new BuscadorGenerico(
            'buscadorEstudiante',
            'resultadosBusqueda',
            'estudiante_regular',
            'IdEstudiante'
        );

        const inputBuscador = document.getElementById('buscadorEstudiante');
        inputBuscador.addEventListener('itemSeleccionado', async function(e) {
            const idEstudiante = e.detail?.IdEstudiante || e.detail?.IdPersona;
            if (idEstudiante) {
                try {
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

    // === BUSCADOR DE ESTUDIANTE PARA REINSCRIPCIÓN ===
    if (document.getElementById('buscadorEstudianteReinscripcion')) {
        const buscadorReinscripcion = new BuscadorGenerico(
            'buscadorEstudianteReinscripcion',
            'resultadosBusquedaReinscripcion',
            'estudiante_reinscripcion',
            'IdEstudianteReinscripcion'
        );

        const inputBuscadorReinscripcion = document.getElementById('buscadorEstudianteReinscripcion');
        inputBuscadorReinscripcion.addEventListener('itemSeleccionado', async function(e) {
            const idEstudiante = e.detail?.IdEstudiante || e.detail?.IdPersona;
            if (idEstudiante) {
                try {
                    // Obtener última inscripción del estudiante
                    const response = await fetch(`../../../controladores/PersonaController.php?action=obtenerUltimaInscripcion&idEstudiante=${idEstudiante}`);
                    const data = await response.json();

                    if (!data.success) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error || 'No se pudo obtener la información del estudiante',
                            confirmButtonColor: '#c90000'
                        });
                        return;
                    }

                    // Mostrar información del estudiante
                    document.getElementById('infoEstudianteReinscripcionNombre').textContent =
                        `${data.estudiante.apellido} ${data.estudiante.nombre} (${data.estudiante.nacionalidad}-${data.estudiante.cedula || 'Sin cédula'})`;

                    if (data.ultimaInscripcion) {
                        document.getElementById('infoUltimaInscripcion').textContent =
                            `${data.ultimaInscripcion.curso} - ${data.ultimaInscripcion.fecha_escolar}`;
                    } else {
                        document.getElementById('infoUltimaInscripcion').textContent = 'Sin inscripciones previas';
                    }

                    // Mostrar el contenedor de información
                    document.getElementById('infoEstudianteReinscripcionContainer').style.display = 'block';

                    // Cargar cursos disponibles (>= último curso)
                    const cursoSelect = document.getElementById('cursoReinscripcion');
                    cursoSelect.innerHTML = '<option value="">Seleccione un curso</option>';

                    if (data.cursosDisponibles && data.cursosDisponibles.length > 0) {
                        let currentNivel = '';
                        let optgroup = null;

                        data.cursosDisponibles.forEach(curso => {
                            // Crear optgroup si cambia el nivel
                            if (curso.nivel !== currentNivel) {
                                if (optgroup) {
                                    cursoSelect.appendChild(optgroup);
                                }
                                optgroup = document.createElement('optgroup');
                                optgroup.label = curso.nivel;
                                currentNivel = curso.nivel;
                            }

                            const option = document.createElement('option');
                            option.value = curso.IdCurso;
                            option.textContent = curso.curso;

                            // Preseleccionar el curso de la última inscripción si existe
                            if (data.ultimaInscripcion && curso.IdCurso == data.ultimaInscripcion.IdCurso) {
                                option.selected = true;
                            }

                            optgroup.appendChild(option);
                        });

                        // Agregar el último optgroup
                        if (optgroup) {
                            cursoSelect.appendChild(optgroup);
                        }
                    }

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
    }

    // === VALIDACIÓN DE CORREO DUPLICADO ===
    // Variable para rastrear correos con errores
    const correosConErrorLocal = new Set();

    async function verificarCorreoDuplicadoLocal(inputCorreo, nombrePersona, idPersonaExcluir = null) {
        const correo = inputCorreo.value.trim().toLowerCase();

        if (correo.length === 0) {
            inputCorreo.classList.remove('is-invalid', 'is-valid');
            correosConErrorLocal.delete(inputCorreo.id);
            return true;
        }

        // Validar formato primero
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(correo)) {
            inputCorreo.classList.remove('is-valid');
            inputCorreo.classList.add('is-invalid');
            correosConErrorLocal.add(inputCorreo.id);
            return false;
        }

        // Verificar duplicados dentro del mismo formulario
        const camposCorreo = ['estudianteCorreo', 'padreCorreo', 'madreCorreo', 'representanteCorreo'];
        const nombresPersonas = {
            'estudianteCorreo': 'Estudiante',
            'padreCorreo': 'Padre',
            'madreCorreo': 'Madre',
            'representanteCorreo': 'Representante Legal'
        };

        for (const campoId of camposCorreo) {
            if (campoId === inputCorreo.id) continue;

            const otroCampo = document.getElementById(campoId);
            if (otroCampo && otroCampo.value.trim().toLowerCase() === correo) {
                inputCorreo.classList.remove('is-valid');
                inputCorreo.classList.add('is-invalid');
                correosConErrorLocal.add(inputCorreo.id);

                Swal.fire({
                    title: 'Correo Duplicado',
                    html: `El correo <strong>${correo}</strong> ya está siendo usado para <strong>${nombresPersonas[campoId]}</strong> en este formulario.<br><br>
                           <small class="text-muted">Cada persona debe tener un correo electrónico diferente.</small>`,
                    icon: 'warning',
                    confirmButtonColor: '#c90000'
                });

                inputCorreo.value = '';
                inputCorreo.focus();
                return false;
            }
        }

        try {
            let url = `../../../controladores/PersonaController.php?action=verificarCorreo&correo=${encodeURIComponent(correo)}`;
            if (idPersonaExcluir) {
                url += `&idPersona=${idPersonaExcluir}`;
            }

            const response = await fetch(url);
            const data = await response.json();

            if (data.existe) {
                inputCorreo.classList.remove('is-valid');
                inputCorreo.classList.add('is-invalid');
                correosConErrorLocal.add(inputCorreo.id);

                Swal.fire({
                    title: 'Correo Duplicado',
                    html: `El correo <strong>${correo}</strong> ya está registrado para:<br><br>
                           <strong>${data.persona.nombreCompleto}</strong><br>
                           Cédula: ${data.persona.nacionalidad}-${data.persona.cedula}<br><br>
                           <small class="text-muted">Por favor ingrese un correo diferente para ${nombrePersona}.</small>`,
                    icon: 'warning',
                    confirmButtonColor: '#c90000'
                });

                // Limpiar el campo
                inputCorreo.value = '';
                inputCorreo.focus();
                return false;
            } else {
                inputCorreo.classList.remove('is-invalid');
                inputCorreo.classList.add('is-valid');
                correosConErrorLocal.delete(inputCorreo.id);
                return true;
            }
        } catch (error) {
            console.error('Error al verificar correo:', error);
            correosConErrorLocal.delete(inputCorreo.id);
            return true;
        }
    }

    // Sincronizar con la variable global de solicitud_cupo.js
    window.correosConError = correosConErrorLocal;

    // Aplicar validación a todos los campos de correo
    const camposCorreoConfig = [
        { id: 'estudianteCorreo', nombre: 'Estudiante' },
        { id: 'madreCorreo', nombre: 'Madre' },
        { id: 'padreCorreo', nombre: 'Padre' },
        { id: 'representanteCorreo', nombre: 'Representante Legal' }
    ];

    camposCorreoConfig.forEach(campo => {
        const input = document.getElementById(campo.id);
        if (input) {
            input.addEventListener('blur', function() {
                verificarCorreoDuplicadoLocal(this, campo.nombre);
            });
        }
    });

    // NOTA: NO es necesario llamar manualmente a las funciones de validación
    // porque solicitud_cupo.js ya las ejecuta automáticamente en su $(document).ready()
});
</script>
