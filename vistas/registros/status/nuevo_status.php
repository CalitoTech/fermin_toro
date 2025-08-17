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
    // Asegurarnos de que conexion.php define la clase Database
    require_once __DIR__ . '/../../../config/conexion.php';

    $database = new Database();
    $conexion = $database->getConnection();

} catch (Exception $e) {
    error_log("Error al conectar a la base de datos: " . $e->getMessage());
}

// === Cargar Tipo_Statuses desde la base de datos ===
$tipo_statuses = [];

try {
    require_once __DIR__ . '/../../../modelos/TipoStatus.php'; // Asumo que tienes un modelo Tipo_Statuses
    $tipo_statusModel = new TipoStatus($conexion);
    $tipo_statuses = $tipo_statusModel->obtenerTodos();
} catch (Exception $e) {
    error_log("Error al cargar tipo_statuses en nuevo_status.php: " . $e->getMessage());
    $tipo_statuses = [];
}

?>

<head>
    <title>UECFT Araure - Nuevo Status</title>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="card shadow-sm border-0" >
                        <div class="card-header bg-danger text-white text-center">
                            <h4 class="mb-0"><i class='bx bxs-user-plus'></i> Nuevo Status</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/StatusController.php" method="POST" id="añadir">
                                <input type="hidden" name="action" value="crear">
                                <div class="row">
                                    <!-- Columna Izquierda -->
                                    <div class="col-md-6">

                                        <!-- Tipo_Status -->
                                        <div class="añadir__grupo" id="grupo__tipo_status">
                                            <label for="tipo_status" class="form-label">Tipo de Status *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-star'></i></span>
                                                <select 
                                                    class="form-control añadir__input" 
                                                    name="tipo_status" 
                                                    id="tipo_status" 
                                                    required>
                                                    <option value="">Seleccione un tipo de status</option>
                                                    <?php foreach ($tipo_statuses as $tipo_status): ?>
                                                        <option value="<?= $tipo_status['IdTipo_Status'] ?>" <?= $tipo_status['IdTipo_Status']?>>
                                                            <?= htmlspecialchars($tipo_status['tipo_status']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Debe seleccionar un tipo de status.</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">

                                        <!-- Status -->
                                        <div class="añadir__grupo" id="grupo__status">
                                            <label for="status" class="form-label">Status *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-user'></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input" 
                                                    name="status" 
                                                    id="texto" 
                                                    required 
                                                    maxlength="10"
                                                    oninput="formatearTexto()"
                                                    onkeypress="return onlyText(event)">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">El status debe tener entre 3 y 10 letras.</p>
                                        </div>
                                    </div>

                                <!-- Botones para Volver y Guardar -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="status.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver a Status
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Guardar Status
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

<script src="../../../assets/js/validacion.js"></script>
<script src="../../../assets/js/formulario.js"></script>

</body>
</html>