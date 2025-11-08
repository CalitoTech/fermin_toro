<?php
session_start();
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Persona.php';
require_once __DIR__ . '/../../../modelos/Representante.php';
require_once __DIR__ . '/../../../modelos/Inscripcion.php';
require_once __DIR__ . '/../../../modelos/Curso.php';
require_once __DIR__ . '/../../../modelos/Seccion.php';
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

$personaModel = new Persona($conexion);
$representanteModel = new Representante($conexion);
$inscripcionModel = new Inscripcion($conexion);
$cursoModel = new Curso($conexion);
$seccionModel = new Seccion($conexion);
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

// Obtener la inscripción más reciente del estudiante
$sqlInscripcionReciente = "
    SELECT i.*, cs.IdCurso, cs.IdSeccion, c.IdNivel
    FROM inscripcion i
    INNER JOIN curso_seccion cs ON cs.IdCurso_Seccion = i.IdCurso_Seccion
    INNER JOIN curso c ON c.IdCurso = cs.IdCurso
    WHERE i.IdEstudiante = :idEstudiante
    ORDER BY i.IdInscripcion DESC
    LIMIT 1
";
$stmtInscripcion = $conexion->prepare($sqlInscripcionReciente);
$stmtInscripcion->bindParam(':idEstudiante', $idEstudiante, PDO::PARAM_INT);
$stmtInscripcion->execute();
$inscripcionReciente = $stmtInscripcion->fetch(PDO::FETCH_ASSOC);

// Obtener el año escolar activo
$añoEscolarActivo = $fechaEscolarModel->obtenerActivo();

// Obtener el curso siguiente
$cursoSiguiente = null;
if ($inscripcionReciente) {
    $sqlCursoSiguiente = "
        SELECT c.IdCurso, c.curso, c.IdNivel
        FROM curso c
        WHERE c.IdNivel = :idNivel
        AND c.IdCurso > :idCursoActual
        ORDER BY c.IdCurso ASC
        LIMIT 1
    ";
    $stmtCursoSiguiente = $conexion->prepare($sqlCursoSiguiente);
    $stmtCursoSiguiente->bindParam(':idNivel', $inscripcionReciente['IdNivel'], PDO::PARAM_INT);
    $stmtCursoSiguiente->bindParam(':idCursoActual', $inscripcionReciente['IdCurso'], PDO::PARAM_INT);
    $stmtCursoSiguiente->execute();
    $cursoSiguiente = $stmtCursoSiguiente->fetch(PDO::FETCH_ASSOC);
}

// Si no hay curso siguiente en el mismo nivel, intentar obtener el primer curso del siguiente nivel
if (!$cursoSiguiente && $inscripcionReciente) {
    $sqlNivelSiguiente = "
        SELECT n.IdNivel
        FROM nivel n
        WHERE n.IdNivel > :idNivelActual
        ORDER BY n.IdNivel ASC
        LIMIT 1
    ";
    $stmtNivelSiguiente = $conexion->prepare($sqlNivelSiguiente);
    $stmtNivelSiguiente->bindParam(':idNivelActual', $inscripcionReciente['IdNivel'], PDO::PARAM_INT);
    $stmtNivelSiguiente->execute();
    $nivelSiguiente = $stmtNivelSiguiente->fetch(PDO::FETCH_ASSOC);

    if ($nivelSiguiente) {
        $sqlPrimerCursoNivel = "
            SELECT c.IdCurso, c.curso, c.IdNivel
            FROM curso c
            WHERE c.IdNivel = :idNivel
            ORDER BY c.IdCurso ASC
            LIMIT 1
        ";
        $stmtPrimerCurso = $conexion->prepare($sqlPrimerCursoNivel);
        $stmtPrimerCurso->bindParam(':idNivel', $nivelSiguiente['IdNivel'], PDO::PARAM_INT);
        $stmtPrimerCurso->execute();
        $cursoSiguiente = $stmtPrimerCurso->fetch(PDO::FETCH_ASSOC);
    }
}

// Obtener secciones disponibles para el curso siguiente
$seccionesDisponibles = [];
if ($cursoSiguiente) {
    $sqlSecciones = "
        SELECT DISTINCT s.IdSeccion, s.seccion, cs.IdCurso_Seccion
        FROM seccion s
        INNER JOIN curso_seccion cs ON cs.IdSeccion = s.IdSeccion
        WHERE cs.IdCurso = :idCurso
        ORDER BY s.seccion ASC
    ";
    $stmtSecciones = $conexion->prepare($sqlSecciones);
    $stmtSecciones->bindParam(':idCurso', $cursoSiguiente['IdCurso'], PDO::PARAM_INT);
    $stmtSecciones->execute();
    $seccionesDisponibles = $stmtSecciones->fetchAll(PDO::FETCH_ASSOC);
}

// Sección por defecto (la misma de la inscripción reciente si existe)
$seccionPorDefecto = $inscripcionReciente['IdSeccion'] ?? null;
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
        background: linear-gradient(135deg, #28a745 0%, #20873a 100%);
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
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
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
        background: #e7f5ff;
        border: 1px solid #b3d9ff;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .info-badge i {
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
    }

    .btn-primary {
        background: #28a745;
        color: white;
    }

    .btn-primary:hover {
        background: #218838;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
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
</style>

<section class="home-section">
    <div class="main-content">
        <div class="form-container">
            <div class="form-card">
                <!-- Header -->
                <div class="form-header">
                    <h2>
                        <i class='bx bx-refresh'></i>
                        Renovar Cupo
                    </h2>
                    <p>Solicitud de renovación de cupo para el próximo año escolar</p>
                </div>

                <!-- Body -->
                <div class="form-body">
                    <?php if (!$cursoSiguiente): ?>
                        <div class="alert-warning">
                            <i class='bx bx-error-circle'></i>
                            <strong>No se puede renovar el cupo.</strong><br>
                            El estudiante ya está en el último curso disponible o no se encontró un curso siguiente.
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
                            Complete el formulario para solicitar la renovación del cupo del estudiante para el próximo año escolar.
                        </div>

                        <form id="formRenovarCupo" method="POST" action="../../../controladores/representantes/procesar_renovacion.php">
                            <input type="hidden" name="IdEstudiante" value="<?= $idEstudiante ?>">
                            <input type="hidden" name="IdFechaEscolar" value="<?= $añoEscolarActivo['IdFecha_Escolar'] ?? '' ?>">
                            <input type="hidden" name="IdCurso" value="<?= $cursoSiguiente['IdCurso'] ?>">
                            <input type="hidden" name="idTipoInscripcion" value="2">

                            <!-- Año Escolar -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class='bx bx-calendar'></i>
                                    Año Escolar
                                    <span class="readonly-badge">Solo lectura</span>
                                </label>
                                <div class="form-control-readonly">
                                    <?= htmlspecialchars($añoEscolarActivo['fecha_escolar'] ?? 'No definido') ?>
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

                            <!-- Curso -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class='bx bxs-graduation'></i>
                                    Curso
                                    <span class="readonly-badge">Solo lectura</span>
                                </label>
                                <div class="form-control-readonly">
                                    <?= htmlspecialchars($cursoSiguiente['curso']) ?>
                                </div>
                            </div>

                            <!-- Sección (editable) -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class='bx bx-group'></i>
                                    Sección
                                    <span style="color: #dc3545;">*</span>
                                </label>
                                <select name="IdCursoSeccion" id="IdCursoSeccion" class="form-select" required>
                                    <option value="">Seleccione una sección</option>
                                    <?php foreach ($seccionesDisponibles as $seccion): ?>
                                        <option
                                            value="<?= $seccion['IdCurso_Seccion'] ?>"
                                            <?= ($seccion['IdSeccion'] == $seccionPorDefecto) ? 'selected' : '' ?>
                                        >
                                            <?= htmlspecialchars($seccion['seccion']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Puede cambiar la sección si lo desea</small>
                            </div>

                            <!-- Acciones -->
                            <div class="form-actions">
                                <a href="representado.php" class="btn btn-secondary">
                                    <i class='bx bx-x'></i>
                                    Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class='bx bx-check'></i>
                                    Solicitar Renovación
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
        confirmButtonText: 'Entendido'
    });
<?php endif; ?>

document.getElementById('formRenovarCupo')?.addEventListener('submit', function(e) {
    e.preventDefault();

    Swal.fire({
        title: '¿Confirmar solicitud?',
        text: '¿Está seguro de que desea solicitar la renovación de cupo para este estudiante?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
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
