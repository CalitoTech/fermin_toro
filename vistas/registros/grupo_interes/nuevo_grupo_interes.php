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
    require_once __DIR__ . '/../../../config/conexion.php';
    $database = new Database();
    $conexion = $database->getConnection();

    // Modelos
    require_once __DIR__ . '/../../../modelos/Nivel.php';
    require_once __DIR__ . '/../../../modelos/TipoGrupoInteres.php';
    require_once __DIR__ . '/../../../modelos/Curso.php';
    require_once __DIR__ . '/../../../modelos/Persona.php';

    // Cargar datos
    $nivelModel = new Nivel($conexion);
    $niveles = $nivelModel->obtenerNiveles($_SESSION['idPersona']);

    $tipoModel = new TipoGrupoInteres($conexion);
    $tipos = $tipoModel->obtenerTodos();

    $cursoModel = new Curso($conexion);
    $cursos = $cursoModel->obtenerTodos();

    $personaModel = new Persona($conexion);
    $profesores = $personaModel->obtenerProfesores();

} catch (Exception $e) {
    error_log("Error al cargar datos: " . $e->getMessage());
    $niveles = []; $tipos = []; $cursos = []; $profesores = [];
}
?>

<head>
    <title>UECFT Araure - Nuevo Grupo de Interés</title>
</head>

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<!-- Content -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="card shadow-sm border-0" >
                        <div class="card-header bg-danger text-white text-center">
                            <h4 class="mb-0"><i class='bx bxs-user-plus'></i> Nuevo Grupo de Interés</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/GrupoInteresController.php" method="POST" id="añadir">
                                <input type="hidden" name="action" value="crear">
                                <div class="row">

                                    <div class="col-md-6">
                                        
                                        <!-- Nivel -->
                                        <div class="añadir__grupo" id="grupo__nivel">
                                            <label for="nivel" class="form-label">Nivel *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-star'></i></span>
                                                <select class="form-control añadir__input" id="nivel" required onchange="filtrarPorNivel()">
                                                    <option value="">Seleccione un nivel</option>
                                                    <?php foreach ($niveles as $nivel): ?>
                                                        <option value="<?= $nivel['IdNivel'] ?>">
                                                            <?= htmlspecialchars($nivel['nivel']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Debe seleccionar un nivel.</p>
                                        </div>

                                        <!-- Tipo de Grupo (Depende de Nivel) -->
                                        <div class="añadir__grupo" id="grupo__IdTipo_Grupo">
                                            <label for="IdTipo_Grupo" class="form-label">Tipo de Grupo *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-category'></i></span>
                                                <select class="form-control añadir__input" name="IdTipo_Grupo" id="IdTipo_Grupo" required disabled>
                                                    <option value="">Seleccione primero un nivel</option>
                                                    <?php foreach ($tipos as $tipo): ?>
                                                        <option value="<?= $tipo['IdTipo_Grupo'] ?>" data-nivel="<?= $tipo['IdNivel'] ?>">
                                                            <?= htmlspecialchars($tipo['nombre_grupo']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Seleccione un tipo de grupo.</p>
                                        </div>

                                    </div>
                                    <div class="col-md-6">

                                        <!-- Curso (Depende de Nivel) -->
                                        <div class="añadir__grupo" id="grupo__IdCurso">
                                            <label for="IdCurso" class="form-label">Curso *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-building'></i></span>
                                                <select class="form-control añadir__input" name="IdCurso" id="IdCurso" required disabled>
                                                    <option value="">Seleccione primero un nivel</option>
                                                    <?php foreach ($cursos as $curso): ?>
                                                        <option value="<?= $curso['IdCurso'] ?>" data-nivel="<?= $curso['IdNivel'] ?>">
                                                            <?= htmlspecialchars($curso['curso']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Seleccione un curso.</p>
                                        </div>

                                        <!-- Profesor -->
                                        <div class="añadir__grupo" id="grupo__IdProfesor">
                                            <label for="IdProfesor" class="form-label">Profesor Responsable *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-id-card'></i></span>
                                                <select class="form-control añadir__input" name="IdProfesor" id="IdProfesor" required>
                                                    <option value="">Seleccione un profesor</option>
                                                    <?php foreach ($profesores as $prof): ?>
                                                        <option value="<?= $prof['IdPersona'] ?>">
                                                            <?= htmlspecialchars($prof['nombre'] . ' ' . $prof['apellido']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Seleccione un profesor.</p>
                                        </div>
                                    </div>

                                </div>

                                <!-- Botones para Volver y Guardar -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="grupo_interes.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Guardar Grupo
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

<script>
function filtrarPorNivel() {
    const nivelId = document.getElementById('nivel').value;
    const selectTipo = document.getElementById('IdTipo_Grupo');
    const selectCurso = document.getElementById('IdCurso');
    
    // Reset selections
    selectTipo.value = "";
    selectCurso.value = "";
    
    if (!nivelId) {
        selectTipo.disabled = true;
        selectCurso.disabled = true;
        return;
    }
    
    selectTipo.disabled = false;
    selectCurso.disabled = false;
    
    // Filter Tipo
    Array.from(selectTipo.options).forEach(opt => {
        if (opt.value === "") return;
        const optNivel = opt.getAttribute('data-nivel');
        if (optNivel === nivelId) {
            opt.hidden = false;
        } else {
            opt.hidden = true;
        }
    });
    
    // Filter Curso
    Array.from(selectCurso.options).forEach(opt => {
         if (opt.value === "") return;
        const optNivel = opt.getAttribute('data-nivel');
        if (optNivel === nivelId) {
            opt.hidden = false;
        } else {
            opt.hidden = true;
        }
    });
}
</script>

<!-- Validaciones básicas (se puede reutilizar validacion.js si aplica los mismos IDs o clases) -->
<!-- <script src="../../../assets/js/validacion.js"></script> -->
<!-- <script src="../../../assets/js/formulario.js"></script> -->

</body>
</html>