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

// Obtener ID del urbanismo a editar
$idUrbanismo = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idUrbanismo <= 0) {
    header("Location: urbanismo.php");
    exit();
}

// Incluir Notificaciones
require_once __DIR__ . '/../../../controladores/Notificaciones.php';

// Manejo de alertas
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito("El urbanismo se actualizó correctamente.");
            break;
        case 'error':
            $alerta = Notificaciones::advertencia("Error al actualizar el urbanismo.");
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
require_once __DIR__ . '/../../../modelos/Urbanismo.php';

$database = new Database();
$conexion = $database->getConnection();

$urbanismoModel = new Urbanismo($conexion);
$urbanismo = $urbanismoModel->obtenerPorId($idUrbanismo); // Cargar datos

// Para guardar cambios:
if ($urbanismoModel->actualizar()) {
    // Actualización exitosa
}

if (!$urbanismo) {
    header("Location: urbanismo.php");
    exit();
}
?>

<head>
    <title>UECFT Araure - Editar Urbanismo</title>
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
                            <h4 class="mb-0"><i class='bx bxs-user-edit'></i> Editar Urbanismo</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/UrbanismoController.php?action=editar" method="POST" id="editar">
                                <input type="hidden" name="id" value="<?= $idUrbanismo ?>">
                                
                                <div class="row">
                                    <!-- Columna Izquierda -->
                                    <div class="col-md-6">
                                        <!-- Urbanismo -->
                                        <div class="añadir__grupo" id="grupo__urbanismo">
                                            <label for="urbanismo" class="form-label">Urbanismo *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-user'></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input" 
                                                    name="urbanismo" 
                                                    id="urbanismo" 
                                                    required 
                                                    maxlength="40"
                                                    value="<?= htmlspecialchars($urbanismo['urbanismo']) ?>"
                                                    onkeypress="return onlyText(event)">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">El urbanismo debe tener entre 3 y 40 letras.</p>
                                        </div>
                                    </div>
                                </div>


                                <!-- Botón -->
                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Actualizar Urbanismo
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