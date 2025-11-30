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
    header("Location: curso_seccion.php");
    exit();
} elseif (isset($_GET['success'])) {
    $_SESSION['alert'] = 'success';
    header("Location: curso_seccion.php");
    exit();
} elseif (isset($_GET['actualizar'])) {
    $_SESSION['alert'] = 'actualizar';
    header("Location: curso_seccion.php");
    exit();
} elseif (isset($_GET['error'])) {
    $_SESSION['alert'] = 'error';
    header("Location: curso_seccion.php");
    exit();
}

// Obtener alerta
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

// Mostrar alerta si existe
if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito("El curso/seccion se creó correctamente.");
            break;
        case 'actualizar':
            $alerta = Notificaciones::exito("El curso/seccion se actualizó correctamente.");
            break;
        case 'deleted':
            $alerta = Notificaciones::exito("El curso/seccion se eliminó correctamente.");
            break;
        case 'dependency_error':
            $alerta = Notificaciones::advertencia("No se puede eliminar el curso/seccion porque está siendo utilizado por una o más personas.");
            break;
        case 'error':
            $alerta = Notificaciones::advertencia("Este curso/seccion ya existe, verifique por favor.");
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
    <title>UECFT Araure - Curso/Seccion</title>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<?php
// Obtener curso/sección según permisos del usuario
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/CursoSeccion.php';

$database = new Database();
$conexion = $database->getConnection();
$cursoSeccionModel = new CursoSeccion($conexion);
$curso_seccions = $cursoSeccionModel->obtenerCursosSecciones($idPersona);
?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class='bx bxs-user-detail'></i> Gestión de Curso/Sección
                        </div>
                        <div class="card-body">
                            <!-- Botones de acción (intercambiados) -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <!-- Imprimir Lista a la izquierda -->
                                <button class="btn btn-imprimir d-flex align-items-center" onclick="imprimirLista()">
                                    <i class='bx bxs-file-pdf me-1'></i> Imprimir Lista
                                </button>
                                <!-- Nuevo Curso/Sección a la derecha -->
                                <a href="nuevo_curso_seccion.php" class="btn btn-danger d-flex align-items-center">
                                    <i class='bx bx-plus-medical me-1'></i> Nuevo Curso/Sección
                                </a>
                            </div>

                            <!-- Búsqueda y Entradas por página en la misma línea -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                <div class="flex-grow-1" style="max-width: 250px;">
                                    <input type="text" class="search-input" id="buscar" placeholder="Buscar...">
                                </div>

                                <!-- Filtros -->
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <label for="filtroNivel" class="fw-semibold mb-0">Nivel:</label>
                                    <select id="filtroNivel" class="form-select" style="width:auto;">
                                        <option value="">Todos</option>
                                    </select>

                                    <label for="filtroCurso" class="fw-semibold mb-0">Curso:</label>
                                    <select id="filtroCurso" class="form-select" style="width:auto;">
                                        <option value="">Todos</option>
                                    </select>

                                    <label for="filtroSeccion" class="fw-semibold mb-0">Sección:</label>
                                    <select id="filtroSeccion" class="form-select" style="width:auto;">
                                        <option value="">Todos</option>
                                    </select>
                                </div>

                                <div class="d-flex align-items-center">
                                    <label for="entries" class="me-2">Entradas:</label>
                                    <select id="entries" class="form-select" style="width: auto;">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Tabla -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="tabla-curso_seccions">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nivel</th>
                                            <th>Curso</th>
                                            <th>Sección</th>
                                            <th>Estudiantes</th>
                                            <th>Aula</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        <?php foreach ($curso_seccions as $user): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['IdCurso_Seccion']) ?></td>
                                                <td><?= htmlspecialchars($user['nivel'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($user['curso']) ?></td>
                                                <td><?= htmlspecialchars($user['seccion']) ?></td>
                                                <td><?= ($user['cantidad_estudiantes'] === 0) ? '0' : htmlspecialchars($user['cantidad_estudiantes']) ?></td>
                                                <td><?= htmlspecialchars($user['aula']) ?></td>
                                                <td>
                                                    <a href="editar_curso_seccion.php?id=<?= $user['IdCurso_Seccion'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                        <i class='bx bxs-edit'></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $user['IdCurso_Seccion'] ?>)">
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
    let allData = <?= json_encode($curso_seccions) ?>;
    let filteredData = [...allData];
    let currentPage = 1;
    let entriesPerPage = parseInt(document.getElementById('entries').value) || 10;

    // Inicialización de TablaDinamica
    document.addEventListener('DOMContentLoaded', function() {
        const config = {
            tablaId: 'tabla-curso_seccions',  // Coincide con tu HTML
            tbodyId: 'table-body',      // Coincide con tu HTML
            buscarId: 'buscar',         // Coincide con tu HTML
            entriesId: 'entries',       // Coincide con tu HTML
            paginationId: 'pagination', // Coincide con tu HTML
            data: allData,
            idField: 'IdCurso_Seccion',
            columns: [
                { label: 'ID', key: 'IdCurso_Seccion' },
                { label: 'Nivel', key: 'nivel' },
                { label: 'Curso', key: 'curso' },
                { label: 'Sección', key: 'seccion' },
                { label: 'Estudiantes', key: 'cantidad_estudiantes' },
                { label: 'Aula', key: 'aula' },
            ],
            acciones: [
                {
                    url: 'editar_curso_seccion.php?id={id}',
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

        // Crear instancia de TablaDinamica
        window.tablaCursoSeccion = new TablaDinamica(config);

        // === POBLAR FILTROS ===
        const filtroNivel = document.getElementById('filtroNivel');
        const filtroCurso = document.getElementById('filtroCurso');
        const filtroSeccion = document.getElementById('filtroSeccion');

        // Obtener valores únicos
        const nivelesUnicos = [...new Set(allData.map(item => item.nivel).filter(Boolean))];
        const cursosUnicos = [...new Set(allData.map(item => item.curso).filter(Boolean))];
        const seccionesUnicas = [...new Set(allData.map(item => item.seccion).filter(Boolean))];

        // Poblar select de niveles
        nivelesUnicos.sort().forEach(nivel => {
            const opt = document.createElement('option');
            opt.value = nivel;
            opt.textContent = nivel;
            filtroNivel.appendChild(opt);
        });

        // Poblar select de cursos
        cursosUnicos.sort().forEach(curso => {
            const opt = document.createElement('option');
            opt.value = curso;
            opt.textContent = curso;
            filtroCurso.appendChild(opt);
        });

        // Poblar select de secciones
        seccionesUnicas.sort().forEach(seccion => {
            const opt = document.createElement('option');
            opt.value = seccion;
            opt.textContent = seccion;
            filtroSeccion.appendChild(opt);
        });

        // === FUNCIÓN DE FILTROS ===
        function aplicarFiltros() {
            const nivelVal = filtroNivel.value.trim();
            const cursoVal = filtroCurso.value.trim();
            const seccionVal = filtroSeccion.value.trim();

            const filtered = allData.filter(item => {
                let matchNivel = true;
                if (nivelVal) {
                    matchNivel = item.nivel === nivelVal;
                }

                let matchCurso = true;
                if (cursoVal) {
                    matchCurso = item.curso === cursoVal;
                }

                let matchSeccion = true;
                if (seccionVal) {
                    matchSeccion = item.seccion === seccionVal;
                }

                return matchNivel && matchCurso && matchSeccion;
            });

            window.tablaCursoSeccion.updateData(filtered);
        }

        // === LISTENERS DE FILTROS ===
        filtroNivel.addEventListener('change', function() {
            // Actualizar opciones de curso según nivel seleccionado
            const nivelSeleccionado = this.value;
            filtroCurso.innerHTML = '<option value="">Todos</option>';

            let cursosFiltrados = allData;
            if (nivelSeleccionado) {
                cursosFiltrados = allData.filter(item => item.nivel === nivelSeleccionado);
            }

            const cursosDelNivel = [...new Set(cursosFiltrados.map(item => item.curso).filter(Boolean))];
            cursosDelNivel.sort().forEach(curso => {
                const opt = document.createElement('option');
                opt.value = curso;
                opt.textContent = curso;
                filtroCurso.appendChild(opt);
            });

            aplicarFiltros();
        });

        filtroCurso.addEventListener('change', aplicarFiltros);
        filtroSeccion.addEventListener('change', aplicarFiltros);
    });

    // === FUNCIONES ===
    function confirmDelete(id) {
        Swal.fire({
            title: "¿Está seguro que desea eliminar este curso_seccion?",
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonText: "Sí, Eliminar",
            denyButtonText: "No, Volver"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../../controladores/CursoSeccionController.php?action=eliminar&id=' + id;
            } else if (result.isDenied) {
                Swal.fire("No se eliminó el curso_seccion", "", "info");
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
            'REPORTE DE SECCIONES DEL SISTEMA',
            '#tabla-curso_seccions',
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