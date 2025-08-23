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
    header("Location: fecha_escolar.php");
    exit();
} elseif (isset($_GET['success'])) {
    $_SESSION['alert'] = 'success';
    header("Location: fecha_escolar.php");
    exit();
} elseif (isset($_GET['actualizar'])) {
    $_SESSION['alert'] = 'actualizar';
    header("Location: fecha_escolar.php");
    exit();
} elseif (isset($_GET['error'])) {
    $_SESSION['alert'] = 'error';
    header("Location: fecha_escolar.php");
    exit();
}

// Obtener alerta
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

// Mostrar alerta si existe
if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito("El Año Escolar se creó correctamente.");
            break;
        case 'actualizar':
            $alerta = Notificaciones::exito("El Año Escolar se actualizó correctamente.");
            break;
        case 'deleted':
            $alerta = Notificaciones::exito("El Año Escolar se eliminó correctamente.");
            break;
        case 'dependency_error':
            $alerta = Notificaciones::advertencia("No se puede eliminar el año escolar porque está siendo utilizado por una o más personas.");
            break;
        case 'error':
            $alerta = Notificaciones::advertencia("Este año escolar ya existe, verifique por favor.");
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
    <title>UECFT Araure - Año Escolar</title>
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
                            <i class='bx bxs-user-detail'></i> Gestión de Año Escolar
                        </div>
                        <div class="card-body">
                            <!-- Botones de acción (intercambiados) -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <!-- Imprimir Lista a la izquierda -->
                                <button class="btn btn-imprimir d-flex align-items-center" onclick="imprimirLista()">
                                    <i class='bx bxs-file-pdf me-1'></i> Imprimir Lista
                                </button>
                                <!-- Nuevo Año Escolar a la derecha -->
                                <a href="nuevo_fecha_escolar.php" class="btn btn-danger d-flex align-items-center">
                                    <i class='bx bx-plus-medical me-1'></i> Nuevo Año Escolar
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
                                <table class="table table-hover align-middle" id="tabla-fecha_escolar">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Año Escolar</th>
                                            <th>Estado</th>
                                            <th>¿Aceptar Inscripciones?</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        <?php
                                        require_once __DIR__ . '/../../../config/conexion.php';
                                        $database = new Database();
                                        $conexion = $database->getConnection();

                                        $query = "SELECT IdFecha_Escolar, fecha_escolar, 
                                                  inscripcion_activa, fecha_activa
                                                  FROM fecha_escolar
                                                  ORDER BY fecha_escolar DESC";
                                        $stmt = $conexion->prepare($query);
                                        $stmt->execute();
                                        $fecha_escolar = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($fecha_escolar as $user): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['IdFecha_Escolar']) ?></td>
                                                <td><?= htmlspecialchars($user['fecha_escolar']) ?></td>
                                                 <td data-search="<?= $user['fecha_activa'] == 1 ? 'Activo' : 'Inactivo' ?>">
                                                    <?php if ($user['fecha_activa'] == 1): ?>
                                                        <span class="badge bg-success">Activo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactivo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td data-search="<?= $user['inscripcion_activa'] == 1 ? 'Sí' : 'No' ?>">
                                                    <?php if ($user['inscripcion_activa'] == 1): ?>
                                                        <span class="badge bg-success">Sí</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="editar_fecha_escolar.php?id=<?= $user['IdFecha_Escolar'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                        <i class='bx bxs-edit'></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $user['IdFecha_Escolar'] ?>)">
                                                        <i class='bx bxs-trash'></i>
                                                    </button>
                                                    <?php if ($user['fecha_activa'] == 0): ?>
                                                        <button class="btn btn-sm btn-outline-success me-1" onclick="activateDate(<?= $user['IdFecha_Escolar'] ?>)">
                                                            <i class='bx bx-check-circle'></i> Activar
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Activo</span>
                                                    <?php endif; ?>
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
    let allData = <?= json_encode($fecha_escolar) ?>;
    let filteredData = [...allData];
    let currentPage = 1;
    let entriesPerPage = parseInt(document.getElementById('entries').value) || 10;

    allData = allData.map(item => {
        // Crear HTML para las acciones
        let accionesHTML = `
            <a href="editar_fecha_escolar.php?id=${item.IdFecha_Escolar}" class="btn btn-sm btn-outline-primary me-1">
                <i class='bx bxs-edit'></i>
            </a>
            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(${item.IdFecha_Escolar})">
                <i class='bx bxs-trash'></i>
            </button>
        `;
        
        // Agregar botón de activar o badge de activo
        if (item.fecha_activa == 1) {
            accionesHTML += '<span class="badge bg-success">Activo</span>';
        } else {
            accionesHTML += `
                <button class="btn btn-sm btn-outline-success me-1" onclick="activateDate(${item.IdFecha_Escolar})">
                    <i class='bx bx-check-circle'></i> Activar
                </button>
            `;
        }

        return {
            ...item,
            // Mantener texto plano para búsqueda
            fecha_activa_text: item.fecha_activa == 1 ? 'Activo' : 'Inactivo',
            inscripcion_activa_text: item.inscripcion_activa == 1 ? 'Sí' : 'No',
            // Convertir a badges HTML para visualización
            fecha_activa: item.fecha_activa == 1 ? 
                '<span class="badge bg-success">Activo</span>' : 
                '<span class="badge bg-secondary">Inactivo</span>',
            inscripcion_activa: item.inscripcion_activa == 1 ? 
                '<span class="badge bg-success">Sí</span>' : 
                '<span class="badge bg-secondary">No</span>',
            // Agregar el HTML de las acciones
            acciones: accionesHTML
        };
    });

    // Inicialización de TablaDinamica
    document.addEventListener('DOMContentLoaded', function() {
        const config = {
            tablaId: 'tabla-fecha_escolar',  // Coincide con tu HTML
            tbodyId: 'table-body',      // Coincide con tu HTML
            buscarId: 'buscar',         // Coincide con tu HTML
            entriesId: 'entries',       // Coincide con tu HTML
            paginationId: 'pagination', // Coincide con tu HTML
            data: allData,
            idField: 'IdFecha_Escolar',
            columns: [
                { label: 'ID', key: 'IdFecha_Escolar'},
                { label: 'Año Escolar', key: 'fecha_escolar', searchKey: 'fecha_activa_text'},
                { label: 'Estado', key: 'fecha_activa', searchKey: 'inscripcion_activa_text'},
                { label: '¿Aceptar Inscripciones?', key: 'inscripcion_activa'},
                { 
                    label: 'Acciones', 
                    key: 'acciones',
                    sortable: false // No ordenar esta columna
                }
            ]
        };

        // Preparar datos para la tabla
        config.data = allData.map(item => ({
            ...item,
            nombreCompleto: `${item.nombre} ${item.apellido}`
        }));

        // Crear instancia de TablaDinamica
        window.tablaFecha_Escolar = new TablaDinamica(config);
    });

    // === FUNCIONES ===
    function confirmDelete(id) {
        Swal.fire({
            title: "¿Está seguro que desea eliminar este fecha_escolar?",
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonText: "Sí, Eliminar",
            denyButtonText: "No, Volver"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../../controladores/FechaEscolarController.php?action=eliminar&id=' + id;
            } else if (result.isDenied) {
                Swal.fire("No se eliminó el fecha_escolar", "", "info");
            }
        });
    }

    function activateDate(id) {
        Swal.fire({
            title: 'Activar Año Escolar',
            text: 'Hacer esto desactivará el año escolar activado anteriormente. ¿Desea continuar?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, activar',
            cancelButtonText: 'No, cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../../controladores/FechaEscolarController.php?id=' + id;
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
            'REPORTE DE AÑOS ESCOLARES DEL SISTEMA',
            '#tabla-fecha_escolar',
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