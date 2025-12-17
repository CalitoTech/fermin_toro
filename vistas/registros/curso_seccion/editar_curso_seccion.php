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

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

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

                            <form action="../../../controladores/CursoSeccionController.php" method="POST" id="form-curso-seccion">
                                <input type="hidden" name="action" value="editar">
                                <input type="hidden" name="id" value="<?= $idCursoSeccion ?>">
                                <input type="hidden" name="reorganizar" id="input-reorganizar" value="false">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nivel</label>
                                        <!-- Mostramos el nivel pero enviamos su ID si fuera necesario (aunque ya no se edita aquí, es informativo) -->
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($niveles[array_search($curso_seccion['IdNivel'], array_column($niveles, 'IdNivel'))]['nivel'] ?? 'Desconocido') ?>" disabled>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Curso</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($curso['curso']) ?>" disabled>
                                        <input type="hidden" name="curso" value="<?= $curso_seccion['IdCurso'] ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Sección</label>
                                         <!-- Se muestra la sección actual, no editable para mantener consistencia con el curso -->
                                        <input type="text" class="form-control" id="input-nombre-seccion" value="<?= htmlspecialchars($curso_seccion['seccion'] ?? '') ?>" disabled>
                                        <input type="hidden" name="seccion" value="<?= $curso_seccion['IdSeccion'] ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="cantidad_estudiantes" class="form-label">Cantidad de Estudiantes (Informativo)</label>
                                        <input type="number" class="form-control" name="cantidad_estudiantes" id="cantidad_estudiantes" 
                                               value="<?= $curso_seccion['cantidad_estudiantes'] ?? 0 ?>" disabled>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <!-- Aula -->
                                        <div class="añadir__grupo" id="grupo__aula">
                                            <label for="aula" class="form-label">Aula (Opcional)</label>
                                                <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-school'></i></span>
                                                <select class="form-select añadir__input" name="aula" id="aula">
                                                    <option value="">Sin Asignar</option>
                                                    <?php foreach ($aulas as $aula): ?>
                                                        <option value="<?= $aula['IdAula'] ?>" <?= $aula['IdAula'] == $curso_seccion['IdAula'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($aula['aula']) ?> (Cap: <?= $aula['capacidad'] ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <!-- Activo -->
                                        <div class="añadir__grupo mt-3" id="grupo__activo">
                                            <label class="form-label d-block">¿Activo?</label>
                                            <label class="toggle-label">
                                                <span class="toggle-text" id="toggle-text-activo"><?= ($curso_seccion['activo'] ?? 0) == 1 ? 'Sí' : 'No' ?></span>
                                                <div class="toggle-container">
                                                    <input 
                                                        type="checkbox" 
                                                        class="toggle-input" 
                                                        id="activo" 
                                                        name="activo" 
                                                        value="1" 
                                                        <?= ($curso_seccion['activo'] ?? 0) == 1 ? 'checked' : '' ?>
                                                        onchange="document.getElementById('toggle-text-activo').textContent = this.checked ? 'Sí' : 'No'">
                                                    <span class="toggle-slider"></span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <a href="curso_seccion.php" class="btn btn-secondary">Cancelar</a>
                                    <button type="submit" class="btn btn-danger">Actualizar</button>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('form-curso-seccion').addEventListener('submit', function(e) {
    if (document.getElementById('input-reorganizar').value === 'chequeado') return;

    // Solo chequeamos si se está desactivando
    const isChecked = document.getElementById('activo').checked;

    // Validar activación de Inscripción
    const nombreSeccion = document.getElementById('input-nombre-seccion').value;
    if (isChecked && nombreSeccion === 'Inscripción') {
         e.preventDefault();
         Swal.fire({
            title: 'Acción no permitida',
            text: "La sección 'Inscripción' está reservada para procesar solicitudes de nuevo ingreso (estudiantes sin asignación académica) y no debe activarse.",
            icon: 'warning',
            confirmButtonColor: '#d33'
        });
        return;
    }

    if (isChecked) {
        // Se está activando o manteniendo activo, no hay problema
        return;
    }

    e.preventDefault();
    const form = this;
    const id = form.querySelector('[name="id"]').value;

    fetch('../../../controladores/CursoSeccionController.php?action=verificar_impacto', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            Swal.fire('Error', data.error, 'error');
            // Restore previous state
            document.getElementById('activo').checked = true;
            document.getElementById('toggle-text-activo').textContent = 'Sí';
            return;
        }

        if (data.estudiantes > 0) {
            Swal.fire({
                title: '¡Atención!',
                html: `Esta sección tiene <b>${data.estudiantes} estudiantes inscritos</b> y estás a punto de desactivarla en el año escolar actual.<br><br>
                       ¿Deseas desactivarla y reorganizar a los estudiantes en el resto de secciones activas?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, reorganizar y desactivar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('input-reorganizar').value = 'true';
                    form.submit();
                } else {
                    // Cancelar: volvemos a marcar el checkbox como activo visualmente para que se entienda que no se guardó el cambio
                    document.getElementById('activo').checked = true;
                    document.getElementById('toggle-text-activo').textContent = 'Sí';
                }
            });
        } else {
            document.getElementById('input-reorganizar').value = 'chequeado';
            form.submit();
        }
    })
    .catch(error => {
        console.error(error);
        form.submit();
    });
});
</script>
</body>
</html>