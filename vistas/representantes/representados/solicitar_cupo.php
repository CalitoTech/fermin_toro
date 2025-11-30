<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    header("Location: ../../login/login.php");
    exit();
}

// Conexión y modelos
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Persona.php';
require_once __DIR__ . '/../../../modelos/Nacionalidad.php';
require_once __DIR__ . '/../../../modelos/Sexo.php';
require_once __DIR__ . '/../../../modelos/Nivel.php';
require_once __DIR__ . '/../../../modelos/Curso.php';
require_once __DIR__ . '/../../../modelos/Urbanismo.php';
require_once __DIR__ . '/../../../modelos/Parentesco.php';
require_once __DIR__ . '/../../../modelos/TipoTrabajador.php';
require_once __DIR__ . '/../../../modelos/Plantel.php';
require_once __DIR__ . '/../../../modelos/FechaEscolar.php';
require_once __DIR__ . '/../../../modelos/Telefono.php';
require_once __DIR__ . '/../../../modelos/Representante.php';
require_once __DIR__ . '/../../../controladores/Notificaciones.php';

$database = new Database();
$conexion = $database->getConnection();

// Verificar que las inscripciones estén activas
$fechaEscolarModel = new FechaEscolar($conexion);
$añoEscolarActivo = $fechaEscolarModel->obtenerActivo();

if (!$añoEscolarActivo || !$añoEscolarActivo['inscripcion_activa']) {
    $_SESSION['mensaje_error'] = 'Las inscripciones no están activas en este momento.';
    header("Location: representado.php");
    exit();
}

// Obtener datos del usuario logueado
$idPersona = $_SESSION['idPersona'];
$personaModel = new Persona($conexion);
$usuarioLogueado = $personaModel->obtenerPorId($idPersona);

if (!$usuarioLogueado) {
    $_SESSION['mensaje_error'] = 'No se pudo obtener la información del usuario.';
    header("Location: representado.php");
    exit();
}

// Obtener teléfonos del usuario logueado
$telefonoModel = new Telefono($conexion);
$telefonosUsuario = $telefonoModel->obtenerPorPersona($idPersona);

// Organizar teléfonos por tipo
$telefonoHabitacion = '';
$prefijoHabitacion = '';
$celular = '';
$prefijoCelular = '';
$telefonoTrabajo = '';
$prefijoTrabajo = '';

foreach ($telefonosUsuario as $tel) {
    switch ($tel['IdTipo_Telefono']) {
        case 1:
            $telefonoHabitacion = $tel['numero_telefono'];
            $prefijoHabitacion = $tel['IdPrefijo'] ?? '';
            break;
        case 2:
            $celular = $tel['numero_telefono'];
            $prefijoCelular = $tel['IdPrefijo'] ?? '';
            break;
        case 3:
            $telefonoTrabajo = $tel['numero_telefono'];
            $prefijoTrabajo = $tel['IdPrefijo'] ?? '';
            break;
    }
}

// Determinar el sexo del usuario
$sexoUsuario = $usuarioLogueado['IdSexo'] ?? null;

// Obtener personas relacionadas a los estudiantes del usuario (para sugerencias)
$representanteModel = new Representante($conexion);
$personasRelacionadas = [];

// Obtener todos los representantes de los estudiantes que tiene este usuario
$queryRelacionados = "
    SELECT DISTINCT p.IdPersona, p.nombre, p.apellido, p.cedula, p.correo,
           n.nacionalidad, p.IdNacionalidad, p.IdSexo, p.direccion, p.IdUrbanismo,
           u.urbanismo, par.parentesco, par.IdParentesco,
           CASE
               WHEN p.IdSexo = 1 THEN 'Masculino'
               WHEN p.IdSexo = 2 THEN 'Femenino'
               ELSE 'No especificado'
           END as sexo_texto,
           CASE
               WHEN r2.IdPersona = :idUsuario THEN 1
               ELSE 0
           END as es_relacionado_directo
    FROM representante r
    INNER JOIN persona p ON r.IdPersona = p.IdPersona
    LEFT JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
    LEFT JOIN urbanismo u ON p.IdUrbanismo = u.IdUrbanismo
    LEFT JOIN parentesco par ON r.IdParentesco = par.IdParentesco
    LEFT JOIN representante r2 ON r.IdEstudiante = r2.IdEstudiante AND r2.IdPersona = :idUsuario2
    WHERE r.IdEstudiante IN (
        SELECT IdEstudiante FROM representante WHERE IdPersona = :idUsuario3
    )
    AND p.IdPersona != :idUsuario4
    ORDER BY es_relacionado_directo DESC, p.nombre, p.apellido
";
$stmtRelacionados = $conexion->prepare($queryRelacionados);
$stmtRelacionados->execute([
    ':idUsuario' => $idPersona,
    ':idUsuario2' => $idPersona,
    ':idUsuario3' => $idPersona,
    ':idUsuario4' => $idPersona
]);
$personasRelacionadas = $stmtRelacionados->fetchAll(PDO::FETCH_ASSOC);

// Separar por sexo para sugerencias
$padresSugeridos = array_filter($personasRelacionadas, fn($p) => $p['IdSexo'] == 1);
$madresSugeridas = array_filter($personasRelacionadas, fn($p) => $p['IdSexo'] == 2);

// Instancias de los modelos
$modeloNacionalidad = new Nacionalidad($conexion);
$modeloSexo = new Sexo($conexion);
$modeloNivel = new Nivel($conexion);
$modeloCurso = new Curso($conexion);
$modeloUrbanismo = new Urbanismo($conexion);
$modeloParentesco = new Parentesco($conexion);
$modeloTipoTrabajador = new TipoTrabajador($conexion);
$modeloPlantel = new Plantel($conexion);

// Obtener datos - Para inscripciones desde representante, mostrar todos los niveles y cursos disponibles
$nacionalidades = $modeloNacionalidad->obtenerConNombresLargos();
$sexos = $modeloSexo->obtenerTodos();
$niveles = $modeloNivel->obtenerTodos();
$cursos = $modeloCurso->obtenerTodos();
$urbanismos = $modeloUrbanismo->obtenerTodos();
$parentescos = $modeloParentesco->obtenerTodos();
$tiposTrabajador = $modeloTipoTrabajador->obtenerTodos();

// Incluir funciones auxiliares para renderizar formularios
require_once __DIR__ . '/../../homepage/includes/form_persona_fields.php';

// Preparar opciones para los selects
$data_options = [
    'nacionalidades' => $nacionalidades,
    'urbanismos' => $urbanismos,
    'parentescos' => $parentescos,
    'tiposTrabajador' => $tiposTrabajador
];

// Manejo de alertas
$alert = $_SESSION['alert'] ?? null;
$message = $_SESSION['message'] ?? '';
unset($_SESSION['alert'], $_SESSION['message']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>UECFT Araure - Solicitar Cupo</title>
    <link rel="stylesheet" href="../../../assets/css/solicitud_cupo.css">
    <style>
        .user-info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid #c90000;
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .user-info-card .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #c90000;
            color: white;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .user-info-card h6 {
            color: #333;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .user-info-card p {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }

        .role-selector {
            background: #fff5f5;
            border: 1px solid #ffcccc;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .role-selector h6 {
            color: #c90000;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .role-option {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .role-option:hover {
            border-color: #c90000;
            background: #fff;
        }

        .role-option.selected {
            border-color: #c90000;
            background: #fff5f5;
        }

        .role-option input[type="radio"] {
            margin-right: 1rem;
            transform: scale(1.2);
            accent-color: #c90000;
        }

        .role-option .role-info {
            flex: 1;
        }

        .role-option .role-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .role-option .role-desc {
            font-size: 0.85rem;
            color: #666;
        }

        .persona-selector {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1rem;
        }

        .persona-selector .selector-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .persona-selector .selector-title {
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sugerencia-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }

        .sugerencia-item:hover {
            border-color: #c90000;
            background: #fff5f5;
        }

        .sugerencia-item.selected {
            border-color: #c90000;
            background: #fff5f5;
            box-shadow: 0 0 0 2px rgba(201, 0, 0, 0.2);
        }

        .sugerencia-item input[type="radio"] {
            margin-right: 1rem;
            accent-color: #c90000;
        }

        .sugerencia-info {
            flex: 1;
        }

        .sugerencia-nombre {
            font-weight: 600;
            color: #333;
        }

        .sugerencia-cedula {
            font-size: 0.85rem;
            color: #666;
        }

        .sugerencia-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            margin-left: 0.5rem;
        }

        .badge-relacionado {
            background: #d4edda;
            color: #155724;
        }

        .badge-otro {
            background: #e2e3e5;
            color: #383d41;
        }

        .nuevo-registro-option {
            border: 2px dashed #c90000 !important;
            background: #fff5f5 !important;
        }

        .nuevo-registro-option:hover {
            background: #ffe6e6 !important;
        }

        .formulario-persona-dinamico {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
            background: white;
            display: none;
        }

        .formulario-persona-dinamico.visible {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .locked-field {
            background-color: #e9ecef !important;
            cursor: not-allowed;
        }

        .section-divider {
            border-top: 2px solid #c90000;
            margin: 2rem 0;
            position: relative;
        }

        .section-divider span {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 0 1rem;
            color: #c90000;
            font-weight: 600;
        }

        .info-alert {
            background: #e7f3ff;
            border-left: 4px solid #0d6efd;
            border-radius: 0 8px 8px 0;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .info-alert i {
            color: #0d6efd;
        }

        .warning-alert {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 0 8px 8px 0;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .warning-alert i {
            color: #856404;
        }
    </style>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<div class="container mt-4" style="min-height: 80vh;">
    <!-- Encabezado -->
    <div class="card mb-4" style="border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div class="card-header" style="background: linear-gradient(135deg, #c90000 0%, #8b0000 100%); color: white; border-radius: 8px 8px 0 0;">
            <h4 class="mb-0">
                <i class='bx bx-user-plus me-2'></i>
                Solicitar Cupo para Nuevo Estudiante
            </h4>
        </div>
        <div class="card-body">
            <p class="text-muted mb-0">
                <i class='bx bx-info-circle me-1'></i>
                Complete el formulario para solicitar un cupo. Necesitamos los datos del <strong>estudiante</strong>,
                <strong>padre</strong>, <strong>madre</strong> y <strong>representante legal</strong>.
                <span class="badge bg-danger ms-2"><?= htmlspecialchars($añoEscolarActivo['fecha_escolar']) ?></span>
            </p>
        </div>
    </div>

    <form id="formInscripcion" data-origen="pagina" method="POST" novalidate>
        <!-- Hidden fields -->
        <input type="hidden" name="IdFechaEscolar" value="<?= $añoEscolarActivo['IdFecha_Escolar'] ?>">
        <input type="hidden" name="idTipoInscripcion" value="1">
        <input type="hidden" name="origen" value="representante">
        <input type="hidden" id="idCursoSeleccionado" name="idCurso">
        <input type="hidden" id="idNivelSeleccionado" name="idNivelSeleccionado">
        <input type="hidden" name="idRepresentanteLogueado" value="<?= $idPersona ?>">
        <input type="hidden" name="idStatus" value="9">

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- PASO 1: SU ROL EN ESTA INSCRIPCIÓN -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div class="card mb-4">
            <div class="card-header" style="background-color: #c90000; color: white;">
                <h5 class="mb-0">
                    <i class="fas fa-user-shield mr-2"></i>Paso 1: Su Rol en esta Inscripción
                </h5>
            </div>
            <div class="card-body">
                <!-- Información del usuario logueado -->
                <div class="user-info-card">
                    <span class="user-badge">
                        <i class='bx bx-user-check'></i>
                        Usuario Verificado
                    </span>
                    <h6><?= htmlspecialchars($usuarioLogueado['nombre'] . ' ' . $usuarioLogueado['apellido']) ?></h6>
                    <p>
                        <i class='bx bx-id-card me-1'></i>
                        <?= htmlspecialchars($usuarioLogueado['nacionalidad'] ?? 'V') ?>-<?= number_format($usuarioLogueado['cedula'], 0, '', '.') ?>
                        &nbsp;|&nbsp;
                        <i class='bx bx-envelope me-1'></i>
                        <?= htmlspecialchars($usuarioLogueado['correo'] ?? 'Sin correo') ?>
                    </p>
                </div>

                <!-- Selector de rol -->
                <div class="role-selector">
                    <h6><i class='bx bx-question-mark'></i> ¿Qué rol tiene usted respecto al estudiante?</h6>
                    <small class="text-muted d-block mb-3">Sus datos se usarán automáticamente para el rol seleccionado.</small>

                    <?php if ($sexoUsuario == 1): // Masculino ?>
                        <label class="role-option selected">
                            <input type="radio" name="rolSolicitante" value="padre" checked>
                            <div class="role-info">
                                <div class="role-title"><i class='bx bx-male'></i> Soy el Padre</div>
                                <div class="role-desc">El estudiante es mi hijo/hija. Deberá completar datos de la madre.</div>
                            </div>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="rolSolicitante" value="representante_m">
                            <div class="role-info">
                                <div class="role-title"><i class='bx bx-user-pin'></i> Soy el Representante Legal (sin ser el padre)</div>
                                <div class="role-desc">Tengo la custodia legal. Deberá completar datos del padre y la madre.</div>
                            </div>
                        </label>
                    <?php elseif ($sexoUsuario == 2): // Femenino ?>
                        <label class="role-option selected">
                            <input type="radio" name="rolSolicitante" value="madre" checked>
                            <div class="role-info">
                                <div class="role-title"><i class='bx bx-female'></i> Soy la Madre</div>
                                <div class="role-desc">El estudiante es mi hijo/hija. Deberá completar datos del padre.</div>
                            </div>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="rolSolicitante" value="representante_f">
                            <div class="role-info">
                                <div class="role-title"><i class='bx bx-user-pin'></i> Soy la Representante Legal (sin ser la madre)</div>
                                <div class="role-desc">Tengo la custodia legal. Deberá completar datos del padre y la madre.</div>
                            </div>
                        </label>
                    <?php else: ?>
                        <label class="role-option selected">
                            <input type="radio" name="rolSolicitante" value="representante_otro" checked>
                            <div class="role-info">
                                <div class="role-title"><i class='bx bx-user-pin'></i> Soy el/la Representante Legal</div>
                                <div class="role-desc">Tengo la custodia legal. Deberá completar datos del padre y la madre.</div>
                            </div>
                        </label>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- PASO 2: SELECCIÓN DE CURSO -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div class="card mb-4">
            <div class="card-header" style="background-color: #c90000; color: white;">
                <h5 class="mb-0">
                    <i class="fas fa-graduation-cap mr-2"></i>Paso 2: Curso a Inscribir
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group required-field">
                            <label for="nivel">Nivel Educativo</label>
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
                    <div class="col-md-6">
                        <div class="form-group required-field">
                            <label for="curso">Curso</label>
                            <select class="form-control" id="curso" name="idCurso" required>
                                <option value="">Primero seleccione un nivel</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- PASO 3: DATOS DEL ESTUDIANTE -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div class="card mb-4">
            <div class="card-header" style="background-color: #c90000; color: white;">
                <h5 class="mb-0"><i class="fas fa-child mr-2"></i>Paso 3: Datos del Estudiante</h5>
            </div>
            <div class="card-body">
                <div class="form-legend">
                    <i class="fas fa-asterisk"></i> Campos obligatorios
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group required-field">
                            <label for="estudianteApellidos">Apellidos</label>
                            <input type="text" class="form-control" id="estudianteApellidos" name="estudianteApellidos"
                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" minlength="3" maxlength="40"
                            onkeypress="return onlyText(event)" placeholder="Ej: Rodríguez Gómez" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group required-field">
                            <label for="estudianteNombres">Nombres</label>
                            <input type="text" class="form-control" id="estudianteNombres" name="estudianteNombres"
                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" minlength="3" maxlength="40"
                            onkeypress="return onlyText(event)" placeholder="Ej: Juan Carlos" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group required-field">
                            <label for="estudianteSexo">Sexo</label>
                            <select class="form-control" id="estudianteSexo" name="estudianteSexo" required>
                                <option value="">Seleccione</option>
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
                                <option value="">Seleccione</option>
                                <?php foreach ($nacionalidades as $nac): ?>
                                    <option value="<?= $nac['IdNacionalidad'] ?>"><?= $nac['nombre_largo'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3" id="estudianteCedulaContainer">
                        <div class="form-group required-field">
                            <label for="estudianteCedula">Cédula</label>
                            <input type="text" class="form-control" id="estudianteCedula" name="estudianteCedula"
                            minlength="7" maxlength="8" pattern="[0-9]+" onkeypress="return onlyNumber(event)" readonly required>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Ingrese la fecha de nacimiento primero
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group required-field">
                            <label for="estudianteLugarNacimiento">Lugar de Nacimiento</label>
                            <input type="text" class="form-control" id="estudianteLugarNacimiento" name="estudianteLugarNacimiento"
                            minlength="3" maxlength="40" placeholder="Ej: Araure, Portuguesa" required>
                        </div>
                    </div>
                    <div class="col-md-6" id="estudianteTelefonoContainer" style="display: none;">
                        <div class="form-group">
                            <label for="estudianteTelefono">Teléfono <small class="text-muted">(Opcional)</small></label>
                            <div class="input-group">
                                <div class="position-relative" style="max-width: 90px;">
                                    <input type="text" class="form-control text-center prefijo-telefono"
                                           id="estudianteTelefonoPrefijo_input" maxlength="4" value="+58"
                                           style="border-right: none; background: #f8f9fa; color: #c90000;">
                                    <input type="hidden" id="estudianteTelefonoPrefijo" name="estudianteTelefonoPrefijo">
                                    <div id="estudianteTelefonoPrefijo_resultados" class="autocomplete-results d-none"></div>
                                </div>
                                <input type="tel" class="form-control" id="estudianteTelefono" name="estudianteTelefono"
                                       minlength="10" maxlength="10" pattern="[0-9]+" onkeypress="return onlyNumber(event)"
                                       placeholder="4121234567">
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
                        <input type="text" class="form-control buscador-input" id="estudiantePlantel_input" autocomplete="off" placeholder="Buscar plantel...">
                        <input type="hidden" id="estudiantePlantel" name="estudiantePlantel" required>
                        <input type="hidden" id="estudiantePlantel_nombre" name="estudiantePlantel_nombre">
                        <div id="estudiantePlantel_resultados" class="autocomplete-results d-none"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Discapacidades o condiciones especiales:</label>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="discapacidadesTable">
                            <thead><tr><th>Tipo</th><th>Descripción</th><th>Acciones</th></tr></thead>
                            <tbody id="discapacidadesBody"></tbody>
                        </table>
                    </div>
                    <button type="button" id="btn-agregar-discapacidad" class="btn btn-sm btn-primary mt-2">
                        <i class="fas fa-plus"></i> Agregar discapacidad
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- PASO 4: DATOS DEL PADRE -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div class="card mb-4" id="seccionPadre">
            <div class="card-header" style="background-color: #c90000; color: white;">
                <h5 class="mb-0"><i class="fas fa-male mr-2"></i>Paso 4: Datos del Padre</h5>
            </div>
            <div class="card-body">
                <!-- Este contenido se muestra si el usuario ES el padre -->
                <div id="padreEsUsuario" style="display: none;">
                    <div class="info-alert">
                        <i class='bx bx-check-circle me-2'></i>
                        <strong>Sus datos serán utilizados como los datos del padre.</strong>
                        No necesita completar esta sección.
                    </div>
                    <input type="hidden" name="padreEsUsuario" value="1">
                </div>

                <!-- Este contenido se muestra si debe seleccionar/ingresar el padre -->
                <div id="padreFormulario">
                    <div class="persona-selector">
                        <div class="selector-header">
                            <span class="selector-title"><i class='bx bx-search-alt'></i> Seleccionar Padre</span>
                        </div>

                        <?php if (count($padresSugeridos) > 0): ?>
                            <p class="text-muted small mb-3">
                                <i class='bx bx-bulb'></i> Personas masculinas relacionadas a sus otros representados:
                            </p>
                            <?php foreach ($padresSugeridos as $idx => $padre): ?>
                                <label class="sugerencia-item">
                                    <input type="radio" name="padreSeleccion" value="existente_<?= $padre['IdPersona'] ?>"
                                           data-persona='<?= json_encode($padre) ?>'>
                                    <div class="sugerencia-info">
                                        <span class="sugerencia-nombre"><?= htmlspecialchars($padre['nombre'] . ' ' . $padre['apellido']) ?></span>
                                        <span class="sugerencia-badge <?= $padre['es_relacionado_directo'] ? 'badge-relacionado' : 'badge-otro' ?>">
                                            <?= $padre['es_relacionado_directo'] ? 'Relacionado' : 'Otro estudiante' ?>
                                        </span>
                                        <br>
                                        <span class="sugerencia-cedula">
                                            <?= htmlspecialchars($padre['nacionalidad'] ?? 'V') ?>-<?= number_format($padre['cedula'], 0, '', '.') ?>
                                            <?php if ($padre['parentesco']): ?>
                                                | <?= htmlspecialchars($padre['parentesco']) ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Opción de buscar en el sistema -->
                        <label class="sugerencia-item">
                            <input type="radio" name="padreSeleccion" value="buscar">
                            <div class="sugerencia-info">
                                <span class="sugerencia-nombre"><i class='bx bx-search'></i> Buscar otra persona en el sistema</span>
                                <br>
                                <span class="sugerencia-cedula">Si el padre ya está registrado pero no aparece arriba</span>
                            </div>
                        </label>

                        <!-- Opción de registrar nuevo -->
                        <label class="sugerencia-item nuevo-registro-option">
                            <input type="radio" name="padreSeleccion" value="nuevo" <?= count($padresSugeridos) == 0 ? 'checked' : '' ?>>
                            <div class="sugerencia-info">
                                <span class="sugerencia-nombre"><i class='bx bx-plus-circle'></i> Registrar nuevo padre</span>
                                <br>
                                <span class="sugerencia-cedula">Si es la primera vez que se registra en el sistema</span>
                            </div>
                        </label>
                    </div>

                    <!-- Buscador (se muestra al seleccionar "buscar") -->
                    <div id="padreBuscador" class="formulario-persona-dinamico">
                        <div class="form-group">
                            <label><i class='bx bx-search'></i> Buscar por cédula o nombre</label>
                            <input type="text" class="form-control buscador-input" id="padreBuscar_input"
                                   autocomplete="off" placeholder="Escriba cédula o nombre del padre...">
                            <input type="hidden" id="padreBuscar" name="padreBuscar">
                            <div id="padreBuscar_resultados" class="autocomplete-results d-none"></div>
                        </div>
                    </div>

                    <!-- Formulario completo (se muestra al seleccionar "nuevo") -->
                    <div id="padreNuevoForm" class="formulario-persona-dinamico <?= count($padresSugeridos) == 0 ? 'visible' : '' ?>">
                        <?php renderizarCamposPersona('padre', 'Padre', $data_options, false); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- PASO 5: DATOS DE LA MADRE -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div class="card mb-4" id="seccionMadre">
            <div class="card-header" style="background-color: #c90000; color: white;">
                <h5 class="mb-0"><i class="fas fa-female mr-2"></i>Paso 5: Datos de la Madre</h5>
            </div>
            <div class="card-body">
                <!-- Este contenido se muestra si el usuario ES la madre -->
                <div id="madreEsUsuario" style="display: none;">
                    <div class="info-alert">
                        <i class='bx bx-check-circle me-2'></i>
                        <strong>Sus datos serán utilizados como los datos de la madre.</strong>
                        No necesita completar esta sección.
                    </div>
                    <input type="hidden" name="madreEsUsuario" value="1">
                </div>

                <!-- Este contenido se muestra si debe seleccionar/ingresar la madre -->
                <div id="madreFormulario">
                    <div class="persona-selector">
                        <div class="selector-header">
                            <span class="selector-title"><i class='bx bx-search-alt'></i> Seleccionar Madre</span>
                        </div>

                        <?php if (count($madresSugeridas) > 0): ?>
                            <p class="text-muted small mb-3">
                                <i class='bx bx-bulb'></i> Personas femeninas relacionadas a sus otros representados:
                            </p>
                            <?php foreach ($madresSugeridas as $madre): ?>
                                <label class="sugerencia-item">
                                    <input type="radio" name="madreSeleccion" value="existente_<?= $madre['IdPersona'] ?>"
                                           data-persona='<?= json_encode($madre) ?>'>
                                    <div class="sugerencia-info">
                                        <span class="sugerencia-nombre"><?= htmlspecialchars($madre['nombre'] . ' ' . $madre['apellido']) ?></span>
                                        <span class="sugerencia-badge <?= $madre['es_relacionado_directo'] ? 'badge-relacionado' : 'badge-otro' ?>">
                                            <?= $madre['es_relacionado_directo'] ? 'Relacionada' : 'Otro estudiante' ?>
                                        </span>
                                        <br>
                                        <span class="sugerencia-cedula">
                                            <?= htmlspecialchars($madre['nacionalidad'] ?? 'V') ?>-<?= number_format($madre['cedula'], 0, '', '.') ?>
                                            <?php if ($madre['parentesco']): ?>
                                                | <?= htmlspecialchars($madre['parentesco']) ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Opción de buscar en el sistema -->
                        <label class="sugerencia-item">
                            <input type="radio" name="madreSeleccion" value="buscar">
                            <div class="sugerencia-info">
                                <span class="sugerencia-nombre"><i class='bx bx-search'></i> Buscar otra persona en el sistema</span>
                                <br>
                                <span class="sugerencia-cedula">Si la madre ya está registrada pero no aparece arriba</span>
                            </div>
                        </label>

                        <!-- Opción de registrar nuevo -->
                        <label class="sugerencia-item nuevo-registro-option">
                            <input type="radio" name="madreSeleccion" value="nuevo" <?= count($madresSugeridas) == 0 ? 'checked' : '' ?>>
                            <div class="sugerencia-info">
                                <span class="sugerencia-nombre"><i class='bx bx-plus-circle'></i> Registrar nueva madre</span>
                                <br>
                                <span class="sugerencia-cedula">Si es la primera vez que se registra en el sistema</span>
                            </div>
                        </label>
                    </div>

                    <!-- Buscador -->
                    <div id="madreBuscador" class="formulario-persona-dinamico">
                        <div class="form-group">
                            <label><i class='bx bx-search'></i> Buscar por cédula o nombre</label>
                            <input type="text" class="form-control buscador-input" id="madreBuscar_input"
                                   autocomplete="off" placeholder="Escriba cédula o nombre de la madre...">
                            <input type="hidden" id="madreBuscar" name="madreBuscar">
                            <div id="madreBuscar_resultados" class="autocomplete-results d-none"></div>
                        </div>
                    </div>

                    <!-- Formulario completo -->
                    <div id="madreNuevoForm" class="formulario-persona-dinamico <?= count($madresSugeridas) == 0 ? 'visible' : '' ?>">
                        <?php renderizarCamposPersona('madre', 'Madre', $data_options, false); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- PASO 6: REPRESENTANTE LEGAL -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div class="card mb-4" id="seccionRepresentante">
            <div class="card-header" style="background-color: #c90000; color: white;">
                <h5 class="mb-0"><i class="fas fa-user-tie mr-2"></i>Paso 6: Representante Legal</h5>
            </div>
            <div class="card-body">
                <div class="warning-alert">
                    <i class='bx bx-info-circle me-2'></i>
                    El representante legal es quien tiene la responsabilidad legal del estudiante ante la institución.
                    <strong>Puede ser el padre, la madre, u otra persona con custodia legal.</strong>
                </div>

                <div class="role-selector">
                    <h6><i class='bx bx-question-mark'></i> ¿Quién será el representante legal?</h6>

                    <label class="role-option selected">
                        <input type="radio" name="tipoRepresentante" value="madre" checked>
                        <div class="role-info">
                            <div class="role-title"><i class='bx bx-female'></i> La Madre</div>
                            <div class="role-desc">La madre será la representante legal del estudiante</div>
                        </div>
                    </label>

                    <label class="role-option">
                        <input type="radio" name="tipoRepresentante" value="padre">
                        <div class="role-info">
                            <div class="role-title"><i class='bx bx-male'></i> El Padre</div>
                            <div class="role-desc">El padre será el representante legal del estudiante</div>
                        </div>
                    </label>

                    <label class="role-option">
                        <input type="radio" name="tipoRepresentante" value="otro">
                        <div class="role-info">
                            <div class="role-title"><i class='bx bx-user-pin'></i> Otra Persona</div>
                            <div class="role-desc">Alguien diferente al padre o la madre (tutor legal, abuelo, etc.)</div>
                        </div>
                    </label>
                </div>

                <!-- Formulario para representante "otro" -->
                <div id="representanteOtroForm" style="display: none;">
                    <?php renderizarCamposPersona('representante', 'Representante', $data_options, false); ?>

                    <!-- Parentesco del representante -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group required-field">
                                <label for="representanteParentesco">Parentesco con el estudiante</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control buscador-input" id="representanteParentesco_input"
                                           autocomplete="off" placeholder="Ej: Tío, Abuelo, Tutor...">
                                    <input type="hidden" id="representanteParentesco" name="representanteParentesco">
                                    <input type="hidden" id="representanteParentesco_nombre" name="representanteParentesco_nombre">
                                    <div id="representanteParentesco_resultados" class="autocomplete-results d-none"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- CONTACTO DE EMERGENCIA -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div class="card mb-4" style="border: 2px solid #ffc107;">
            <div class="card-header" style="background-color: #fff3cd; color: #856404;">
                <h5 class="mb-0"><i class="fas fa-phone-alt mr-2"></i>Contacto de Emergencia</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group required-field">
                            <label for="emergenciaNombre">Nombre Completo</label>
                            <input type="text" class="form-control" id="emergenciaNombre" name="emergenciaNombre"
                                placeholder="Nombre y Apellido" minlength="5" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group required-field">
                            <label for="emergenciaCedula">Cédula</label>
                            <div class="input-group">
                                <select id="emergenciaNacionalidad" name="emergenciaNacionalidad" class="form-select"
                                    style="max-width: 60px; border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: none; text-align: center; font-weight: bold; background: #f8f9fa; color: #c90000;">
                                    <option value="1">V</option>
                                    <option value="2">E</option>
                                </select>
                                <input type="text" class="form-control" id="emergenciaCedula" name="emergenciaCedula"
                                    placeholder="12345678" minlength="7" maxlength="8" pattern="[0-9]+"
                                    onkeypress="return onlyNumber(event)" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group required-field">
                            <label for="emergenciaParentesco">Parentesco</label>
                            <div class="position-relative">
                                <input type="text" class="form-control buscador-input" id="emergenciaParentesco_input"
                                    autocomplete="off" placeholder="Buscar parentesco...">
                                <input type="hidden" id="emergenciaParentesco" name="emergenciaParentesco" required>
                                <input type="hidden" id="emergenciaParentesco_nombre" name="emergenciaParentesco_nombre">
                                <div id="emergenciaParentesco_resultados" class="autocomplete-results d-none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group required-field">
                            <label for="emergenciaCelular">Teléfono</label>
                            <div class="input-group">
                                <div class="position-relative" style="max-width: 90px;">
                                    <input type="text" class="form-control text-center prefijo-telefono"
                                        id="emergenciaCelularPrefijo_input" maxlength="4" value="+58"
                                        style="border-right: none; background: #f8f9fa; color: #c90000;">
                                    <input type="hidden" id="emergenciaCelularPrefijo" name="emergenciaCelularPrefijo">
                                    <div id="emergenciaCelularPrefijo_resultados" class="autocomplete-results d-none"></div>
                                </div>
                                <input type="tel" class="form-control" id="emergenciaCelular" name="emergenciaCelular"
                                    minlength="10" maxlength="10" pattern="[0-9]+"
                                    onkeypress="return onlyNumber(event)" placeholder="4121234567" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones -->
        <div class="d-flex justify-content-between mt-4 mb-5">
            <a href="representado.php" class="btn btn-outline-danger btn-lg">
                <i class='bx bx-arrow-back'></i> Volver
            </a>
            <button type="submit" class="btn btn-danger btn-lg" id="btnEnviarFormulario">
                <i class='bx bxs-send'></i> Enviar Solicitud
            </button>
        </div>
    </form>
</div>

<?php include '../../layouts/footer.php'; ?>

<!-- Incluir scripts de validación -->
<script src="../../../assets/js/buscador_generico.js"></script>
<script src="../../../assets/js/validaciones_solicitud.js?v=6"></script>
<script src="../../../assets/js/solicitud_cupo.js?v=16"></script>
<script src="../../../assets/js/validacion.js?v=4"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sexoUsuario = <?= json_encode($sexoUsuario) ?>;
    const cursosOriginales = <?= json_encode($cursos) ?>;
    const niveles = <?= json_encode($niveles) ?>;

    // === Inicializar buscadores ===
    new BuscadorGenerico("estudiantePlantel_input", "estudiantePlantel_resultados", "plantel", "estudiantePlantel", "estudiantePlantel_nombre");
    new BuscadorGenerico("estudianteTelefonoPrefijo_input", "estudianteTelefonoPrefijo_resultados", "prefijo", "estudianteTelefonoPrefijo", null);
    new BuscadorGenerico("emergenciaParentesco_input", "emergenciaParentesco_resultados", "parentesco", "emergenciaParentesco", "emergenciaParentesco_nombre");
    new BuscadorGenerico("emergenciaCelularPrefijo_input", "emergenciaCelularPrefijo_resultados", "prefijo", "emergenciaCelularPrefijo", null);
    new BuscadorGenerico("representanteParentesco_input", "representanteParentesco_resultados", "parentesco", "representanteParentesco", "representanteParentesco_nombre");

    // Buscadores para padre/madre
    new BuscadorGenerico("padreBuscar_input", "padreBuscar_resultados", "persona_masculino", "padreBuscar", null);
    new BuscadorGenerico("madreBuscar_input", "madreBuscar_resultados", "persona_femenino", "madreBuscar", null);

    // === Manejo de selección de rol del usuario ===
    const roleOptions = document.querySelectorAll('.role-option');
    roleOptions.forEach(option => {
        option.addEventListener('click', function() {
            const parentSelector = this.closest('.role-selector');
            parentSelector.querySelectorAll('.role-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input[type="radio"]').checked = true;

            const inputName = this.querySelector('input[type="radio"]').name;
            if (inputName === 'rolSolicitante') {
                actualizarFormularioSegunRol(this.querySelector('input[type="radio"]').value);
            } else if (inputName === 'tipoRepresentante') {
                actualizarSeccionRepresentante(this.querySelector('input[type="radio"]').value);
            }
        });
    });

    function actualizarFormularioSegunRol(rol) {
        const padreEsUsuario = document.getElementById('padreEsUsuario');
        const padreFormulario = document.getElementById('padreFormulario');
        const madreEsUsuario = document.getElementById('madreEsUsuario');
        const madreFormulario = document.getElementById('madreFormulario');
        const seccionRepresentante = document.getElementById('seccionRepresentante');
        const representanteOtroForm = document.getElementById('representanteOtroForm');

        // Resetear todas las secciones
        padreEsUsuario.style.display = 'none';
        padreFormulario.style.display = 'block';
        madreEsUsuario.style.display = 'none';
        madreFormulario.style.display = 'block';
        padreEsUsuario.querySelector('input[name="padreEsUsuario"]').disabled = true;
        madreEsUsuario.querySelector('input[name="madreEsUsuario"]').disabled = true;

        // Por defecto mostrar sección representante
        seccionRepresentante.style.display = 'block';

        if (rol === 'padre') {
            // Usuario es el padre - puede elegir quién es el representante legal
            padreEsUsuario.style.display = 'block';
            padreFormulario.style.display = 'none';
            padreEsUsuario.querySelector('input[name="padreEsUsuario"]').disabled = false;
            // Mostrar opción de elegir representante (padre, madre, u otro)
            seccionRepresentante.style.display = 'block';
        } else if (rol === 'madre') {
            // Usuario es la madre - puede elegir quién es el representante legal
            madreEsUsuario.style.display = 'block';
            madreFormulario.style.display = 'none';
            madreEsUsuario.querySelector('input[name="madreEsUsuario"]').disabled = false;
            // Mostrar opción de elegir representante (padre, madre, u otro)
            seccionRepresentante.style.display = 'block';
        } else if (rol === 'representante_m' || rol === 'representante_f' || rol === 'representante_otro') {
            // Usuario ES el representante legal (no es padre ni madre)
            // Ocultar la sección de elegir representante ya que él mismo lo es
            seccionRepresentante.style.display = 'none';
            representanteOtroForm.style.display = 'none';
            // Desactivar campos del representante para que no sean requeridos
            representanteOtroForm.querySelectorAll('input, select').forEach(el => {
                el.dataset.wasRequired = el.required;
                el.disabled = true;
                el.required = false;
            });
            // Establecer valor oculto para indicar que el usuario es el representante
            document.querySelector('input[name="tipoRepresentante"][value="otro"]').checked = false;
            // Crear/actualizar campo oculto para indicar que el usuario logueado es el representante
            let hiddenRepUsuario = document.getElementById('representanteEsUsuario');
            if (!hiddenRepUsuario) {
                hiddenRepUsuario = document.createElement('input');
                hiddenRepUsuario.type = 'hidden';
                hiddenRepUsuario.id = 'representanteEsUsuario';
                hiddenRepUsuario.name = 'representanteEsUsuario';
                seccionRepresentante.appendChild(hiddenRepUsuario);
            }
            hiddenRepUsuario.value = '1';
            hiddenRepUsuario.disabled = false;
        }

        // Si NO es representante, asegurarse de que el campo oculto esté deshabilitado
        if (rol === 'padre' || rol === 'madre') {
            const hiddenRepUsuario = document.getElementById('representanteEsUsuario');
            if (hiddenRepUsuario) {
                hiddenRepUsuario.disabled = true;
                hiddenRepUsuario.value = '0';
            }
        }
    }

    function actualizarSeccionRepresentante(tipo) {
        const representanteOtroForm = document.getElementById('representanteOtroForm');
        if (tipo === 'otro') {
            representanteOtroForm.style.display = 'block';
            representanteOtroForm.querySelectorAll('input, select').forEach(el => {
                if (el.dataset.wasRequired !== undefined) {
                    el.required = el.dataset.wasRequired === 'true';
                }
            });
        } else {
            representanteOtroForm.style.display = 'none';
            representanteOtroForm.querySelectorAll('input, select').forEach(el => {
                el.dataset.wasRequired = el.required;
                el.required = false;
            });
        }
    }

    // === Manejo de selección de padre/madre ===
    function setupPersonaSelector(tipo) {
        const radios = document.querySelectorAll(`input[name="${tipo}Seleccion"]`);
        const buscador = document.getElementById(`${tipo}Buscador`);
        const nuevoForm = document.getElementById(`${tipo}NuevoForm`);

        // Agregar click handler a los items de sugerencia
        document.querySelectorAll(`input[name="${tipo}Seleccion"]`).forEach(radio => {
            radio.closest('.sugerencia-item')?.addEventListener('click', function() {
                document.querySelectorAll(`input[name="${tipo}Seleccion"]`).forEach(r => {
                    r.closest('.sugerencia-item')?.classList.remove('selected');
                });
                this.classList.add('selected');
            });
        });

        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                actualizarEstadoFormularioPersona(tipo, this.value);
            });
        });

        // Inicializar estado según el radio seleccionado por defecto
        const radioSeleccionado = document.querySelector(`input[name="${tipo}Seleccion"]:checked`);
        if (radioSeleccionado) {
            actualizarEstadoFormularioPersona(tipo, radioSeleccionado.value);
        } else if (nuevoForm) {
            // Si no hay sugerencias (formulario nuevo visible por defecto), habilitarlo
            const esVisible = nuevoForm.classList.contains('visible');
            toggleFormFields(nuevoForm, esVisible);
        }
    }

    function actualizarEstadoFormularioPersona(tipo, valor) {
        const buscador = document.getElementById(`${tipo}Buscador`);
        const nuevoForm = document.getElementById(`${tipo}NuevoForm`);

        // Ocultar ambos por defecto
        if (buscador) buscador.classList.remove('visible');
        if (nuevoForm) nuevoForm.classList.remove('visible');

        if (valor === 'buscar') {
            if (buscador) buscador.classList.add('visible');
            // Deshabilitar campos del formulario nuevo
            if (nuevoForm) toggleFormFields(nuevoForm, false);
        } else if (valor === 'nuevo') {
            if (nuevoForm) {
                nuevoForm.classList.add('visible');
                toggleFormFields(nuevoForm, true);
            }
        } else if (valor && valor.startsWith('existente_')) {
            // Persona seleccionada de sugerencias
            const radio = document.querySelector(`input[name="${tipo}Seleccion"][value="${valor}"]`);
            if (radio) {
                const personaData = JSON.parse(radio.dataset.persona || '{}');
                // Guardar ID en hidden
                document.getElementById(`${tipo}Buscar`).value = personaData.IdPersona;
            }
            if (nuevoForm) toggleFormFields(nuevoForm, false);
        } else {
            // Por defecto, deshabilitar el formulario nuevo
            if (nuevoForm) toggleFormFields(nuevoForm, false);
        }
    }

    function toggleFormFields(container, enable) {
        container.querySelectorAll('input, select, textarea').forEach(el => {
            if (enable) {
                el.disabled = false;
                if (el.dataset.wasRequired === 'true') el.required = true;
            } else {
                el.dataset.wasRequired = el.required;
                el.disabled = true;
                el.required = false;
            }
        });
    }

    setupPersonaSelector('padre');
    setupPersonaSelector('madre');

    // === Filtro de cursos por nivel ===
    const selectNivel = document.getElementById("nivel");
    const selectCurso = document.getElementById("curso");

    selectNivel.addEventListener("change", function() {
        const nivelSeleccionado = this.value.trim();
        selectCurso.innerHTML = '<option value="">Seleccione un curso</option>';

        if (nivelSeleccionado !== "") {
            // Filtrar cursos que pertenecen al nivel seleccionado
            const cursosFiltrados = cursosOriginales.filter(c => String(c.IdNivel) === String(nivelSeleccionado));
            cursosFiltrados.forEach(curso => {
                const opt = document.createElement("option");
                opt.value = curso.IdCurso;
                opt.textContent = curso.curso;
                selectCurso.appendChild(opt);
            });
        }
        document.getElementById('idNivelSeleccionado').value = nivelSeleccionado;
    });

    selectCurso.addEventListener("change", function() {
        document.getElementById('idCursoSeleccionado').value = this.value;
        actualizarCamposSegunCurso(this.value);
    });

    function actualizarCamposSegunCurso(idCurso) {
        const cedulaContainer = document.getElementById('estudianteCedulaContainer');
        const plantelContainer = document.getElementById('estudiantePlantelContainer');
        const cedulaInput = document.getElementById('estudianteCedula');
        const plantelInput = document.getElementById('estudiantePlantel');
        const plantelInputVisible = document.getElementById('estudiantePlantel_input');

        if (parseInt(idCurso) === 1) {
            cedulaContainer.style.display = 'none';
            cedulaInput.required = false;
            plantelContainer.style.display = 'none';
            plantelInput.required = false;
            plantelInput.value = '1';
            plantelInputVisible.value = 'U.E.C "Fermín Toro"';
        } else if (idCurso) {
            cedulaContainer.style.display = '';
            cedulaInput.required = true;
            cedulaInput.readOnly = true;
            plantelContainer.style.display = '';
            plantelInput.required = true;
            plantelInput.value = '';
            plantelInputVisible.value = '';
        }
    }

    // === Fecha de nacimiento ===
    const fechaNacInput = document.getElementById('estudianteFechaNacimiento');
    if (fechaNacInput) {
        const hoy = new Date();
        const fechaMax = new Date(hoy.getFullYear() - 6, hoy.getMonth(), hoy.getDate());
        const fechaMin = new Date(hoy.getFullYear() - 18, hoy.getMonth(), hoy.getDate());
        const fmt = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
        fechaNacInput.min = fmt(fechaMin);
        fechaNacInput.max = fmt(fechaMax);

        fechaNacInput.addEventListener('blur', function() {
            if (!this.value) return;
            const fecha = new Date(this.value + 'T00:00:00');
            let edad = hoy.getFullYear() - fecha.getFullYear();
            const m = hoy.getMonth() - fecha.getMonth();
            if (m < 0 || (m === 0 && hoy.getDate() < fecha.getDate())) edad--;

            if (edad < 6 || edad > 18) {
                Swal.fire({icon:'warning', title:'Edad no válida', text:`El estudiante debe tener entre 6 y 18 años (edad: ${edad})`, confirmButtonColor:'#c90000'});
                this.value = '';
                return;
            }

            const cursoActual = parseInt(document.getElementById('curso')?.value || 0);
            if (cursoActual !== 1) {
                const cedulaInput = document.getElementById('estudianteCedula');
                const cedulaLabel = document.querySelector('label[for="estudianteCedula"]');
                if (cedulaInput) {
                    cedulaInput.readOnly = false;
                    cedulaInput.nextElementSibling.style.display = 'none';
                    if (cedulaLabel) {
                        cedulaLabel.textContent = edad < 10 ? 'Cédula escolar' : 'Cédula';
                        cedulaInput.maxLength = edad < 10 ? 11 : 8;
                    }
                }
                const telContainer = document.getElementById('estudianteTelefonoContainer');
                if (telContainer) telContainer.style.display = edad >= 10 ? 'block' : 'none';
            }
        });
    }

    // Inicializar rol por defecto
    const rolInicial = document.querySelector('input[name="rolSolicitante"]:checked')?.value;
    if (rolInicial) actualizarFormularioSegunRol(rolInicial);

    // Inicializar representante
    actualizarSeccionRepresentante('madre');

    // === Envío del formulario ===
    const formInscripcion = document.getElementById('formInscripcion');
    const btnEnviar = document.getElementById('btnEnviarFormulario');

    formInscripcion.addEventListener('submit', function(e) {
        e.preventDefault();
        enviarFormularioRepresentante();
    });

    function enviarFormularioRepresentante() {
        const formData = new FormData(formInscripcion);
        const rolSolicitante = document.querySelector('input[name="rolSolicitante"]:checked')?.value;

        // Limpiar validaciones anteriores
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        let errores = [];

        // Validar curso
        if (!document.getElementById('idCursoSeleccionado').value) {
            errores.push('Debe seleccionar un curso');
            document.getElementById('curso').classList.add('is-invalid');
        }

        // Validar datos básicos del estudiante
        const camposEstudiante = ['estudianteNombres', 'estudianteApellidos', 'estudianteFechaNacimiento',
                                   'estudianteLugarNacimiento', 'estudianteCorreo', 'estudianteSexo', 'estudianteNacionalidad'];

        camposEstudiante.forEach(campo => {
            const el = document.getElementById(campo);
            if (el && el.required && !el.value) {
                errores.push(`Falta: ${el.previousElementSibling?.textContent || campo}`);
                el.classList.add('is-invalid');
            }
        });

        // Validar padre (si no es el usuario)
        const padreEsUsuarioVisible = document.getElementById('padreEsUsuario').style.display !== 'none';
        if (!padreEsUsuarioVisible) {
            const padreSeleccion = document.querySelector('input[name="padreSeleccion"]:checked')?.value;
            if (!padreSeleccion) {
                errores.push('Debe seleccionar una opción para el padre');
            } else if (padreSeleccion === 'buscar') {
                if (!document.getElementById('padreBuscar').value) {
                    errores.push('Debe buscar y seleccionar un padre del sistema');
                    document.getElementById('padreBuscar_input').classList.add('is-invalid');
                }
            } else if (padreSeleccion === 'nuevo') {
                // Validar campos del formulario nuevo del padre
                const camposPadre = document.querySelectorAll('#padreNuevoForm input[required], #padreNuevoForm select[required]');
                camposPadre.forEach(campo => {
                    if (!campo.disabled && !campo.value) {
                        errores.push(`Falta dato del padre: ${campo.name}`);
                        campo.classList.add('is-invalid');
                    }
                });
            }
            // Si es "existente_XXX", ya está validado
        }

        // Validar madre (si no es el usuario)
        const madreEsUsuarioVisible = document.getElementById('madreEsUsuario').style.display !== 'none';
        if (!madreEsUsuarioVisible) {
            const madreSeleccion = document.querySelector('input[name="madreSeleccion"]:checked')?.value;
            if (!madreSeleccion) {
                errores.push('Debe seleccionar una opción para la madre');
            } else if (madreSeleccion === 'buscar') {
                if (!document.getElementById('madreBuscar').value) {
                    errores.push('Debe buscar y seleccionar una madre del sistema');
                    document.getElementById('madreBuscar_input').classList.add('is-invalid');
                }
            } else if (madreSeleccion === 'nuevo') {
                // Validar campos del formulario nuevo de la madre
                const camposMadre = document.querySelectorAll('#madreNuevoForm input[required], #madreNuevoForm select[required]');
                camposMadre.forEach(campo => {
                    if (!campo.disabled && !campo.value) {
                        errores.push(`Falta dato de la madre: ${campo.name}`);
                        campo.classList.add('is-invalid');
                    }
                });
            }
        }

        // Validar representante legal (solo si la sección está visible)
        const seccionRepresentante = document.getElementById('seccionRepresentante');
        const representanteEsUsuario = document.getElementById('representanteEsUsuario');

        // Solo validar si la sección está visible (el usuario NO es el representante)
        if (seccionRepresentante.style.display !== 'none') {
            const tipoRepresentante = document.querySelector('input[name="tipoRepresentante"]:checked')?.value;
            if (tipoRepresentante === 'otro') {
                const camposRep = document.querySelectorAll('#representanteOtroForm input[required], #representanteOtroForm select[required]');
                camposRep.forEach(campo => {
                    if (!campo.disabled && !campo.value) {
                        errores.push(`Falta dato del representante: ${campo.name}`);
                        campo.classList.add('is-invalid');
                    }
                });
            }
        }

        // Validar contacto de emergencia
        if (!document.getElementById('emergenciaNombre').value) {
            errores.push('Falta el nombre del contacto de emergencia');
            document.getElementById('emergenciaNombre').classList.add('is-invalid');
        }
        if (!document.getElementById('emergenciaCedula').value) {
            errores.push('Falta la cédula del contacto de emergencia');
            document.getElementById('emergenciaCedula').classList.add('is-invalid');
        }
        if (!document.getElementById('emergenciaCelular').value) {
            errores.push('Falta el teléfono del contacto de emergencia');
            document.getElementById('emergenciaCelular').classList.add('is-invalid');
        }

        // Si hay errores, mostrarlos
        if (errores.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                html: '<ul style="text-align:left;">' + errores.slice(0, 5).map(e => `<li>${e}</li>`).join('') + '</ul>' +
                      (errores.length > 5 ? `<p>...y ${errores.length - 5} errores más</p>` : ''),
                confirmButtonColor: '#c90000'
            });
            return;
        }

        // Verificar correos duplicados antes de continuar
        if (!verificarCorreosAntesDeEnviar()) {
            return;
        }

        // Agregar IdCurso al formData
        formData.append('IdCurso', document.getElementById('idCursoSeleccionado').value);
        formData.append('origenSolicitud', 'representante');

        // Deshabilitar botón
        btnEnviar.disabled = true;
        btnEnviar.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Enviando...';

        // Enviar formulario
        fetch('../../../controladores/InscripcionController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Solicitud enviada!',
                    html: `Número de solicitud: <strong>${data.numeroSolicitud}</strong><br>
                           Código de seguimiento: <strong>${data.codigo_inscripcion}</strong>`,
                    confirmButtonColor: '#28a745'
                }).then(() => {
                    window.location.href = 'representado.php';
                });
            } else {
                throw new Error(data.message || 'Error al procesar la solicitud');
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Error de conexión. Intente nuevamente.',
                confirmButtonColor: '#c90000'
            });
        })
        .finally(() => {
            btnEnviar.disabled = false;
            btnEnviar.innerHTML = '<i class="bx bxs-send"></i> Enviar Solicitud';
        });
    }

    // === VALIDACIÓN DE CORREO DUPLICADO ===
    // Variable para rastrear correos con errores
    const correosConError = new Set();

    async function verificarCorreoDuplicado(inputCorreo, nombrePersona, idPersonaExcluir = null) {
        const correo = inputCorreo.value.trim().toLowerCase();

        if (correo.length === 0) {
            inputCorreo.classList.remove('is-invalid', 'is-valid');
            correosConError.delete(inputCorreo.id);
            return true;
        }

        // Validar formato primero
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(correo)) {
            inputCorreo.classList.remove('is-valid');
            inputCorreo.classList.add('is-invalid');
            correosConError.add(inputCorreo.id);
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
                correosConError.add(inputCorreo.id);

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
                correosConError.add(inputCorreo.id);

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
                correosConError.delete(inputCorreo.id);
                return true;
            }
        } catch (error) {
            console.error('Error al verificar correo:', error);
            correosConError.delete(inputCorreo.id);
            return true;
        }
    }

    // Función para verificar correos antes de enviar
    function verificarCorreosAntesDeEnviar() {
        if (correosConError.size > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Correos inválidos',
                html: 'Hay correos electrónicos duplicados o inválidos. Por favor corrija los campos marcados antes de continuar.',
                confirmButtonColor: '#c90000'
            });
            const primerCampo = document.getElementById(Array.from(correosConError)[0]);
            if (primerCampo) primerCampo.focus();
            return false;
        }
        return true;
    }

    // Aplicar validación a todos los campos de correo
    const camposCorreoConfig = [
        { id: 'estudianteCorreo', nombre: 'Estudiante' },
        { id: 'padreCorreo', nombre: 'Padre' },
        { id: 'madreCorreo', nombre: 'Madre' },
        { id: 'representanteCorreo', nombre: 'Representante Legal' }
    ];

    camposCorreoConfig.forEach(campo => {
        const input = document.getElementById(campo.id);
        if (input) {
            input.addEventListener('blur', function() {
                verificarCorreoDuplicado(this, campo.nombre);
            });
        }
    });
});
</script>
