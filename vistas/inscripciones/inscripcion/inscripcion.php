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

// Obtener alerta
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

// Mostrar alerta si existe
if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito("La inscripcion se creó correctamente.");
            break;
        case 'actualizar':
            $alerta = Notificaciones::exito("La inscripcion se actualizó correctamente.");
            break;
        case 'deleted':
            $alerta = Notificaciones::exito("La inscripcion se eliminó correctamente.");
            break;
        case 'error':
            $alerta = Notificaciones::advertencia("Esta inscripcion ya existe, verifique por favor.");
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
    <title>UECFT Araure - Inscripciones</title>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

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
                            <!-- Botones de acción (intercambiados) -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <!-- Imprimir Lista a la izquierda -->
                                <button class="btn btn-imprimir d-flex align-items-center" onclick="imprimirLista()">
                                    <i class='bx bxs-file-pdf me-1'></i> Imprimir Lista
                                </button>
                                <!-- Nuevo Inscripcion a la derecha -->
                                <a href="nuevo_inscripcion.php" class="btn btn-danger d-flex align-items-center">
                                    <i class='bx bx-plus-medical me-1'></i> Nuevo Inscripcion
                                </a>
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
                                    <tbody id="table-body">
                                        <?php
                                        require_once __DIR__ . '/../../../config/conexion.php';
                                        $database = new Database();
                                        $conexion = $database->getConnection();

                                        $query = "SELECT IdInscripcion, estudiante.nombre AS nombre_estudiante, estudiante.apellido 
                                                  AS apellido_estudiante, codigo_inscripcion, responsable.nombre AS nombre_responsable, 
                                                  responsable.apellido AS apellido_responsable, fecha_inscripcion, curso, seccion, fecha_escolar, status
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

                                        foreach ($inscripciones as $user): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['codigo_inscripcion']) ?></td>
                                                <td><?= htmlspecialchars($user['nombre_estudiante'] . ' ' . $user['apellido_estudiante']) ?></td>
                                                <td><?= htmlspecialchars($user['nombre_responsable'] . ' ' . $user['apellido_responsable']) ?></td>
                                                <td><?= htmlspecialchars($user['fecha_inscripcion']) ?></td>
                                                <td><?= htmlspecialchars($user['curso'] . ' - ' . $user['seccion']) ?></td>
                                                <td><?= htmlspecialchars($user['fecha_escolar']) ?></td>
                                                <td><?= htmlspecialchars($user['status']) ?></td>
                                                <td>
                                                    <a href="editar_inscripcion.php?id=<?= $user['IdInscripcion'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                        <i class='bx bxs-edit'></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $user['IdInscripcion'] ?>)">
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
    let allData = <?= json_encode($inscripciones) ?>;
    let filteredData = [...allData];
    let currentPage = 1;
    let entriesPerPage = parseInt(document.getElementById('entries').value) || 10;

    // Inicialización de TablaDinamica
    document.addEventListener('DOMContentLoaded', function() {
        const config = {
            tablaId: 'tabla-inscripciones',  // Coincide con tu HTML
            tbodyId: 'table-body',      // Coincide con tu HTML
            buscarId: 'buscar',         // Coincide con tu HTML
            entriesId: 'entries',       // Coincide con tu HTML
            paginationId: 'pagination', // Coincide con tu HTML
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
                {
                    url: 'editar_inscripcion.php?id={id}',
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
            nombreCompleto: `${item.nombre_estudiante} ${item.apellido_estudiante}`,
            nombreResponsable: `${item.nombre_responsable} ${item.apellido_responsable}`,
            curso_seccion: `${item.curso} - ${item.seccion}`
        }));

        // Crear instancia de TablaDinamica
        window.tablaInscripciones = new TablaDinamica(config);
    });

    // === FUNCIONES ===
    function confirmDelete(id) {
        Swal.fire({
            title: "¿Está seguro que desea eliminar este inscripcion?",
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonText: "Sí, Eliminar",
            denyButtonText: "No, Volver"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../../controladores/InscripcionController.php?action=eliminar&id=' + id;
            } else if (result.isDenied) {
                Swal.fire("No se eliminó el inscripcion", "", "info");
            }
        });
    }

    window.imprimirLista = function() {
        const nombreCompleto = "<?php 
            echo htmlspecialchars(
                $_SESSION['nombre_completo'] ?? 
                ($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '') ??
                $_SESSION['inscripcion'] ?? 
                'Sistema'
            );
        ?>";
        
        generarReporteImprimible(
            'REPORTE DE INSCRIPCIONES DEL SISTEMA',
            '#tabla-inscripciones',
            {
                logoUrl: '../../../assets/images/fermin.png',
                colorPrincipal: '#c90000',
                inscripcion: nombreCompleto
            }
        );
    };
</script>
</body>
</html>