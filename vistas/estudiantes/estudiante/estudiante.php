<?php
session_start();

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

require_once __DIR__ . '/../../../controladores/Notificaciones.php';

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


require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Persona.php';
require_once __DIR__ . '/../../../modelos/Nivel.php';
require_once __DIR__ . '/../../../modelos/Curso.php';
require_once __DIR__ . '/../../../modelos/Seccion.php';
require_once __DIR__ . '/../../../modelos/TipoDiscapacidad.php';
$database = new Database();
$conexion = $database->getConnection();

$personaModel = new Persona($conexion);
$estudiantes = $personaModel->obtenerEstudiantes();
$nivelModel = new Nivel($conexion);
$cursoModel = new Curso($conexion);
$seccionModel = new Seccion($conexion);
$tipoDiscapacidadModel = new TipoDiscapacidad($conexion);

// === FILTROS ===
$niveles = $nivelModel->obtenerTodos();
$cursos = $cursoModel->obtenerTodos();
$secciones = $seccionModel->obtenerTodos();
$tiposDiscapacidad = $tipoDiscapacidadModel->obtenerTodos();
?>

<head>
    <title>UECFT Araure - Estudiantes</title>
    <style>
        @media(min-width: 768px) {
            .filters-row { flex-wrap: nowrap; }
        }
        #filtro-curso option[style*="display:none"] { display: none !important; }
    </style>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header">
                            <i class='bx bx-wheelchair'></i> Gestión de Estudiantes
                        </div>
                        <div class="card-body">

                            <!-- 🔹 Línea superior: Botón Imprimir | Filtro Discapacidad centrado | Nuevo Estudiante -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <!-- Botón Imprimir -->
                                <div class="mb-2">
                                    <button class="btn btn-imprimir d-flex align-items-center" id="btnImprimir" onclick="imprimirLista()">
                                        <i class='bx bxs-file-pdf me-1'></i> Imprimir Lista
                                    </button>
                                </div>

                                <!-- Filtro Discapacidad centrado -->
                                <div class="d-flex justify-content-center align-items-center mb-2 flex-grow-1 text-center">
                                    <label for="filtroDiscapacidad" class="fw-semibold mb-0 me-2">Tipo de Discapacidad:</label>
                                    <select id="filtroDiscapacidad" class="form-select d-inline-block w-auto text-center">
                                        <option value="">Todos los registros</option>
                                        <option value="CON_DISCAPACIDAD">Con discapacidad</option>
                                        <option value="SIN_DISCAPACIDAD">Sin discapacidad</option>
                                        <?php foreach ($tiposDiscapacidad as $tipo): ?>
                                            <option value="<?= $tipo['tipo_discapacidad']; ?>">
                                                <?= htmlspecialchars($tipo['tipo_discapacidad']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- 🔹 Línea inferior: Buscador, filtros (nivel/curso/sección) y entradas -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                <!-- Buscador -->
                                <div class="flex-grow-1" style="max-width: 250px;">
                                    <input type="text" class="search-input form-control" id="buscar" placeholder="Buscar...">
                                </div>

                                <!-- Filtros Nivel, Curso, Sección -->
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <label for="filtroNivel" class="fw-semibold mb-0">Nivel:</label>
                                    <select id="filtroNivel" class="form-select" style="width:auto;">
                                        <option value="">Todos</option>
                                        <?php foreach ($niveles as $nivel): ?>
                                            <option value="<?= $nivel['nivel']; ?>"><?= htmlspecialchars($nivel['nivel']); ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <label for="filtroCurso" class="fw-semibold mb-0">Curso:</label>
                                    <select id="filtroCurso" class="form-select" style="width:auto;">
                                        <option value="">Todos</option>
                                        <?php foreach ($cursos as $curso): ?>
                                            <option value="<?= $curso['curso']; ?>"><?= htmlspecialchars($curso['curso']); ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <label for="filtroSeccion" class="fw-semibold mb-0">Sección:</label>
                                    <select id="filtroSeccion" class="form-select" style="width:auto;">
                                        <option value="">Todas</option>
                                        <?php foreach ($secciones as $seccion): ?>
                                            <option value="<?= $seccion['seccion']; ?>"><?= htmlspecialchars($seccion['seccion']); ?></option>
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
                                <table class="table table-hover align-middle" id="tabla-estudiantes">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Estudiante</th>
                                            <th>Cédula</th>
                                            <th>Sexo</th>
                                            <th>Nivel</th>
                                            <th>Curso</th>
                                            <th>Sección</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body"></tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-center mt-3">
                                <div id="pagination" class="pagination"></div>
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
        const allData = <?= json_encode($estudiantes, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
        const niveles = <?= json_encode($niveles) ?>;
        const cursosOriginales = <?= json_encode($cursos) ?>;

        const config = {
            tablaId: 'tabla-estudiantes',
            tbodyId: 'table-body',
            buscarId: 'buscar',
            entriesId: 'entries',
            paginationId: 'pagination',
            data: allData.map(item => ({
                ...item,
                nombreCompleto: `${item.nombre} ${item.apellido}`,
                cedulaCompleta: `${item.nacionalidad ? item.nacionalidad + ' ' : ''}${item.cedula || ''}`,
            })),
            idField: 'IdPersona',
            columns: [
                { label: 'ID', key: 'IdPersona' },
                { label: 'Estudiante', key: 'nombreCompleto' },
                { label: 'Cédula', key: 'cedulaCompleta' },
                { label: 'Sexo', key: 'sexo' },
                { label: 'Nivel', key: 'nivel' },
                { label: 'Curso', key: 'curso' },
                { label: 'Sección', key: 'seccion' }
            ],
            acciones: [
                { url: 'editar_estudiante.php?id={id}', class: 'btn-outline-primary', icon: '<i class="bx bxs-edit"></i>' },
                { url: 'ver_estudiante.php?id={id}', class: 'btn-outline-info', icon: '<i class="bx bxs-show"></i>' }
            ]
        };

        window.tablaEstudiantes = new TablaDinamica(config);

        // === REFERENCIAS ===
        const filtroNivel = document.getElementById('filtroNivel');
        const filtroCurso = document.getElementById('filtroCurso');
        const filtroSeccion = document.getElementById('filtroSeccion');
        const filtroDiscapacidad = document.getElementById('filtroDiscapacidad');
        const filtroBuscar = document.getElementById('buscar');
        const filtroEntries = document.getElementById('entries');

        // Actualizar cursos cuando cambia el nivel
        filtroNivel.addEventListener('change', function() {
            const nivelSeleccionado = this.value.trim();
            filtroCurso.innerHTML = '<option value="">Todos</option>';

            if (nivelSeleccionado === '') {
                cursosOriginales.forEach(curso => {
                    const opt = document.createElement('option');
                    opt.value = curso.curso;
                    opt.textContent = curso.curso;
                    filtroCurso.appendChild(opt);
                });
            } else {
                const nivelObj = niveles.find(n => n.nivel.toLowerCase() === nivelSeleccionado.toLowerCase());
                if (nivelObj) {
                    const cursosFiltrados = cursosOriginales.filter(curso =>
                        parseInt(curso.IdNivel) === parseInt(nivelObj.IdNivel)
                    );
                    cursosFiltrados.forEach(curso => {
                        const opt = document.createElement('option');
                        opt.value = curso.curso;
                        opt.textContent = curso.curso;
                        filtroCurso.appendChild(opt);
                    });
                }
            }
            aplicarFiltros();
        });

        function aplicarFiltros() {
            const nivelVal = filtroNivel.value.trim();
            const cursoVal = filtroCurso.value.trim();
            const seccionVal = filtroSeccion.value.trim();
            const discVal = filtroDiscapacidad.value.trim();
            const textoBuscar = filtroBuscar.value.trim().toLowerCase();

            const filtrados = config.data.filter(item => {
                let matchNivel = !nivelVal || item.nivel?.toLowerCase() === nivelVal.toLowerCase();
                let matchCurso = !cursoVal || item.curso?.toLowerCase() === cursoVal.toLowerCase();
                let matchSeccion = !seccionVal || item.seccion?.toLowerCase() === seccionVal.toLowerCase();

                // === FILTRO DE DISCAPACIDAD ===
                let matchDiscapacidad = true;

                if (discVal === "CON_DISCAPACIDAD") {
                    // Tiene al menos una discapacidad registrada
                    matchDiscapacidad = item.tipo_discapacidad && item.tipo_discapacidad.trim() !== "";
                } else if (discVal === "SIN_DISCAPACIDAD") {
                    // No tiene discapacidad
                    matchDiscapacidad = !item.tipo_discapacidad || item.tipo_discapacidad.trim() === "";
                } else if (discVal) {
                    // Filtro por tipo específico (ej: Alergia)
                    matchDiscapacidad = item.tipo_discapacidad?.toLowerCase() === discVal.toLowerCase();
                } else {
                    // ⚠️ Cuando el valor es "", debe mostrar todos los registros
                    matchDiscapacidad = true;
                }

                let matchBuscar = !textoBuscar ||
                    `${item.nombreCompleto} ${item.cedulaCompleta}`.toLowerCase().includes(textoBuscar);

                return matchNivel && matchCurso && matchSeccion && matchDiscapacidad && matchBuscar;
            });

            window.tablaEstudiantes.updateData(filtrados);

            // 🔹 Restaura el texto del buscador por si el DOM fue reemplazado
            filtroBuscar.value = textoBuscar;
        }

        [filtroCurso, filtroSeccion, filtroDiscapacidad, filtroBuscar, filtroEntries].forEach(el => {
            el.addEventListener(el === filtroBuscar ? 'input' : 'change', aplicarFiltros);
        });

        aplicarFiltros();

        window.imprimirLista = function() {
            const nombreCompleto = "<?= htmlspecialchars($_SESSION['nombre_completo'] ?? ($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '') ?? $_SESSION['usuario'] ?? 'Sistema') ?>";
            generarReporteImprimible(
                'REPORTE DE ESTUDIANTES CON DISCAPACIDAD',
                '#tabla-estudiantes',
                {
                    logoUrl: '../../../assets/images/fermin.png',
                    colorPrincipal: '#c90000',
                    usuario: nombreCompleto
                }
            );
        };
    });
</script>