<?php
session_start();
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Persona.php';
require_once __DIR__ . '/../../../modelos/Representante.php';
require_once __DIR__ . '/../../../modelos/Inscripcion.php';
require_once __DIR__ . '/../../../modelos/Curso.php';
require_once __DIR__ . '/../../../modelos/FechaEscolar.php';
require_once __DIR__ . '/../../../modelos/CursoSeccion.php';

$database = new Database();
$conexion = $database->getConnection();

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

$idEstudiante = intval($_GET['id'] ?? 0);
$idRepresentante = $_SESSION['idPersona'];

if ($idEstudiante <= 0) {
    header("Location: representado.php");
    exit();
}

$personaModel = new Persona($conexion);
$representanteModel = new Representante($conexion);
$inscripcionModel = new Inscripcion($conexion);
$cursoModel = new Curso($conexion);
$fechaEscolarModel = new FechaEscolar($conexion);
$cursoSeccionModel = new CursoSeccion($conexion);

// Verificar que el estudiante sea realmente representado por este usuario
$estudiantesRepresentados = $representanteModel->obtenerEstudiantesPorRepresentante($idRepresentante);
$esRepresentado = false;
foreach ($estudiantesRepresentados as $est) {
    if ($est['IdEstudiante'] == $idEstudiante) {
        $esRepresentado = true;
        break;
    }
}

if (!$esRepresentado) {
    header("Location: representado.php");
    exit();
}

// Obtener datos del estudiante
$estudiante = $personaModel->obtenerEstudiantePorId($idEstudiante);
if (!$estudiante) {
    header("Location: representado.php");
    exit();
}

// Obtener el año escolar activo
$añoEscolarActivo = $fechaEscolarModel->obtenerActivo();
if (!$añoEscolarActivo) {
    $_SESSION['mensaje_error'] = "No hay un año escolar activo configurado.";
    header("Location: representado.php");
    exit();
}

// Verificar que las inscripciones estén activas
if (!$añoEscolarActivo['inscripcion_activa']) {
    $_SESSION['mensaje_error'] = "Las inscripciones no están activas en este momento.";
    header("Location: representado.php");
    exit();
}

// Verificar que el estudiante NO tenga ya una inscripción en el año activo
$sqlVerificarInscripcion = "SELECT COUNT(*) FROM inscripcion
                            WHERE IdEstudiante = :idEstudiante
                            AND IdFecha_Escolar = :idFechaEscolar";
$stmtVerificar = $conexion->prepare($sqlVerificarInscripcion);
$stmtVerificar->bindParam(':idEstudiante', $idEstudiante, PDO::PARAM_INT);
$stmtVerificar->bindParam(':idFechaEscolar', $añoEscolarActivo['IdFecha_Escolar'], PDO::PARAM_INT);
$stmtVerificar->execute();

if ($stmtVerificar->fetchColumn() > 0) {
    $_SESSION['mensaje_error'] = "El estudiante ya tiene una inscripción registrada para este año escolar.";
    header("Location: representado.php");
    exit();
}

// Obtener la última inscripción del estudiante (para saber el curso mínimo permitido)
$sqlUltimaInscripcion = "SELECT i.*, cs.IdCurso, c.curso, c.IdNivel
                         FROM inscripcion i
                         INNER JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
                         INNER JOIN curso c ON cs.IdCurso = c.IdCurso
                         WHERE i.IdEstudiante = :idEstudiante
                         ORDER BY i.IdFecha_Escolar DESC, i.IdInscripcion DESC
                         LIMIT 1";
$stmtUltima = $conexion->prepare($sqlUltimaInscripcion);
$stmtUltima->bindParam(':idEstudiante', $idEstudiante, PDO::PARAM_INT);
$stmtUltima->execute();
$ultimaInscripcion = $stmtUltima->fetch(PDO::FETCH_ASSOC);

// Obtener cursos disponibles (igual o mayor al último curso)
$idCursoMinimo = $ultimaInscripcion ? $ultimaInscripcion['IdCurso'] : 1;

$sqlCursosDisponibles = "SELECT c.IdCurso, c.curso, n.nivel, n.IdNivel
                         FROM curso c
                         INNER JOIN nivel n ON c.IdNivel = n.IdNivel
                         WHERE c.IdCurso >= :idCursoMinimo
                         ORDER BY n.IdNivel ASC, c.IdCurso ASC";
$stmtCursos = $conexion->prepare($sqlCursosDisponibles);
$stmtCursos->bindParam(':idCursoMinimo', $idCursoMinimo, PDO::PARAM_INT);
$stmtCursos->execute();
$cursosDisponibles = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);

// Agrupar cursos por nivel para el select
$cursosPorNivel = [];
foreach ($cursosDisponibles as $curso) {
    $cursosPorNivel[$curso['nivel']][] = $curso;
}
?>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<?php
// Capturar mensajes de sesión
$mensajeExito = $_SESSION['mensaje_exito'] ?? null;
$mensajeError = $_SESSION['mensaje_error'] ?? null;
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);
?>

<style>
    .form-container {
        max-width: 800px;
        margin: 2rem auto;
    }

    .form-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .form-header {
        background: linear-gradient(135deg, #c90000 0%, #8b0000 100%);
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .form-header h2 {
        margin: 0;
        font-size: 1.75rem;
        font-weight: 700;
    }

    .form-header p {
        margin: 0.5rem 0 0;
        opacity: 0.95;
    }

    .form-body {
        padding: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-control-readonly {
        background-color: #e9ecef;
        border: 1px solid #ced4da;
        padding: 0.75rem;
        border-radius: 8px;
        font-size: 1rem;
        color: #495057;
        cursor: not-allowed;
    }

    .form-select {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #ced4da;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }

    .form-select:focus {
        outline: none;
        border-color: #c90000;
        box-shadow: 0 0 0 0.2rem rgba(201, 0, 0, 0.25);
    }

    .readonly-badge {
        display: inline-block;
        background: #6c757d;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-left: 0.5rem;
    }

    .info-badge {
        background: #fff3e0;
        border: 1px solid #ffcc80;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .info-badge i {
        color: #e65100;
        font-size: 1.2rem;
        margin-right: 0.5rem;
    }

    .info-badge-blue {
        background: #e7f5ff;
        border: 1px solid #b3d9ff;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .info-badge-blue i {
        color: #0066cc;
        font-size: 1.2rem;
        margin-right: 0.5rem;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
        justify-content: flex-end;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }

    .btn-primary {
        background: #c90000;
        color: white;
    }

    .btn-primary:hover {
        background: #a00000;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(201, 0, 0, 0.3);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
        color: white;
    }

    .alert-warning {
        background: #fff3cd;
        border: 1px solid #ffc107;
        color: #856404;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }

    .alert-warning i {
        margin-right: 0.5rem;
    }

    .last-course-info {
        background: #f8f9fa;
        border-left: 4px solid #c90000;
        padding: 1rem;
        border-radius: 0 8px 8px 0;
        margin-bottom: 1.5rem;
    }

    .last-course-info strong {
        color: #c90000;
    }
</style>

<section class="home-section">
    <div class="main-content">
        <div class="form-container">
            <div class="form-card">
                <!-- Header -->
                <div class="form-header">
                    <h2>
                        <i class='bx bx-user-plus'></i>
                        Solicitar Reinscripción
                    </h2>
                    <p>Solicitud de reinscripción para el año escolar <?= htmlspecialchars($añoEscolarActivo['fecha_escolar']) ?></p>
                </div>

                <!-- Body -->
                <div class="form-body">
                    <?php if (empty($cursosDisponibles)): ?>
                        <div class="alert-warning">
                            <i class='bx bx-error-circle'></i>
                            <strong>No hay cursos disponibles.</strong><br>
                            No se encontraron cursos configurados en el sistema.
                        </div>
                        <div class="form-actions">
                            <a href="representado.php" class="btn btn-secondary">
                                <i class='bx bx-arrow-back'></i>
                                Volver
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="info-badge">
                            <i class='bx bx-info-circle'></i>
                            <strong>Reinscripción:</strong> Esta solicitud es para estudiantes que ya estuvieron en la institución y desean retomar sus estudios.
                        </div>

                        <?php if ($ultimaInscripcion): ?>
                            <div class="last-course-info">
                                <i class='bx bx-history'></i>
                                <strong>Último curso registrado:</strong> <?= htmlspecialchars($ultimaInscripcion['curso']) ?>
                                <br>
                                <small class="text-muted">Solo puede seleccionar este curso o uno superior.</small>
                            </div>
                        <?php endif; ?>

                        <form id="formReinscripcion" method="POST" action="../../../controladores/representantes/procesar_renovacion.php">
                            <input type="hidden" name="IdEstudiante" value="<?= $idEstudiante ?>">
                            <input type="hidden" name="IdFechaEscolar" value="<?= $añoEscolarActivo['IdFecha_Escolar'] ?>">
                            <input type="hidden" name="idTipoInscripcion" value="3">
                            <input type="hidden" name="idStatus" value="8">
                            <input type="hidden" name="esReinscripcion" value="1">

                            <!-- Año Escolar -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class='bx bx-calendar'></i>
                                    Año Escolar
                                    <span class="readonly-badge">Solo lectura</span>
                                </label>
                                <div class="form-control-readonly">
                                    <?= htmlspecialchars($añoEscolarActivo['fecha_escolar']) ?>
                                </div>
                            </div>

                            <!-- Datos del Estudiante -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class='bx bx-user'></i>
                                            Nombre del Estudiante
                                            <span class="readonly-badge">Solo lectura</span>
                                        </label>
                                        <div class="form-control-readonly">
                                            <?= htmlspecialchars($estudiante['nombre']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class='bx bx-user'></i>
                                            Apellido del Estudiante
                                            <span class="readonly-badge">Solo lectura</span>
                                        </label>
                                        <div class="form-control-readonly">
                                            <?= htmlspecialchars($estudiante['apellido']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cédula -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class='bx bx-id-card'></i>
                                    Cédula
                                    <span class="readonly-badge">Solo lectura</span>
                                </label>
                                <div class="form-control-readonly">
                                    <?php
                                        if (!empty($estudiante['cedula'])) {
                                            echo htmlspecialchars($estudiante['nacionalidad']) . ' ' . number_format($estudiante['cedula'], 0, '', '.');
                                        } else {
                                            echo 'No registrada';
                                        }
                                    ?>
                                </div>
                            </div>

                            <!-- Curso (seleccionable) -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class='bx bxs-graduation'></i>
                                    Curso a inscribir
                                    <span style="color: #dc3545;">*</span>
                                </label>
                                <select name="IdCurso" id="IdCurso" class="form-select" required>
                                    <option value="">Seleccione un curso</option>
                                    <?php foreach ($cursosPorNivel as $nivel => $cursos): ?>
                                        <optgroup label="<?= htmlspecialchars($nivel) ?>">
                                            <?php foreach ($cursos as $curso): ?>
                                                <option
                                                    value="<?= $curso['IdCurso'] ?>"
                                                    <?= ($ultimaInscripcion && $curso['IdCurso'] == $ultimaInscripcion['IdCurso']) ? 'selected' : '' ?>
                                                >
                                                    <?= htmlspecialchars($curso['curso']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Seleccione el curso en el que desea inscribir al estudiante</small>
                            </div>

                            <div class="info-badge-blue">
                                <i class='bx bx-time'></i>
                                <strong>Nota:</strong> Su solicitud quedará en estado <strong>Pendiente</strong> hasta que sea revisada y aprobada por la administración.
                            </div>

                            <!-- Acciones -->
                            <div class="form-actions">
                                <a href="representado.php" class="btn btn-secondary">
                                    <i class='bx bx-x'></i>
                                    Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary" id="btnSubmit" <?= ($ultimaInscripcion) ? '' : 'disabled' ?>>
                                    <i class='bx bx-check'></i>
                                    Solicitar Reinscripción
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>

<script>
// Mostrar mensajes de éxito o error
<?php if ($mensajeExito): ?>
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: '<?= addslashes($mensajeExito) ?>',
        confirmButtonColor: '#c90000',
        confirmButtonText: 'Aceptar'
    });
<?php endif; ?>

<?php if ($mensajeError): ?>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?= addslashes($mensajeError) ?>',
        confirmButtonColor: '#c90000',
        confirmButtonText: 'Entendido'
    });
<?php endif; ?>

// Habilitar botón cuando se selecciona curso
document.getElementById('IdCurso')?.addEventListener('change', function() {
    const btnSubmit = document.getElementById('btnSubmit');
    btnSubmit.disabled = !this.value;
});

// Confirmación antes de enviar
document.getElementById('formReinscripcion')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const cursoSelect = document.getElementById('IdCurso');
    const cursoTexto = cursoSelect.options[cursoSelect.selectedIndex].text;

    Swal.fire({
        title: '¿Confirmar solicitud?',
        html: `¿Está seguro de que desea solicitar la reinscripción del estudiante en <strong>${cursoTexto}</strong>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#c90000',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, solicitar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});
</script>
