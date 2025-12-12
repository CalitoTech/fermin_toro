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

// Manejo de alertas por GET (para redirigir limpiando la URL)
if (isset($_GET['deleted'])) {
    $_SESSION['alert'] = 'deleted';
    header("Location: grupo_interes.php");
    exit();
} elseif (isset($_GET['success'])) {
    $_SESSION['alert'] = 'success';
    header("Location: grupo_interes.php");
    exit();
} elseif (isset($_GET['actualizar'])) {
    $_SESSION['alert'] = 'actualizar';
    header("Location: grupo_interes.php");
    exit();
} elseif (isset($_GET['error'])) {
    $_SESSION['alert'] = 'error';
    header("Location: grupo_interes.php");
    exit();
}

// Obtener alerta
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

// Mostrar alerta si existe
if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito($_SESSION['message'] ?? "Operación exitosa.");
            break;
        case 'actualizar':
            $alerta = Notificaciones::exito("El grupo de interés se actualizó correctamente.");
            break;
        case 'deleted':
            $alerta = Notificaciones::exito("El grupo de interés se eliminó correctamente.");
            break;
        case 'dependency_error':
            $alerta = Notificaciones::advertencia("No se puede eliminar el grupo porque está siendo utilizado.");
            break;
        case 'error':
            $alerta = Notificaciones::advertencia($_SESSION['message'] ?? "Ocurrió un error.");
            break;
        default:
            $alerta = null;
    }
    unset($_SESSION['message']); // Limpiar mensaje después de usarlo

    if ($alerta) {
        Notificaciones::mostrar($alerta);
    }
}
?>

<head>
    <title>UECFT Araure - Grupo Interés</title>
</head>

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<?php
// Obtener grupo_interes
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/GrupoInteres.php';
require_once __DIR__ . '/../../../modelos/FechaEscolar.php';

$database = new Database();
$conexion = $database->getConnection();
$grupo_interesModel = new GrupoInteres($conexion);
$grupo_interes = $grupo_interesModel->obtenerGrupoInteres();

// Verificar estado de grupos para el año activo
$fechaEscolarModel = new FechaEscolar($conexion);
$fechaActiva = $fechaEscolarModel->obtenerActivo();

$mostrarBotonDuplicar = false;
$idAnioActivo = 0;
if ($fechaActiva) {
    $idAnioActivo = $fechaActiva['IdFecha_Escolar'];
    
    // Contar grupos del año activo
    $cantidadActivos = $grupo_interesModel->contarPorFechaEscolar($idAnioActivo);
    
    if ($cantidadActivos == 0) {
        // Verificar si hay grupos en el año anterior
        $idAnioAnterior = $idAnioActivo - 1;
        $cantidadAnteriores = $grupo_interesModel->contarPorFechaEscolar($idAnioAnterior);
        
        if ($cantidadAnteriores > 0) {
            $mostrarBotonDuplicar = true;
        }
    }
}
?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class='bx bxs-user-detail'></i> Gestión de Grupo Interés
                        </div>
                        <div class="card-body">
                            <!-- Botones de acción -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <div class="d-flex gap-2">
                                     <!-- Imprimir Lista -->
                                    <button class="btn btn-imprimir d-flex align-items-center" onclick="imprimirLista()">
                                        <i class='bx bxs-file-pdf me-1'></i> Imprimir Lista
                                    </button>
                                    
                                    <?php if ($mostrarBotonDuplicar): ?>
                                    <!-- Duplicar Año Anterior -->
                                    <button class="btn btn-warning text-white d-flex align-items-center" onclick="confirmarDuplicacion()">
                                        <i class='bx bx-copy me-1'></i> Duplicar Grupos Año Anterior
                                    </button>
                                    <?php endif; ?>
                                </div>
                               
                                <!-- Nuevo Grupo -->
                                <a href="nuevo_grupo_interes.php" class="btn btn-danger d-flex align-items-center">
                                    <i class='bx bx-plus-medical me-1'></i> Nuevo Grupo
                                </a>
                            </div>

                             <!-- Filtros -->
                            <div class="d-flex flex-wrap align-items-center mb-3 gap-3 p-3 bg-light rounded shadow-sm">
                                <span class="fw-bold text-secondary text-uppercase" style="font-size: 0.85rem;">
                                    <i class='bx bx-filter-alt'></i> Filtros:
                                </span>
                                
                                <div class="d-flex align-items-center gap-2">
                                    <label for="filtroFecha" class="mb-0 fw-semibold text-muted" style="font-size: 0.9rem;">Año Escolar:</label>
                                    <select id="filtroFecha" class="form-select form-select-sm border-secondary-subtle" style="width:160px;">
                                        <option value="">Todos</option>
                                    </select>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <label for="filtroCurso" class="mb-0 fw-semibold text-muted" style="font-size: 0.9rem;">Curso:</label>
                                    <select id="filtroCurso" class="form-select form-select-sm border-secondary-subtle" style="width:160px;">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Búsqueda y Entradas por página -->
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
                                <table class="table table-hover align-middle" id="tabla-grupo_interes">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Grupo</th>
                                            <th>Profesor</th>
                                            <th>Curso</th>
                                            <th>Año Escolar</th>
                                            <th class="text-center">Total Est.</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        <!-- Content filled by JS -->
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
    let allData = <?= json_encode($grupo_interes) ?>;
    let filteredData = [...allData];
    let currentPage = 1;
    let entriesPerPage = parseInt(document.getElementById('entries').value) || 10;

    // Inicialización de TablaDinamica
    document.addEventListener('DOMContentLoaded', function() {
        // Mapear datos para búsqueda fácil
         allData = allData.map(item => ({
            ...item,
            profesor: `${item.nombre} ${item.apellido}`,
            total_estudiantes: item.total_estudiantes || 0 // Asegurar que exista
        }));

        const config = {
            tablaId: 'tabla-grupo_interes',
            tbodyId: 'table-body',
            buscarId: 'buscar',
            entriesId: 'entries',
            paginationId: 'pagination',
            data: allData,
            idField: 'IdGrupo_Interes',
            columns: [
                { label: 'ID', key: 'IdGrupo_Interes' },
                { label: 'Grupo', key: 'nombre_grupo' },
                { label: 'Profesor', key: 'profesor' },
                { label: 'Curso', key: 'curso' },
                { label: 'Año Escolar', key: 'fecha_escolar' },
                { 
                    label: 'Total Est.', 
                    key: 'total_estudiantes',
                    render: (item) => `<div class="text-center"><span class="badge bg-secondary rounded-pill">${item.total_estudiantes}</span></div>`
                }
            ],
            acciones: [
                {
                    url: 'editar_grupo_interes.php?id={id}',
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

        window.tablaGrupoInteres = new TablaDinamica(config);

        // === POBLAR FILTROS ===
        const filtroFecha = document.getElementById('filtroFecha');
        const filtroCurso = document.getElementById('filtroCurso');

        // Valores Únicos para Fecha
        const fechasUnicas = [...new Set(allData.map(item => item.fecha_escolar).filter(Boolean))];
        fechasUnicas.sort().reverse().forEach(fecha => {
            const opt = document.createElement('option');
            opt.value = fecha;
            opt.textContent = fecha;
            filtroFecha.appendChild(opt);
        });

        // Valores Únicos para Curso
        const cursosUnicos = [...new Set(allData.map(item => item.curso).filter(Boolean))];
        cursosUnicos.sort().forEach(curso => {
            const opt = document.createElement('option');
            opt.value = curso;
            opt.textContent = curso;
            filtroCurso.appendChild(opt);
        });

        // === FUNCIÓN DE FILTROS ===
        function aplicarFiltros() {
            const fechaVal = filtroFecha.value;
            const cursoVal = filtroCurso.value;

            const filtered = allData.filter(item => {
                const matchFecha = !fechaVal || item.fecha_escolar === fechaVal;
                const matchCurso = !cursoVal || item.curso === cursoVal;
                return matchFecha && matchCurso;
            });

            window.tablaGrupoInteres.updateData(filtered);
        }

        filtroFecha.addEventListener('change', aplicarFiltros);
        filtroCurso.addEventListener('change', aplicarFiltros);

    });

    // === FUNCIONES ===
    function confirmDelete(id) {
        Swal.fire({
            title: "¿Está seguro que desea eliminar este grupo?",
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonText: "Sí, Eliminar",
            denyButtonText: "No, Volver"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../../controladores/GrupoInteresController.php?action=eliminar&id=' + id;
            }
        });
    }

    function confirmarDuplicacion() {
        Swal.fire({
            title: "¿Duplicar grupos del año anterior?",
            text: "Se copiarán todos los grupos del año escolar pasado al año escolar actual. Esta acción es útil para iniciar el nuevo año rápidamente.",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Sí, duplicar",
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#ffc107", // Warning color
            cancelButtonColor: "#6c757d"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../../controladores/GrupoInteresController.php?action=duplicar_anterior';
            }
        });
    }

    window.imprimirLista = function() {
        const nombreCompleto = "<?php 
            echo htmlspecialchars(
                $_SESSION['nombre_completo'] ?? 
                ($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '') ??
                $_SESSION['usuario'] ?? 
                'Sistema'
            );
        ?>";
        
        generarReporteImprimible(
            'REPORTE DE GRUPOS DE INTERÉS',
            '#tabla-grupo_interes',
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