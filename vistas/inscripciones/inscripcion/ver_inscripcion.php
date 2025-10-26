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
unset($_SESSION['alert']);
unset($_SESSION['message']);

if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito($message ?: 'Operación realizada correctamente.');
            break;
        case 'error':
            $alerta = Notificaciones::advertencia($message ?: 'Ocurrió un error. Por favor verifique.');
            break;
        default:
            $alerta = null;
    }

    if ($alerta) {
        Notificaciones::mostrar($alerta);
    }
}

// Obtener ID de la inscripción
$idInscripcion = $_GET['id'] ?? 0;
if ($idInscripcion <= 0) {
    header("Location: inscripcion.php?error=id_invalido");
    exit();
}

require_once __DIR__ . '/../../../config/conexion.php';
$database = new Database();
$conexion = $database->getConnection();

// Consulta para obtener todos los datos de la inscripción
$query = "SELECT 
    -- Datos de la inscripción
    i.IdInscripcion,
    i.codigo_inscripcion,
    i.fecha_inscripcion,
    i.ultimo_plantel,
    i.nro_hermanos,
    i.modificado_por,
    i.ultima_modificacion,
    i.IdCurso_Seccion,

    -- Curso, sección, nivel, año escolar
    c.curso, 
    c.IdCurso,
    s.seccion,
    n.nivel,
    fe.fecha_escolar,
    st.status AS status_inscripcion,
    st.IdStatus,

    -- Estudiante
    e.IdPersona AS id_estudiante,
    e.nombre AS estudiante_nombre,
    e.apellido AS estudiante_apellido,
    e.cedula AS estudiante_cedula,
    e.fecha_nacimiento AS estudiante_fecha_nacimiento,
    e.correo AS estudiante_correo,
    e.direccion AS estudiante_direccion,
    sexo_e.sexo AS estudiante_sexo,
    urb_e.urbanismo AS estudiante_urbanismo,
    urb_e.IdUrbanismo AS estudiante_id_urbanismo,
    nac_e.nacionalidad AS estudiante_nacionalidad,

    -- Teléfonos del estudiante
    tel_e.numero_telefono AS estudiante_telefono,
    tipo_tel_e.tipo_telefono AS estudiante_tipo_telefono,

    -- Representante Legal
    rp.IdRepresentante,
    rp.IdParentesco,
    resp.IdPersona AS id_responsable,
    resp.nombre AS responsable_nombre,
    resp.apellido AS responsable_apellido,
    resp.cedula AS responsable_cedula,
    resp.correo AS responsable_correo,
    resp.direccion AS responsable_direccion,
    sexo_r.sexo AS responsable_sexo,
    urb_r.urbanismo AS responsable_urbanismo,
    nac_r.nacionalidad AS responsable_nacionalidad,
    parent.parentesco AS responsable_parentesco,
    rp.ocupacion AS responsable_ocupacion,
    rp.lugar_trabajo AS responsable_lugar_trabajo,

    
    -- Teléfonos del representante legal (múltiples)
    GROUP_CONCAT(DISTINCT tel_r.numero_telefono SEPARATOR '||') AS responsable_numeros,
    GROUP_CONCAT(DISTINCT tipo_tel_r.tipo_telefono SEPARATOR '||') AS responsable_tipos,

    -- Padre
    padre.IdPersona AS id_padre,
    padre.nombre AS padre_nombre,
    padre.apellido AS padre_apellido,
    padre.cedula AS padre_cedula,
    padre.correo AS padre_correo,
    padre.direccion AS padre_direccion,
    sexo_p.sexo AS padre_sexo,
    urb_p.urbanismo AS padre_urbanismo,
    nac_p.nacionalidad AS padre_nacionalidad,
    rp_padre.ocupacion AS padre_ocupacion,
    rp_padre.lugar_trabajo AS padre_lugar_trabajo,

     -- Teléfonos del padre (múltiples)
    GROUP_CONCAT(DISTINCT tel_p.numero_telefono SEPARATOR '||') AS padre_numeros,
    GROUP_CONCAT(DISTINCT tipo_tel_p.tipo_telefono SEPARATOR '||') AS padre_tipos,

    -- Madre
    madre.IdPersona AS id_madre,
    madre.nombre AS madre_nombre,
    madre.apellido AS madre_apellido,
    madre.cedula AS madre_cedula,
    madre.correo AS madre_correo,
    madre.direccion AS madre_direccion,
    sexo_m.sexo AS madre_sexo,
    urb_m.urbanismo AS madre_urbanismo,
    nac_m.nacionalidad AS madre_nacionalidad,
    rp_madre.ocupacion AS madre_ocupacion,
    rp_madre.lugar_trabajo AS madre_lugar_trabajo,
    
    -- Teléfonos de la madre (múltiples)
    GROUP_CONCAT(DISTINCT tel_m.numero_telefono SEPARATOR '||') AS madre_numeros,
    GROUP_CONCAT(DISTINCT tipo_tel_m.tipo_telefono SEPARATOR '||') AS madre_tipos,

    -- Contacto de emergencia CORREGIDO
    ce.IdPersona AS id_contacto,
    ce.nombre AS contacto_nombre,
    ce.apellido AS contacto_apellido,
    ce.cedula AS contacto_cedula,
    ce.correo AS contacto_correo,
    ce.direccion AS contacto_direccion,
    parent_ce.parentesco AS contacto_parentesco,
    rp_ce.ocupacion AS contacto_ocupacion,
    rp_ce.lugar_trabajo AS contacto_lugar_trabajo,

    -- Teléfonos del contacto de emergencia
    tel_ce.numero_telefono AS contacto_telefono,
    tipo_tel.tipo_telefono AS contacto_tipo_telefono

FROM inscripcion i
INNER JOIN persona e ON i.IdEstudiante = e.IdPersona
INNER JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
INNER JOIN curso c ON cs.IdCurso = c.IdCurso
INNER JOIN seccion s ON cs.IdSeccion = s.IdSeccion
INNER JOIN nivel n ON c.IdNivel = n.IdNivel
INNER JOIN fecha_escolar fe ON i.IdFecha_Escolar = fe.IdFecha_Escolar
INNER JOIN status st ON i.IdStatus = st.IdStatus

-- Representante Legal
INNER JOIN representante rp ON i.responsable_inscripcion = rp.IdRepresentante
INNER JOIN persona resp ON rp.IdPersona = resp.IdPersona
LEFT JOIN sexo sexo_r ON resp.IdSexo = sexo_r.IdSexo
LEFT JOIN urbanismo urb_r ON resp.IdUrbanismo = urb_r.IdUrbanismo
LEFT JOIN nacionalidad nac_r ON resp.IdNacionalidad = nac_r.IdNacionalidad
LEFT JOIN parentesco parent ON rp.IdParentesco = parent.IdParentesco
LEFT JOIN telefono tel_r ON resp.IdPersona = tel_r.IdPersona
LEFT JOIN tipo_telefono tipo_tel_r ON tel_r.IdTipo_Telefono = tipo_tel_r.IdTipo_Telefono

-- Padre
LEFT JOIN representante rp_padre ON e.IdPersona = rp_padre.IdEstudiante AND rp_padre.IdParentesco = 1
LEFT JOIN persona padre ON rp_padre.IdPersona = padre.IdPersona
LEFT JOIN sexo sexo_p ON padre.IdSexo = sexo_p.IdSexo
LEFT JOIN urbanismo urb_p ON padre.IdUrbanismo = urb_p.IdUrbanismo
LEFT JOIN nacionalidad nac_p ON padre.IdNacionalidad = nac_p.IdNacionalidad
LEFT JOIN telefono tel_p ON padre.IdPersona = tel_p.IdPersona
LEFT JOIN tipo_telefono tipo_tel_p ON tel_p.IdTipo_Telefono = tipo_tel_p.IdTipo_Telefono

-- Madre
LEFT JOIN representante rp_madre ON e.IdPersona = rp_madre.IdEstudiante AND rp_madre.IdParentesco = 2
LEFT JOIN persona madre ON rp_madre.IdPersona = madre.IdPersona
LEFT JOIN sexo sexo_m ON madre.IdSexo = sexo_m.IdSexo
LEFT JOIN urbanismo urb_m ON madre.IdUrbanismo = urb_m.IdUrbanismo
LEFT JOIN nacionalidad nac_m ON madre.IdNacionalidad = nac_m.IdNacionalidad
LEFT JOIN telefono tel_m ON madre.IdPersona = tel_m.IdPersona
LEFT JOIN tipo_telefono tipo_tel_m ON tel_m.IdTipo_Telefono = tipo_tel_m.IdTipo_Telefono

-- Contacto de emergencia - SOLO el que tiene perfil 5
LEFT JOIN representante rp_ce ON e.IdPersona = rp_ce.IdEstudiante 
    AND EXISTS (
        SELECT 1 
        FROM detalle_perfil dp 
        WHERE dp.IdPersona = rp_ce.IdPersona 
        AND dp.IdPerfil = 5
    )
LEFT JOIN persona ce ON rp_ce.IdPersona = ce.IdPersona
LEFT JOIN parentesco parent_ce ON rp_ce.IdParentesco = parent_ce.IdParentesco
-- Teléfonos del contacto de emergencia
LEFT JOIN telefono tel_ce ON ce.IdPersona = tel_ce.IdPersona
LEFT JOIN tipo_telefono tipo_tel ON tel_ce.IdTipo_Telefono = tipo_tel.IdTipo_Telefono

-- Datos del estudiante
LEFT JOIN sexo sexo_e ON e.IdSexo = sexo_e.IdSexo
LEFT JOIN urbanismo urb_e ON e.IdUrbanismo = urb_e.IdUrbanismo
LEFT JOIN nacionalidad nac_e ON e.IdNacionalidad = nac_e.IdNacionalidad
LEFT JOIN telefono tel_e ON e.IdPersona = tel_e.IdPersona
LEFT JOIN tipo_telefono tipo_tel_e ON tel_e.IdTipo_Telefono = tipo_tel_e.IdTipo_Telefono

WHERE i.IdInscripcion = :id;";

$stmt = $conexion->prepare($query);
$stmt->bindParam(':id', $idInscripcion, PDO::PARAM_INT);
$stmt->execute();
$inscripcion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inscripcion) {
    header("Location: inscripcion.php?error=inscripcion_no_encontrada");
    exit();
}

// Obtener requisitos del nivel
$queryRequisitos = "SELECT 
    r.IdRequisito,
    r.requisito,
    r.obligatorio,
    ir.cumplido
FROM requisito r
LEFT JOIN inscripcion_requisito ir ON r.IdRequisito = ir.IdRequisito AND ir.IdInscripcion = :id
WHERE r.IdNivel = (SELECT c.IdNivel FROM curso c INNER JOIN curso_seccion cs ON c.IdCurso = cs.IdCurso INNER JOIN inscripcion i ON cs.IdCurso_Seccion = i.IdCurso_Seccion WHERE i.IdInscripcion = :id)
ORDER BY r.obligatorio DESC, r.requisito";

$stmtRequisitos = $conexion->prepare($queryRequisitos);
$stmtRequisitos->bindParam(':id', $idInscripcion, PDO::PARAM_INT);
$stmtRequisitos->execute();
$requisitos = $stmtRequisitos->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los status disponibles
$queryStatus = "SELECT IdStatus, status FROM status WHERE IdTipo_Status = 2 ORDER BY IdStatus";
$stmtStatus = $conexion->prepare($queryStatus);
$stmtStatus->execute();
$todosStatus = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);

$idInscrito = 11; // Valor por defecto

foreach ($todosStatus as $status) {
    if ($status['status'] === 'Inscrito') {
        $idInscrito = $status['IdStatus'];
        break;
    }
}

// Obtener discapacidades del estudiante
$queryDiscapacidades = "SELECT 
    td.tipo_discapacidad,
    d.discapacidad
FROM discapacidad d
INNER JOIN tipo_discapacidad td ON d.IdTipo_Discapacidad = td.IdTipo_Discapacidad
WHERE d.IdPersona = :id_estudiante";

$stmtDiscapacidades = $conexion->prepare($queryDiscapacidades);
$stmtDiscapacidades->bindParam(':id_estudiante', $inscripcion['id_estudiante'], PDO::PARAM_INT);
$stmtDiscapacidades->execute();
$discapacidades = $stmtDiscapacidades->fetchAll(PDO::FETCH_ASSOC);

// Mostrar mensajes de éxito/error específicos de inscripción
if (isset($_GET['success'])) {
    $mensaje = match($_GET['success']) {
        'status_actualizado' => 'Estado actualizado correctamente',
        'requisito_actualizado' => 'Requisito actualizado correctamente',
        default => 'Operación realizada con éxito'
    };
    $_SESSION['alert'] = 'success';
    $_SESSION['message'] = $mensaje;
}

if (isset($_GET['error'])) {
    $mensaje = match($_GET['error']) {
        'error_actualizacion' => 'Error al actualizar',
        'error_interno' => 'Error interno del servidor',
        default => 'Ocurrió un error'
    };
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
}

// Función helper para separar teléfonos por tipo
function separarTelefonosPorTipo($numerosStr, $tiposStr) {
    if (empty($numerosStr)) return ['personales' => [], 'laborales' => []];
    
    $numeros = explode('||', $numerosStr);
    $tipos = explode('||', $tiposStr);
    
    $telefonos = [
        'personales' => [], // Celular, Habitación, etc.
        'laborales' => []   // Trabajo
    ];
    
    foreach ($numeros as $index => $numero) {
        $tipo = $tipos[$index] ?? '';
        $categoria = ($tipo === 'Trabajo') ? 'laborales' : 'personales';
        
        $telefonos[$categoria][] = [
            'numero' => $numero,
            'tipo' => $tipo
        ];
    }
    
    return $telefonos;
}

// Función para mostrar teléfonos con estilo
function mostrarTelefonos($telefonos) {
    if (empty($telefonos)) return null;
    
    $html = '<div class="telefonos-list">';
    foreach ($telefonos as $telefono) {
        $badgeClass = match($telefono['tipo']) {
            'Celular' => 'bg-primary',
            'Trabajo' => 'bg-warning text-dark',
            'Habitación' => 'bg-info',
            default => 'bg-secondary'
        };
        
        $icon = match($telefono['tipo']) {
            'Celular' => 'fa-mobile-alt',
            'Trabajo' => 'fa-briefcase',
            'Habitación' => 'fa-phone',
            default => 'fa-phone'
        };
        
        $html .= '
        <div class="telefono-item d-flex align-items-center mb-1">
            <i class="fas ' . $icon . ' me-2 text-muted"></i>
            <span class="telefono-numero">' . htmlspecialchars($telefono['numero']) . '</span>
            <span class="badge ' . $badgeClass . ' ms-2">' . htmlspecialchars($telefono['tipo']) . '</span>
        </div>';
    }
    $html .= '</div>';
    
    return $html;
}
// Después de obtener los datos de la inscripción, agregamos esta verificación
$esPadreRepresentante = false;
$esMadreRepresentante = false;

if ($inscripcion) {
    // Verificar si el representante legal es el padre (IdParentesco = 1)
    if ($inscripcion['responsable_parentesco'] === 'Padre' || $inscripcion['IdParentesco'] == 1) {
        $esPadreRepresentante = true;
    }
    // Verificar si el representante legal es la madre (IdParentesco = 2)
    elseif ($inscripcion['responsable_parentesco'] === 'Madre' || $inscripcion['IdParentesco'] == 2) {
        $esMadreRepresentante = true;
    }
}

// Obtener información del modificador
if (!empty($inscripcion['modificado_por'])) {
    $queryModificador = "SELECT nombre, apellido FROM persona WHERE IdPersona = :id_modificador";
    $stmtModificador = $conexion->prepare($queryModificador);
    $stmtModificador->bindParam(':id_modificador', $inscripcion['modificado_por'], PDO::PARAM_INT);
    $stmtModificador->execute();
    $modificador = $stmtModificador->fetch(PDO::FETCH_ASSOC);
    
    $nombreModificador = $modificador ? htmlspecialchars($modificador['nombre'] . ' ' . $modificador['apellido']) : 'Usuario no encontrado';
} else {
    $nombreModificador = 'No modificado aún';
}

// Formatear la fecha de modificación
$fechaModificacion = !empty($inscripcion['ultima_modificacion']) ? 
    date('d/m/Y H:i', strtotime($inscripcion['ultima_modificacion'])) : 
    'No modificado aún';

// Después de obtener los datos de la inscripción:
$idCursoActual = $inscripcion['IdCurso'] ?? null;
$idCursoSeccionActual = $inscripcion['IdCurso_Seccion'] ?? null;

// Obtener secciones disponibles (excluyendo la sección "Inscripción" y del mismo curso)
$seccionesDisponibles = [];
$seccionesConCupo = [];
$maxMismosUrbanismo = 0;
$hayRecomendada = false;
if ($idCursoActual && $inscripcion['IdStatus'] == $idInscrito) {
    $querySecciones = "SELECT 
                        cs.IdCurso_Seccion,
                        s.seccion,
                        a.aula,
                        a.capacidad,
                        (SELECT COUNT(*) FROM inscripcion i2 
                         WHERE i2.IdCurso_Seccion = cs.IdCurso_Seccion 
                         AND i2.IdStatus = 11) as estudiantes_actuales,
                        (SELECT COUNT(*) FROM inscripcion i3 
                         INNER JOIN persona e ON i3.IdEstudiante = e.IdPersona
                         WHERE i3.IdCurso_Seccion = cs.IdCurso_Seccion 
                         AND i3.IdStatus = 11 
                         AND e.IdUrbanismo = :id_urbanismo) as mismos_urbanismo
                      FROM curso_seccion cs
                      INNER JOIN seccion s ON cs.IdSeccion = s.IdSeccion
                      LEFT JOIN aula a ON cs.IdAula = a.IdAula
                      WHERE cs.IdCurso = :id_curso
                      AND s.seccion != 'Inscripción'
                      AND cs.IdCurso_Seccion != :id_curso_seccion_actual
                      ORDER BY s.seccion";
    
    $stmtSecciones = $conexion->prepare($querySecciones);
    $stmtSecciones->bindParam(':id_curso', $idCursoActual, PDO::PARAM_INT);
    $stmtSecciones->bindParam(':id_urbanismo', $inscripcion['estudiante_id_urbanismo'], PDO::PARAM_INT);
    $stmtSecciones->bindParam(':id_curso_seccion_actual', $idCursoSeccionActual, PDO::PARAM_INT);
    $stmtSecciones->execute();
    $seccionesDisponibles = $stmtSecciones->fetchAll(PDO::FETCH_ASSOC);
    

     // Encontrar el máximo de estudiantes con mismo urbanismo
    foreach ($seccionesDisponibles as $seccion) {
        if (($seccion['mismos_urbanismo'] ?? 0) > $maxMismosUrbanismo) {
            $maxMismosUrbanismo = $seccion['mismos_urbanismo'];
        }
    }
    
    $hayRecomendada = $maxMismosUrbanismo > 0;

   // Filtrar solo las que tienen cupo
    $seccionesConCupo = array_filter($seccionesDisponibles, function($sec) {
        // Si no hay capacidad definida, consideramos que hay cupo
        if (empty($sec['capacidad'])) {
            return true;
        }
        return (int)$sec['estudiantes_actuales'] < (int)$sec['capacidad'];
    });
}
?>

<head>
    <title>UECFT Araure - Detalles de Inscripción</title>
</head>
<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

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

                    <!-- Información de modificación -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="modification-info-card">
                                <div class="d-flex justify-content-between align-items-center">
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
                                </div>
                            </div>
                        </div>
                    </div>

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
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-child me-2"></i>Datos del Estudiante</h5>
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
                                        <?= date('d/m/Y', strtotime($inscripcion['estudiante_fecha_nacimiento'])) ?>
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
                                        <?= htmlspecialchars($inscripcion['ultimo_plantel'] ?? 'No especificado') ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>N° de hermanos:</strong> 
                                        <?= $inscripcion['nro_hermanos'] ?>
                                    </div>
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

<?php include '../../layouts/footer.php'; ?>

<script>
    const ID_INSCRIPCION = <?= $inscripcion['IdInscripcion'] ?>;
    const ID_INSCRITO = <?= $idInscrito ?>;
    const SECCIONES_CON_CUPO = <?= isset($seccionesConCupo) ? count($seccionesConCupo) : 0 ?>;
    const ID_CURSO = <?= (int)$inscripcion['IdCurso'] ?>;
</script>
<script src="../../../assets/js/inscripcion.js"></script>

</body>
</html>