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

// === Cargar Niveles desde la base de datos ===
$niveles = [];

try {
    require_once __DIR__ . '/../../../modelos/Nivel.php'; // Asumo que tienes un modelo Niveles
    $nivelModel = new Nivel($conexion);
    $niveles = $nivelModel->obtenerTodos();
} catch (Exception $e) {
    error_log("Error al cargar niveles en nuevo_tipo_grupo_interes.php: " . $e->getMessage());
    $niveles = [];
}

?>

<head>
    <title>UECFT Araure - Nuevo Tipo de Grupo de Interés</title>
</head>

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-10"> <!-- Ampliado a lg-10 para más espacio -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-danger text-white text-center">
                            <h4 class="mb-0"><i class='bx bxs-user-plus'></i> Nuevo Grupo de Interés</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/TipoGrupoInteresController.php" method="POST" id="añadir">
                                <input type="hidden" name="action" value="crear">
                                
                                <div class="row g-4"> <!-- Espaciado consistente entre filas -->

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
                                                    <option value="">Seleccione un nivel</option>
                                                    <?php foreach ($niveles as $nivel): ?>
                                                        <option value="<?= $nivel['IdNivel'] ?>" <?= $nivel['IdNivel'] == ($nivelSeleccionado ?? '') ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($nivel['nivel']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Debe seleccionar un nivel.</p>
                                        </div>

                                        <!-- Nombre de Grupo -->
                                        <div class="añadir__grupo" id="grupo__nombre_grupo">
                                            <label for="nombre_grupo" class="form-label">Nombre de Grupo *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-group'></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input" 
                                                    name="nombre_grupo" 
                                                    id="nombre_grupo" 
                                                    required 
                                                    maxlength="40"
                                                    oninput="formatearTexto()">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">El nombre del grupo debe tener entre 3 y 40 caracteres.</p>
                                        </div>

                                        <!-- ¿Aceptar Inscripciones? -->
                                        <div class="añadir__grupo" id="grupo__inscripcion_activa">
                                            <label class="toggle-label">
                                                <span class="form-label">¿Aceptar Inscripciones?</span>
                                                <div class="toggle-container">
                                                    <input 
                                                        type="checkbox" 
                                                        name="inscripcion_activa" 
                                                        id="inscripcion_activa" 
                                                        value="1"
                                                        class="toggle-input añadir__input">
                                                    <label for="inscripcion_activa" class="toggle-slider"></label>
                                                </div>
                                            </label>
                                            <p class="añadir__input-error">Seleccione si el grupo está aceptando inscripciones.</p>
                                        </div>
                                    </div>

                                    <!-- Columna Derecha -->
                                    <div class="col-md-6">

                                        <!-- Descripción -->
                                        <div class="añadir__grupo" id="grupo__descripcion">
                                            <label for="descripcion" class="form-label">Descripción *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-notepad'></i></span>
                                                <textarea 
                                                    class="form-control añadir__input" 
                                                    name="descripcion" 
                                                    id="descripcion" 
                                                    required 
                                                    rows="4" 
                                                    maxlength="500"
                                                    placeholder="Describa brevemente el propósito del grupo de interés..."></textarea>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">La descripción debe tener entre 10 y 500 caracteres.</p>
                                        </div>

                                        <!-- Capacidad Máxima -->
                                        <div class="añadir__grupo" id="grupo__capacidad_maxima">
                                            <label for="capacidad_maxima" class="form-label">Capacidad Máxima *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-user-plus'></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input" 
                                                    name="capacidad_maxima" 
                                                    id="texto" 
                                                    required 
                                                    maxlength="2"
                                                    pattern="^[0-9]+"
                                                    onkeypress="return onlyNumber(event)">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">La capacidad máxima es obligatoria.</p>
                                        </div>
                                    </div>

                                <!-- Botones para Volver y Guardar -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="tipo_grupo_interes.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver a Grupo de Interés
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Guardar Grupo de Interés
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