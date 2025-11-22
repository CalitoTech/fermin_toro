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

// Obtener ID del curso_seccion a editar
$idCursoSeccion = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idCursoSeccion <= 0) {
    header("Location: curso_seccion.php");
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
require_once __DIR__ . '/../../../modelos/CursoSeccion.php';
require_once __DIR__ . '/../../../modelos/Nivel.php';
require_once __DIR__ . '/../../../modelos/Curso.php';
require_once __DIR__ . '/../../../modelos/Seccion.php';
require_once __DIR__ . '/../../../modelos/Aula.php';

$database = new Database();
$conexion = $database->getConnection();

$curso_seccionModel = new CursoSeccion($conexion);
$curso_seccion = $curso_seccionModel->obtenerPorId($idCursoSeccion); // Cargar datos

if (!$curso_seccion) {
    header("Location: curso_seccion.php");
    exit();
}

$cursoModel = new Curso($conexion);
$curso = $cursoModel->obtenerPorId($curso_seccion['IdCurso']);

// Agregar el IdNivel al array curso_seccion para usarlo en la vista
$curso_seccion['IdNivel'] = $curso['IdNivel'] ?? null;
?>

<head>
    <title>UECFT Araure - Editar Aula</title>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<?php
// Cargar datos con filtro por permisos
$nivelModel = new Nivel($conexion);
$niveles = $nivelModel->obtenerNiveles($idPersona);

$cursos = $cursoModel->obtenerCursos($idPersona);

$seccionModel = new Seccion($conexion);
$secciones = $seccionModel->obtenerTodos();

$aulaModel = new Aula($conexion);
$aulas = $aulaModel->obtenerAulas($idPersona);
?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-danger text-white text-center">
                            <h4 class="mb-0"><i class='bx bxs-user-edit'></i> Editar Curso/Sección</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/CursoSeccionController.php?action=editar" method="POST" id="editar">
                                <input type="hidden" name="id" value="<?= $idCursoSeccion ?>">
                                
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
                                                   <?php foreach ($niveles as $nivel): ?>
                                                        <option value="<?= $nivel['IdNivel'] ?>" 
                                                            <?= $nivel['IdNivel'] == $curso_seccion['IdNivel'] ? 'selected' : '' ?>>
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
                                                   <?php foreach ($cursos as $curso): ?>
                                                        <option value="<?= $curso['IdCurso'] ?>" 
                                                            <?= $curso['IdCurso'] == $curso_seccion['IdCurso'] ? 'selected' : '' ?>>
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
                                        
                                        <!-- Seccion -->
                                        <div class="añadir__grupo" id="grupo__seccion">
                                            <label for="seccion" class="form-label">Sección *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-star'></i></span>
                                                <select 
                                                    class="form-control añadir__input" 
                                                    name="seccion" 
                                                    id="seccion" 
                                                    required>
                                                   <?php foreach ($secciones as $seccion): ?>
                                                        <option value="<?= $seccion['IdSeccion'] ?>" 
                                                            <?= $seccion['IdSeccion'] == $curso_seccion['IdSeccion'] ? 'selected' : '' ?>>
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
                                                   <option value="" selected>Sin aula asignada</option>
                                                   <?php foreach ($aulas as $aula): ?>
                                                        <option value="<?= $aula['IdAula'] ?>" 
                                                            <?= ($aula['IdAula'] == ($curso_seccion['IdAula'] ?? null)) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($aula['aula']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Debe seleccionar una Aula.</p>
                                        </div>
                                    </div>
                                </div>


                                <!-- Botones para Volver y Actualizar -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="curso_seccion.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver a Curso/Sección
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Actualizar Curso/Sección
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