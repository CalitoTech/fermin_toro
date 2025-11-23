<?php
session_start();

// === Verificación de sesión ===
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

// === Incluir dependencias ===
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../controladores/Notificaciones.php';
require_once __DIR__ . '/../../../modelos/Curso.php';

// === Manejo de alertas por GET ===
if (isset($_GET['deleted'])) {
    $_SESSION['alert'] = 'deleted';
    header("Location: curso.php");
    exit();
} elseif (isset($_GET['success'])) {
    $_SESSION['alert'] = 'success';
    header("Location: curso.php");
    exit();
} elseif (isset($_GET['actualizar'])) {
    $_SESSION['alert'] = 'actualizar';
    header("Location: curso.php");
    exit();
} elseif (isset($_GET['error'])) {
    $_SESSION['alert'] = 'error';
    header("Location: curso.php");
    exit();
}

// === Obtener alerta ===
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

// === Mostrar alerta si existe ===
if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito("El curso se creó correctamente.");
            break;
        case 'actualizar':
            $alerta = Notificaciones::exito("El curso se actualizó correctamente.");
            break;
        case 'deleted':
            $alerta = Notificaciones::exito("El curso se eliminó correctamente.");
            break;
        case 'dependency_error':
            $alerta = Notificaciones::advertencia("No se puede eliminar el curso porque está siendo utilizado por una o más personas.");
            break;
        case 'error':
            $alerta = Notificaciones::advertencia("Este curso ya existe, verifique por favor.");
            break;
        default:
            $alerta = null;
    }

    if ($alerta) {
        Notificaciones::mostrar($alerta);
    }
}

// === Conexión y obtención de cursos ===
$database = new Database();
$conexion = $database->getConnection();

$cursoModel = new Curso($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>UECFT Araure - Cursos</title>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>
<?php
// Obtener cursos según permisos del usuario
$cursos = $cursoModel->obtenerCursos($idPersona);
?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class='bx bxs-user-detail'></i> Gestión de Cursos
                        </div>
                        <div class="card-body">
                            <!-- Botones de acción -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <button class="btn btn-imprimir d-flex align-items-center" onclick="imprimirLista()">
                                    <i class='bx bxs-file-pdf me-1'></i> Imprimir Lista
                                </button>
                                <a href="nuevo_curso.php" class="btn btn-danger d-flex align-items-center">
                                    <i class='bx bx-plus-medical me-1'></i> Nuevo Curso
                                </a>
                            </div>

                            <!-- Filtros -->
                            <div class="d-flex flex-wrap align-items-center mb-3 gap-2">
                                <label for="filtroNivel" class="fw-semibold mb-0">Nivel:</label>
                                <select id="filtroNivel" class="form-select" style="width:auto;">
                                    <option value="">Todos</option>
                                </select>
                            </div>

                            <!-- Buscador y Entradas -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                <div class="flex-grow-1" style="max-width: 300px;">
                                    <input type="text" class="search-input" id="buscar" placeholder="Buscar...">
                                </div>
                                <div class="d-flex align-items-center">
                                    <label for="entries" class="me-2">Entradas por página:</label>
                                    <select id="entries" class="form-select" style="width: auto;">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Tabla -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="tabla-cursos">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nivel</th>
                                            <th>Curso</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        <?php foreach ($cursos as $curso): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($curso['IdCurso']) ?></td>
                                                <td><?= htmlspecialchars($curso['nombre_nivel']) ?></td>
                                                <td><?= htmlspecialchars($curso['curso']) ?></td>
                                                <td>
                                                    <a href="editar_curso.php?id=<?= $curso['IdCurso'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                        <i class='bx bxs-edit'></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $curso['IdCurso'] ?>)">
                                                        <i class='bx bxs-trash'></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <div class="d-flex justify-content-center mt-3">
                                <div class="pagination" id="pagination"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>

<script src="../../../assets/js/tablas.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../../assets/js/reportes.js"></script>
<script>
    // === DATOS GLOBALES ===
    let allData = <?= json_encode($cursos) ?>;
    let filteredData = [...allData];
    let currentPage = 1;
    let entriesPerPage = parseInt(document.getElementById('entries').value) || 10;

    // Inicializar tabla dinámica
    document.addEventListener('DOMContentLoaded', function() {
        const config = {
            tablaId: 'tabla-cursos',
            tbodyId: 'table-body',
            buscarId: 'buscar',
            entriesId: 'entries',
            paginationId: 'pagination',
            data: allData,
            idField: 'IdCurso',
            columns: [
                { label: 'ID', key: 'IdCurso' },
                { label: 'Nivel', key: 'nombre_nivel' },
                { label: 'Curso', key: 'curso' }
            ],
            acciones: [
                {
                    url: 'editar_curso.php?id={id}',
                    class: 'btn-outline-primary',
                    icon: '<i class="bx bxs-edit"></i>'
                },
                {
                    onClick: 'confirmDelete({id})',
                    class: 'btn-outline-danger',
                    icon: '<i class="bx bxs-trash"></i>'
                }
            ]
        };

        // Preparar datos para la tabla
        config.data = allData.map(item => ({
            ...item,
            nombreCompleto: `${item.nombre} ${item.apellido}`
        }));

        // Crear instancia de TablaDinamica
        window.tablaCursos = new TablaDinamica(config);

        // === POBLAR FILTROS ===
        const filtroNivel = document.getElementById('filtroNivel');

        // Obtener valores únicos
        const nivelesUnicos = [...new Set(allData.map(item => item.nombre_nivel).filter(Boolean))];

        // Poblar select de niveles
        nivelesUnicos.sort().forEach(nivel => {
            const opt = document.createElement('option');
            opt.value = nivel;
            opt.textContent = nivel;
            filtroNivel.appendChild(opt);
        });

        // === FUNCIÓN DE FILTROS ===
        function aplicarFiltros() {
            const nivelVal = filtroNivel.value.trim();

            const filtered = allData.filter(item => {
                let matchNivel = true;
                if (nivelVal) {
                    matchNivel = item.nombre_nivel === nivelVal;
                }
                return matchNivel;
            });

            window.tablaCursos.updateData(filtered);
        }

        // === LISTENERS DE FILTROS ===
        filtroNivel.addEventListener('change', aplicarFiltros);
    });

    // Confirmar eliminación
    function confirmDelete(id) {
        Swal.fire({
            title: "¿Está seguro que desea eliminar este curso?",
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonText: "Sí, Eliminar",
            denyButtonText: "No, Volver"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../../controladores/CursoController.php?action=eliminar&id=' + id;
            } else if (result.isDenied) {
                Swal.fire("No se eliminó el curso", "", "info");
            }
        });
    }

    // Imprimir reporte
    window.imprimirLista = function() {
        const nombreCompleto = "<?= htmlspecialchars($_SESSION['nombre_completo'] ?? ($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '') ?? $_SESSION['usuario'] ?? 'Sistema') ?>";
        generarReporteImprimible(
            'REPORTE DE CURSOS DEL SISTEMA',
            '#tabla-cursos',
            {
                logoUrl: '../../../assets/images/fermin.png',
                colorPrincipal: '#c90000',
                usuario: nombreCompleto
            }
        );
    };
</script>
</body>
</html>