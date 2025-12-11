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

// Incluir Notificaciones y conexión
require_once __DIR__ . '/../../../controladores/Notificaciones.php';
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Status.php';

$database = new Database();
$conexion = $database->getConnection();

// Manejo de alertas
if (isset($_GET['deleted'])) {
    $_SESSION['alert'] = 'deleted';
    header("Location: egreso.php");
    exit();
} elseif (isset($_GET['success'])) {
    $_SESSION['alert'] = 'success';
    header("Location: egreso.php");
    exit();
} elseif (isset($_GET['actualizar'])) {
    $_SESSION['alert'] = 'actualizar';
    header("Location: egreso.php");
    exit();
} elseif (isset($_GET['error'])) {
    $_SESSION['alert'] = 'error';
    header("Location: egreso.php");
    exit();
}

// Mostrar alerta
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito("El egreso se creó correctamente.");
            break;
        case 'actualizar':
            $alerta = Notificaciones::exito("El egreso se actualizó correctamente.");
            break;
        case 'deleted':
            $alerta = Notificaciones::exito("El egreso se eliminó correctamente.");
            break;
        case 'error':
            $alerta = Notificaciones::advertencia("Este egreso ya existe, verifique por favor.");
            break;
        default:
            $alerta = null;
    }
    if ($alerta) Notificaciones::mostrar($alerta);
}

// === CONSULTA DE EGRESOS CON TIPO DE PERSONA ===
$query = "SELECT
            egreso.IdEgreso,
            egreso.fecha_egreso,
            egreso.motivo,
            persona.IdPersona,
            persona.nombre,
            persona.apellido,
            persona.cedula,
            nacionalidad.nacionalidad,
            sexo.sexo,
            status.status,
            egreso.IdStatus,
            detalle_perfil.IdPerfil,
            perfil.nombre_perfil
          FROM egreso
          INNER JOIN persona ON egreso.IdPersona = persona.IdPersona
          LEFT JOIN nacionalidad ON persona.IdNacionalidad = nacionalidad.IdNacionalidad
          LEFT JOIN sexo ON persona.IdSexo = sexo.IdSexo
          INNER JOIN status ON egreso.IdStatus = status.IdStatus
          LEFT JOIN detalle_perfil ON persona.IdPersona = detalle_perfil.IdPersona
          LEFT JOIN perfil ON detalle_perfil.IdPerfil = perfil.IdPerfil
          ORDER BY egreso.fecha_egreso DESC";
$stmt = $conexion->prepare($query);
$stmt->execute();
$egresos = $stmt->fetchAll(PDO::FETCH_ASSOC);


$statusModel = new Status($conexion);
$statuses = $statusModel->obtenerStatusEgreso();
?>

<head>
    <title>UECFT Araure - Egresos</title>
    <style>
    </style>
</head>

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class='bx bxs-user-x'></i> Gestión de Egresos
                        </div>
                        <div class="card-body">

                            <!-- 🔹 Línea superior: Imprimir | Nuevo egreso -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <!-- Botón Imprimir -->
                                <div class="mb-2">
                                    <button class="btn btn-imprimir d-flex align-items-center" id="btnImprimir">
                                        <i class='bx bxs-file-pdf me-1'></i> Imprimir Lista
                                    </button>
                                </div>

                                <!-- Botón Nuevo Egreso -->
                                <div class="mb-2">
                                    <a href="nuevo_egreso.php" id="btnNuevoRegistro" class="btn btn-danger d-flex align-items-center">
                                        <i class='bx bx-plus-medical me-1'></i> Nuevo Egreso
                                    </a>
                                </div>
                            </div>

                            <!-- 🔹 Línea inferior: Buscador y entradas -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                <!-- Buscador -->
                                <div class="flex-grow-1" style="max-width: 250px;">
                                    <input type="text" class="search-input" id="buscar" placeholder="Buscar...">
                                </div>

                                <!-- Filtro de Tipo -->
                                <div class="d-flex align-items-center gap-2">
                                    <label for="filtroTipo" class="fw-semibold mb-0">Tipo:</label>
                                    <select id="filtroTipo" class="form-select" style="width:auto;">
                                        <option value="">Todos</option>
                                        <option value="Estudiante">Estudiantes</option>
                                        <option value="Docente">Docentes</option>
                                        <option value="Administrativo">Administrativos</option>
                                    </select>
                                </div>

                                <!-- Filtro de Status -->
                                <div class="d-flex align-items-center gap-2">
                                    <label for="filtroStatus" class="fw-semibold mb-0">Status:</label>
                                    <select id="filtroStatus" class="form-select" style="width:auto;">
                                        <option value="">Todos</option>
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= $status['status']; ?>"><?= htmlspecialchars($status['status']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Entradas -->
                                <div class="d-flex align-items-center">
                                    <label for="entries" class="me-2">Entradas:</label>
                                    <select id="entries" class="form-select" style="width:auto;">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                            </div>

                            <!-- 🔹 Tabla -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="tabla-egresos">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Cédula</th>
                                            <th>Tipo</th>
                                            <th>Fecha Egreso</th>
                                            <th>Status</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body"></tbody>
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
let allData = <?= json_encode($egresos) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const config = {
        tablaId: 'tabla-egresos',
        tbodyId: 'table-body',
        buscarId: 'buscar',
        entriesId: 'entries',
        paginationId: 'pagination',
        data: allData,
        idField: 'IdEgreso',
        columns: [
            { label: 'ID', key: 'IdEgreso' },
            { label: 'Nombre', key: 'nombreCompleto' },
            { label: 'Cédula', key: 'cedulaCompleta' },
            { label: 'Tipo', key: 'tipoBadge' },
            { label: 'Fecha Egreso', key: 'fecha_egreso' },
            { label: 'Status', key: 'status' }
        ],
        acciones: [
            { url: 'ver_egreso.php?id={id}', class: 'btn-outline-info', icon: '<i class="bx bxs-show"></i>' },
            { url: 'editar_egreso.php?id={id}', class: 'btn-outline-primary', icon: '<i class="bx bxs-edit"></i>' }
        ]
    };

    // === Añadimos campos auxiliares ===
    config.data = allData.map(item => {
        // Determinar tipo de persona basado en IdPerfil
        let tipo = '';
        let tipoBadgeClass = 'secondary';

        if (item.IdPerfil == 3) {
            // Si IdPerfil es 3, es Estudiante
            tipo = 'Estudiante';
            tipoBadgeClass = 'primary';
        } else if (item.nombre_perfil) {
            // Sino, mostrar el perfil que tiene
            tipo = item.nombre_perfil;

            // Asignar color según el perfil
            if (item.nombre_perfil.includes('Docente')) {
                tipoBadgeClass = 'success';
            } else if (item.nombre_perfil.includes('Administrador') || item.nombre_perfil.includes('Director')) {
                tipoBadgeClass = 'info';
            }
        }

        return {
            ...item,
            nombreCompleto: `${item.nombre} ${item.apellido}`,
            cedulaCompleta: `${item.nacionalidad || ''}-${item.cedula || ''}`,
            motivoCorto: item.motivo ? (item.motivo.length > 50 ? item.motivo.substring(0, 50) + '...' : item.motivo) : 'Sin especificar',
            tipo: tipo,
            tipoBadge: `<span class="badge bg-${tipoBadgeClass}">${tipo}</span>`
        };
    });

    window.tablaEgresos = new TablaDinamica(config);

    // === FILTROS ===
    const filtroTipo = document.getElementById('filtroTipo');
    const filtroStatus = document.getElementById('filtroStatus');
    const filtroBuscar = document.getElementById('buscar');
    const filtroEntries = document.getElementById('entries');

    // === FUNCIÓN GENERAL DE FILTROS ===
    function aplicarFiltros() {
        const tipoVal = filtroTipo ? filtroTipo.value.trim() : '';
        const statusVal = filtroStatus ? filtroStatus.value.trim() : '';
        const textoBuscar = filtroBuscar ? filtroBuscar.value.trim().toLowerCase() : '';

        const filtered = config.data.filter(item => {
            // Filtro por tipo
            let matchTipo = true;
            if (tipoVal) {
                matchTipo = (item.tipo === tipoVal);
            }

            // Filtro por status
            let matchStatus = true;
            if (statusVal && statusVal.toLowerCase() !== 'todos') {
                matchStatus = (item.status && item.status.toLowerCase() === statusVal.toLowerCase());
            }

            // Filtro por búsqueda
            let matchBuscar = true;
            if (textoBuscar) {
                const combo = `${item.IdEgreso} ${item.nombre} ${item.apellido} ${item.cedula} ${item.motivo || ''}`.toLowerCase();
                matchBuscar = combo.includes(textoBuscar);
            }

            return matchTipo && matchStatus && matchBuscar;
        });

        window.tablaEgresos.updateData(filtered);
    }

    // === LISTENERS DE FILTROS ===
    [filtroTipo, filtroStatus, filtroBuscar, filtroEntries].forEach(el => {
        if (!el) return;
        const ev = (el === filtroBuscar) ? 'input' : 'change';
        el.addEventListener(ev, aplicarFiltros);
    });

    // Ejecutar una vez al cargar
    aplicarFiltros();
});

// === IMPRIMIR LISTA ===
window.imprimirLista = function() {
    const nombreCompleto = "<?php
        echo htmlspecialchars($_SESSION['nombre_completo'] ?? ($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '') ?? 'Sistema');
    ?>";
    generarReporteImprimible(
        'REPORTE DE EGRESOS DEL SISTEMA',
        '#tabla-egresos',
        { logoUrl: '../../../assets/images/fermin.png', colorPrincipal: '#c90000', inscripcion: nombreCompleto }
    );
};

document.getElementById('btnImprimir').addEventListener('click', window.imprimirLista);

// === FUNCIÓN PARA ELIMINAR EGRESO ===
window.eliminarEgreso = function(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: "Esta acción eliminará el registro de egreso. ¿Desea continuar?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#c90000',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `../../../controladores/EgresoController.php?action=eliminar&id=${id}`;
        }
    });
};
</script>

</body>
</html>
