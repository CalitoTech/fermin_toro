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

<?php
// Cargar niveles con filtro por permisos
$nivelModel = new Nivel($conexion);
$niveles = $nivelModel->obtenerNiveles($idPersona);

// Cargar tipos de requisito
$tiposRequisito = [];
try {
    $query = "SELECT IdTipo_Requisito, tipo_requisito FROM tipo_requisito ORDER BY IdTipo_Requisito";
    $stmt = $conexion->prepare($query);
    $stmt->execute();
    $tiposRequisito = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error al cargar tipos de requisito: " . $e->getMessage());
    $tiposRequisito = [];
}

// Cargar tipos de trabajador
$tiposTrabajador = [];
try {
    $query = "SELECT IdTipoTrabajador, tipo_trabajador FROM tipo_trabajador ORDER BY tipo_trabajador";
    $stmt = $conexion->prepare($query);
    $stmt->execute();
    $tiposTrabajador = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error al cargar tipos de trabajador: " . $e->getMessage());
    $tiposTrabajador = [];
}
?>

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
                                        <!-- Tipo de Requisito -->
                                        <div class="añadir__grupo" id="grupo__tipoRequisito">
                                            <label for="tipoRequisito" class="form-label">Tipo de Requisito *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-category'></i></span>
                                                <select
                                                    class="form-control añadir__input"
                                                    name="tipoRequisito"
                                                    id="tipoRequisito"
                                                    required>
                                                    <option value="">Seleccione un tipo</option>
                                                    <?php foreach ($tiposRequisito as $tipo): ?>
                                                        <option value="<?= $tipo['IdTipo_Requisito'] ?>"
                                                            <?= $tipo['IdTipo_Requisito'] == $requisito['IdTipo_Requisito'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($tipo['tipo_requisito']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Debe seleccionar un tipo de requisito.</p>
                                        </div>

                                        <!-- Nivel -->
                                        <div class="añadir__grupo" id="grupo__nivel">
                                            <label for="nivel" class="form-label">Nivel</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-star'></i></span>
                                                <select
                                                    class="form-control añadir__input"
                                                    name="nivel"
                                                    id="nivel">
                                                    <option value="">General (aplica a todos)</option>
                                                    <?php foreach ($niveles as $nivel): ?>
                                                        <option value="<?= $nivel['IdNivel'] ?>"
                                                            <?= $nivel['IdNivel'] == $requisito['IdNivel'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($nivel['nivel']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Seleccione el nivel o deje en blanco para que aplique a todos.</p>
                                        </div>

                                        <!-- Tipo de Trabajador -->
                                        <div class="añadir__grupo" id="grupo__tipoTrabajador">
                                            <label for="tipoTrabajador" class="form-label">Tipo de Trabajador</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-briefcase'></i></span>
                                                <select
                                                    class="form-control añadir__input"
                                                    name="tipoTrabajador"
                                                    id="tipoTrabajador">
                                                    <option value="">Todos los tipos</option>
                                                    <?php foreach ($tiposTrabajador as $tipo): ?>
                                                        <option value="<?= $tipo['IdTipoTrabajador'] ?>"
                                                            <?= $tipo['IdTipoTrabajador'] == $requisito['IdTipoTrabajador'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($tipo['tipo_trabajador']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Seleccione si aplica solo a un tipo de trabajador.</p>
                                        </div>

                                        <!-- Requisito -->
                                        <div class="añadir__grupo" id="grupo__requisito">
                                            <label for="requisito" class="form-label">Requisito *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-file'></i></span>
                                                <input
                                                    type="text"
                                                    class="form-control añadir__input"
                                                    name="requisito"
                                                    id="texto"
                                                    required
                                                    maxlength="255"
                                                    value="<?= htmlspecialchars($requisito['requisito']) ?>">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">El requisito es requerido (máximo 255 caracteres).</p>
                                        </div>
                                    </div>

                                    <!-- Columna Derecha -->
                                    <div class="col-md-6">
                                        <!-- Descripción Adicional -->
                                        <div class="añadir__grupo" id="grupo__descripcionAdicional">
                                            <label for="descripcionAdicional" class="form-label">Descripción Adicional</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-detail'></i></span>
                                                <textarea
                                                    class="form-control añadir__input"
                                                    name="descripcionAdicional"
                                                    id="descripcionAdicional"
                                                    rows="3"
                                                    placeholder="Ej: Especificaciones, aclaraciones, etc."><?= htmlspecialchars($requisito['descripcion_adicional'] ?? '') ?></textarea>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Puede agregar información adicional sobre el requisito.</p>
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
                                            <p class="añadir__input-error">Indique si el requisito es obligatorio.</p>
                                        </div>

                                        <!-- Solo Plantel Privado -->
                                        <div class="añadir__grupo" id="grupo__soloPlantelPrivado">
                                            <label class="toggle-label">
                                                <span class="form-label">¿Solo para Plantel Privado?</span>
                                                <div class="toggle-container">
                                                    <input
                                                        type="checkbox"
                                                        name="soloPlantelPrivado"
                                                        id="soloPlantelPrivado"
                                                        value="1"
                                                        class="toggle-input añadir__input"
                                                        <?= $requisito['solo_plantel_privado'] == 1 ? 'checked' : '' ?>>
                                                    <label for="soloPlantelPrivado" class="toggle-slider"></label>
                                                </div>
                                            </label>
                                            <p class="añadir__input-error">Marque si solo aplica cuando el plantel anterior es privado.</p>
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