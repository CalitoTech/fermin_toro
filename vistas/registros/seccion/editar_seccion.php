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

// Obtener ID del seccion a editar
$idSeccion = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idSeccion <= 0) {
    header("Location: seccion.php");
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
require_once __DIR__ . '/../../../modelos/Seccion.php';

$database = new Database();
$conexion = $database->getConnection();

$seccionModel = new Seccion($conexion);
$seccion = $seccionModel->obtenerPorId($idSeccion); // Cargar datos

if (!$seccion) {
    header("Location: seccion.php");
    exit();
}
?>

<head>
    <title>UECFT Araure - Editar Sección</title>
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
                            <h4 class="mb-0"><i class='bx bxs-user-edit'></i> Editar Sección</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/SeccionController.php?action=editar" method="POST" id="editar">
                                <input type="hidden" name="id" value="<?= $idSeccion ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <!-- Sección -->
                                        <div class="añadir__grupo" id="grupo__seccion">
                                            <label for="seccion" class="form-label">Sección *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-user'></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input" 
                                                    name="seccion" 
                                                    id="texto" 
                                                    required 
                                                    maxlength="40"
                                                    oninput="formatearTexto()"
                                                    onkeypress="return onlyText(event)"
                                                    value="<?= htmlspecialchars($seccion['seccion']) ?>">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">El seccion debe tener entre 3 y 40 letras.</p>
                                        </div>
                                    </div>
                                </div>


                                <!-- Botones para Volver y Actualizar -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="seccion.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver a Secciones
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Actualizar Sección
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