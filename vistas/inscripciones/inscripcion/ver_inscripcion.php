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
    i.IdInscripcion,
    i.codigo_inscripcion,
    i.fecha_inscripcion,
    i.ultimo_plantel,
    i.nro_hermanos,
    
    -- Datos del estudiante
    e.IdPersona as id_estudiante,
    e.nombre as estudiante_nombre,
    e.apellido as estudiante_apellido,
    e.cedula as estudiante_cedula,
    e.fecha_nacimiento as estudiante_fecha_nacimiento,
    e.correo as estudiante_correo,
    e.direccion as estudiante_direccion,
    sexo.sexo as estudiante_sexo,
    urb.urbanismo as estudiante_urbanismo,
    nac.nacionalidad as estudiante_nacionalidad,
    
    -- Datos del responsable
    rp.IdRepresentante,
    rp.ocupacion as responsable_ocupacion,
    rp.lugar_trabajo as responsable_lugar_trabajo,
    parent.parentesco as responsable_parentesco,
    resp.nombre as responsable_nombre,
    resp.apellido as responsable_apellido,
    resp.cedula as responsable_cedula,
    resp.correo as responsable_correo,
    
    -- Datos del padre
    padre.nombre as padre_nombre,
    padre.apellido as padre_apellido,
    padre.cedula as padre_cedula,
    padre.correo as padre_correo,
    
    -- Datos de la madre
    madre.nombre as madre_nombre,
    madre.apellido as madre_apellido,
    madre.cedula as madre_cedula,
    madre.correo as madre_correo,
    
    -- Datos del curso y sección
    c.curso,
    s.seccion,
    n.nivel,
    fe.fecha_escolar,
    st.status,
    st.IdStatus
    
FROM inscripcion i
INNER JOIN persona e ON i.IdEstudiante = e.IdPersona
INNER JOIN representante rp ON i.responsable_inscripcion = rp.IdRepresentante
INNER JOIN persona resp ON rp.IdPersona = resp.IdPersona
INNER JOIN parentesco parent ON rp.IdParentesco = parent.IdParentesco
LEFT JOIN persona padre ON (SELECT IdPersona FROM representante WHERE IdEstudiante = e.IdPersona AND IdParentesco = 1 LIMIT 1) = padre.IdPersona
LEFT JOIN persona madre ON (SELECT IdPersona FROM representante WHERE IdEstudiante = e.IdPersona AND IdParentesco = 2 LIMIT 1) = madre.IdPersona
INNER JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
INNER JOIN curso c ON cs.IdCurso = c.IdCurso
INNER JOIN seccion s ON cs.IdSeccion = s.IdSeccion
INNER JOIN nivel n ON c.IdNivel = n.IdNivel
INNER JOIN fecha_escolar fe ON i.IdFecha_Escolar = fe.IdFecha_Escolar
INNER JOIN status st ON i.IdStatus = st.IdStatus
LEFT JOIN sexo ON e.IdSexo = sexo.IdSexo
LEFT JOIN urbanismo urb ON e.IdUrbanismo = urb.IdUrbanismo
LEFT JOIN nacionalidad nac ON e.IdNacionalidad = nac.IdNacionalidad
WHERE i.IdInscripcion = :id";

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
                            <a href="editar_inscripcion.php?id=<?= $inscripcion['IdInscripcion'] ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                        </div>
                    </div>

                    <!-- Información General -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información General</h5>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-card">
                                    <h6 class="section-title">Datos de la Inscripción</h6>
                                    <div class="mb-2">
                                        <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($inscripcion['fecha_inscripcion'])) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Curso:</strong> <?= htmlspecialchars($inscripcion['curso']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Sección:</strong> <?= htmlspecialchars($inscripcion['seccion']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Nivel:</strong> <?= htmlspecialchars($inscripcion['nivel']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Año Escolar:</strong> <?= htmlspecialchars($inscripcion['fecha_escolar']) ?>
                                    </div>
                                </div>

                                <div class="info-card">
                                    <h6 class="section-title">Estado Actual</h6>
                                    <div class="mb-3">
                                        <span class="status-badge" style="background-color: 
                                            <?= $inscripcion['IdStatus'] == 10 ? '#28a745' : 
                                                ($inscripcion['IdStatus'] == 11 ? '#dc3545' : 
                                                ($inscripcion['IdStatus'] == 7 ? '#ffc107' : '#17a2b8')) ?>; 
                                            color: white;">
                                            <?= htmlspecialchars($inscripcion['status']) ?>
                                        </span>
                                    </div>
                                    
                                    <form action="../../../controladores/InscripcionController.php" method="POST">
                                        <input type="hidden" name="action" value="cambiarStatus">
                                        <input type="hidden" name="idInscripcion" value="<?= $inscripcion['IdInscripcion'] ?>">
                                        
                                        <div class="mb-3">
                                            <label for="nuevoStatus" class="form-label"><strong>Cambiar Estado:</strong></label>
                                            <select class="form-select" id="nuevoStatus" name="nuevoStatus" required>
                                                <?php foreach ($todosStatus as $status): ?>
                                                    <option value="<?= $status['IdStatus'] ?>" <?= $status['IdStatus'] == $inscripcion['IdStatus'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($status['status']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-sync-alt me-1"></i> Actualizar Estado
                                        </button>
                                    </form>
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

                    <!-- Datos de los Padres -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-users me-2"></i>Datos de los Padres</h5>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <!-- Madre -->
                                <div class="info-card">
                                    <h6 class="section-title">Madre</h6>
                                    <?php if ($inscripcion['madre_nombre']): ?>
                                        <div class="mb-2">
                                            <strong>Nombre:</strong> 
                                            <?= htmlspecialchars($inscripcion['madre_nombre'] . ' ' . $inscripcion['madre_apellido']) ?>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Cédula:</strong> 
                                            <?= htmlspecialchars($inscripcion['madre_cedula']) ?>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Correo:</strong> 
                                            <?= htmlspecialchars($inscripcion['madre_correo']) ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No registrada</p>
                                    <?php endif; ?>
                                </div>

                                <!-- Padre -->
                                <div class="info-card">
                                    <h6 class="section-title">Padre</h6>
                                    <?php if ($inscripcion['padre_nombre']): ?>
                                        <div class="mb-2">
                                            <strong>Nombre:</strong> 
                                            <?= htmlspecialchars($inscripcion['padre_nombre'] . ' ' . $inscripcion['padre_apellido']) ?>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Cédula:</strong> 
                                            <?= htmlspecialchars($inscripcion['padre_cedula']) ?>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Correo:</strong> 
                                            <?= htmlspecialchars($inscripcion['padre_correo']) ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No registrado</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del Representante Legal -->
                    <div class="card mb-4">
                        <div class="card-header">
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
                                        <?= htmlspecialchars($inscripcion['responsable_cedula']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Parentesco:</strong> 
                                        <?= htmlspecialchars($inscripcion['responsable_parentesco']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Correo:</strong> 
                                        <?= htmlspecialchars($inscripcion['responsable_correo']) ?>
                                    </div>
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
                                </div>
                            </div>
                        </div>
                    </div>

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
</script>
<script src="../../../assets/js/inscripcion.js"></script>

</body>
</html>
