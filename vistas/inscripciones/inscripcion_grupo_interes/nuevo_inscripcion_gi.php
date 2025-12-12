<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    header("Location: ../../login/login.php");
    exit();
}

require_once __DIR__ . '/../../../controladores/Notificaciones.php';
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/InscripcionGrupoInteres.php';
require_once __DIR__ . '/../../../modelos/GrupoInteres.php';
require_once __DIR__ . '/../../../modelos/FechaEscolar.php';

// Manejo de alertas de sesión
$alert = $_SESSION['alert'] ?? null;
$message = $_SESSION['message'] ?? '';
unset($_SESSION['alert']);
unset($_SESSION['message']);

if ($alert) {
    if ($alert === 'error') {
        $alerta = Notificaciones::error($message);
        Notificaciones::mostrar($alerta);
    }
}

$database = new Database();
$db = $database->getConnection();

// Obtener Año Escolar Activo
$fechaModel = new FechaEscolar($db);
$fechaActiva = $fechaModel->obtenerActivo();
$idFechaEscolar = $fechaActiva ? $fechaActiva['IdFecha_Escolar'] : 0;

// Obtener Grupos de Interés (Activos del año escolar actual)
$grupos = [];
if ($idFechaEscolar) {
    $grupoModel = new GrupoInteres($db);
    $grupos = $grupoModel->obtenerPorFechaEscolar($idFechaEscolar);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>UECFT Araure - Nueva Inscripción a Grupo</title>
   <!-- Estilos personalizados -->
    <style>
        .card-header {
            background: linear-gradient(90deg, #c90000, #8b0000);
            color: #fff;
        }
        .input-group-text {
            background-color: #c90000;
            color: #fff;
        }
        .btn-danger {
            background-color: #c90000;
            border: none;
        }
        .btn-outline-danger:hover {
            background-color: #c90000;
            color: #fff;
        }
    </style>
     <link href="../../../assets/css/solicitud_cupo.css" rel="stylesheet" />
</head>
<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header text-center">
                            <h4 class="mb-0"><i class='bx bx-plus-medical'></i> Nueva Inscripción - Grupo de Interés</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/InscripcionGrupoInteresController.php" method="POST" id="formInscripcion">
                                <input type="hidden" name="action" value="crear">
                                <input type="hidden" name="IdEstudiante" id="IdEstudiante">

                                <!-- Estudiante -->
                                <div class="mb-4 position-relative">
                                    <label for="buscadorEstudiante" class="form-label">Estudiante *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class='bx bxs-user'></i></span>
                                        <input type="text" class="form-control" id="buscadorEstudiante"
                                               placeholder="Buscar por nombre o cédula..." autocomplete="off" required>
                                    </div>
                                    <div id="resultadosBusqueda" class="autocomplete-results d-none"></div>
                                    <small class="text-muted">Busque y seleccione un estudiante activo sin grupo.</small>
                                </div>

                                <!-- Grupo de Interés -->
                                <div class="mb-4">
                                    <label for="IdGrupo_Interes" class="form-label">Grupo de Interés *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class='bx bxs-group'></i></span>
                                        <select class="form-select" name="IdGrupo_Interes" id="IdGrupo_Interes" required disabled>
                                            <option value="">Seleccione primero un estudiante...</option>
                                            <?php foreach ($grupos as $grupo): ?>
                                                <option value="<?= $grupo['IdGrupo_Interes'] ?>" data-curso="<?= $grupo['IdCurso'] ?>">
                                                    <?= htmlspecialchars($grupo['nombre_grupo'] . ' - ' . $grupo['curso']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <small class="text-muted" id="grupoHelp">Seleccione un estudiante para ver grupos disponibles para su curso.</small>
                                </div>

                                <!-- Botones -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="inscripcion_grupo_interes.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Inscribir
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

<!-- Scripts -->
<script src="../../../assets/js/buscador_generico.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. Inicializar Buscador Genérico
        const buscador = new BuscadorGenerico(
            'buscadorEstudiante',
            'resultadosBusqueda',
            'estudiante_activo', // Nuevo tipo en BuscarGeneral.php
            'IdEstudiante',
            null,
            { showOnFocus: true }
        );

        // 2. Escuchar evento de selección del buscador
        const inputBuscador = document.getElementById('buscadorEstudiante');
        inputBuscador.addEventListener('itemSeleccionado', function(e) {
            if (e.detail) {
                const idCursoEstudiante = e.detail.IdCurso;
                filtrarGruposPorCurso(idCursoEstudiante);
            }
        });
        
        // 3. Función para filtrar grupos (Lógica Invertida: Mostrar SOLO si coincide el curso)
        function filtrarGruposPorCurso(idCursoEstudiante) {
            const groupSelect = document.getElementById('IdGrupo_Interes');
            const grupoHelp = document.getElementById('grupoHelp');
            
            // Habilitar select
            groupSelect.disabled = false;
            
            // Loop de opciones
            let countVisible = 0;
            Array.from(groupSelect.options).forEach(opt => {
                if (opt.value === "") return; // Skip placeholder
                
                const groupCourseId = opt.getAttribute('data-curso');
                
                // REGLA: Mostrar SOLO si IdCurso Grupo == IdCurso Estudiante
                if (groupCourseId == idCursoEstudiante) {
                    opt.hidden = false;
                    opt.disabled = false;
                    countVisible++;
                } else {
                    opt.hidden = true;
                    opt.disabled = true; // También deshabilitar por seguridad
                }
            });
            
            // Resetear selección
            groupSelect.value = "";
            
            if (countVisible > 0) {
                 grupoHelp.textContent = "Grupos disponibles para el curso del estudiante.";
                 grupoHelp.className = "text-muted";
            } else {
                 grupoHelp.textContent = "No hay grupos de interés disponibles para el curso de este estudiante.";
                 grupoHelp.className = "text-danger";
            }
        }

        // 4. Validación extra al enviar
        document.getElementById('formInscripcion').addEventListener('submit', function(e) {
            const idEstudiante = document.getElementById('IdEstudiante').value;
            if (!idEstudiante) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Faltan datos',
                    text: 'Debe seleccionar un estudiante válido.',
                    confirmButtonColor: '#c90000'
                });
            }
        });
    });
</script>

</body>
</html>
