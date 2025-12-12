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

require_once __DIR__ . '/../../../controladores/Notificaciones.php';

// Manejo de alertas por GET
if (isset($_GET['deleted'])) {
    $_SESSION['alert'] = 'deleted';
    header("Location: inscripcion_grupo_interes.php");
    exit();
} elseif (isset($_GET['success'])) {
    $_SESSION['alert'] = 'success';
    header("Location: inscripcion_grupo_interes.php");
    exit();
} elseif (isset($_GET['actualizar'])) {
    $_SESSION['alert'] = 'actualizar';
    header("Location: inscripcion_grupo_interes.php");
    exit();
} elseif (isset($_GET['error'])) {
    $_SESSION['alert'] = 'error';
    header("Location: inscripcion_grupo_interes.php");
    exit();
}

// Obtener alerta de sesión
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

// Mostrar alerta si existe
if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito("Inscripción realizada correctamente.");
            break;
        case 'actualizar':
            $alerta = Notificaciones::exito("Inscripción actualizada correctamente.");
            break;
        case 'deleted':
            $alerta = Notificaciones::exito("Inscripción eliminada correctamente.");
            break;
        case 'error':
            $alerta = Notificaciones::error($_SESSION['message'] ?? "Ocurrió un error.");
            unset($_SESSION['message']);
            break;
        default:
            $alerta = null;
    }
    if ($alerta) Notificaciones::mostrar($alerta);
}

// Obtener datos
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/InscripcionGrupoInteres.php';

$database = new Database();
$db = $database->getConnection();
$inscripcionModel = new InscripcionGrupoInteres($db);
$inscripciones = $inscripcionModel->obtenerTodos(); // Trae nombre_grupo (Tipo), nivel, curso, etc.
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>UECFT Araure - Inscripciones Grupos de Interés</title>
</head>
<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class='bx bxs-user-check'></i> Inscripciones de Grupos de Interés
                        </div>
                        <div class="card-body">
                            <!-- Botones de acción -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <button class="btn btn-imprimir d-flex align-items-center" onclick="imprimirLista()">
                                    <i class='bx bxs-file-pdf me-1'></i> Imprimir Lista
                                </button>
                                <a href="nuevo_inscripcion_gi.php" class="btn btn-danger d-flex align-items-center">
                                    <i class='bx bx-plus-medical me-1'></i> Nueva Inscripción
                                </a>
                            </div>

                            <!-- Filtros -->
                            <div class="d-flex flex-wrap align-items-center mb-3 gap-3 p-3 bg-light rounded">
                                <span class="fw-bold text-secondary"><i class='bx bx-filter-alt'></i> Filtros:</span>
                                
                                <div class="d-flex align-items-center gap-2">
                                    <label for="filtroNivel" class="mb-0 fw-semibold">Nivel:</label>
                                    <select id="filtroNivel" class="form-select form-select-sm" style="width:150px;">
                                        <option value="">Todos</option>
                                    </select>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <label for="filtroCurso" class="mb-0 fw-semibold">Curso:</label>
                                    <select id="filtroCurso" class="form-select form-select-sm" style="width:150px;">
                                        <option value="">Todos</option>
                                    </select>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <label for="filtroTipo" class="mb-0 fw-semibold">Tipo Grupo:</label>
                                    <select id="filtroTipo" class="form-select form-select-sm" style="width:200px;">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Búsqueda y Paginación -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                <div class="flex-grow-1" style="max-width: 300px;">
                                    <input type="text" class="search-input" id="buscar" placeholder="Buscar estudiante, cédula...">
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
                                            <th>ID</th>
                                            <th>Estudiante</th>
                                            <th>Cédula</th>
                                            <th>Tipo Grupo</th>
                                            <th>Nivel</th>
                                            <th>Curso</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        <!-- Se llena vía JS -->
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
<!-- Reportes Script (ajustar path si necesario, en aula es assets/js/reportes.js) -->
<script src="../../../assets/js/reportes.js"></script>

<script>
    // === DATOS GLOBALES ===
    let allData = <?= json_encode($inscripciones) ?>;
    
    // Preparar datos (concatenar nombre completo para facilidad)
    allData = allData.map(item => ({
        ...item,
        nombreCompleto: `${item.nombre} ${item.apellido}`,
        // Normalizar valores nulos
        nivel: item.nivel || 'Sin Nivel',
        curso: item.curso || 'Sin Curso',
        nombre_grupo: item.nombre_grupo || 'Sin Tipo'
    }));

    // Inicialización
    document.addEventListener('DOMContentLoaded', function() {
        const config = {
            tablaId: 'tabla-inscripciones',
            tbodyId: 'table-body',
            buscarId: 'buscar',
            entriesId: 'entries',
            paginationId: 'pagination',
            data: allData,
            idField: 'IdInscripcion_Grupo',
            columns: [
                { label: 'ID', key: 'IdInscripcion_Grupo' },
                { label: 'Estudiante', key: 'nombreCompleto' },
                { label: 'Cédula', key: 'cedula' },
                { label: 'Tipo Grupo', key: 'nombre_grupo' },
                { label: 'Nivel', key: 'nivel' },
                { label: 'Curso', key: 'curso' }
            ],
            acciones: [
                {
                    url: 'editar_inscripcion_gi.php?id={id}',
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

        window.tablaInscripciones = new TablaDinamica(config);

        // === POBLAR FILTROS ===
        const filtroNivel = document.getElementById('filtroNivel');
        const filtroCurso = document.getElementById('filtroCurso');
        const filtroTipo = document.getElementById('filtroTipo');

        // Función helper para poblar selects
        function poblarSelect(selectElement, key) {
            const values = [...new Set(allData.map(item => item[key]).filter(val => val && val !== 'Sin Nivel' && val !== 'Sin Curso' && val !== 'Sin Tipo'))];
            values.sort().forEach(val => {
                const opt = document.createElement('option');
                opt.value = val;
                opt.textContent = val;
                selectElement.appendChild(opt);
            });
        }

        poblarSelect(filtroNivel, 'nivel');
        poblarSelect(filtroCurso, 'curso');
        poblarSelect(filtroTipo, 'nombre_grupo');

        // === LÓGICA DE FILTRADO ===
        function aplicarFiltros() {
            const nivelVal = filtroNivel.value;
            const cursoVal = filtroCurso.value;
            const tipoVal = filtroTipo.value;

            const filtered = allData.filter(item => {
                const matchNivel = !nivelVal || item.nivel === nivelVal;
                const matchCurso = !cursoVal || item.curso === cursoVal;
                const matchTipo = !tipoVal || item.nombre_grupo === tipoVal;
                
                return matchNivel && matchCurso && matchTipo;
            });

            window.tablaInscripciones.updateData(filtered);
        }

        filtroNivel.addEventListener('change', aplicarFiltros);
        filtroCurso.addEventListener('change', aplicarFiltros);
        filtroTipo.addEventListener('change', aplicarFiltros);
    });

    // === ELIMINAR ===
    function confirmDelete(id) {
        Swal.fire({
            title: "¿Está seguro de eliminar esta inscripción?",
            text: "Esta acción liberará al estudiante del grupo.",
            showDenyButton: true,
            confirmButtonText: "Sí, Eliminar",
            denyButtonText: "Cancelar",
            confirmButtonColor: "#c90000",
            denyButtonColor: "#6c757d"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../../controladores/InscripcionGrupoInteresController.php?action=eliminar&id=' + id;
            }
        });
    }

    // === IMPRIMIR ===
    window.imprimirLista = function() {
        const usuarioNombre = "<?php 
            echo htmlspecialchars(
                $_SESSION['nombre_completo'] ?? 
                ($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?? 
                'Usuario'
            ); 
        ?>";
        
        generarReporteImprimible(
            'LISTADO DE INSCRIPCIONES - GRUPOS DE INTERÉS',
            '#tabla-inscripciones',
            {
                logoUrl: '../../../assets/images/fermin.png',
                colorPrincipal: '#c90000',
                usuario: usuarioNombre,
                subtitulo: 'Año Escolar Activo'
            }
        );
    };
</script>

</body>
</html>
