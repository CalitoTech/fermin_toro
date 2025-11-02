<?php
session_start();

// ========================
// 🔒 Verificación de sesión
// ========================
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

// ========================
// 📦 Dependencias
// ========================
require_once __DIR__ . '/../../../controladores/Notificaciones.php';
require_once __DIR__ . '/../../../config/conexion.php';

$database = new Database();
$conexion = $database->getConnection();

// ========================
// ⚠️ Manejo de alertas (GET → sesión temporal)
// ========================
if (isset($_GET['deleted'])) $_SESSION['alert'] = 'deleted';
elseif (isset($_GET['success'])) $_SESSION['alert'] = 'success';
elseif (isset($_GET['actualizar'])) $_SESSION['alert'] = 'actualizar';
elseif (isset($_GET['error'])) $_SESSION['alert'] = 'error';

// Guardar y limpiar alerta
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']); // 🔹 Limpieza inmediata

// Mostrar alerta si existe
if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito("El usuario se creó correctamente.");
            break;
        case 'actualizar':
            $alerta = Notificaciones::exito("El usuario se actualizó correctamente.");
            break;
        case 'deleted':
            $alerta = Notificaciones::exito("El usuario se eliminó correctamente.");
            break;
        case 'error':
            $alerta = Notificaciones::advertencia("Este usuario ya existe, verifique por favor.");
            break;
        default:
            $alerta = null;
    }

    if ($alerta) Notificaciones::mostrar($alerta);
}

// ========================
// 👥 Consulta de usuarios con perfil
// ========================
$query = "
    SELECT 
        p.IdPersona,
        p.nombre,
        p.apellido,
        p.usuario,
        p.correo,
        pf.IdPerfil,
        pf.nombre_perfil
    FROM persona AS p
    INNER JOIN detalle_perfil AS dp ON dp.IdPersona = p.IdPersona
    INNER JOIN perfil AS pf ON pf.IdPerfil = dp.IdPerfil
    WHERE p.usuario IS NOT NULL AND p.password IS NOT NULL
    ORDER BY p.nombre, p.apellido
";
$stmt = $conexion->prepare($query);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<head>
    <title>UECFT Araure - Usuarios</title>
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
                            <i class='bx bxs-user-detail'></i> Gestión de Usuarios
                        </div>
                        <div class="card-body">

                            <!-- 🔹 Botones Superiores -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <button class="btn btn-imprimir d-flex align-items-center" onclick="imprimirLista()">
                                    <i class='bx bxs-file-pdf me-1'></i> Imprimir Lista
                                </button>
                                <a href="nuevo_usuario.php" class="btn btn-danger d-flex align-items-center">
                                    <i class='bx bx-plus-medical me-1'></i> Nuevo Usuario
                                </a>
                            </div>

                            <!-- 🔹 Filtros y Búsqueda -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                
                                <!-- 🔸 Buscador -->
                                <div class="flex-grow-1" style="max-width: 250px;">
                                    <input type="text" class="search-input" id="buscar" placeholder="Buscar...">
                                </div>
                                
                                <!-- 🔸 Filtro tipo de usuario -->
                                <div class="d-flex align-items-center gap-2">
                                    <label for="filtroTipo" class="fw-semibold mb-0">Tipo de Usuario:</label>
                                    <select id="filtroTipo" class="form-select" style="width:auto;">
                                        <option value="todos">Todos</option>
                                        <option value="internos">Usuarios Internos</option>
                                        <option value="representantes">Representantes</option>
                                    </select>
                                </div>

                                

                                <!-- 🔸 Entradas -->
                                <div class="d-flex align-items-center">
                                    <label for="entries" class="me-2">Entradas por página:</label>
                                    <select id="entries" class="form-select" style="width: auto;">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                            </div>

                            <!-- 🔹 Tabla -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="tabla-usuarios">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre Completo</th>
                                            <th>Usuario</th>
                                            <th>Correo</th>
                                            <th>Perfil</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        <?php foreach ($usuarios as $user): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['IdPersona']) ?></td>
                                                <td><?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?></td>
                                                <td><?= htmlspecialchars($user['usuario']) ?></td>
                                                <td><?= htmlspecialchars($user['correo']) ?></td>
                                                <td><?= htmlspecialchars($user['nombre_perfil']) ?></td>
                                                <td>
                                                    <a href="editar_usuario.php?id=<?= $user['IdPersona'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                        <i class='bx bxs-edit'></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $user['IdPersona'] ?>)">
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
    let allData = <?= json_encode($usuarios) ?>;
    let filteredData = [...allData];

    document.addEventListener('DOMContentLoaded', function() {
        const config = {
            tablaId: 'tabla-usuarios',
            tbodyId: 'table-body',
            buscarId: 'buscar',
            entriesId: 'entries',
            paginationId: 'pagination',
            data: allData.map(item => ({
                ...item,
                nombreCompleto: `${item.nombre} ${item.apellido}`,
                tipo: (item.IdPerfil == 4 || item.IdPerfil == 5) ? 'representantes' : 'internos'
            })),
            idField: 'IdPersona',
            columns: [
                { label: 'ID', key: 'IdPersona' },
                { label: 'Nombre Completo', key: 'nombreCompleto' },
                { label: 'Usuario', key: 'usuario' },
                { label: 'Correo', key: 'correo' },
                { label: 'Perfil', key: 'nombre_perfil' }
            ],
            acciones: [
                {
                    url: 'editar_usuario.php?id={id}',
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

        window.tablaUsuarios = new TablaDinamica(config);

        // === FILTROS ===
        const filtroTipo = document.getElementById('filtroTipo');
        const filtroBuscar = document.getElementById('buscar');
        const filtroEntries = document.getElementById('entries');

        function aplicarFiltros() {
            const tipoVal = filtroTipo.value.trim().toLowerCase();
            const textoBuscar = filtroBuscar.value.trim().toLowerCase();

            const filtrados = config.data.filter(item => {
                let matchTipo = true;
                if (tipoVal !== 'todos')
                    matchTipo = item.tipo === tipoVal;

                let matchBuscar = true;
                if (textoBuscar) {
                    const combo = `${item.nombreCompleto} ${item.usuario} ${item.correo} ${item.nombre_perfil}`.toLowerCase();
                    matchBuscar = combo.includes(textoBuscar);
                }

                return matchTipo && matchBuscar;
            });

            window.tablaUsuarios.updateData(filtrados);
        }

        filtroTipo.addEventListener('change', aplicarFiltros);
        filtroBuscar.addEventListener('input', aplicarFiltros);
        filtroEntries.addEventListener('change', aplicarFiltros);

        aplicarFiltros();
    });

    function confirmDelete(id) {
        Swal.fire({
            title: "¿Está seguro que desea eliminar este usuario?",
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonText: "Sí, Eliminar",
            denyButtonText: "No, Volver"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../../controladores/PersonaController.php?action=eliminar&id=' + id;
            } else if (result.isDenied) {
                Swal.fire("No se eliminó el usuario", "", "info");
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
            'REPORTE DE USUARIOS DEL SISTEMA',
            '#tabla-usuarios',
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