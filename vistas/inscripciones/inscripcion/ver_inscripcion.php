<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    header("Location: ../../login/login.php");
    exit();
}

require_once __DIR__ . '/../../../controladores/Notificaciones.php';
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Inscripcion.php';
require_once __DIR__ . '/../../../modelos/Status.php';
require_once __DIR__ . '/../../../modelos/Requisito.php';
require_once __DIR__ . '/../../../modelos/Seccion.php';
require_once __DIR__ . '/../../../modelos/Discapacidad.php';
require_once __DIR__ . '/../../../modelos/FechaEscolar.php';

// 🔹 Manejo de alertas
$alert = $_SESSION['alert'] ?? null;
$message = $_SESSION['message'] ?? '';
unset($_SESSION['alert'], $_SESSION['message']);

if ($alert) {
    $alerta = match ($alert) {
        'success' => Notificaciones::exito($message ?: 'Operación realizada correctamente.'),
        'error' => Notificaciones::advertencia($message ?: 'Ocurrió un error. Por favor verifique.'),
        default => null
    };
    if ($alerta) Notificaciones::mostrar($alerta);
}

// 🔹 Obtener ID de inscripción
$idInscripcion = $_GET['id'] ?? 0;
if ($idInscripcion <= 0) {
    header("Location: inscripcion.php?error=id_invalido");
    exit();
}

// 🔹 Conexión
$database = new Database();
$conexion = $database->getConnection();

$inscripcionModel = new Inscripcion($conexion);
$inscripcion = $inscripcionModel->obtenerDetallePorId($idInscripcion);
if (!$inscripcion) {
    header("Location: inscripcion.php?error=inscripcion_no_encontrada");
    exit();
}

// 🔹 Status y Requisitos
$statusModel = new Status($conexion);
$todosStatus = $statusModel->obtenerStatusInscripcion();

$requisitoModel = new Requisito($conexion);
$requisitos = $requisitoModel->obtenerConCumplidoPorNivel($idInscripcion);

// 🔹 Obtener Id del status “Inscrito”
$idInscrito = 11;
foreach ($todosStatus as $st) {
    if ($st['status'] === 'Inscrito') {
        $idInscrito = $st['IdStatus'];
        break;
    }
}

// 🔹 Discapacidades
$discapacidadModel = new Discapacidad($conexion);
$discapacidades = $discapacidadModel->obtenerPorPersona($inscripcion['id_estudiante']);

// 🔹 Verificar si la inscripción pertenece al año escolar activo
$fechaEscolarModel = new FechaEscolar($conexion);
$añoEscolarActivo = $fechaEscolarModel->obtenerActivo();
$esAñoEscolarActivo = $añoEscolarActivo && ($inscripcion['IdFecha_Escolar'] == $añoEscolarActivo['IdFecha_Escolar']);

// 🔹 Mensajes GET (opcional)
if (isset($_GET['success'])) {
    $_SESSION['alert'] = 'success';
    $_SESSION['message'] = match ($_GET['success']) {
        'status_actualizado' => 'Estado actualizado correctamente',
        'requisito_actualizado' => 'Requisito actualizado correctamente',
        default => 'Operación realizada con éxito'
    };
}

if (isset($_GET['error'])) {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = match ($_GET['error']) {
        'error_actualizacion' => 'Error al actualizar',
        'error_interno' => 'Error interno del servidor',
        default => 'Ocurrió un error'
    };
}

// 🔹 Helpers de teléfonos
function separarTelefonosPorTipo($numerosStr, $tiposStr) {
    if (empty($numerosStr)) return ['personales' => [], 'laborales' => []];
    $numeros = explode('||', $numerosStr);
    $tipos = explode('||', $tiposStr);
    $telefonos = ['personales' => [], 'laborales' => []];
    foreach ($numeros as $i => $num) {
        $tipo = $tipos[$i] ?? '';
        $cat = ($tipo === 'Trabajo') ? 'laborales' : 'personales';
        $telefonos[$cat][] = ['numero' => $num, 'tipo' => $tipo];
    }
    return $telefonos;
}

// 🔹 Helper para calcular edad
function calcularEdad($fechaNacimiento) {
    if (empty($fechaNacimiento)) return null;
    $fecha = new DateTime($fechaNacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha);
    return $edad->y;
}

// Función para mostrar teléfonos con estilo
function mostrarTelefonos($telefonos) {
    if (empty($telefonos)) return null;
    $html = '<div class="telefonos-list">';
    foreach ($telefonos as $t) {
        $badge = match($t['tipo']) {
            'Celular' => 'bg-primary', 'Trabajo' => 'bg-warning text-dark',
            'Habitación' => 'bg-info', default => 'bg-secondary'
        };
        $icon = match($t['tipo']) {
            'Celular' => 'fa-mobile-alt', 'Trabajo' => 'fa-briefcase',
            'Habitación' => 'fa-phone', default => 'fa-phone'
        };
        $html .= "<div class='telefono-item d-flex align-items-center mb-1'>
                    <i class='fas {$icon} me-2 text-muted'></i>
                    <span class='telefono-numero'>" . htmlspecialchars($t['numero']) . "</span>
                    <span class='badge {$badge} ms-2'>" . htmlspecialchars($t['tipo']) . "</span>
                 </div>";
    }
    return $html . '</div>';
}

// 🔹 Verificar parentesco del representante
$esPadreRepresentante = ($inscripcion['responsable_parentesco'] === 'Padre' || $inscripcion['IdParentesco'] == 1);
$esMadreRepresentante = ($inscripcion['responsable_parentesco'] === 'Madre' || $inscripcion['IdParentesco'] == 2);

// 🔹 Información del modificador (desde inscripcion_historial)
require_once __DIR__ . '/../../../modelos/InscripcionHistorial.php';
$historialModel = new InscripcionHistorial($conexion);
$ultimoCambio = $historialModel->obtenerUltimoCambio($idInscripcion);

if ($ultimoCambio) {
    $nombreModificador = htmlspecialchars($ultimoCambio['usuario_nombre'] . ' ' . $ultimoCambio['usuario_apellido']);
    $fechaModificacion = date('d/m/Y H:i', strtotime($ultimoCambio['fecha_cambio']));
} else {
    $nombreModificador = 'Sin cambios registrados';
    $fechaModificacion = 'Sin cambios registrados';
}

// 🔹 Secciones disponibles (optimizadas)
$idCursoActual = $inscripcion['IdCurso'] ?? null;
$idCursoSeccionActual = $inscripcion['IdCurso_Seccion'] ?? null;

// Obtener secciones disponibles (excluyendo la sección "Inscripción" y del mismo curso)
$seccionesDisponibles = [];
$seccionesConCupo = [];
$maxMismosUrbanismo = 0;
$hayRecomendada = false;

if ($idCursoActual && $inscripcion['IdStatus'] == $idInscrito) {
    $seccionModel = new Seccion($conexion);
    $resultadoSecciones = $seccionModel->obtenerDisponiblesConCupo(
        $idCursoActual,
        $inscripcion['estudiante_id_urbanismo'],
        $idCursoSeccionActual
    );
    $seccionesDisponibles = $resultadoSecciones['todas'];
    $seccionesConCupo = $resultadoSecciones['con_cupo'];
    $maxMismosUrbanismo = $resultadoSecciones['max_urbanismo'];
    $hayRecomendada = $resultadoSecciones['hay_recomendada'];
}
?>

<head>
    <title>UECFT Araure - Detalles de Inscripción</title>
</head>
<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <!-- Header con código y acciones -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Detalles de Inscripción</h2>
                            <p class="text-muted mb-0">Código: <strong><?= htmlspecialchars($inscripcion['codigo_inscripcion']) ?></strong></p>
                        </div>
                        <div>
                            <a href="inscripcion.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Volver
                            </a>
                        </div>
                    </div>

                    <div class="status-bar d-flex align-items-center">
                        <?php 
                        $mostrarRechazado = true;
                        $idStatusInscrito = 11;
                        $idStatusRechazado = 12;
                        $estaRechazado = $inscripcion['IdStatus'] == $idStatusRechazado;
                        
                        if ($estaRechazado): ?>
                            <div class="status-step rejected-mode active" 
                                data-id="<?= $idStatusRechazado ?>" 
                                data-nombre="Rechazada">
                                <span class="status-icon">
                                    <i class="fas fa-times-circle"></i>
                                </span>
                                <span class="status-label">Rechazada</span>
                                <div class="rejected-message">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Inscripción rechazada
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($todosStatus as $index => $status): 
                                $esInscrito = $inscripcion['IdStatus'] == $idStatusInscrito;
                                $esRechazado = $status['status'] === 'Rechazada' || $status['IdStatus'] == $idStatusRechazado;
                                
                                if ($esInscrito && $esRechazado) {
                                    $mostrarRechazado = false;
                                    continue;
                                }
                            ?>
                                <div class="status-step <?= $status['IdStatus'] == $inscripcion['IdStatus'] ? 'active' : '' ?> 
                                    <?= $status['IdStatus'] < $inscripcion['IdStatus'] ? 'completed' : '' ?>"
                                    data-id="<?= $status['IdStatus'] ?>" 
                                    data-nombre="<?= htmlspecialchars($status['status']) ?>">
                                    <span class="status-icon">
                                        <i class="fas <?= $status['IdStatus'] <= $inscripcion['IdStatus'] ? 'fa-check-circle' : 'fa-circle' ?>"></i>
                                    </span>
                                    <span class="status-label"><?= htmlspecialchars($status['status']) ?></span>
                                    
                                    <?php if ($esInscrito && $esRechazado && $mostrarRechazado === false): ?>
                                    <span class="status-badge">✗</span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($index !== count($todosStatus) - 1 && (!$esRechazado || $mostrarRechazado)): ?>
                                <div class="status-line"></div>
                                <?php endif; ?>
                                
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Información de modificación + Historial -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="modification-info-card historial-toggle" id="btn-toggle-historial" role="button" title="Click para ver historial completo">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-history me-2 text-info"></i>
                                        <span class="text-muted small">Última modificación:</span>
                                        <span class="ms-1 fw-medium"><?= $fechaModificacion ?></span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-edit me-2 text-info"></i>
                                        <span class="text-muted small">Por:</span>
                                        <span class="ms-1 fw-medium"><?= $nombreModificador ?></span>
                                    </div>
                                    <div class="d-flex align-items-center historial-hint">
                                        <i class="fas fa-chevron-down me-1 historial-chevron"></i>
                                        <span class="small text-primary">Ver historial</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Historial expandible -->
                            <div class="collapse" id="historial-container">
                                <div class="historial-panel" id="historial-content">
                                    <!-- El contenido se carga dinámicamente -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($inscripcion['codigo_pago'])): ?>
                    <!-- Información de Pago -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="alert alert-success mb-0">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="d-flex align-items-center mb-2 mb-md-0">
                                        <i class="fas fa-check-circle me-2 fa-lg"></i>
                                        <div>
                                            <strong>Pago Validado</strong>
                                            <span class="ms-2 badge bg-light text-success border border-success">
                                                <?= htmlspecialchars($inscripcion['codigo_pago']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center text-muted small flex-wrap">
                                        <span class="me-3">
                                            <i class="fas fa-calendar-check me-1"></i>
                                            <?= !empty($inscripcion['fecha_validacion_pago'])
                                                ? date('d/m/Y H:i', strtotime($inscripcion['fecha_validacion_pago']))
                                                : 'N/A' ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-user-shield me-1"></i>
                                            <?= !empty($inscripcion['validador_nombre'])
                                                ? htmlspecialchars($inscripcion['validador_nombre'] . ' ' . $inscripcion['validador_apellido'])
                                                : 'N/A' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Información General -->
                    <div class="row mb-4">
                        <!-- Datos de la Inscripción -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información General</h5>
                                    <?php if ($inscripcion['IdStatus'] == $idInscrito && !empty($seccionesDisponibles)): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary text-light" id="btn-cambiar-seccion">
                                        <i class="fas fa-exchange-alt me-1"></i> Cambiar Sección
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <div class="info-card">
                                        <h6 class="section-title">Datos de la Inscripción</h6>
                                        <div class="mb-2">
                                            <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($inscripcion['fecha_inscripcion'])) ?>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Tipo:</strong>
                                            <span class="badge <?= match($inscripcion['IdTipo_Inscripcion'] ?? 1) {
                                                1 => 'bg-success',
                                                2 => 'bg-primary',
                                                3 => 'bg-warning text-dark',
                                                default => 'bg-secondary'
                                            } ?>">
                                                <?= htmlspecialchars($inscripcion['tipo_inscripcion'] ?? 'Nuevo Ingreso') ?>
                                            </span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Curso:</strong> <?= htmlspecialchars($inscripcion['curso']) ?>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Sección:</strong> 
                                            <span id="texto-seccion-actual"><?= htmlspecialchars($inscripcion['seccion']) ?></span>
                                            <?php if ($inscripcion['IdStatus'] == $idInscrito && !empty($seccionesDisponibles)): ?>
                                            <small class="text-muted ms-1">(Click en "Cambiar Sección")</small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Nivel:</strong> <?= htmlspecialchars($inscripcion['nivel']) ?>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Año Escolar:</strong> <?= htmlspecialchars($inscripcion['fecha_escolar']) ?>
                                        </div>

                                        <!-- Selector de sección (oculto inicialmente) -->
                                        <?php if ($inscripcion['IdStatus'] == $idInscrito && !empty($seccionesDisponibles)): ?>
                                        <div id="selector-seccion" class="mt-3 p-3 border rounded" style="display: none;">
                                            <h6 class="section-title mb-3">Cambiar Sección</h6>
                                            
                                            <div class="mb-3">
                                                <label class="form-label fw-medium">Selecciona una nueva sección:</label>
                                                <select class="form-select" id="select-nueva-seccion">
                                                    <option value="">-- Selecciona una sección --</option>
                                                    <?php foreach ($seccionesDisponibles as $seccion): 
                                                        $esRecomendada = $hayRecomendada && 
                                                                        ($seccion['mismos_urbanismo'] ?? 0) > 0 && 
                                                                        $seccion['mismos_urbanismo'] == $maxMismosUrbanismo;
                                                    ?>
                                                    <option value="<?= $seccion['IdCurso_Seccion'] ?>" 
                                                            data-capacidad="<?= $seccion['capacidad'] ?? 0 ?>"
                                                            data-estudiantes="<?= $seccion['estudiantes_actuales'] ?? 0 ?>"
                                                            data-urbanismo="<?= $seccion['mismos_urbanismo'] ?? 0 ?>"
                                                            <?php if ($esRecomendada): ?>
                                                            style="background-color: #fff3cd; font-weight: bold; color: #856404;" 
                                                            <?php endif; ?>>
                                                        <?= htmlspecialchars($seccion['seccion']) ?> 
                                                        - Aula: <?= htmlspecialchars($seccion['aula'] ?? 'Sin asignar') ?>
                                                        (<?= ($seccion['estudiantes_actuales'] ?? 0) . '/' . ($seccion['capacidad'] ?? 0) ?>)
                                                        <?php if ($esRecomendada): ?> 
                                                            ⭐ (Recomendada)
                                                        <?php endif; ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div id="info-seccion" class="p-2 bg-light rounded mb-3" style="display: none;">
                                                <small>
                                                    <div><strong>Capacidad:</strong> <span id="info-capacidad"></span> estudiantes</div>
                                                    <div><strong>Ocupación actual:</strong> <span id="info-ocupacion"></span></div>
                                                    <div><strong>Estudiantes de mismo urbanismo:</strong> <span id="info-urbanismo"></span></div>
                                                </small>
                                            </div>

                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-primary btn-sm" id="btn-confirmar-cambio">
                                                    <i class="fas fa-check me-1"></i> Confirmar Cambio
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-sm" id="btn-cancelar-cambio">
                                                    <i class="fas fa-times me-1"></i> Cancelar
                                                </button>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contacto de Emergencia -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-phone-alt me-2"></i>Contacto de Emergencia</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($inscripcion['contacto_nombre'])): ?>
                                        <div class="info-card">
                                            <h6 class="section-title">Datos Personales</h6>
                                            <div class="mb-2">
                                                <strong>Nombre:</strong> 
                                                <?= htmlspecialchars($inscripcion['contacto_nombre'] . ' ' . $inscripcion['contacto_apellido']) ?>
                                            </div>
                                            <?php if (!empty($inscripcion['contacto_telefono'])): ?>
                                            <div class="mb-3">
                                                <strong class="d-block mb-2">Teléfono:</strong>
                                                <div class="telefono-item d-flex align-items-center">
                                                    <i class="fas fa-phone me-2 text-success"></i>
                                                    <span class="telefono-numero"><?= htmlspecialchars($inscripcion['contacto_telefono']) ?></span>
                                                    <?php if (!empty($inscripcion['contacto_tipo_telefono'])): ?>
                                                    <span class="badge bg-success ms-2"><?= htmlspecialchars($inscripcion['contacto_tipo_telefono']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            <div class="mb-2">
                                                <strong>Relación:</strong> 
                                                <?= htmlspecialchars($inscripcion['contacto_parentesco'] ?? 'No especificado') ?>
                                            </div>
                                            <?php if (!empty($inscripcion['contacto_ocupacion'])): ?>
                                            <div class="mb-2">
                                                <strong>Ocupación:</strong> 
                                                <?= htmlspecialchars($inscripcion['contacto_ocupacion']) ?>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($inscripcion['contacto_lugar_trabajo'])): ?>
                                            <div class="mb-2">
                                                <strong>Lugar de trabajo:</strong> 
                                                <?= htmlspecialchars($inscripcion['contacto_lugar_trabajo']) ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-user-slash fa-3x mb-3"></i>
                                            <p class="mb-0">No se ha registrado contacto de emergencia</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del Estudiante -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-child me-2"></i>Datos del Estudiante</h5>
                            <?php if (!empty($inscripcion['repite']) && $inscripcion['repite']): ?>
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-redo me-1"></i>Repitiente
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-card">
                                    <h6 class="section-title">Información Personal</h6>
                                    <div class="mb-2">
                                        <strong>Nombre completo:</strong> 
                                        <?= htmlspecialchars($inscripcion['estudiante_nombre'] . ' ' . $inscripcion['estudiante_apellido']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Cédula:</strong> 
                                        <?= htmlspecialchars($inscripcion['estudiante_nacionalidad'] . '-' . $inscripcion['estudiante_cedula']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Fecha de nacimiento:</strong>
                                        <?php
                                            if (!empty($inscripcion['estudiante_fecha_nacimiento'])) {
                                                $edad = calcularEdad($inscripcion['estudiante_fecha_nacimiento']);
                                                echo date('d/m/Y', strtotime($inscripcion['estudiante_fecha_nacimiento']));
                                                if ($edad !== null) {
                                                    echo ' <div class="mb-2">
                                                                <strong>Edad:</strong> 
                                                                ' . htmlspecialchars($edad) . ' años
                                                            </div>';
                                                }
                                            } else {
                                                echo 'No registrada';
                                            }
                                        ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Lugar de nacimiento:</strong> 
                                        <?= htmlspecialchars($inscripcion['estudiante_lugar_nacimiento']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Sexo:</strong> 
                                        <?= htmlspecialchars($inscripcion['estudiante_sexo']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Correo:</strong> 
                                        <?= htmlspecialchars($inscripcion['estudiante_correo']) ?>
                                    </div>
                                </div>

                                <div class="info-card">
                                    <h6 class="section-title">Dirección y Contacto</h6>
                                    <div class="mb-2">
                                        <strong>Dirección:</strong> 
                                        <?= htmlspecialchars($inscripcion['estudiante_direccion']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Urbanismo:</strong> 
                                        <?= htmlspecialchars($inscripcion['estudiante_urbanismo']) ?>
                                    </div>
                                    <?php if (!empty($inscripcion['estudiante_telefono'])): ?>
                                        <div class="mb-3">
                                            <strong class="d-block mb-2">Teléfono:</strong>
                                            <div class="telefono-item d-flex align-items-center">
                                                <i class="fas fa-phone me-2 text-success"></i>
                                                <span class="telefono-numero"><?= htmlspecialchars($inscripcion['estudiante_telefono']) ?></span>
                                                <?php if (!empty($inscripcion['estudiante_tipo_telefono'])): ?>
                                                <span class="badge bg-success ms-2"><?= htmlspecialchars($inscripcion['estudiante_tipo_telefono']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mb-2">
                                        <strong>Último plantel:</strong> 
                                        <?= htmlspecialchars($inscripcion['plantel_nombre'] ?? 'No especificado') ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>N° de hermanos:</strong>
                                        <?= $inscripcion['nro_hermanos'] ?>
                                    </div>
                                    <?php if (!empty($inscripcion['cursos_hermanos'])): ?>
                                    <div class="mb-2">
                                        <strong>Cursos que cursan los hermanos:</strong>
                                        <?= htmlspecialchars($inscripcion['cursos_hermanos']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Discapacidades -->
                            <?php if (!empty($discapacidades)): ?>
                            <div class="mt-4">
                                <h6 class="section-title">Discapacidades/Condiciones Especiales</h6>
                                <div class="row">
                                    <?php foreach ($discapacidades as $disc): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="info-badge">
                                                <strong><?= htmlspecialchars($disc['tipo_discapacidad']) ?>:</strong> 
                                                <?= htmlspecialchars($disc['discapacidad']) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Madre</h5>
                        <?php if ($esMadreRepresentante): ?>
                        <span class="badge bg-success">
                            <i class="fas fa-check-circle me-1"></i>Representante Legal
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                            <div class="info-grid">
                                <div class="info-card">
                                    <h6 class="section-title">Información Personal</h6>
                                    <div class="mb-2">
                                        <strong>Nombre:</strong> 
                                        <?= htmlspecialchars($inscripcion['madre_nombre'] . ' ' . $inscripcion['madre_apellido']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Cédula:</strong> 
                                        <?= htmlspecialchars($inscripcion['madre_nacionalidad'] . '-' . $inscripcion['madre_cedula']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Correo:</strong> 
                                        <?= htmlspecialchars($inscripcion['madre_correo']) ?>
                                    </div>
                                    <?php
                                    $telefonosMadre = separarTelefonosPorTipo($inscripcion['madre_numeros'] ?? '', $inscripcion['madre_tipos'] ?? '');
                                    if (!empty($telefonosMadre['personales'])): ?>
                                    <div class="mb-3">
                                        <strong class="d-block mb-2">Teléfonos Personales:</strong>
                                        <?= mostrarTelefonos($telefonosMadre['personales']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="info-card">
                                    <h6 class="section-title">Información Laboral</h6>
                                    <div class="mb-2">
                                        <strong>Tipo de trabajador:</strong>
                                        <?= htmlspecialchars($inscripcion['madre_tipo_trabajador'] ?? 'No especificado') ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Ocupación:</strong>
                                        <?= htmlspecialchars($inscripcion['madre_ocupacion']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Lugar de trabajo:</strong>
                                        <?= htmlspecialchars($inscripcion['madre_lugar_trabajo']) ?>
                                    </div>
                                    <?php if (!empty($telefonosMadre['laborales'])): ?>
                                    <div class="mb-2">
                                        <strong class="d-block mb-2">Teléfonos Laborales:</strong>
                                        <?= mostrarTelefonos($telefonosMadre['laborales']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del Padre -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Padre</h5>
                            <?php if ($esPadreRepresentante): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Representante Legal
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-card">
                                    <h6 class="section-title">Información Personal</h6>
                                    <div class="mb-2">
                                        <strong>Nombre:</strong> 
                                        <?= htmlspecialchars($inscripcion['padre_nombre'] . ' ' . $inscripcion['padre_apellido']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Cédula:</strong> 
                                        <?= htmlspecialchars($inscripcion['padre_nacionalidad'] . '-' . $inscripcion['padre_cedula']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Correo:</strong> 
                                        <?= htmlspecialchars($inscripcion['padre_correo']) ?>
                                    </div>
                                    <?php
                                    $telefonosPadre = separarTelefonosPorTipo($inscripcion['padre_numeros'] ?? '', $inscripcion['padre_tipos'] ?? '');
                                    if (!empty($telefonosPadre['personales'])): ?>
                                    <div class="mb-3">
                                        <strong class="d-block mb-2">Teléfonos Personales:</strong>
                                        <?= mostrarTelefonos($telefonosPadre['personales']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="info-card">
                                    <h6 class="section-title">Información Laboral</h6>
                                    <div class="mb-2">
                                        <strong>Tipo de trabajador:</strong>
                                        <?= htmlspecialchars($inscripcion['padre_tipo_trabajador'] ?? 'No especificado') ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Ocupación:</strong>
                                        <?= htmlspecialchars($inscripcion['padre_ocupacion']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Lugar de trabajo:</strong>
                                        <?= htmlspecialchars($inscripcion['padre_lugar_trabajo']) ?>
                                    </div>
                                    <?php if (!empty($telefonosPadre['laborales'])): ?>
                                    <div class="mb-2">
                                        <strong class="d-block mb-2">Teléfonos Laborales:</strong>
                                        <?= mostrarTelefonos($telefonosPadre['laborales']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del Representante Legal -->
                    <?php if (!$esPadreRepresentante && !$esMadreRepresentante): ?>
                    <div class="card mb-4 contact-card">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Representante Legal</h5>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-card">
                                    <h6 class="section-title">Información Personal</h6>
                                    <div class="mb-2">
                                        <strong>Nombre:</strong> 
                                        <?= htmlspecialchars($inscripcion['responsable_nombre'] . ' ' . $inscripcion['responsable_apellido']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Cédula:</strong> 
                                        <?= htmlspecialchars($inscripcion['responsable_nacionalidad'] . '-' . $inscripcion['responsable_cedula']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Parentesco:</strong> 
                                        <?= htmlspecialchars($inscripcion['responsable_parentesco']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Correo:</strong> 
                                        <?= htmlspecialchars($inscripcion['responsable_correo']) ?>
                                    </div>
                                    <?php
                                    $telefonosResponsable = separarTelefonosPorTipo($inscripcion['responsable_numeros'] ?? '', $inscripcion['responsable_tipos'] ?? '');
                                    if (!empty($telefonosResponsable['personales'])): ?>
                                    <div class="mb-3">
                                        <strong class="d-block mb-2">Teléfonos Personales:</strong>
                                        <?= mostrarTelefonos($telefonosResponsable['personales']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="info-card">
                                    <h6 class="section-title">Información Laboral</h6>
                                    <div class="mb-2">
                                        <strong>Tipo de trabajador:</strong>
                                        <?= htmlspecialchars($inscripcion['responsable_tipo_trabajador'] ?? 'No especificado') ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Ocupación:</strong>
                                        <?= htmlspecialchars($inscripcion['responsable_ocupacion']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Lugar de trabajo:</strong>
                                        <?= htmlspecialchars($inscripcion['responsable_lugar_trabajo']) ?>
                                    </div>
                                    <?php if (!empty($telefonosResponsable['laborales'])): ?>
                                    <div class="mb-2">
                                        <strong class="d-block mb-2">Teléfonos Laborales:</strong>
                                        <?= mostrarTelefonos($telefonosResponsable['laborales']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Requisitos -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Requisitos</h5>
                            <div id="contador-requisitos" class="badge bg-info">0/<?= count($requisitos) ?> seleccionados</div>
                        </div>
                        <div class="card-body">
                            <form id="form-requisitos" class="requisitos-form" action="../../../controladores/InscripcionController.php" method="POST">
                                <input type="hidden" name="action" value="actualizarMultiplesRequisitos">
                                <input type="hidden" name="idInscripcion" value="<?= $inscripcion['IdInscripcion'] ?>">
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th width="50px">Estado</th>
                                                <th>Requisito</th>
                                                <th>Tipo</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($requisitos as $requisito): ?>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input 
                                                                class="form-check-input requisito-checkbox" 
                                                                type="checkbox" 
                                                                name="requisitos[<?= $requisito['IdRequisito'] ?>]" 
                                                                value="1" 
                                                                id="req-<?= $requisito['IdRequisito'] ?>"
                                                                <?= $requisito['cumplido'] ? 'checked' : '' ?>
                                                            >
                                                            <label class="form-check-label" for="req-<?= $requisito['IdRequisito'] ?>"></label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <label for="req-<?= $requisito['IdRequisito'] ?>" class="requisito-label">
                                                            <div class="requisito-item <?= $requisito['obligatorio'] ? 'requisito-obligatorio' : 'requisito-opcional' ?>">
                                                                <?= htmlspecialchars($requisito['requisito']) ?>
                                                                <?php if (!empty($requisito['descripcion_adicional'])): ?>
                                                                    <br><small class="text-muted"><em><?= htmlspecialchars($requisito['descripcion_adicional']) ?></em></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </label>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?= $requisito['obligatorio'] ? 'bg-danger' : 'bg-success' ?>">
                                                            <?= $requisito['obligatorio'] ? 'Obligatorio' : 'Opcional' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button 
                                                            type="button" 
                                                            class="btn btn-sm btn-outline-primary guardar-individual" 
                                                            data-requisito="<?= $requisito['IdRequisito'] ?>"
                                                            data-cumplido="<?= $requisito['cumplido'] ? 0 : 1 ?>">
                                                            <i class="fas <?= $requisito['cumplido'] ? 'fa-times' : 'fa-check' ?> me-1"></i>
                                                            <?= $requisito['cumplido'] ? 'Desmarcar' : 'Marcar' ?>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="guardar-cambios" id="guardar-cambios-container">
                                    <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded shadow-sm">
                                        <div>
                                            <span id="texto-cambios">Tienes cambios pendientes por guardar</span>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-secondary me-2" id="descartar-cambios">
                                                <i class="fas fa-times me-1"></i> Descartar
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i> Guardar cambios
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

<style>
/* Estilos del timeline de historial */
.timeline-historial {
    position: relative;
    padding-left: 30px;
}

.timeline-historial::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e0e0e0;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -24px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #6c757d;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e0e0e0;
}

.timeline-item-latest .timeline-marker {
    background: #c90000;
    box-shadow: 0 0 0 2px #c90000;
}

.timeline-content {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px 15px;
    border-left: 3px solid #e0e0e0;
}

.timeline-item-latest .timeline-content {
    border-left-color: #c90000;
    background: #fff5f5;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
    flex-wrap: wrap;
    gap: 5px;
}

.timeline-date {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 500;
}

.timeline-user {
    font-size: 0.8rem;
    color: #495057;
    background: #e9ecef;
    padding: 2px 8px;
    border-radius: 12px;
}

.timeline-body p {
    color: #333;
    font-size: 0.9rem;
}

/* Panel del historial expandible */
#historial-container {
    max-height: 400px;
    overflow-y: auto;
}

.historial-panel {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 8px 8px;
    padding: 15px;
}

/* Card de última modificación clickeable */
.historial-toggle {
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 8px;
}

.historial-toggle:hover {
    background: linear-gradient(135deg, #e3f2fd 0%, #e8f5e9 100%);
    border-color: #90caf9;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.historial-toggle.expanded {
    border-radius: 8px 8px 0 0;
    border-bottom: none;
    background: linear-gradient(135deg, #e3f2fd 0%, #e8f5e9 100%);
}

/* Indicador de "Ver historial" */
.historial-hint {
    opacity: 0.7;
    transition: all 0.3s ease;
}

.historial-toggle:hover .historial-hint {
    opacity: 1;
}

.historial-chevron {
    transition: transform 0.3s ease;
}

.historial-toggle.expanded .historial-chevron {
    transform: rotate(180deg);
}

.historial-toggle.expanded .historial-hint span {
    display: none;
}

.historial-toggle.expanded .historial-hint::after {
    content: "Ocultar historial";
    font-size: 0.875rem;
    color: #0d6efd;
}
</style>

<?php include '../../layouts/footer.php'; ?>

<!-- Modal de Validación de Pago -->
<div class="modal fade" id="modalValidarPago" tabindex="-1" aria-labelledby="modalValidarPagoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalValidarPagoLabel">
                    <i class="fas fa-credit-card me-2"></i>Validar Pago de Inscripción
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Para completar la inscripción, debe ingresar el código de pago emitido por el sistema administrativo.
                </div>
                <form id="form-validar-pago">
                    <div class="mb-3">
                        <label for="codigo-pago" class="form-label fw-bold">Código de Pago / Factura</label>
                        <input type="text"
                               class="form-control form-control-lg"
                               id="codigo-pago"
                               name="codigoPago"
                               placeholder="Ej: PAG-2024-001234"
                               required
                               minlength="5"
                               autocomplete="off">
                        <div class="form-text">
                            Ingrese el código exactamente como aparece en el comprobante de pago.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btn-confirmar-pago">
                    <i class="fas fa-check me-1"></i>Validar y Completar Inscripción
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const ID_INSCRIPCION = <?= $inscripcion['IdInscripcion'] ?>;
    const ID_INSCRITO = <?= $idInscrito ?>;
    const SECCIONES_CON_CUPO = <?= isset($seccionesConCupo) ? count($seccionesConCupo) : 0 ?>;
    const ID_CURSO = <?= (int)$inscripcion['IdCurso'] ?>;
    const ES_ANO_ESCOLAR_ACTIVO = <?= $esAñoEscolarActivo ? 'true' : 'false' ?>;
</script>
<script src="../../../assets/js/inscripcion.js"></script>

</body>
</html>