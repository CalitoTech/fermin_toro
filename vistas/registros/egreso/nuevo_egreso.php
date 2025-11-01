<?php
session_start();

// Verificación de sesión
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

try {
    require_once __DIR__ . '/../../../config/conexion.php';
    $database = new Database();
    $conexion = $database->getConnection();
} catch (Exception $e) {
    error_log("Error al conectar a la base de datos: " . $e->getMessage());
}

// === Cargar Estudiantes (personas con perfil de estudiante) ===
$estudiantes = [];
try {
    $queryEstudiantes = "SELECT DISTINCT p.IdPersona, p.nombre, p.apellido, p.cedula, n.nacionalidad
                        FROM persona p
                        INNER JOIN detalle_perfil dp ON p.IdPersona = dp.IdPersona
                        INNER JOIN perfil pr ON dp.IdPerfil = pr.IdPerfil
                        LEFT JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
                        WHERE pr.nombre_perfil = 'Estudiante'
                        AND p.IdPersona NOT IN (SELECT IdPersona FROM egreso)
                        ORDER BY p.apellido, p.nombre";
    $stmt = $conexion->prepare($queryEstudiantes);
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
    <title>UECFT Araure - Nuevo Egreso</title>
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
                        <div class="card-header bg-danger text-white text-center">
                            <h4 class="mb-0"><i class='bx bxs-user-x'></i> Nuevo Egreso</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/EgresoController.php" method="POST" id="formEgreso">
                                <input type="hidden" name="action" value="crear">

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
                                                        <option value="<?= $est['IdPersona'] ?>">
                                                            <?= htmlspecialchars($est['apellido'] . ' ' . $est['nombre']) ?>
                                                            (<?= htmlspecialchars($est['nacionalidad'] . '-' . $est['cedula']) ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <small class="text-muted">Solo se muestran estudiantes sin egreso registrado</small>
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
                                                    max="<?= date('Y-m-d') ?>">
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
                                                            <?= $status['status'] === 'Graduado' ? 'selected' : '' ?>>
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
                                                    placeholder="Describa el motivo del egreso (opcional)"></textarea>
                                            </div>
                                            <small class="text-muted">Máximo 255 caracteres</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones para Volver y Guardar -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="egreso.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver a Egresos
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Guardar Egreso
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
        motivoTextarea.addEventListener('input', function() {
            const remaining = 255 - this.value.length;
            this.nextElementSibling.textContent = `${remaining} caracteres restantes`;
        });
    }
});
</script>

</body>
</html>
