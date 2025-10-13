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
require_once __DIR__ . '/../../../config/conexion.php';

$database = new Database();
$conexion = $database->getConnection();

// === CONSULTA PRINCIPAL ===
$query = "
    SELECT 
        p.IdPersona,
        p.cedula,
        p.IdNacionalidad,
        n.nacionalidad,
        p.nombre,
        p.apellido,
        p.IdSexo,
        sx.sexo,
        niv.IdNivel,
        niv.nivel,
        c.IdCurso,
        c.curso,
        s.IdSeccion,
        s.seccion,
        fe.IdFecha_Escolar,
        fe.fecha_escolar AS anio_escolar,
        -- Agrupamos posibles múltiples tipos en una sola columna separada por coma
        GROUP_CONCAT(DISTINCT td.tipo_discapacidad SEPARATOR ', ') AS tipo_discapacidad
    FROM persona AS p
    INNER JOIN detalle_perfil AS dp ON p.IdPersona = dp.IdPersona
    INNER JOIN inscripcion AS i ON i.IdEstudiante = p.IdPersona
    INNER JOIN curso_seccion AS cs ON cs.IdCurso_Seccion = i.IdCurso_Seccion
    INNER JOIN curso AS c ON c.IdCurso = cs.IdCurso
    INNER JOIN nivel AS niv ON niv.IdNivel = c.IdNivel
    INNER JOIN seccion AS s ON s.IdSeccion = cs.IdSeccion
    INNER JOIN fecha_escolar AS fe ON fe.IdFecha_Escolar = i.IdFecha_Escolar
    LEFT JOIN nacionalidad AS n ON n.IdNacionalidad = p.IdNacionalidad
    LEFT JOIN sexo AS sx ON sx.IdSexo = p.IdSexo
    LEFT JOIN discapacidad AS d ON d.IdPersona = p.IdPersona
    LEFT JOIN tipo_discapacidad AS td ON td.IdTipo_Discapacidad = d.IdTipo_Discapacidad
    WHERE dp.IdPerfil = 3
    GROUP BY p.IdPersona
    ORDER BY niv.IdNivel, c.IdCurso, s.IdSeccion, p.apellido
";
$stmt = $conexion->prepare($query);
$stmt->execute();
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === FILTROS ===
$niveles = $conexion->query("SELECT IdNivel, nivel FROM nivel ORDER BY nivel ASC")->fetchAll(PDO::FETCH_ASSOC);
$cursos = $conexion->query("SELECT IdCurso, curso, IdNivel FROM curso ORDER BY IdNivel, curso ASC")->fetchAll(PDO::FETCH_ASSOC);
$secciones = $conexion->query("SELECT IdSeccion, seccion FROM seccion ORDER BY seccion ASC")->fetchAll(PDO::FETCH_ASSOC);
$tiposDiscapacidad = $conexion->query("SELECT IdTipo_Discapacidad, tipo_discapacidad FROM tipo_discapacidad ORDER BY tipo_discapacidad ASC")->fetchAll(PDO::FETCH_ASSOC);
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

                                <!-- Botón Nuevo Estudiante -->
                                <div class="mb-2">
                                    <a href="nuevo_estudiante.php" class="btn btn-danger d-flex align-items-center">
                                        <i class='bx bx-plus-medical me-1'></i> Nuevo Estudiante
                                    </a>
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