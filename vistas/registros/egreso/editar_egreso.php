<?php
session_start();

// Verificación de sesión
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

// === Obtener ID del egreso ===
$idEgreso = $_GET['id'] ?? 0;
if ($idEgreso <= 0) {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = 'ID de egreso inválido';
    header("Location: egreso.php");
    exit();
}

try {
    require_once __DIR__ . '/../../../config/conexion.php';
    require_once __DIR__ . '/../../../modelos/Egreso.php';

    $database = new Database();
    $conexion = $database->getConnection();
    $egresoModel = new Egreso($conexion);

    // Obtener datos del egreso
    $egreso = $egresoModel->obtenerPorId($idEgreso);

    if (!$egreso) {
        $_SESSION['alert'] = 'error';
        $_SESSION['message'] = 'Egreso no encontrado';
        header("Location: egreso.php");
        exit();
    }

} catch (Exception $e) {
    error_log("Error al cargar egreso: " . $e->getMessage());
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = 'Error al cargar el egreso';
    header("Location: egreso.php");
    exit();
}

// === Cargar Estudiantes (todos los estudiantes, incluyendo el actual) ===
$estudiantes = [];
try {
    $queryEstudiantes = "SELECT DISTINCT p.IdPersona, p.nombre, p.apellido, p.cedula, n.nacionalidad
                        FROM persona p
                        INNER JOIN detalle_perfil dp ON p.IdPersona = dp.IdPersona
                        INNER JOIN perfil pr ON dp.IdPerfil = pr.IdPerfil
                        LEFT JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
                        WHERE pr.nombre_perfil = 'Estudiante'
                        AND (p.IdPersona NOT IN (SELECT IdPersona FROM egreso WHERE IdEgreso != :idEgreso)
                             OR p.IdPersona = :idPersonaActual)
                        ORDER BY p.apellido, p.nombre";
    $stmt = $conexion->prepare($queryEstudiantes);
    $stmt->bindParam(':idEgreso', $idEgreso, PDO::PARAM_INT);
    $stmt->bindParam(':idPersonaActual', $egreso['IdPersona'], PDO::PARAM_INT);
    $stmt->execute();
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error al cargar estudiantes: " . $e->getMessage());
    $estudiantes = [];
}

// === Cargar Status de tipo Persona ===
$status_list = [];
try {
    require_once __DIR__ . '/../../../modelos/Status.php';
    $statusModel = new Status($conexion);
    $status_list = $statusModel->obtenerStatusPersona();
} catch (Exception $e) {
    error_log("Error al cargar status: " . $e->getMessage());
    $status_list = [];
}

?>

<head>
    <title>UECFT Araure - Editar Egreso</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white text-center">
                            <h4 class="mb-0"><i class='bx bxs-edit'></i> Editar Egreso</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/EgresoController.php" method="POST" id="formEgreso">
                                <input type="hidden" name="action" value="editar">
                                <input type="hidden" name="IdEgreso" value="<?= $egreso['IdEgreso'] ?>">

                                <div class="row">
                                    <!-- Estudiante -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="IdPersona" class="form-label">Estudiante *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-user'></i></span>
                                                <select
                                                    class="form-control"
                                                    name="IdPersona"
                                                    id="IdPersona"
                                                    required>
                                                    <option value="">Seleccione un estudiante</option>
                                                    <?php foreach ($estudiantes as $est): ?>
                                                        <option value="<?= $est['IdPersona'] ?>"
                                                            <?= $est['IdPersona'] == $egreso['IdPersona'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($est['apellido'] . ' ' . $est['nombre']) ?>
                                                            (<?= htmlspecialchars($est['nacionalidad'] . '-' . $est['cedula']) ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Fecha de Egreso -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="fecha_egreso" class="form-label">Fecha de Egreso *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-calendar'></i></span>
                                                <input
                                                    type="date"
                                                    class="form-control"
                                                    name="fecha_egreso"
                                                    id="fecha_egreso"
                                                    required
                                                    max="<?= date('Y-m-d') ?>"
                                                    value="<?= htmlspecialchars($egreso['fecha_egreso']) ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="IdStatus" class="form-label">Status *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-flag'></i></span>
                                                <select
                                                    class="form-control"
                                                    name="IdStatus"
                                                    id="IdStatus"
                                                    required>
                                                    <option value="">Seleccione un status</option>
                                                    <?php foreach ($status_list as $status): ?>
                                                        <option value="<?= $status['IdStatus'] ?>"
                                                            <?= $status['IdStatus'] == $egreso['IdStatus'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($status['status']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Motivo -->
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="motivo" class="form-label">Motivo del Egreso</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-message-detail'></i></span>
                                                <textarea
                                                    class="form-control"
                                                    name="motivo"
                                                    id="motivo"
                                                    rows="4"
                                                    maxlength="255"
                                                    placeholder="Describa el motivo del egreso (opcional)"><?= htmlspecialchars($egreso['motivo'] ?? '') ?></textarea>
                                            </div>
                                            <small class="text-muted">Máximo 255 caracteres</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones para Volver y Guardar -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="egreso.php" class="btn btn-outline-secondary btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver a Egresos
                                    </a>
                                    <div>
                                        <a href="ver_egreso.php?id=<?= $egreso['IdEgreso'] ?>" class="btn btn-outline-info btn-lg me-2">
                                            <i class='bx bxs-show'></i> Ver Detalle
                                        </a>
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class='bx bxs-save'></i> Actualizar Egreso
                                        </button>
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

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Flatpickr para fecha
    flatpickr("#fecha_egreso", {
        dateFormat: "Y-m-d",
        maxDate: "today",
        locale: "es"
    });

    // Validación del formulario
    const form = document.getElementById('formEgreso');
    form.addEventListener('submit', function(e) {
        const estudiante = document.getElementById('IdPersona').value;
        const fecha = document.getElementById('fecha_egreso').value;
        const status = document.getElementById('IdStatus').value;

        if (!estudiante || !fecha || !status) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Campos Requeridos',
                text: 'Por favor complete todos los campos obligatorios',
                confirmButtonColor: '#c90000'
            });
        }
    });

    // Contador de caracteres para motivo
    const motivoTextarea = document.getElementById('motivo');
    if (motivoTextarea) {
        const updateCounter = function() {
            const remaining = 255 - this.value.length;
            this.nextElementSibling.textContent = `${remaining} caracteres restantes`;
        };
        motivoTextarea.addEventListener('input', updateCounter);
        // Ejecutar una vez al cargar para mostrar el contador inicial
        updateCounter.call(motivoTextarea);
    }
});
</script>

</body>
</html>
