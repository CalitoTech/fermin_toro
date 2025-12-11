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

// Obtener ID del plantel a editar
$idPlantel = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idPlantel <= 0) {
    header("Location: plantel.php");
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
require_once __DIR__ . '/../../../modelos/Plantel.php';

$database = new Database();
$conexion = $database->getConnection();

$plantelModel = new Plantel($conexion);
$plantel = $plantelModel->obtenerPorId($idPlantel); // Cargar datos

if (!$plantel) {
    header("Location: plantel.php");
    exit();
}
?>

<head>
    <title>UECFT Araure - Editar Plantel</title>
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
                            <h4 class="mb-0"><i class='bx bxs-school'></i> Editar Plantel</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/PlantelController.php?action=editar" method="POST" id="editar">
                                <input type="hidden" name="id" value="<?= $idPlantel ?>">

                                <div class="row">
                                    <!-- Columna Izquierda -->
                                    <div class="col-md-6">
                                        <!-- Plantel -->
                                        <div class="añadir__grupo" id="grupo__plantel">
                                            <label for="plantel" class="form-label">Plantel *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-school'></i></span>
                                                <input
                                                    type="text"
                                                    class="form-control añadir__input"
                                                    name="plantel"
                                                    id="texto"
                                                    required
                                                    maxlength="30"
                                                    value="<?= htmlspecialchars($plantel['plantel']) ?>"
                                                    oninput="formatearTexto()">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">El plantel debe tener entre 3 y 100 letras.</p>
                                        </div>
                                    </div>

                                    <!-- Columna Derecha -->
                                    <div class="col-md-6">
                                        <!-- Es Privado -->
                                        <div class="añadir__grupo" id="grupo__es_privado">
                                            <label class="form-label">Tipo de Plantel</label>
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" name="es_privado" id="es_privado"
                                                       <?= $plantel['es_privado'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="es_privado">
                                                    <i class='bx bx-building-house'></i> Es Plantel Privado
                                                </label>
                                            </div>
                                            <small class="text-muted">Marque si el plantel es de carácter privado</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones para Volver y Actualizar -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="plantel.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver a Planteles
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Actualizar Plantel
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
