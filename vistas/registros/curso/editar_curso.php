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

// Obtener ID del curso a editar
$idCurso = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idCurso <= 0) {
    header("Location: curso.php");
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
require_once __DIR__ . '/../../../modelos/Curso.php';
require_once __DIR__ . '/../../../modelos/Nivel.php';

$database = new Database();
$conexion = $database->getConnection();

$cursoModel = new Curso($conexion);
$curso = $cursoModel->obtenerPorId($idCurso); // Cargar datos

if (!$curso) {
    header("Location: curso.php");
    exit();
}
?>

<head>
    <title>UECFT Araure - Editar Curso</title>
</head>

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<?php
// Cargar niveles con filtro por permisos
$nivelModel = new Nivel($conexion);
$niveles = $nivelModel->obtenerNiveles($idPersona);
?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-danger text-white text-center">
                            <h4 class="mb-0"><i class='bx bxs-user-edit'></i> Editar Curso</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/CursoController.php" method="POST" id="form-curso">
                                <input type="hidden" name="action" value="editar">
                                <input type="hidden" name="id" value="<?= $idCurso ?>">
                                <input type="hidden" name="reorganizar" id="input-reorganizar" value="false">
                                
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
                                                            <?= $nivel['IdNivel'] == $curso['IdNivel'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($nivel['nivel']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Debe seleccionar un nivel.</p>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <!-- Curso -->
                                        <div class="añadir__grupo" id="grupo__curso">
                                            <label for="curso" class="form-label">Curso *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-user'></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input" 
                                                    name="curso" 
                                                    id="texto" 
                                                    required 
                                                    maxlength="40"
                                                    value="<?= htmlspecialchars($curso['curso']) ?>">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">El curso debe tener entre 3 y 40 letras.</p>
                                        </div>

                                        <!-- Cantidad de Secciones -->
                                        <div class="añadir__grupo mt-3" id="grupo__cantidad_secciones">
                                            <label for="cantidad_secciones" class="form-label">Cantidad de Secciones *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bx-list-ol'></i></span>
                                                <input 
                                                    type="number" 
                                                    class="form-control añadir__input" 
                                                    name="cantidad_secciones" 
                                                    id="cantidad_secciones" 
                                                    required 
                                                    min="1" 
                                                    max="20"
                                                    value="<?= htmlspecialchars($curso['cantidad_secciones']) ?>">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Debe ser un número entre 1 y 20.</p>
                                        </div>
                                    </div>
                                </div>


                                <!-- Botones para Volver y Actualizar -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="curso.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver a Cursos
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Actualizar Curso
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('form-curso').addEventListener('submit', function(e) {
    // Si ya confirmamos la reorganización, o no requerimos chequeo, permitimos el submit normalmente
    if (document.getElementById('input-reorganizar').value === 'chequeado') return;

    e.preventDefault();
    const form = this;
    const idCurso = form.querySelector('[name="id"]').value;
    const nuevaCantidad = parseInt(document.getElementById('cantidad_secciones').value);

    // Verificar impacto
    fetch('../../../controladores/CursoController.php?action=verificar_impacto_reduccion', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            idCurso: idCurso,
            cantidadObjetivo: nuevaCantidad
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            Swal.fire('Error', data.error, 'error');
            return;
        }

        if (data.afectadas && data.afectadas.length > 0) {
            const seccionesStr = data.afectadas.map(s => `Sección ${s.seccion}`).join(', ');
            
            Swal.fire({
                title: '¡Atención!',
                html: `Estás a punto de desactivar las siguientes secciones que tienen <b>${data.total_estudiantes} estudiantes inscritos</b>:<br><br>
                       <b>${seccionesStr}</b><br><br>
                       ¿Deseas desactivarlas y reorganizar a los estudiantes en el resto de secciones activas?`,
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
                    // Si cancela, no hacemos nada (se mantiene en la página)
                }
            });
        } else {
            // Sin impacto, marcamos como chequeado y enviamos
            document.getElementById('input-reorganizar').value = 'chequeado';
            form.submit();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // En caso de error de red, preguntamos si continuar igual
        Swal.fire({
             title: 'Error de verificación',
             text: 'No se pudo verificar el impacto de la reducción. ¿Desea continuar de todos modos?',
             icon: 'error',
             showCancelButton: true,
             confirmButtonText: 'Sí, continuar'
        }).then((result) => {
             if (result.isConfirmed) {
                 document.getElementById('input-reorganizar').value = 'chequeado';
                 form.submit();
             }
        });
    });
});
</script>
</body>
</html>