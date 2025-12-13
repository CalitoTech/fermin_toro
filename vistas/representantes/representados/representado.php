<?php
// --- CONFIGURACIÓN DE SESIÓN Y CABECERAS ---
session_start();

// --- CONEXIONES Y DEPENDENCIAS ---
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Representante.php';
require_once __DIR__ . '/../../../controladores/Notificaciones.php';
require_once __DIR__ . '/../../../modelos/TipoGrupoInteres.php';

$database = new Database();
$conexion = $database->getConnection();

// Verificar si hay grupos de interés activos para inscripción
$queryGruposActivos = "SELECT COUNT(*) as total FROM tipo_grupo_interes WHERE inscripcion_activa = 1";
$stmtGrupos = $conexion->prepare($queryGruposActivos);
$stmtGrupos->execute();
$hayGruposActivos = $stmtGrupos->fetch(PDO::FETCH_ASSOC)['total'] > 0;
// $hayGruposActivos = true; // Descomentar para probar si la base de datos está vacía

// === VERIFICACIÓN DE SESIÓN ===
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "Acceso Denegado",
                text: "Por favor, debes iniciar sesión",
                icon: "warning",
                confirmButtonText: "Aceptar",
                confirmButtonColor: "#c90000"
            }).then(() => {
                window.location.href = "../../login/login.php";
            });
        });
    </script>';
    session_destroy();
    exit();
}

$idPersona = $_SESSION['idPersona'];
$representanteModel = new Representante($conexion);

// Obtener estudiantes representados
$estudiantes = $representanteModel->obtenerEstudiantesPorRepresentante($idPersona);

// Obtener estado de renovacion_activa e inscripcion_activa del año escolar activo
$queryAnoEscolar = "SELECT renovacion_activa, inscripcion_activa, fecha_escolar FROM fecha_escolar WHERE fecha_activa = 1 LIMIT 1";
$stmtAnoEscolar = $conexion->prepare($queryAnoEscolar);
$stmtAnoEscolar->execute();
$añoEscolar = $stmtAnoEscolar->fetch(PDO::FETCH_ASSOC);
$renovacionActiva = $añoEscolar ? (bool)$añoEscolar['renovacion_activa'] : false;
$inscripcionActiva = $añoEscolar ? (bool)$añoEscolar['inscripcion_activa'] : false;
$nombreAnoEscolar = $añoEscolar ? $añoEscolar['fecha_escolar'] : '';

// Capturar mensajes de sesión
$mensajeExito = $_SESSION['mensaje_exito'] ?? null;
$mensajeError = $_SESSION['mensaje_error'] ?? null;
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

// --- FUNCIONES AUXILIARES ---
function mostrar($valor, $default = 'No registrado') {
    return !empty(trim($valor)) ? htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') : '<span class="text-muted">' . $default . '</span>';
}

function calcularEdad($fechaNacimiento) {
    if (empty($fechaNacimiento)) return null;
    $fecha = new DateTime($fechaNacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha);
    return $edad->y;
}

// Comentado: Función de renovación de cupo (no se usa actualmente)
// function tieneCupoPendiente($idEstudiante, $conexion) {
//     // Verificar si el estudiante tiene una renovación pendiente
//     // IdTipo_Inscripcion = 2 (Estudiante Regular) y IdStatus != 11 (Inscrito)
//     $query = "SELECT COUNT(*) as total FROM inscripcion
//               WHERE IdEstudiante = :idEstudiante
//               AND IdTipo_Inscripcion = 2
//               AND IdStatus != 11
//               ORDER BY fecha_inscripcion DESC LIMIT 1";
//     $stmt = $conexion->prepare($query);
//     $stmt->bindParam(':idEstudiante', $idEstudiante, PDO::PARAM_INT);
//     $stmt->execute();
//     $result = $stmt->fetch(PDO::FETCH_ASSOC);
//     return $result['total'] > 0;
// }

// Obtener el ID del año escolar activo
$idAnoEscolarActivo = $añoEscolar ? $añoEscolar['IdFecha_Escolar'] ?? 0 : 0;

// Función para verificar si el estudiante tiene inscripción en el año activo
function tieneInscripcionEnAnoActivo($estudiante) {
    return !empty($estudiante['IdInscripcion']);
}

// Función helper para verificar si un estudiante tiene un grupo de interés activo
function tieneGrupoInteresActivo($idEstudiante, $idFechaEscolar, $conn) {
    $query = "SELECT COUNT(*) as total 
              FROM inscripcion_grupo_interes igi
              INNER JOIN grupo_interes gi ON igi.IdGrupo_Interes = gi.IdGrupo_Interes
              WHERE igi.IdEstudiante = :idEstudiante 
              AND gi.IdFecha_Escolar = :idFecha";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':idEstudiante', $idEstudiante);
    $stmt->bindParam(':idFecha', $idFechaEscolar);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['total'] > 0;
}
?>

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<style>
        .students-container {
            padding: 2rem 0;
        }

        .student-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
            border: 2px solid transparent;
        }

        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(201, 0, 0, 0.15);
            border-color: #c90000;
        }

        .student-header {
            background: linear-gradient(135deg, #c90000 0%, #8b0000 100%);
            color: white;
            padding: 1.5rem;
            text-align: center;
            position: relative;
        }

        .student-avatar {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #c90000;
            font-weight: bold;
            border: 4px solid rgba(255, 255, 255, 0.3);
        }

        .student-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .student-grade {
            font-size: 0.9rem;
            opacity: 0.95;
            margin-top: 0.5rem;
            font-weight: 500;
        }

        .student-body {
            padding: 1.5rem;
        }

        .info-row {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: #fff5f5;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c90000;
            font-size: 1.1rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 0.75rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            display: block;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 0.95rem;
            color: #333;
            font-weight: 500;
        }

        .student-footer {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }

        .btn-view-details {
            width: 100%;
            background: #c90000;
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-view-details:hover {
            background: #a00000;
            color: white;
            transform: scale(1.02);
        }

        .btn-renew-quota {
            width: 100%;
            background: #28a745;
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-renew-quota:hover {
            background: #218838;
            color: white;
            transform: scale(1.02);
        }

        /* Card para solicitar nuevo cupo */
        .new-student-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px dashed #c90000;
            border-radius: 16px;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 380px;
            cursor: pointer;
            text-decoration: none;
        }

        .new-student-card:hover {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe6e6 100%);
            border-color: #a00000;
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(201, 0, 0, 0.15);
            text-decoration: none;
        }

        .new-student-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #c90000 0%, #8b0000 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 16px rgba(201, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .new-student-card:hover .new-student-icon {
            transform: scale(1.1);
            box-shadow: 0 12px 24px rgba(201, 0, 0, 0.4);
        }

        .new-student-icon i {
            font-size: 3rem;
            color: white;
        }

        .new-student-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #c90000;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .new-student-subtitle {
            font-size: 0.9rem;
            color: #666;
            text-align: center;
            max-width: 200px;
            line-height: 1.4;
        }

        .new-student-badge {
            background: #c90000;
            color: white;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .new-student-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            border-color: #999;
        }

        .new-student-card.disabled:hover {
            transform: none;
            box-shadow: none;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .new-student-card.disabled .new-student-icon {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            box-shadow: 0 8px 16px rgba(108, 117, 125, 0.3);
        }

        .new-student-card.disabled:hover .new-student-icon {
            transform: none;
        }

        .new-student-card.disabled .new-student-title {
            color: #6c757d;
        }

        .new-student-card.disabled .new-student-badge {
            background: #6c757d;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state-icon {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .empty-state-title {
            font-size: 1.5rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .empty-state-text {
            color: #999;
        }

        .page-header {
            background: linear-gradient(135deg, #c90000 0%, #8b0000 100%);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(201, 0, 0, 0.2);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .page-subtitle {
            font-size: 1rem;
            opacity: 0.95;
            margin-top: 0.5rem;
        }

        .badge-age {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .student-card {
                margin-bottom: 1.5rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.5rem;
            }
        }

        /* Botón Inscribir Grupo Custom */
        .btn-group-enroll {
            border: 2px solid #c90000;
            color: #c90000;
            transition: all 0.3s ease;
        }
        .btn-group-enroll:hover {
            background-color: #c90000;
            color: #ffffff !important;
            border-color: #c90000;
            transform: scale(1.02);
        }
</style>

<section class="home-section">
    <div class="main-content">
        <div class="container students-container">

            <!-- Encabezado -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class='bx bx-user-voice me-2'></i>
                    Mis Representados
                </h1>
                <p class="page-subtitle mb-0">
                    <?php if (count($estudiantes) > 0): ?>
                        Tienes <strong><?= count($estudiantes) ?></strong> estudiante<?= count($estudiantes) != 1 ? 's' : '' ?> bajo tu representación
                    <?php else: ?>
                        Aquí podrás ver la información de tus estudiantes representados
                    <?php endif; ?>
                </p>
            </div>

            <!-- Grid de Estudiantes -->
            <?php if (count($estudiantes) > 0 || $inscripcionActiva): ?>
                <div class="row">
                    <!-- Card para solicitar cupo de nuevo estudiante -->
                    <?php if ($inscripcionActiva): ?>
                        <div class="col-12 col-md-6 col-lg-4 mb-4">
                            <a href="solicitar_cupo.php" class="new-student-card">
                                <div class="new-student-icon">
                                    <i class='bx bx-user-plus'></i>
                                </div>
                                <h3 class="new-student-title">Inscribir Nuevo Estudiante</h3>
                                <p class="new-student-subtitle">
                                    Solicita un cupo para un hijo, hija o representado que aún no está en el sistema
                                </p>
                                <span class="new-student-badge">
                                    <i class='bx bx-calendar-check'></i>
                                    <?= htmlspecialchars($nombreAnoEscolar) ?>
                                </span>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="col-12 col-md-6 col-lg-4 mb-4">
                            <div class="new-student-card disabled" title="Las inscripciones están cerradas actualmente">
                                <div class="new-student-icon">
                                    <i class='bx bx-lock-alt'></i>
                                </div>
                                <h3 class="new-student-title">Inscripciones Cerradas</h3>
                                <p class="new-student-subtitle">
                                    El período de inscripciones no está activo. Intenta más tarde.
                                </p>
                                <span class="new-student-badge">
                                    <i class='bx bx-time'></i>
                                    No disponible
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($estudiantes as $estudiante):
                        $edad = calcularEdad($estudiante['fecha_nacimiento']);
                        $iniciales = strtoupper(
                            substr($estudiante['nombre'], 0, 1) .
                            substr($estudiante['apellido'], 0, 1)
                        );
                    ?>
                        <div class="col-12 col-md-6 col-lg-4 mb-4">
                            <div class="student-card">
                                <div class="student-header">
                                    <div class="student-avatar">
                                        <?= $iniciales ?>
                                    </div>
                                    <h3 class="student-name">
                                        <?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido'], ENT_QUOTES, 'UTF-8') ?>
                                    </h3>
                                    <?php if (!empty($estudiante['curso_actual'])): ?>
                                        <div class="student-grade">
                                            <i class='bx bxs-graduation'></i>
                                            <?= htmlspecialchars($estudiante['curso_actual'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($edad): ?>
                                        <span class="badge-age">
                                            <i class='bx bx-cake'></i> <?= $edad ?> año<?= $edad != 1 ? 's' : '' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="student-body">
                                    <div class="info-row">
                                        <div class="info-icon">
                                            <i class='bx bx-id-card'></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Cédula</span>
                                            <div class="info-value">
                                                <?php
                                                    if (!empty($estudiante['cedula'])) {
                                                        echo mostrar($estudiante['nacionalidad'], '') . ' ' . number_format($estudiante['cedula'], 0, '', '.');
                                                    } else {
                                                        echo mostrar('');
                                                    }
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="info-row">
                                        <div class="info-icon">
                                            <i class='bx bx-male-female'></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Sexo</span>
                                            <div class="info-value">
                                                <?= mostrar($estudiante['sexo']) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="info-row">
                                        <div class="info-icon">
                                            <i class='bx bx-map'></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Urbanismo</span>
                                            <div class="info-value">
                                                <?= mostrar($estudiante['urbanismo']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="student-footer">
                                    <div class="d-grid gap-2">
                                        <a href="ver_representado.php?id=<?= $estudiante['IdEstudiante'] ?>" class="btn btn-view-details">
                                            <i class='bx bx-show'></i>
                                            Ver Detalles Completos
                                        </a>
                                        <?php
                                        $tieneInscripcion = tieneInscripcionEnAnoActivo($estudiante);

                                        if (!$tieneInscripcion && $inscripcionActiva):
                                            // No tiene inscripción en el año activo y las inscripciones están abiertas
                                        ?>
                                            <a href="solicitar_reinscripcion.php?id=<?= $estudiante['IdEstudiante'] ?>" class="btn btn-renew-quota">
                                                <i class='bx bx-user-plus'></i>
                                                Solicitar Reinscripción
                                            </a>
                                        <?php elseif (!$tieneInscripcion && !$inscripcionActiva): ?>
                                            <!-- No tiene inscripción y las inscripciones están cerradas -->
                                            <button class="btn btn-renew-quota" disabled style="opacity: 0.6; cursor: not-allowed;" title="Las inscripciones están cerradas actualmente">
                                                <i class='bx bx-lock'></i>
                                                Inscripciones Cerradas
                                            </button>
                                        <?php endif; ?>

                                        <!-- Botón de Inscripción a Grupo de Interés -->
                                        <?php
                                        // Verificar si hay grupos activos (variable global $hayGruposActivos)
                                        // Y verificar si el estudiante YA tiene un grupo en el año activo
                                        // Necesitamos checkear esto por cada estudiante. 
                                        // Podemos usar una función helper pequeña o query ad-hoc.
                                        // Usaremos la función auxiliar que definiremos al inicio o una query directa.
                                        // Para mantenerlo limpio, agregaremos una función helper 'tieneGrupoInteresActivo($estudianteId, $idAnoActivo, $conexion)' al inicio del archivo.
                                        
                                        if ($hayGruposActivos) {
                                            $tieneGrupo = tieneGrupoInteresActivo($estudiante['IdEstudiante'], $idAnoEscolarActivo, $conexion);
                                            
                                            if ($tieneGrupo) {
                                                // Estudiante YA tiene grupo -> Botón verde o diferente
                                                ?>
                                                <a href="inscripcion_grupo.php?id=<?= $estudiante['IdEstudiante'] ?>" class="btn btn-success d-flex align-items-center justify-content-center gap-2 fw-semibold btn-group-manage" style="background-color: #198754; border: none;">
                                                    <i class='bx bx-check-circle'></i>
                                                    Gestionar Grupo de Interés
                                                </a>
                                                <?php
                                            } else {
                                                // Estudiante NO tiene grupo -> Botón rojo normal de inscripción
                                                ?>
                                                <a href="inscripcion_grupo.php?id=<?= $estudiante['IdEstudiante'] ?>" class="btn btn-outline-danger d-flex align-items-center justify-content-center gap-2 fw-semibold btn-group-enroll">
                                                    <i class='bx bx-group'></i>
                                                    Inscribir en Grupo de Interés
                                                </a>
                                                <?php
                                            }

                                        } else {
                                            // Si no hay grupos activos, mostrar botón deshabilitado/informativo
                                            ?>
                                            <button class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2 fw-semibold" disabled style="border: 2px solid #6c757d; color: #6c757d; cursor: not-allowed;" title="No hay grupos de interés habilitados para inscripción en este momento">
                                                <i class='bx bx-lock-alt'></i>
                                                Grupos de Interés Cerrados
                                            </button>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- No hay estudiantes pero puede solicitar cupo -->
                <div class="row">
                    <?php if ($inscripcionActiva): ?>
                        <div class="col-12 col-md-6 col-lg-4 mb-4 mx-auto">
                            <a href="solicitar_cupo.php" class="new-student-card">
                                <div class="new-student-icon">
                                    <i class='bx bx-user-plus'></i>
                                </div>
                                <h3 class="new-student-title">Inscribir Nuevo Estudiante</h3>
                                <p class="new-student-subtitle">
                                    Solicita un cupo para un hijo, hija o representado
                                </p>
                                <span class="new-student-badge">
                                    <i class='bx bx-calendar-check'></i>
                                    <?= htmlspecialchars($nombreAnoEscolar) ?>
                                </span>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class='bx bx-user-x'></i>
                                        </div>
                                        <h3 class="empty-state-title">No tienes estudiantes registrados</h3>
                                        <p class="empty-state-text">
                                            Actualmente no tienes estudiantes bajo tu representación.<br>
                                            Las inscripciones no están activas en este momento.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Mostrar alertas de éxito o error
    <?php if ($mensajeExito): ?>
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '<?= addslashes($mensajeExito) ?>',
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Aceptar'
        });
    <?php endif; ?>

    <?php if ($mensajeError): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?= addslashes($mensajeError) ?>',
            confirmButtonColor: '#c90000',
            confirmButtonText: 'Aceptar'
        });
    <?php endif; ?>
</script>

<?php include '../../layouts/footer.php'; ?>
