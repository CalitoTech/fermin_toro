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

// === CONSULTA DE INSCRIPCIONES ===
$query = "SELECT IdInscripcion, estudiante.nombre AS nombre_estudiante, estudiante.apellido 
          AS apellido_estudiante, codigo_inscripcion, responsable.nombre AS nombre_responsable, 
          responsable.apellido AS apellido_responsable, fecha_inscripcion, curso, seccion, 
          fecha_escolar, status, inscripcion.IdStatus
          FROM inscripcion
          INNER JOIN persona as estudiante ON inscripcion.IdEstudiante = estudiante.IdPersona
          INNER JOIN representante ON inscripcion.responsable_inscripcion = representante.IdRepresentante
          INNER JOIN persona as responsable ON representante.IdPersona = responsable.IdPersona
          INNER JOIN fecha_escolar ON inscripcion.IdFecha_Escolar = fecha_escolar.IdFecha_Escolar
          INNER JOIN curso_seccion ON inscripcion.IdCurso_Seccion = curso_seccion.IdCurso_Seccion
          LEFT JOIN curso ON curso_seccion.IdCurso = curso.IdCurso
          LEFT JOIN seccion ON curso_seccion.IdSeccion = seccion.IdSeccion
          INNER JOIN status ON inscripcion.IdStatus = status.IdStatus
          ORDER BY fecha_inscripcion ASC";
$stmt = $conexion->prepare($query);
$stmt->execute();
$inscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === CONSULTA DE STATUS ===
$sth = $conexion->prepare("SELECT IdStatus, status FROM status WHERE IdTipo_Status = 2 ORDER BY IdStatus");
$sth->execute();
$estados_inscripcion = $sth->fetchAll(PDO::FETCH_ASSOC);
$defaultStatus = 7; // Pendiente de aprobación

// Contar inscripciones por status
$statusCounts = [];
foreach ($estados_inscripcion as $st) {
    $count = 0;
    foreach ($inscripciones as $insc) {
        if ($insc['IdStatus'] == $st['IdStatus']) $count++;
    }
    $statusCounts[$st['IdStatus']] = $count;
}
?>

<head>
    <title>UECFT Araure - Inscripciones</title>
    <style>
    </style>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<!-- Filtro de Status fuera del card -->
<div class="container">
    <div class="status-bar-modern" id="status-bar">
        <?php foreach ($estados_inscripcion as $st):
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
                            <!-- Botones de acción -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <button class="btn btn-imprimir d-flex align-items-center" onclick="imprimirLista()">
                                    <i class='bx bxs-file-pdf me-1'></i> Imprimir Lista
                                </button>
                                <a href="nuevo_inscripcion.php" class="btn btn-danger d-flex align-items-center">
                                    <i class='bx bx-plus-medical me-1'></i> Nuevo Inscripcion
                                </a>
                            </div>

                            <!-- Búsqueda y Entradas -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                <div class="flex-grow-1" style="max-width: 300px;">
                                    <input type="text" class="search-input" id="buscar" placeholder="Buscar...">
                                </div>
                                <div class="d-flex align-items-center">
                                    <label for="entries" class="me-2">Entradas:</label>
                                    <select id="entries" class="form-select" style="width:auto;">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Tabla -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="tabla-inscripciones">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código Insc.</th>
                                            <th>Estudiante</th>
                                            <th>Responsable</th>
                                            <th>Fecha Insc.</th>
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
                { label: 'Fecha Inscripción', key: 'fecha_inscripcion' },
                { label: 'Sección', key: 'curso_seccion' },
                { label: 'Fecha Escolar', key: 'fecha_escolar' },
                { label: 'Status', key: 'status' }
            ],
            acciones: [
                { url: 'editar_inscripcion.php?id={id}', class: 'btn-outline-primary', icon: '<i class="bx bxs-edit"></i>' },
                { url: 'ver_inscripcion.php?id={id}', class: 'btn-outline-info', icon: '<i class="bx bxs-show"></i>' },
                { url: 'reporte_inscripcion.php?id={id}', class: 'btn-outline-danger', icon: '<i class="bx bxs-file-pdf"></i>', target: '_blank' }
            ]
        };

        config.data = allData.map(item => ({
            ...item,
            nombreCompleto: `${item.nombre_estudiante} ${item.apellido_estudiante}`,
            nombreResponsable: `${item.nombre_responsable} ${item.apellido_responsable}`,
            curso_seccion: `${item.curso} - ${item.seccion}`
        }));

        window.tablaInscripciones = new TablaDinamica(config);

        // === FILTRO POR STATUS MODERNO CON TOGGLE ===
        const statusSteps = document.querySelectorAll('.status-step-modern');
        function filterByStatus(idStatus) {
            if (!idStatus) return window.tablaInscripciones.updateData(config.data);
            const filtered = config.data.filter(item => parseInt(item.IdStatus) === parseInt(idStatus));
            window.tablaInscripciones.updateData(filtered);
        }

        let activeStatusId = null;

        statusSteps.forEach(step => {
            step.addEventListener('click', function() {
                const id = this.dataset.id;

                if (activeStatusId === id) {
                    // Si hacemos click sobre el mismo, desactivamos y mostramos todo
                    statusSteps.forEach(s => s.classList.remove('active'));
                    activeStatusId = null;
                    filterByStatus(null);
                } else {
                    // Activar solo el seleccionado
                    statusSteps.forEach(s => s.classList.remove('active'));
                    this.classList.add('active');
                    activeStatusId = id;
                    filterByStatus(id);
                }
            });
        });

        // Aplicar filtro inicial
        const initialStep = document.querySelector('.status-step-modern.active');
        if (initialStep) {
            activeStatusId = initialStep.dataset.id;
            filterByStatus(activeStatusId);
        }

    });

    // === IMPRIMIR LISTA ===
    window.imprimirLista = function() {
        const nombreCompleto = "<?php 
            echo htmlspecialchars($_SESSION['nombre_completo'] ?? ($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '') ?? 'Sistema');
        ?>";
        generarReporteImprimible(
            'REPORTE DE INSCRIPCIONES DEL SISTEMA',
            '#tabla-inscripciones',
            { logoUrl: '../../../assets/images/fermin.png', colorPrincipal: '#c90000', inscripcion: nombreCompleto }
        );
    };
</script>

</body>
</html>