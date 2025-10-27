<?php
session_start();

require_once __DIR__ . '/../../../controladores/Notificaciones.php';
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Representante.php';
require_once __DIR__ . '/../../../modelos/Parentesco.php';

$database = new Database();
$conexion = $database->getConnection();

// === VERIFICACIÓN DE SESIÓN ===
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

// === ALERTAS ===
if (isset($_GET['deleted'])) $_SESSION['alert'] = 'deleted';
elseif (isset($_GET['success'])) $_SESSION['alert'] = 'success';
elseif (isset($_GET['actualizar'])) $_SESSION['alert'] = 'actualizar';
elseif (isset($_GET['error'])) $_SESSION['alert'] = 'error';

$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

if ($alert) {
    switch ($alert) {
        case 'success': $alerta = Notificaciones::exito("El representante se creó correctamente."); break;
        case 'actualizar': $alerta = Notificaciones::exito("El representante se actualizó correctamente."); break;
        case 'deleted': $alerta = Notificaciones::exito("El representante se eliminó correctamente."); break;
        case 'error': $alerta = Notificaciones::advertencia("Este representante ya existe, verifique por favor."); break;
        default: $alerta = null;
    }
    if ($alerta) Notificaciones::mostrar($alerta);
}

$representanteModel = new Representante($conexion);
$representantes = $representanteModel->obtenerTodos();
$parentescoModel = new Parentesco($conexion);
$parentescos = $parentescoModel->obtenerTodos();
?>

<head>
    <title>UECFT Araure - Representantes</title>
    <style>
        @media(min-width: 768px) {
            .filters-row { flex-wrap: nowrap; }
        }
        /* Si quieres ocultar el checkbox con CSS pequeño */
        .chk-disabled { pointer-events: none; }
    </style>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class='bx bxs-user-detail'></i> Gestión de Representantes
                        </div>
                        <div class="card-body">

                            <!-- BOTONES SUPERIORES -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <button class="btn btn-imprimir d-flex align-items-center" onclick="imprimirLista()">
                                    <i class='bx bxs-file-pdf me-1'></i> Imprimir Lista
                                </button>
                            </div>

                            <!-- FILTROS: Buscador | Parentesco (nuevo) | Entradas -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2 filters-row">
                                <!-- Buscador -->
                                <div class="flex-grow-1" style="max-width: 300px;">
                                    <input type="text" class="search-input" id="buscar" placeholder="Buscar...">
                                </div>

                                <!-- Filtro parentesco -->
                                <div class="d-flex align-items-center gap-2">
                                    <label for="filtro-parentesco" class="fw-semibold mb-0">Parentesco:</label>
                                    <select id="filtro-parentesco" class="form-select" style="width:auto;">
                                        <option value="">Todos</option>
                                        <?php foreach ($parentescos as $p): ?>
                                            <option value="<?= $p['IdParentesco'] ?>"><?= htmlspecialchars($p['parentesco']) ?></option>
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

                            <!-- TABLA -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="tabla-representantes">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Representante</th>
                                            <th>Cédula</th>
                                            <th>Sexo</th>
                                            <th>Parentesco</th>
                                            <th style="width: 80px;">Estudiantes a Cargo</th>
                                            <th style="width: 80px;">¿Contacto Emergencia?</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        <!-- Se renderiza por JS -->
                                    </tbody>
                                </table>
                            </div>

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
<script src="../../../assets/js/reportes.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const allData = <?= json_encode($representantes, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;

    // === CONFIGURACIÓN DE TABLA DINÁMICA ===
    const config = {
        tablaId: 'tabla-representantes',
        tbodyId: 'table-body',
        buscarId: 'buscar',
        entriesId: 'entries',
        paginationId: 'pagination',
        data: allData.map(item => ({
            ...item,
            nombreCompleto: `${item.nombre} ${item.apellido}`,
            cedulaCompleta: `${item.nacionalidad ? item.nacionalidad + ' ' : ''}${item.cedula || ''}`,
            cantidad_estudiantes: parseInt(item.cantidad_estudiantes || 0, 10),
            contacto_emergencia: item.contacto_emergencia == 1 ? 1 : 0,
            contacto_html: (item.contacto_emergencia == 1)
                ? '<input type="checkbox" checked disabled class="chk-disabled">'
                : '<input type="checkbox" disabled class="chk-disabled">',
            IdParentesco: item.IdParentesco ? item.IdParentesco.toString() : ''
        })),
        idField: 'IdPersona',
        columns: [
            { label: 'Representante', key: 'nombreCompleto' },
            { label: 'Cédula', key: 'cedulaCompleta' },
            { label: 'Sexo', key: 'sexo' },
            { label: 'Parentesco', key: 'parentesco' },
            { label: 'Estudiantes a Cargo', key: 'cantidad_estudiantes' },
            { label: 'Contacto Emergencia', key: 'contacto_html' }
        ],
        acciones: [
            { url: 'editar_representante.php?id={id}', class: 'btn-outline-primary', icon: '<i class="bx bxs-edit"></i>' },
            { url: 'ver_representante.php?id={id}', class: 'btn-outline-info', icon: '<i class="bx bxs-show"></i>' }
        ]
    };

    // === CREAR TABLA DINÁMICA ===
    window.tablaRepresentantes = new TablaDinamica(config);

    // === REFERENCIAS ===
    const filtroParentesco = document.getElementById('filtro-parentesco');
    const buscar = document.getElementById('buscar');
    const entries = document.getElementById('entries');

    // === FUNCIÓN DE FILTRADO ===
    function aplicarFiltros() {
        const parentescoVal = filtroParentesco.value;
        const textoBuscar = (buscar.value || '').trim().toLowerCase();

        const filtrados = config.data.filter(item => {
            // Filtro por parentesco
            if (parentescoVal && parentescoVal !== '') {
                if (!item.IdParentesco) return false;
                if (item.IdParentesco.toString() !== parentescoVal.toString()) return false;
            }

            // Filtro por texto (nombre, cédula, parentesco)
            if (textoBuscar) {
                const coincide =
                    (item.nombreCompleto || '').toLowerCase().includes(textoBuscar) ||
                    (item.cedulaCompleta || '').toLowerCase().includes(textoBuscar) ||
                    (item.parentesco || '').toLowerCase().includes(textoBuscar);
                if (!coincide) return false;
            }

            return true;
        });

        // === ACTUALIZAR TABLA ===
        window.tablaRepresentantes.updateData(filtrados);

        // ✅ Restaurar valor del buscador (evita que se borre al escribir)
        buscar.value = textoBuscar;
    }

    // === EVENTOS ===
    filtroParentesco.addEventListener('change', aplicarFiltros);
    buscar.addEventListener('input', aplicarFiltros);
    entries.addEventListener('change', aplicarFiltros);

    // === CARGA INICIAL ===
    aplicarFiltros();

    // === IMPRIMIR ===
    window.imprimirLista = function() {
        const nombreCompleto = "<?= htmlspecialchars($_SESSION['nombre_completo'] ?? ($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '') ?? $_SESSION['usuario'] ?? 'Sistema') ?>";
        generarReporteImprimible(
            'REPORTE DE REPRESENTANTES DEL SISTEMA',
            '#tabla-representantes',
            {
                logoUrl: '../../../assets/images/fermin.png',
                colorPrincipal: '#c90000',
                usuario: nombreCompleto
            }
        );
    };
});
</script>

</body>
</html>