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

// Obtener ID del bloque a editar
$idBloque = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idBloque <= 0) {
    header("Location: bloque.php");
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
require_once __DIR__ . '/../../../modelos/Bloque.php';

$database = new Database();
$conexion = $database->getConnection();

$bloqueModel = new Bloque($conexion);
$bloque = $bloqueModel->obtenerPorId($idBloque); // Cargar datos

if (!$bloque) {
    header("Location: bloque.php");
    exit();
}
?>

<head>
    <title>UECFT Araure - Editar Bloque</title>
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
                            <h4 class="mb-0"><i class='bx bxs-user-edit'></i> Editar Bloque</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/BloqueController.php?action=editar" method="POST" id="editar" lass="necesita-validacion-horario">
                                <input type="hidden" name="id" value="<?= $idBloque ?>">
                                
                                <div class="row">
                                    <!-- Columna Izquierda -->
                                    <div class="col-md-6">
                                        <!-- Hora de Inicio -->
                                        <div class="añadir__grupo" id="grupo__hora_inicio">
                                            <label for="hora_inicio" class="form-label">Hora de Inicio *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bx-time'></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input hora-picker" 
                                                    name="hora_inicio"
                                                    placeholder="Seleccione hora"
                                                    value="<?= date('h:i A', strtotime($bloque['hora_inicio'])) ?>">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">La hora de inicio es obligatoria</p>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <!-- Hora de Fin -->
                                        <div class="añadir__grupo" id="grupo__hora_fin">
                                            <label for="hora_fin" class="form-label">Hora de Fin *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bx-time'></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input hora-picker" 
                                                    name="hora_fin"
                                                    placeholder="Seleccione hora"
                                                    value="<?= date('h:i A', strtotime($bloque['hora_fin'])) ?>">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">La hora de fin es obligatoria</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones para Volver y Actualizar -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="bloque.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver a Bloques
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Actualizar Bloque
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

<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.min.js"></script>
<script src="../../../assets/js/flatpickr-config.js"></script>
<script src="../../../assets/js/validacion_bloque.js"></script>
<script src="../../../assets/js/bloques.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar configuraciones
    configurarTimepickers();
    configurarValidacionFormulario();
    
    // Otras inicializaciones específicas si son necesarias
});
</script>

</body>
</html>