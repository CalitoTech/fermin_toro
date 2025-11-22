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
    header("Location: requisito.php");
    exit();
} elseif (isset($_GET['success'])) {
    $_SESSION['alert'] = 'success';
    header("Location: requisito.php");
    exit();
} elseif (isset($_GET['actualizar'])) {
    $_SESSION['alert'] = 'actualizar';
    header("Location: requisito.php");
    exit();
} elseif (isset($_GET['error'])) {
    $_SESSION['alert'] = 'error';
    header("Location: requisito.php");
    exit();
}

// Obtener alerta
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

// Mostrar alerta si existe
if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito("El requisito se creó correctamente.");
            break;
        case 'actualizar':
            $alerta = Notificaciones::exito("El requisito se actualizó correctamente.");
            break;
        case 'deleted':
            $alerta = Notificaciones::exito("El requisito se eliminó correctamente.");
            break;
        case 'dependency_error':
            $alerta = Notificaciones::advertencia("No se puede eliminar el requisito porque está siendo utilizado por una o más personas.");
            break;
        case 'error':
            $alerta = Notificaciones::advertencia("Este requisito ya existe, verifique por favor.");
            break;
        default:
            $alerta = null;
    }

    if ($alerta) {
        Notificaciones::mostrar($alerta);
    }
}
?>

<head>
    <title>UECFT Araure - Requisitos</title>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<?php
// Obtener requisitos según permisos del usuario
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Requisito.php';

$database = new Database();
$conexion = $database->getConnection();
$requisitoModel = new Requisito($conexion);
$requisitos = $requisitoModel->obtenerRequisitos($idPersona);

// Obtener niveles para el selector (también filtrados por permisos)
require_once __DIR__ . '/../../../modelos/Nivel.php';
$nivelModel = new Nivel($conexion);
$niveles = $nivelModel->obtenerTodos();

// Cargar tipos de requisito para el filtro
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
?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class='bx bxs-user-detail'></i> Gestión de Requisitos
                        </div>
                        <div class="card-body">
                            <!-- Botones de acción (intercambiados) -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <!-- Botón y selector para generar reporte por nivel -->
                                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <label for="nivelReporte" class="mb-0">Seleccionar Nivel:</label>
                                        <select id="nivelReporte" class="form-select" style="width: auto;">
                                            <option value="">Todos los niveles</option>
                                            <?php foreach ($niveles as $n): ?>
                                                <option value="<?= $n['IdNivel'] ?>"><?= htmlspecialchars($n['nivel']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <a href="#" id="btnReporteNivel" class="btn btn-danger d-flex align-items-center">
                                        <i class='bx bxs-file-pdf me-1'></i> Generar Reporte PDF
                                    </a>
                                </div>

                                <!-- Nuevo Requisito a la derecha -->
                                <a href="nuevo_requisito.php" class="btn btn-danger d-flex align-items-center">
                                    <i class='bx bx-plus-medical me-1'></i> Nuevo Requisito
                                </a>
                            </div>

                            <!-- Filtros -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="filtroNivel" class="form-label">Filtrar por Nivel:</label>
                                    <select id="filtroNivel" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="General">General (aplica a todos)</option>
                                        <?php foreach ($niveles as $n): ?>
                                            <option value="<?= htmlspecialchars($n['nivel']) ?>"><?= htmlspecialchars($n['nivel']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filtroTipoRequisito" class="form-label">Filtrar por Tipo:</label>
                                    <select id="filtroTipoRequisito" class="form-select">
                                        <option value="">Todos</option>
                                        <?php foreach ($tiposRequisito as $tr): ?>
                                            <option value="<?= htmlspecialchars($tr['tipo_requisito']) ?>"><?= htmlspecialchars($tr['tipo_requisito']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filtroObligatorio" class="form-label">Filtrar por Obligatorio:</label>
                                    <select id="filtroObligatorio" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="Sí">Sí</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filtroPlantelPrivado" class="form-label">Solo Plantel Privado:</label>
                                    <select id="filtroPlantelPrivado" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="Sí">Sí</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Búsqueda y Entradas por página en la misma línea -->
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
                                <table class="table table-hover align-middle" id="tabla-requisitos">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Tipo</th>
                                            <th>Nivel</th>
                                            <th>Requisito</th>
                                            <th>Tipo Trabajador</th>
                                            <th>¿Obligatorio?</th>
                                            <th>¿Solo Privado?</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        <?php foreach ($requisitos as $req): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($req['IdRequisito']) ?></td>
                                                <td><?= htmlspecialchars($req['tipo_requisito']) ?></td>
                                                <td><?= htmlspecialchars($req['nivel']) ?></td>
                                                <td><?= htmlspecialchars($req['requisito']) ?></td>
                                                <td><?= htmlspecialchars($req['tipo_trabajador'] ?? 'Todos') ?></td>
                                                <td><?= $req['obligatorio'] == 1 ? 'Sí' : 'No' ?></td>
                                                <td><?= $req['solo_plantel_privado'] == 1 ? 'Sí' : 'No' ?></td>
                                                <td>
                                                    <a href="editar_requisito.php?id=<?= $req['IdRequisito'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                        <i class='bx bxs-edit'></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $req['IdRequisito'] ?>)">
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
    let allData = <?= json_encode($requisitos) ?>;
    let filteredData = [...allData];
    let currentPage = 1;
    let entriesPerPage = parseInt(document.getElementById('entries').value) || 10;

    allData = allData.map(item => ({
        ...item,
        // Convertir campos a texto
        obligatorio: item.obligatorio == 1 ? 'Sí' : 'No',
        solo_plantel_privado_texto: item.solo_plantel_privado == 1 ? 'Sí' : 'No',
        tipo_trabajador: item.tipo_trabajador || 'Todos'
    }));

    // Inicialización de TablaDinamica
    document.addEventListener('DOMContentLoaded', function() {
        const config = {
            tablaId: 'tabla-requisitos',
            tbodyId: 'table-body',
            buscarId: 'buscar',
            entriesId: 'entries',
            paginationId: 'pagination',
            data: allData,
            idField: 'IdRequisito',
            columns: [
                { label: 'ID', key: 'IdRequisito' },
                { label: 'Tipo', key: 'tipo_requisito' },
                { label: 'Nivel', key: 'nivel' },
                { label: 'Requisito', key: 'requisito' },
                { label: 'Tipo Trabajador', key: 'tipo_trabajador' },
                { label: '¿Obligatorio?', key: 'obligatorio' },
                { label: '¿Solo Privado?', key: 'solo_plantel_privado_texto' }
            ],
            acciones: [
                {
                    url: 'editar_requisito.php?id={id}',
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

        // Crear instancia de TablaDinamica
        window.tablaRequisitos = new TablaDinamica(config);

        // Agregar listeners a los filtros
        document.getElementById('filtroNivel').addEventListener('change', aplicarFiltros);
        document.getElementById('filtroTipoRequisito').addEventListener('change', aplicarFiltros);
        document.getElementById('filtroObligatorio').addEventListener('change', aplicarFiltros);
        document.getElementById('filtroPlantelPrivado').addEventListener('change', aplicarFiltros);
    });

    // Función para aplicar filtros
    function aplicarFiltros() {
        const filtroNivel = document.getElementById('filtroNivel').value;
        const filtroTipo = document.getElementById('filtroTipoRequisito').value;
        const filtroObligatorio = document.getElementById('filtroObligatorio').value;
        const filtroPlantelPrivado = document.getElementById('filtroPlantelPrivado').value;

        let datosFiltrados = allData.filter(item => {
            let cumpleNivel = true;
            let cumpleTipo = true;
            let cumpleObligatorio = true;
            let cumplePlantelPrivado = true;

            // Filtro por nivel
            if (filtroNivel) {
                if (filtroNivel === 'General') {
                    cumpleNivel = item.nivel === 'Todos los niveles';
                } else {
                    cumpleNivel = item.nivel === filtroNivel;
                }
            }

            // Filtro por tipo de requisito
            if (filtroTipo) {
                cumpleTipo = item.tipo_requisito === filtroTipo;
            }

            // Filtro por obligatorio
            if (filtroObligatorio) {
                cumpleObligatorio = item.obligatorio === filtroObligatorio;
            }

            // Filtro por plantel privado
            if (filtroPlantelPrivado) {
                cumplePlantelPrivado = item.solo_plantel_privado_texto === filtroPlantelPrivado;
            }

            return cumpleNivel && cumpleTipo && cumpleObligatorio && cumplePlantelPrivado;
        });

        // Actualizar la tabla con datos filtrados
        window.tablaRequisitos.actualizarDatos(datosFiltrados);
    }

    // === FUNCIONES ===
    function confirmDelete(id) {
        Swal.fire({
            title: "¿Está seguro que desea eliminar este requisito?",
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonText: "Sí, Eliminar",
            denyButtonText: "No, Volver"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../../controladores/RequisitoController.php?action=eliminar&id=' + id;
            } else if (result.isDenied) {
                Swal.fire("No se eliminó el requisito", "", "info");
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
            'REPORTE DE REQUISITOS DEL SISTEMA',
            '#tabla-requisitos',
            {
                logoUrl: '../../../assets/images/fermin.png',
                colorPrincipal: '#c90000',
                usuario: nombreCompleto
            }
        );
    };
    document.getElementById('btnReporteNivel').addEventListener('click', function() {
    const nivelId = document.getElementById('nivelReporte').value;
    if (!nivelId) {
        Swal.fire({
            title: "Nivel no seleccionado",
            text: "Por favor selecciona un nivel para generar el reporte.",
            icon: "warning",
            confirmButtonText: "Aceptar"
        });
        return;
    }
    // Abrir reporte en nueva ventana
    window.open('reporte_requisitos.php?nivel=' + nivelId, '_blank');
});
</script>
</body>
</html>