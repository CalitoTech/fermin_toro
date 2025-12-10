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
$database = new Database();
$conexion = $database->getConnection();

// Manejo de alertas
if (isset($_GET['deleted'])) {
    $_SESSION['alert'] = 'deleted';
    header("Location: inscripcion.php");
    exit();
} elseif (isset($_GET['success'])) {
    $_SESSION['alert'] = 'success';
    header("Location: inscripcion.php");
    exit();
} elseif (isset($_GET['actualizar'])) {
    $_SESSION['alert'] = 'actualizar';
    header("Location: inscripcion.php");
    exit();
} elseif (isset($_GET['error'])) {
    $_SESSION['alert'] = 'error';
    header("Location: inscripcion.php");
    exit();
}

// Mostrar alerta
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito("La inscripción se creó correctamente.");
            break;
        case 'actualizar':
            $alerta = Notificaciones::exito("La inscripción se actualizó correctamente.");
            break;
        case 'deleted':
            $alerta = Notificaciones::exito("La inscripción se eliminó correctamente.");
            break;
        case 'error':
            $alerta = Notificaciones::advertencia("Esta inscripción ya existe, verifique por favor.");
            break;
        default:
            $alerta = null;
    }
    if ($alerta) Notificaciones::mostrar($alerta);
}

require_once __DIR__ . '/../../../modelos/Inscripcion.php';
require_once __DIR__ . '/../../../modelos/TipoInscripcion.php';
require_once __DIR__ . '/../../../modelos/Nivel.php';
require_once __DIR__ . '/../../../modelos/Status.php';
require_once __DIR__ . '/../../../modelos/Curso.php';
require_once __DIR__ . '/../../../modelos/Seccion.php';
require_once __DIR__ . '/../../../modelos/FechaEscolar.php';

// Instancias de modelos
$modeloNivel = new Nivel($conexion);
$modeloStatus = new Status($conexion);
$modeloCurso = new Curso($conexion);
$modeloSeccion = new Seccion($conexion);
$modeloFechaEscolar = new FechaEscolar($conexion);
$modeloInscripcion = new Inscripcion($conexion);
$modeloTipoInscripcion = new TipoInscripcion($conexion);

// Obtener secciones y fechas escolares sin filtro
$statuses = $modeloStatus->obtenerStatusInscripcion();
$defaultStatus = 8; // Pendiente de aprobación
$secciones = $modeloSeccion->obtenerTodos();
$añoActivo = $modeloFechaEscolar->obtenerActivo();
$añosEscolares = $modeloFechaEscolar->obtenerTodos();
$tiposInscripcion = $modeloTipoInscripcion->obtenerTodos();

// Año escolar activo o el más reciente
$yearSelected = $añoActivo ? $añoActivo['IdFecha_Escolar'] : ($añosEscolares[0]['IdFecha_Escolar'] ?? '');
?>

<head>
    <title>UECFT Araure - Inscripciones</title>
    <style>
    </style>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<?php
// Obtener datos con filtro por permisos
$niveles = $modeloNivel->obtenerNiveles($idPersona);
$cursos = $modeloCurso->obtenerCursos($idPersona);
?>

<?php
$inscripciones = $modeloInscripcion->obtenerTodas($idPerfil, $idPersona);

// Contar inscripciones por status
$statusCounts = [];
foreach ($statuses as $st) {
    $count = 0;
    foreach ($inscripciones as $insc) {
        if ($insc['IdStatus'] == $st['IdStatus']) $count++;
    }
    $statusCounts[$st['IdStatus']] = $count;
}
?>

<!-- Filtro de Status fuera del card -->
<div class="container">
    <div class="status-bar-modern" id="status-bar">
        <?php foreach ($statuses as $st):
            $count = $statusCounts[$st['IdStatus']] ?? 0;
        ?>
        <div class="status-step-modern <?= ($st['IdStatus'] == $defaultStatus) ? 'active' : '' ?>" 
             data-id="<?= $st['IdStatus'] ?>">
            <span class="status-label"><?= htmlspecialchars($st['status']) ?></span>
            <span class="status-badge"><?= $count ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class='bx bxs-user-detail'></i> Gestión de Inscripciones
                        </div>
                        <div class="card-body">

                            <!-- 🔹 Línea superior: Imprimir | Año Escolar centrado | Nueva inscripción -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <!-- Botón Imprimir -->
                                <div class="mb-2">
                                    <button class="btn btn-imprimir d-flex align-items-center" id="btnImprimir" onclick="imprimirLista()">
                                        <i class='bx bxs-file-pdf me-1'></i> Imprimir Lista
                                    </button>
                                </div>

                                <!-- Filtro Año Escolar centrado -->
                                <div class="d-flex justify-content-center align-items-center mb-2 flex-grow-1 text-center">
                                    <label for="filtroAnio" class="fw-semibold mb-0 me-2">Año Escolar:</label>
                                    <select id="filtroAnio" class="form-select d-inline-block w-auto text-center">
                                        <option value="">Todos</option>
                                        <?php foreach ($añosEscolares as $año): ?>
                                            <option value="<?= $año['IdFecha_Escolar']; ?>"
                                                <?= $año['IdFecha_Escolar'] == $yearSelected ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($año['fecha_escolar']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Botón Nueva Inscripción -->
                                <div class="mb-2">
                                    <a href="nuevo_inscripcion.php" id="btnNuevoRegistro" class="btn btn-danger d-flex align-items-center">
                                        <i class='bx bx-plus-medical me-1'></i> Nueva Inscripción
                                    </a>
                                </div>
                            </div>

                            <!-- 🔹 Línea inferior: Buscador, filtros (nivel/curso/sección) y entradas -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                <!-- Buscador -->
                                <div class="flex-grow-1" style="max-width: 250px;">
                                    <input type="text" class="search-input" id="buscar" placeholder="Buscar...">
                                </div>

                                <!-- Filtros Nivel, Curso, Sección, Tipo Inscripción -->
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <label for="filtroTipoInscripcion" class="fw-semibold mb-0">Tipo:</label>
                                    <select id="filtroTipoInscripcion" class="form-select" style="width:auto;">
                                        <option value="">Todos</option>
                                        <?php foreach ($tiposInscripcion as $tipo): ?>
                                            <option value="<?= $tipo['tipo_inscripcion']; ?>"><?= htmlspecialchars($tipo['tipo_inscripcion']); ?></option>
                                        <?php endforeach; ?>
                                    </select>

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
                                        <option value="">Todos</option>
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
                                <table class="table table-hover align-middle" id="tabla-inscripciones">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código Insc.</th>
                                            <th>Estudiante</th>
                                            <th>Responsable</th>
                                            <th>Tipo</th>
                                            <th>Sección</th>
                                            <th>Año Escolar</th>
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
let allData = <?= json_encode($inscripciones) ?>;
const cursosOriginales = <?= json_encode($cursos) ?>; // <- Añadimos esto para poder filtrar los cursos

document.addEventListener('DOMContentLoaded', function() {
    const config = {
        tablaId: 'tabla-inscripciones',
        tbodyId: 'table-body',
        buscarId: 'buscar',
        entriesId: 'entries',
        paginationId: 'pagination',
        data: allData,
        idField: 'IdInscripcion',
        columns: [
            { label: 'Código Insc.', key: 'codigo_inscripcion' },
            { label: 'Estudiante', key: 'nombreCompleto' },
            { label: 'Responsable', key: 'nombreResponsable' },
            { label: 'Tipo', key: 'tipo_inscripcion' },
            { label: 'Sección', key: 'curso_seccion' },
            { label: 'Fecha Escolar', key: 'fecha_escolar' },
            { label: 'Status', key: 'status' }
        ],
        acciones: [
            { url: 'ver_inscripcion.php?id={id}', class: 'btn-outline-info', icon: '<i class="bx bxs-show"></i>' },
            { url: 'reporte_inscripcion.php?id={id}', class: 'btn-outline-danger', icon: '<i class="bx bxs-file-pdf"></i>', target: '_blank' }
        ]
    };

    // === Añadimos campos auxiliares ===
    config.data = allData.map(item => ({
        ...item,
        nombreCompleto: `${item.nombre_estudiante} ${item.apellido_estudiante}`,
        nombreResponsable: `${item.nombre_responsable} ${item.apellido_responsable}`,
        curso_seccion: `${item.curso} - ${item.seccion}`
    }));

    window.tablaInscripciones = new TablaDinamica(config);

    // === FILTRO POR STATUS ===
    const statusSteps = document.querySelectorAll('.status-step-modern');
    let activeStatusId = null;

    statusSteps.forEach(step => {
        step.addEventListener('click', function() {
            const id = this.dataset.id;
            if (activeStatusId === id) {
                statusSteps.forEach(s => s.classList.remove('active'));
                activeStatusId = null;
            } else {
                statusSteps.forEach(s => s.classList.remove('active'));
                this.classList.add('active');
                activeStatusId = id;
            }
            // Usar aplicarFiltros para combinar todos los filtros incluyendo status
            aplicarFiltros();
        });
    });

    // === FILTROS COMBINADOS ===
    const filtroTipoInscripcion = document.getElementById('filtroTipoInscripcion');
    const filtroNivel = document.getElementById('filtroNivel');
    const filtroCurso = document.getElementById('filtroCurso');
    const filtroSeccion = document.getElementById('filtroSeccion');
    const filtroAnio = document.getElementById('filtroAnio');
    const filtroBuscar = document.getElementById('buscar');
    const filtroEntries = document.getElementById('entries');

    // ✅ CUANDO CAMBIA EL NIVEL: actualizar opciones del select de curso
    filtroNivel.addEventListener('change', function() {
        const nivelSeleccionado = this.value.trim();
        filtroCurso.innerHTML = '<option value="">Todos</option>'; // limpiar

        // Si es "Todos", mostramos todos los cursos
        if (nivelSeleccionado === '' || nivelSeleccionado.toLowerCase() === 'todos') {
            cursosOriginales.forEach(curso => {
                const opt = document.createElement('option');
                opt.value = curso.curso;
                opt.textContent = curso.curso;
                filtroCurso.appendChild(opt);
            });
        } else {
            // Buscar el objeto del nivel seleccionado para obtener su IdNivel
            const nivelObj = <?= json_encode($niveles) ?>.find(n =>
                n.nivel.toLowerCase() === nivelSeleccionado.toLowerCase()
            );

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

        aplicarFiltros(); // aplicar el filtro general
    });

    // === FUNCIÓN GENERAL DE FILTROS ===
    function aplicarFiltros() {
        const tipoInscripcionVal = filtroTipoInscripcion ? filtroTipoInscripcion.value.trim() : '';
        const nivelVal = filtroNivel ? filtroNivel.value.trim() : '';
        const cursoVal = filtroCurso ? filtroCurso.value.trim() : '';
        const seccionVal = filtroSeccion ? filtroSeccion.value.trim() : '';
        const anioVal = filtroAnio ? filtroAnio.value.trim() : '';
        const textoBuscar = filtroBuscar ? filtroBuscar.value.trim().toLowerCase() : '';

        // Función auxiliar para aplicar filtros sin status
        const aplicarFiltrosSinStatus = (item) => {
            let matchTipoInscripcion = true;
            if (tipoInscripcionVal && tipoInscripcionVal.toLowerCase() !== 'todos') {
                matchTipoInscripcion = (item.tipo_inscripcion && item.tipo_inscripcion.toLowerCase() === tipoInscripcionVal.toLowerCase());
            }

            let matchNivel = true;
            if (nivelVal && nivelVal.toLowerCase() !== 'todos') {
                matchNivel = (item.nivel && item.nivel.toLowerCase() === nivelVal.toLowerCase());
            }

            let matchCurso = true;
            if (cursoVal && cursoVal.toLowerCase() !== 'todos') {
                matchCurso = (item.curso && item.curso.toLowerCase() === cursoVal.toLowerCase());
            }

            let matchSeccion = true;
            if (seccionVal && seccionVal.toLowerCase() !== 'todos') {
                matchSeccion = (item.seccion && item.seccion.toLowerCase() === seccionVal.toLowerCase());
            }

            let matchAnio = true;
            if (anioVal && anioVal.toLowerCase() !== 'todos') {
                if (!isNaN(anioVal)) {
                    matchAnio = (item.IdFecha_Escolar && item.IdFecha_Escolar.toString() === anioVal);
                } else {
                    matchAnio = (item.fecha_escolar && item.fecha_escolar === anioVal);
                }
            }

            let matchBuscar = true;
            if (textoBuscar) {
                const combo = `${item.codigo_inscripcion} ${item.nombre_estudiante} ${item.apellido_estudiante} ${item.nombre_responsable} ${item.apellido_responsable} ${item.curso} ${item.seccion}`.toLowerCase();
                matchBuscar = combo.includes(textoBuscar);
            }

            return matchTipoInscripcion && matchNivel && matchCurso && matchSeccion && matchAnio && matchBuscar;
        };

        // Filtrar datos sin considerar status para actualizar conteos
        const filteredSinStatus = config.data.filter(aplicarFiltrosSinStatus);

        // Actualizar conteos de badges de status
        actualizarConteosBadges(filteredSinStatus);

        // Aplicar filtro completo incluyendo status
        const filtered = filteredSinStatus.filter(item => {
            if (activeStatusId) {
                return parseInt(item.IdStatus) === parseInt(activeStatusId);
            }
            return true;
        });

        window.tablaInscripciones.updateData(filtered);
    }

    // === FUNCIÓN PARA ACTUALIZAR CONTEOS DE BADGES ===
    function actualizarConteosBadges(datosFiltrados) {
        const statusIds = <?= json_encode(array_column($statuses, 'IdStatus')) ?>;

        // Contar por cada status
        const conteos = {};
        statusIds.forEach(id => conteos[id] = 0);

        datosFiltrados.forEach(item => {
            const statusId = parseInt(item.IdStatus);
            if (conteos.hasOwnProperty(statusId)) {
                conteos[statusId]++;
            }
        });

        // Actualizar los badges en el DOM
        statusSteps.forEach(step => {
            const id = parseInt(step.dataset.id);
            const badge = step.querySelector('.status-badge');
            if (badge && conteos.hasOwnProperty(id)) {
                badge.textContent = conteos[id];
            }
        });
    }

    // === LISTENERS DE FILTROS ===
    [filtroTipoInscripcion, filtroCurso, filtroSeccion, filtroAnio, filtroBuscar, filtroEntries].forEach(el => {
        if (!el) return;
        const ev = (el === filtroBuscar) ? 'input' : 'change';
        el.addEventListener(ev, aplicarFiltros);
    });

    // === Aplicar filtro inicial de status ===
    const initialStep = document.querySelector('.status-step-modern.active');
    if (initialStep) {
        activeStatusId = initialStep.dataset.id;
    }

    // Ejecutar filtros una vez al cargar (incluye el status inicial)
    aplicarFiltros();
});

// === IMPRIMIR LISTA ===
window.imprimirLista = function() {
    const nombreCompleto = "<?php 
        echo htmlspecialchars($_SESSION['nombre_completo'] ?? ($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '') ?? 'Sistema');
    ?>";
    generarReporteImprimible(
        'REPORTE DE INSCRIPCIONES DEL SISTEMA',
        '#tabla-inscripciones',
        { logoUrl: '../../../assets/images/fermin.png', colorPrincipal: '#c90000', usuario: nombreCompleto }
    );
};
</script>


</body>
</html>