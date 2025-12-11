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
                            <i class='bx bxs-user-detail'></i> Gestión de Grupo de Interés
                        </div>
                        <div class="card-body">
                            <!-- Botones de acción -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <!-- Imprimir y Botones de masa a la izquierda -->
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-imprimir d-flex align-items-center" onclick="imprimirLista()">
                                        <i class='bx bxs-file-pdf me-1'></i> Imprimir Lista
                                    </button>
                                    <button class="btn btn-outline-success d-flex align-items-center" onclick="activarTodas()">
                                        <i class='bx bx-check-circle me-1'></i> Activar Todos
                                    </button>
                                    <button class="btn btn-outline-secondary d-flex align-items-center" onclick="desactivarTodas()">
                                        <i class='bx bx-block me-1'></i> Desactivar Todos
                                    </button>
                                </div>
                                <!-- Nuevo Grupo a la derecha -->
                                <a href="nuevo_tipo_grupo_interes.php" class="btn btn-danger d-flex align-items-center">
                                    <i class='bx bx-plus-medical me-1'></i> Nuevo Grupo
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
                                                <td data-search="<?= $user['inscripcion_activa'] == 1 ? 'Sí' : 'No' ?>" class="text-center">
                                                    <div class="text-center">
                                                        <label class="toggle-label" title="Activar/desactivar inscripciones">
                                                            <span class="toggle-text"><?= $user['inscripcion_activa'] == 1 ? 'Sí' : 'No' ?></span>
                                                            <div class="toggle-container">
                                                                <input 
                                                                    type="checkbox" 
                                                                    class="toggle-input toggle-inscripcion" 
                                                                    data-id="<?= $user['IdTipo_Grupo'] ?>"
                                                                    <?= $user['inscripcion_activa'] == 1 ? 'checked' : '' ?>
                                                                >
                                                                <span class="toggle-slider"></span>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </td>
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

    allData = allData.map(item => {
        const toggleChecked = item.inscripcion_activa == 1 ? 'checked' : '';
        const inscripcionHTML = `
            <div class="text-center">
                <label class="toggle-label" title="Activar/desactivar inscripciones">
                    <span class="toggle-text">${item.inscripcion_activa == 1 ? 'Sí' : 'No'}</span>
                    <div class="toggle-container">
                        <input 
                            type="checkbox" 
                            class="toggle-input toggle-inscripcion" 
                            data-id="${item.IdTipo_Grupo}"
                            ${toggleChecked}
                        >
                        <span class="toggle-slider"></span>
                    </div>
                </label>
            </div>
        `;

        return {
            ...item,
            inscripcion_activa_text: item.inscripcion_activa == 1 ? 'Sí' : 'No',
            inscripcion_activa: inscripcionHTML
        };
    });

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

        inicializarToggleInscripcion();
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
                window.location.href = '../../../controladores/TipoGrupoInteresController.php?action=eliminar&id=' + id;
            } else if (result.isDenied) {
                Swal.fire("No se eliminó el grupo_interes", "", "info");
            }
        });
    }

    // === INICIALIZAR TOGGLE DE INSCRIPCIÓN ===
    function inicializarToggleInscripcion() {
        document.addEventListener('change', async function(e) {
            // Solo reacciona si es un toggle de inscripción
            if (!e.target.matches('.toggle-inscripcion')) return;

            const toggle = e.target;
            const id = toggle.dataset.id;
            const nuevoEstado = toggle.checked ? 1 : 0;

            try {
                const response = await fetch('../../../controladores/TipoGrupoInteresController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle_inscripcion&id=${id}&estado=${nuevoEstado}`
                });

                const data = await response.json();

                if (data.success) {
                    // 1. Actualizar texto del toggle
                    const labelText = toggle.closest('.toggle-label').querySelector('.toggle-text');
                    labelText.textContent = nuevoEstado ? 'Sí' : 'No';

                    // 2. Actualizar datos locales
                    allData = allData.map(item => {
                        if (item.IdTipo_Grupo == id) {
                            const html = `
                                <div class="text-center">
                                    <label class="toggle-label" title="Activar/desactivar inscripciones">
                                        <span class="toggle-text">${nuevoEstado ? 'Sí' : 'No'}</span>
                                        <div class="toggle-container">
                                            <input 
                                                type="checkbox" 
                                                class="toggle-input toggle-inscripcion" 
                                                data-id="${id}"
                                                ${nuevoEstado ? 'checked' : ''}
                                            >
                                            <span class="toggle-slider"></span>
                                        </div>
                                    </label>
                                </div>
                            `;
                            return {
                                ...item,
                                inscripcion_activa: html,
                                inscripcion_activa_text: nuevoEstado ? 'Sí' : 'No'
                            };
                        }
                        return item;
                    });

                    // 3. Refrescar tabla
                    window.tablaGrupoInteres.updateData(allData);

                    // 4. Mostrar notificación
                    Swal.fire({
                        icon: 'success',
                        title: '¡Listo!',
                        text: `Inscripciones ${nuevoEstado ? 'activadas' : 'desactivadas'}.`,
                        showConfirmButton: false,
                        timer: 1500,
                        toast: true,
                        position: 'top-end',
                        background: '#f8f9fa',
                        timerProgressBar: true
                    });
                } else {
                    throw new Error(data.message || 'Error al actualizar');
                }
            } catch (error) {
                // Revertir el estado del toggle
                toggle.checked = !nuevoEstado;

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                });
            }
        });
    }

    // === ACTIVAR/DESACTIVAR TODAS LAS INSCRIPCIONES ===
    async function activarTodas() {
        Swal.fire({
            title: '¿Activar todas las inscripciones?',
            text: 'Esta acción activará la inscripción en todos los grupos de interés.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, activar todas',
            cancelButtonText: 'Cancelar',
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch('../../../controladores/TipoGrupoInteresController.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=toggle_todas&estado=1'
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Actualizar todos los toggles localmente
                        allData = allData.map(item => {
                            const html = `
                                <div class="text-center">
                                    <label class="toggle-label" title="Activar/desactivar inscripciones">
                                        <span class="toggle-text">Sí</span>
                                        <div class="toggle-container">
                                            <input 
                                                type="checkbox" 
                                                class="toggle-input toggle-inscripcion" 
                                                data-id="${item.IdTipo_Grupo}"
                                                checked
                                            >
                                            <span class="toggle-slider"></span>
                                        </div>
                                    </label>
                                </div>
                            `;
                            return {
                                ...item,
                                inscripcion_activa: html,
                                inscripcion_activa_text: 'Sí'
                            };
                        });

                        // Refrescar tabla
                        window.tablaGrupoInteres.updateData(allData);

                        Swal.fire({
                            icon: 'success',
                            title: '¡Listo!',
                            text: 'Todas las inscripciones fueron activadas.',
                            showConfirmButton: false,
                            timer: 1800,
                            toast: true,
                            position: 'top-end',
                            background: '#f8f9fa',
                            timerProgressBar: true
                        });
                    } else {
                        throw new Error(data.message || 'Error al activar');
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message,
                    });
                }
            }
        });
    }

    async function desactivarTodas() {
        Swal.fire({
            title: '¿Desactivar todas las inscripciones?',
            text: 'Esta acción desactivará la inscripción en todos los grupos de interés.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, desactivar todas',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch('../../../controladores/TipoGrupoInteresController.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=toggle_todas&estado=0'
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Actualizar todos los toggles localmente
                        allData = allData.map(item => {
                            const html = `
                                <div class="text-center">
                                    <label class="toggle-label" title="Activar/desactivar inscripciones">
                                        <span class="toggle-text">No</span>
                                        <div class="toggle-container">
                                            <input 
                                                type="checkbox" 
                                                class="toggle-input toggle-inscripcion" 
                                                data-id="${item.IdTipo_Grupo}"
                                            >
                                            <span class="toggle-slider"></span>
                                        </div>
                                    </label>
                                </div>
                            `;
                            return {
                                ...item,
                                inscripcion_activa: html,
                                inscripcion_activa_text: 'No'
                            };
                        });

                        // Refrescar tabla
                        window.tablaGrupoInteres.updateData(allData);

                        Swal.fire({
                            icon: 'success',
                            title: '¡Listo!',
                            text: 'Todas las inscripciones fueron desactivadas.',
                            showConfirmButton: false,
                            timer: 1800,
                            toast: true,
                            position: 'top-end',
                            background: '#f8f9fa',
                            timerProgressBar: true
                        });
                    } else {
                        throw new Error(data.message || 'Error al desactivar');
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message,
                    });
                }
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