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

// Obtener ID del status a editar
$idStatus = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idStatus <= 0) {
    header("Location: status.php");
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

// Cargar modelos y datos
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Status.php';
require_once __DIR__ . '/../../../modelos/TipoStatus.php';

$database = new Database();
$conexion = $database->getConnection();

$statusModel = new Status($conexion);
$status = $statusModel->obtenerPorId($idStatus); // Cargar datos

$tipo_statusModel = new TipoStatus($conexion);
$tipo_statuses = $tipo_statusModel->obtenerTodos();

if (!$status) {
    header("Location: status.php");
    exit();
}
?>

<head>
    <title>UECFT Araure - Editar Status</title>
</head>

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-danger text-white text-center">
                            <h4 class="mb-0"><i class='bx bxs-user-edit'></i> Editar Status</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/StatusController.php?action=editar" method="POST" id="editar">
                                <input type="hidden" name="id" value="<?= $idStatus ?>">
                                
                                <div class="row">
                                    <!-- Columna Izquierda -->
                                    <div class="col-md-6">
                                     <!-- Tipo de Status -->
                                        <div class="añadir__grupo" id="grupo__tipo_status">
                                            <label for="tipo_status" class="form-label">Tipo de Status *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-star'></i></span>
                                                <select 
                                                    class="form-control añadir__input" 
                                                    name="tipo_status" 
                                                    id="tipo_status" 
                                                    required>
                                                   <?php foreach ($tipo_statuses as $tipo_status): ?>
                                                        <option value="<?= $tipo_status['IdTipo_Status'] ?>" 
                                                            <?= $tipo_status['IdTipo_Status'] == $status['IdTipo_Status'] ? 'selected' : '' ?>>
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
                                                    maxlength="40"
                                                    value="<?= htmlspecialchars($status['status']) ?>"
                                                    oninput="formatearTexto()"
                                                    onkeypress="return onlyText(event)">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">El status debe tener entre 3 y 40 letras.</p>
                                        </div>
                                    </div>
                                </div>


                                <!-- Botones para Volver y Actualizar -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="status.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver a Status
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Actualizar Status
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