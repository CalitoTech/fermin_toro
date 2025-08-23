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

// Obtener ID del fecha_escolar a editar
$idFechaEscolar = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idFechaEscolar <= 0) {
    header("Location: fecha_escolar.php");
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
require_once __DIR__ . '/../../../modelos/FechaEscolar.php';

$database = new Database();
$conexion = $database->getConnection();

$fecha_escolarModel = new FechaEscolar($conexion);
$fecha_escolar = $fecha_escolarModel->obtenerPorId($idFechaEscolar); // Cargar datos

if (!$fecha_escolar) {
    header("Location: fecha_escolar.php");
    exit();
}
?>

<head>
    <title>UECFT Araure - Editar Año Escolar</title>
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
                            <h4 class="mb-0"><i class='bx bxs-user-edit'></i> Editar Año Escolar</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/FechaEscolarController.php?action=editar" method="POST" id="editar">
                                <input type="hidden" name="id" value="<?= $idFechaEscolar ?>">
                                
                                <div class="row">
                                    <!-- Columna Izquierda -->
                                    <div class="col-md-6">
                                        <!-- FechaEscolar -->
                                        <div class="añadir__grupo" id="grupo__fecha_escolar">
                                            <label for="fecha_escolar" class="form-label">Año Escolar *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-user'></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input" 
                                                    name="fecha_escolar" 
                                                    id="fecha_escolar"
                                                    value="<?= htmlspecialchars($fecha_escolar['fecha_escolar']) ?>"
                                                    autocomplete="off" 
                                                    minlength="5" 
                                                    maxlength="11" 
                                                    required 
                                                    onkeypress="return fechita(event)">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">La fecha solo puede tener números y "/" o "-". Requiere de 5 carácteres como mínimo y 11 como máximo.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones para Volver y Actualizar -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="fecha_escolar.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver a Año Escolar
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Actualizar Año Escolar
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