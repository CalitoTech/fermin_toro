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
            $alerta = Notificaciones::exito("El grupo de interés se creó correctamente.");
            break;
        case 'actualizar':
            $alerta = Notificaciones::exito("El grupo de interés se actualizó correctamente.");
            break;
        case 'deleted':
            $alerta = Notificaciones::exito("El grupo de interés se eliminó correctamente.");
            break;
        case 'dependency_error':
            $alerta = Notificaciones::advertencia("No se puede eliminar el grupo de interés porque está siendo utilizado por una o más personas.");
            break;
        case 'error':
            $alerta = Notificaciones::advertencia("Este grupo de interés ya existe, verifique por favor.");
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
    <title>UECFT Araure - GrupoInteres</title>
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
                            <i class='bx bxs-user-detail'></i> Gestión de Grupo de Interés
                        </div>
                        <div class="card-body">
                            <!-- Botones de acción (intercambiados) -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <!-- Imprimir Lista a la izquierda -->
                                <button class="btn btn-imprimir d-flex align-items-center" onclick="imprimirLista()">
                                    <i class='bx bxs-file-pdf me-1'></i> Imprimir Lista
                                </button>
                                <!-- Nuevo Grupo de Interés a la derecha -->
                                <a href="nuevo_tipo_grupo_interes.php" class="btn btn-danger d-flex align-items-center">
                                    <i class='bx bx-plus-medical me-1'></i> Nuevo Grupo de Interés
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
                                <table class="table table-hover align-middle" id="tabla-grupo_interes">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nivel</th>
                                            <th>Grupo de Interés</th>
                                            <th>Capacidad Máxima</th>
                                            <th>¿Inscripción Activa?</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        <?php
                                        require_once __DIR__ . '/../../../config/conexion.php';
                                        $database = new Database();
                                        $conexion = $database->getConnection();

                                        $query = "SELECT IdTipo_Grupo, nivel, nombre_grupo, descripcion,
                                                  capacidad_maxima, inscripcion_activa
                                                  FROM tipo_grupo_interes
                                                  INNER JOIN nivel ON tipo_grupo_interes.IdNivel = nivel.IdNivel";
                                        $stmt = $conexion->prepare($query);
                                        $stmt->execute();
                                        $grupo_interes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($grupo_interes as $user): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['IdTipo_Grupo']) ?></td>
                                                <td><?= htmlspecialchars($user['nivel']) ?></td>
                                                <td><?= htmlspecialchars($user['nombre_grupo']) ?></td>
                                                <td><?= htmlspecialchars($user['capacidad_maxima']) ?></td>
                                                <td><?= $user['inscripcion_activa'] == 1 ? 'Sí' : 'No' ?></td>
                                                <td>
                                                    <a href="editar_tipo_grupo_interes.php?id=<?= $user['IdTipo_Grupo'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                        <i class='bx bxs-edit'></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $user['IdTipo_Grupo'] ?>)">
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
    let allData = <?= json_encode($grupo_interes) ?>;
    let filteredData = [...allData];
    let currentPage = 1;
    let entriesPerPage = parseInt(document.getElementById('entries').value) || 10;

    allData = allData.map(item => ({
        ...item,
        // Convertir inscripcion_activa a texto
        inscripcion_activa: item.inscripcion_activa == 1 ? 'Sí' : 'No'
    }));

    // Inicialización de TablaDinamica
    document.addEventListener('DOMContentLoaded', function() {
        const config = {
            tablaId: 'tabla-grupo_interes',  // Coincide con tu HTML
            tbodyId: 'table-body',      // Coincide con tu HTML
            buscarId: 'buscar',         // Coincide con tu HTML
            entriesId: 'entries',       // Coincide con tu HTML
            paginationId: 'pagination', // Coincide con tu HTML
            data: allData,
            idField: 'IdTipo_Grupo',
            columns: [
                { label: 'ID', key: 'IdTipo_Grupo' },
                { label: 'Nivel', key: 'nivel' },
                { label: 'Grupo de Interés', key: 'nombre_grupo' },
                { label: 'Capacidad Máxima', key: 'capacidad_maxima' },
                { label: '¿Inscripción Activa?', key: 'inscripcion_activa' }
            ],
            acciones: [
                {
                    url: 'editar_tipo_grupo_interes.php?id={id}',
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
        window.tablaGrupoInteres = new TablaDinamica(config);
    });

    // === FUNCIONES ===
    function confirmDelete(id) {
        Swal.fire({
            title: "¿Está seguro que desea eliminar este grupo_interes?",
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonText: "Sí, Eliminar",
            denyButtonText: "No, Volver"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../../controladores/GrupoInteresController.php?action=eliminar&id=' + id;
            } else if (result.isDenied) {
                Swal.fire("No se eliminó el grupo_interes", "", "info");
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
            'REPORTE DE GRUPOS DE INTERÉS DEL SISTEMA',
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