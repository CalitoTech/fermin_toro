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

require_once __DIR__ . '/../../../controladores/Notificaciones.php';
require_once __DIR__ . '/../../../config/conexion.php';

// Manejo de alertas
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

// === Cargar Status ===
$status_list = [];
try {
    require_once __DIR__ . '/../../../modelos/Status.php';
    $database = new Database();
    $conexion = $database->getConnection();
    $statusModel = new Status($conexion);
    $status_list = $statusModel->obtenerStatusEgreso();
} catch (Exception $e) {
    error_log("Error al cargar status: " . $e->getMessage());
}
?>

<head>
    <title>UECFT Araure - Nuevo Egreso</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../../../assets/css/solicitud_cupo.css">
    <style>
        /* === ESTILO PERSONALIZADO === */
        .card-header {
            background: linear-gradient(90deg, #c90000, #8b0000);
            color: #fff;
        }

        .input-group-text {
            background-color: #c90000;
            color: #fff;
        }

        .btn-danger {
            background-color: #c90000;
            border: none;
        }

        .btn-outline-danger:hover {
            background-color: #c90000;
            color: #fff;
        }
    </style>
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
                        <div class="card-header text-center">
                            <h4 class="mb-0"><i class='bx bxs-user-x'></i> Nuevo Egreso</h4>
                        </div>
                        <div class="card-body p-4">
                            <form action="../../../controladores/EgresoController.php" method="POST" id="formEgreso">
                                <input type="hidden" name="action" value="crear">
                                <input type="hidden" name="IdPersona" id="IdPersona">

                                <div class="row">
                                    <!-- Buscador de Estudiante -->
                                    <div class="col-md-12 position-relative mb-4">
                                        <label for="buscadorEstudiante" class="form-label">Buscar Estudiante *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class='bx bxs-user'></i></span>
                                            <input type="text" class="form-control" id="buscadorEstudiante"
                                                placeholder="Escriba nombre o cédula..." autocomplete="off" required>
                                        </div>
                                        <div id="resultadosBusqueda" class="autocomplete-results d-none"></div>
                                        <small class="text-muted">Solo se muestran estudiantes sin egreso registrado</small>
                                    </div>

                                    <!-- Fecha de Egreso -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="fecha_egreso" class="form-label">Fecha de Egreso *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-calendar'></i></span>
                                                <input type="date" class="form-control" name="fecha_egreso" id="fecha_egreso"
                                                    required max="<?= date('Y-m-d') ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="IdStatus" class="form-label">Status *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-flag'></i></span>
                                                <select class="form-control" name="IdStatus" id="IdStatus" required>
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
                                                <textarea class="form-control" name="motivo" id="motivo"
                                                    rows="4" maxlength="255"
                                                    placeholder="Describa el motivo del egreso (opcional)"></textarea>
                                            </div>
                                            <small class="text-muted">Máximo 255 caracteres</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="egreso.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Guardar
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="../../../assets/js/buscador_generico.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    flatpickr("#fecha_egreso", {
        dateFormat: "Y-m-d",
        maxDate: "today",
        locale: "es"
    });

    // Usar el buscador genérico para estudiantes
    const buscadorEstudiante = new BuscadorGenerico(
        'buscadorEstudiante',
        'resultadosBusqueda',
        'estudiante',
        'IdPersona'
    );

    // Validación del formulario
    document.getElementById('formEgreso').addEventListener('submit', function(e) {
        const idPersonaInput = document.getElementById('IdPersona');
        if (!idPersonaInput.value) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Seleccione un estudiante',
                text: 'Debe buscar y seleccionar un estudiante válido',
                confirmButtonColor: '#c90000'
            });
        }
    });
});
</script>

</body>
</html>
