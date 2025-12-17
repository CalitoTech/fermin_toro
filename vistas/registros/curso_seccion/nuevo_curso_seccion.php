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

?>

<head>
    <title>UECFT Araure - Nuevo Curso/Sección</title>
</head>

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<?php
// Cargar datos con filtro por permisos
$niveles = [];
$cursos = [];
$secciones = [];
$aulas = [];

try {
    require_once __DIR__ . '/../../../modelos/Nivel.php';
    require_once __DIR__ . '/../../../modelos/Curso.php';
    require_once __DIR__ . '/../../../modelos/Seccion.php';
    require_once __DIR__ . '/../../../modelos/Aula.php';

    $nivelModel = new Nivel($conexion);
    $niveles = $nivelModel->obtenerNiveles($idPersona);

    $cursoModel = new Curso($conexion);
    $cursos = $cursoModel->obtenerCursos($idPersona);

    $seccionModel = new Seccion($conexion);
    $secciones = $seccionModel->obtenerTodos();

    $aulaModel = new Aula($conexion);
    $aulas = $aulaModel->obtenerAulas($idPersona);
} catch (Exception $e) {
    error_log("Error al cargar datos: " . $e->getMessage());
}
?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="card shadow-sm border-0" >
                        <div class="card-header bg-danger text-white text-center">
                            <h4 class="mb-0"><i class='bx bxs-user-plus'></i> Nuevo Curso/Sección</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/CursoSeccionController.php" method="POST" id="añadir">
                                <input type="hidden" name="action" value="crear">
                                <div class="row">

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
                                                        <option value="<?= $nivel['IdNivel'] ?>" <?= $nivel['IdNivel']?>>
                                                            <?= htmlspecialchars($nivel['nivel']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Debe seleccionar un nivel.</p>
                                        </div>

                                        <!-- Curso -->
                                        <div class="añadir__grupo" id="grupo__curso">
                                            <label for="curso" class="form-label">Curso *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-star'></i></span>
                                                <select 
                                                    class="form-control añadir__input" 
                                                    name="curso" 
                                                    id="curso" 
                                                    required>
                                                    <option value="">Seleccione un curso</option>
                                                    <?php foreach ($cursos as $curso): ?>
                                                        <option value="<?= $curso['IdCurso'] ?>" <?= $curso['IdCurso']?>>
                                                            <?= htmlspecialchars($curso['curso']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Debe seleccionar un curso.</p>
                                        </div>

                                    </div>
                                    <div class="col-md-6">

                                       <div class="añadir__grupo" id="grupo__seccion">
                                            <label for="seccion" class="form-label">Sección *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-star'></i></span>
                                                <select 
                                                    class="form-control añadir__input" 
                                                    name="seccion" 
                                                    id="seccion" 
                                                    required>
                                                    <option value="">Seleccione una sección</option>
                                                    <?php foreach ($secciones as $seccion): ?>
                                                        <option value="<?= $seccion['IdSeccion'] ?>" <?= $seccion['IdSeccion']?>>
                                                            <?= htmlspecialchars($seccion['seccion']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Debe seleccionar una sección.</p>
                                        </div>

                                        <!-- Aula -->
                                        <div class="añadir__grupo" id="grupo__aula">
                                            <label for="aula" class="form-label">Aula *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-star'></i></span>
                                                <select 
                                                    class="form-control añadir__input" 
                                                    name="aula" 
                                                    id="aula" 
                                                    required>
                                                    <option value="">Seleccione un aula</option>
                                                    <?php foreach ($aulas as $aula): ?>
                                                        <option value="<?= $aula['IdAula'] ?>" <?= $aula['IdAula']?>>
                                                            <?= htmlspecialchars($aula['aula']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Debe seleccionar un aula.</p>
                                        </div>

                                        <!-- Activo -->
                                        <div class="añadir__grupo mt-3" id="grupo__activo">
                                            <label class="form-label d-block">¿Activo?</label>
                                            <label class="toggle-label">
                                                <span class="toggle-text" id="toggle-text-activo">Sí</span>
                                                <div class="toggle-container">
                                                    <input 
                                                        type="checkbox" 
                                                        class="toggle-input" 
                                                        id="activo" 
                                                        name="activo" 
                                                        value="1" 
                                                        checked 
                                                        onchange="document.getElementById('toggle-text-activo').textContent = this.checked ? 'Sí' : 'No'">
                                                    <span class="toggle-slider"></span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                <!-- Botones para Volver y Guardar -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="curso_seccion.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver a Curso/Sección
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Guardar Curso/Sección
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

<script src="../../../assets/js/get_by_nivel.js"></script>
<script src="../../../assets/js/validacion.js"></script>
<script src="../../../assets/js/formulario.js"></script>

</body>
</html>