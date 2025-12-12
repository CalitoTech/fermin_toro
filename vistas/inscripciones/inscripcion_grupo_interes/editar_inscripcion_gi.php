<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    header("Location: ../../login/login.php");
    exit();
}

require_once __DIR__ . '/../../../controladores/Notificaciones.php';

// Manejo de alertas
$alert = $_SESSION['alert'] ?? null;
$message = $_SESSION['message'] ?? '';
unset($_SESSION['alert']);
unset($_SESSION['message']);

if ($alert) {
    if ($alert === 'actualizar') {
        $alerta = Notificaciones::exito($message);
    } elseif ($alert === 'error') {
        $alerta = Notificaciones::error($message);
    }
    
    if (isset($alerta)) {
        Notificaciones::mostrar($alerta);
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: inscripcion_grupo_interes.php");
    exit();
}

require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/InscripcionGrupoInteres.php';
require_once __DIR__ . '/../../../modelos/Persona.php';
require_once __DIR__ . '/../../../modelos/GrupoInteres.php';
require_once __DIR__ . '/../../../modelos/FechaEscolar.php';
require_once __DIR__ . '/../../../modelos/Inscripcion.php'; // Required to get course info

$database = new Database();
$db = $database->getConnection();

// Cargar inscripción grupo
$inscripcionModel = new InscripcionGrupoInteres($db);
$inscripcion = $inscripcionModel->obtenerPorId($id);

if (!$inscripcion) {
    header("Location: inscripcion_grupo_interes.php");
    exit();
}

// Cargar estudiante
$personaModel = new Persona($db);
$estudiante = $personaModel->obtenerEstudiantePorId($inscripcion['IdEstudiante']);

// Obtener Curso del Estudiante (desde la inscripción académica vinculada)
$insAcademicaModel = new Inscripcion($db);
$inscripcionAcademica = $insAcademicaModel->obtenerPorId($inscripcion['IdInscripcion']);
$idCursoEstudiante = $inscripcionAcademica ? $inscripcionAcademica['IdCurso'] : 0;

// Cargar Grupos Activos
$fechaModel = new FechaEscolar($db);
$fechaActiva = $fechaModel->obtenerActivo();

$grupos = [];
if ($fechaActiva) {
    $grupoModel = new GrupoInteres($db);
    $grupos = $grupoModel->obtenerPorFechaEscolar($fechaActiva['IdFecha_Escolar']);
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>UECFT Araure - Editar Inscripción Grupo</title>
     <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
</head>
<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-danger text-white text-center">
                            <h4 class="mb-0"><i class='bx bxs-edit'></i> Editar Inscripción - Grupo de Interés</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/InscripcionGrupoInteresController.php" method="POST">
                                <input type="hidden" name="action" value="editar">
                                <input type="hidden" name="id" value="<?= $id ?>">

                                <!-- Estudiante (Solo lectura) -->
                                <div class="mb-4">
                                    <label class="form-label">Estudiante</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class='bx bxs-user'></i></span>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($estudiante['cedula'] . ' - ' . $estudiante['nombre'] . ' ' . $estudiante['apellido']) ?>" disabled>
                                    </div>
                                    <input type="hidden" name="IdEstudiante" value="<?= $inscripcion['IdEstudiante'] ?>">
                                </div>

                                <!-- Grupo de Interés -->
                                <div class="mb-4">
                                    <label for="IdGrupo_Interes" class="form-label">Grupo de Interés *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class='bx bxs-group'></i></span>
                                        <select class="form-select" name="IdGrupo_Interes" id="IdGrupo_Interes" required>
                                            <option value="">Seleccione un grupo...</option>
                                            <?php foreach ($grupos as $grupo): ?>
                                                <?php 
                                                    // Filtrar: Mostrar solo si coincide el curso
                                                    // O si es el grupo que ya tiene asignado (para que no desaparezca su selección actual aunque cambien reglas, por seguridad de visualización)
                                                    if ($grupo['IdCurso'] == $idCursoEstudiante || $grupo['IdGrupo_Interes'] == $inscripcion['IdGrupo_Interes']): 
                                                ?>
                                                <option value="<?= $grupo['IdGrupo_Interes'] ?>" <?= ($grupo['IdGrupo_Interes'] == $inscripcion['IdGrupo_Interes']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($grupo['nombre_grupo'] . ' (' . $grupo['curso'] . ')') ?>
                                                </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <small class="text-muted">Mostrando solo grupos del curso correspondiente.</small>
                                </div>

                                <!-- Botones -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="inscripcion_grupo_interes.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Actualizar
                                    </button>
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

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#IdGrupo_Interes').select2({
            theme: 'bootstrap-5',
            placeholder: 'Seleccione un grupo'
        });
    });
</script>

</body>
</html>
