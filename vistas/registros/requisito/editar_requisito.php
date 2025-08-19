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

// Obtener ID del requisito a editar
$idRequisito = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idRequisito <= 0) {
    header("Location: requisito.php");
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
require_once __DIR__ . '/../../../modelos/Requisito.php';
require_once __DIR__ . '/../../../modelos/Nivel.php';

$database = new Database();
$conexion = $database->getConnection();

$requisitoModel = new Requisito($conexion);
$requisito = $requisitoModel->obtenerPorId($idRequisito); // Cargar datos

$nivelModel = new Nivel($conexion);
$niveles = $nivelModel->obtenerTodos();

if (!$requisito) {
    header("Location: requisito.php");
    exit();
}
?>

<head>
    <title>UECFT Araure - Editar Requisito</title>
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
                            <h4 class="mb-0"><i class='bx bxs-user-edit'></i> Editar Requisito</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/RequisitoController.php?action=editar" method="POST" id="editar">
                                <input type="hidden" name="id" value="<?= $idRequisito ?>">
                                
                                <div class="row">
                                    <!-- Columna Izquierda -->
                                    <div class="col-md-6">
                                     <!-- Nivel -->
                                        <div class="añadir__grupo" id="grupo__nivel">
                                            <label for="nivel" class="form-label">Nivel *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-star'></i></span>
                                                <select 
                                                    class="form-control añadir__input" 
                                                    name="nivel" 
                                                    id="nivel" 
                                                    required>
                                                   <?php foreach ($niveles as $nivel): ?>
                                                        <option value="<?= $nivel['IdNivel'] ?>" 
                                                            <?= $nivel['IdNivel'] == $requisito['IdNivel'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($nivel['nivel']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Debe seleccionar un nivel.</p>
                                        </div>

                                        <!-- Requisito -->
                                        <div class="añadir__grupo" id="grupo__requisito">
                                            <label for="requisito" class="form-label">Requisito *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-user'></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input" 
                                                    name="requisito" 
                                                    id="texto" 
                                                    required 
                                                    maxlength="40"
                                                    value="<?= htmlspecialchars($requisito['requisito']) ?>">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">El requisito debe tener entre 3 y 40 letras.</p>
                                        </div>

                                         <!-- ¿Es Obligatorio? -->
                                        <div class="añadir__grupo" id="grupo__obligatorio">
                                            <label class="toggle-label">
                                                <span class="form-label">¿Es Obligatorio?</span>
                                                <div class="toggle-container">
                                                    <input 
                                                        type="checkbox" 
                                                        name="obligatorio" 
                                                        id="obligatorio"
                                                        class="toggle-input añadir__input"
                                                        value="1"
                                                        <?= $requisito['obligatorio'] == 1 ? 'checked' : '' ?>>
                                                    <label for="obligatorio" class="toggle-slider"></label>
                                                </div>
                                            </label>
                                            <p class="añadir__input-error">Seleccione si el elemento es obligatorio.</p>
                                        </div>
                                    </div>
                                </div>


                                <!-- Botones para Volver y Actualizar -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="requisito.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver a Requisitos
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Actualizar Requisito
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