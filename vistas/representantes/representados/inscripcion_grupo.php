<?php
session_start();

// --- DEPENDENCIAS ---
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Persona.php';
require_once __DIR__ . '/../../../modelos/GrupoInteres.php';
require_once __DIR__ . '/../../../modelos/FechaEscolar.php';
require_once __DIR__ . '/../../../modelos/Inscripcion.php';
require_once __DIR__ . '/../../../modelos/InscripcionGrupoInteres.php';

// --- VERIFICACIÓN DE SESIÓN ---
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    header("Location: ../../login/login.php");
    exit();
}

$idPersona = $_SESSION['idPersona']; // Representante
$idEstudiante = $_GET['id'] ?? null;

if (!$idEstudiante) {
    header("Location: representado.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// --- DATOS ESTUDIANTE ---
$personaModel = new Persona($db);
$estudiante = $personaModel->obtenerEstudiantePorId($idEstudiante);

if (!$estudiante) {
    // Si no existe, volver
    header("Location: representado.php");
    exit();
}

// Obtener Curso Actual del Estudiante
$inscripcionModel = new Inscripcion($db);
// Usamos lógica similar a buscarEstudiantesActivos para saber su curso en el año activo
// O mejor, obtenemos el año escolar activo primero
$fechaModel = new FechaEscolar($db);
$fechaActiva = $fechaModel->obtenerActivo();
$idFechaEscolar = $fechaActiva ? $fechaActiva['IdFecha_Escolar'] : 0;

if ($idFechaEscolar == 0) {
    // No hay año escolar activo
    $mensaje = "No hay año escolar activo.";
    $gruposDisponibles = [];
} else {
    // Obtener sección/curso actual
    $datosAcademicos = $personaModel->obtenerSeccionActualEstudiante($idEstudiante, $idFechaEscolar);
    
    // Obtener GRUPOS del año escolar activo
    $grupoModel = new GrupoInteres($db);
    $todosGruposAndInscripciones = $grupoModel->obtenerPorFechaEscolar($idFechaEscolar);

    // FILTRAR: 
    // 1. Que coincidan con el curso del estudiante (si existe dato académico)
    // 2. Que el Tipo de Grupo tenga inscripcion_activa = 1
    // 3. (Opcional) Que no esté lleno? User dijo mostrar cantidad, no ocultar.
    
    // Obtener inscripciones actuales del estudiante en grupos de interés (para saber si ya tiene uno)
    $inscripcionGIModel = new InscripcionGrupoInteres($db);
    // Necesitamos un método para ver si el estudiante X tiene grupo en el año Y.
    // Usaremos "obtenerEstudiantesConGrupo" como base o mejor hacemos una query directa aqui, 
    // pero lo ideal es usar el modelo si tuviera un metodo específico.
    // Vamos a buscar grupos donde el estudiante esté inscrito en este año.
    // Como el modelo InscripcionGrupoInteres no tiene un 'obtenerPorEstudianteYAnio', 
    // iteraremos sobre los grupos traídos (obtenerPorFechaEscolar trae todos los grupos del año)
    // Para cada grupo, verificaremos si el estudiante está inscrito.
    
    // Mejor optimización: Traer todos los grupos donde el estudiante está inscrito en este año.
    $queryInscrito = "SELECT igi.IdGrupo_Interes FROM inscripcion_grupo_interes igi 
                      INNER JOIN grupo_interes gi ON igi.IdGrupo_Interes = gi.IdGrupo_Interes 
                      WHERE igi.IdEstudiante = :idEstudiante AND gi.IdFecha_Escolar = :idFecha";
    $stmtInscrito = $db->prepare($queryInscrito);
    $stmtInscrito->bindParam(':idEstudiante', $idEstudiante);
    $stmtInscrito->bindParam(':idFecha', $idFechaEscolar);
    $stmtInscrito->execute();
    $gruposInscritosIds = $stmtInscrito->fetchAll(PDO::FETCH_COLUMN); // Array de IDs de grupo
    
    $tieneGrupoActivo = !empty($gruposInscritosIds);

    $cursoEstudiante = $datosAcademicos['curso'] ?? ''; // Nombre del curso, e.g. "1er Año"
    
    $gruposDisponibles = [];
    foreach ($todosGruposAndInscripciones as $grupo) {
        // Verificar inscripción en este grupo específico
        $enGrupo = in_array($grupo['IdGrupo_Interes'], $gruposInscritosIds);
        
        // Asignar flag al array del grupo para usarlo en la vista
        $grupo['en_grupo'] = $enGrupo;
        // Verificar si el tipo de grupo tiene inscripción activa
        // Necesitamos saber si 'inscripcion_activa' viene en 'obtenerPorFechaEscolar'.
        // Revisando GrupoInteres.php, hace JOIN con tipo_grupo_interes pero no selecciona 'inscripcion_activa' explícitamente en la consulta original,
        // pero selecciona 'tg.nombre_grupo', 'tg.descripcion'.
        // Debo verificar si el SELECT * de gc (grupo_interes) o el join trae el flag.
        // Voy a asumir que necesito verificarlo. Si no está, lo ideal es agregarlo al modelo, pero user dijo "no funcionalidad".
        // Sin embargo, puedo hacer una consulta extra o confiar en que 'obtenerPorFechaEscolar' trae lo necesario.
        // Espera, el query en GrupoInteres.php es:
        // SELECT gc.*, tg.nombre_grupo, tg.descripcion, ... FROM ...
        // NO selecciona tg.inscripcion_activa. 
        // Haré una consulta pequeña manual aquí para filtrar o asumiré que debo modificar el modelo?
        // El user dijo "si hay al menos un grupo de interes activo para inscripcion o no en la tabla tipo_grupo_interes", eso era para el botón.
        // Aquí debo listar. 
        // Voy a modificar el query dentro del PHP aquí para obtener 'inscripcion_activa' haciendo una query auxiliar rápida o
        // asumiendo que todos los listados se mostrarán y el usuario decidirá, pero la instrucción dice "listara todos los grupos de interes que acepten inscripciones".
        // Mejor agregaré 'tg.inscripcion_activa' al Modelo GrupoInteres.php rápidamente?
        // No, mejor hago un fetch extra aquí por cada grupo o traigo los tipos activos y cruzo.
        
        // Fetch tipo info separately to be safe without touching model again if not strictly needed?
        // Actually, let's just create a quick query here to fetch IDs of ACTIVE group types.
        
        $sqlCheckActive = "SELECT inscripcion_activa, capacidad_maxima FROM tipo_grupo_interes WHERE IdTipo_Grupo = :idTipo";
        $stmtCheck = $db->prepare($sqlCheckActive);
        $stmtCheck->bindParam(':idTipo', $grupo['IdTipo_Grupo']);
        $stmtCheck->execute();
        $tipoInfo = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($tipoInfo && $tipoInfo['inscripcion_activa'] == 1) {
            // Validar Curso
            if ($grupo['curso'] == $cursoEstudiante) {
                $grupo['capacidad_real'] = $tipoInfo['capacidad_maxima']; // User mentioned fetching max attributes
                $gruposDisponibles[] = $grupo;
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Inscripción Grupo de Interés | UECFT Araure</title>
    <style>
        .page-header {
            background: linear-gradient(135deg, #c90000 0%, #8b0000 100%);
            color: white;
            padding: 2.5rem 2rem;
            border-radius: 0 0 50% 50% / 20px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 4px 12px rgba(201, 0, 0, 0.2);
        }

        .student-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            margin-top: 1rem;
            backdrop-filter: blur(5px);
        }

        .student-badge i {
            margin-right: 0.5rem;
        }
        
        .group-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .group-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(201, 0, 0, 0.15);
        }
        
        .group-header {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            position: relative;
        }
        
        .group-title {
            color: #c90000;
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        
        .group-body {
            padding: 1.5rem;
            flex-grow: 1;
        }
        
        .stat-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 0.8rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        
        .badge-enrolled {
            background-color: #e3f2fd;
            color: #0d47a1;
        }
        
        .badge-capacity {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }

        .progress-container {
            margin: 1.5rem 0;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
        }
        
        .progress-bar {
            background-color: #c90000;
            border-radius: 5px;
        }
        
        /* Updated Button Styles */
        .btn-enroll-action {
            width: 100%;
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            background: #c90000;
            color: #ffffff;
            border: 2px solid #c90000;
            box-shadow: 0 4px 6px rgba(201, 0, 0, 0.2);
        }
        
        .btn-enroll-action:hover {
            background: #a00000;
            border-color: #a00000;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(201, 0, 0, 0.3);
        }

        .btn-change-action {
            width: 100%;
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            background: #ffffff;
            color: #ffc107;
            border: 2px solid #ffc107;
        }

        .btn-change-action:hover {
            background: #ffc107;
            color: #000000;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(255, 193, 7, 0.3);
        }

        .btn-enrolled {
            width: 100%;
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: #ffffff !important;
            border: none;
            cursor: default;
            box-shadow: 0 4px 6px rgba(40, 167, 69, 0.3);
            opacity: 1;
        }
        .btn-enrolled:disabled {
             opacity: 1;
             color: white; 
        }

        .btn-full {
             width: 100%;
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 600;
            background: #e9ecef;
            color: #6c757d;
            border: 1px solid #ced4da;
            cursor: not-allowed;
        }

        .description-text {
            color: #555;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #666;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1rem;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #c90000;
        }

        /* Full status */
        .status-full {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #dc3545;
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 700;
            text-transform: uppercase;
        }
    </style>
</head>
<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<section class="home-section">
    <div class="main-content">
        
        <div class="page-header">
            <div class="container">
                <h1 class="mb-2 fw-bold">Inscripción a Grupos de Interés</h1>
                <p class="mb-0 text-white-50">Año Escolar <?= $fechaActiva['fecha_escolar'] ?? '' ?></p>
                <div class="student-badge">
                    <i class='bx bxs-user-circle'></i>
                    <?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?>
                    <span class="mx-2">|</span>
                    <?= htmlspecialchars($datosAcademicos['curso'] ?? 'Sin curso asignado') ?>
                </div>
            </div>
        </div>

        <div class="container pb-5">
            <a href="representado.php" class="back-link">
                <i class='bx bx-arrow-back me-1'></i> Volver
            </a>

            <?php if (empty($gruposDisponibles)): ?>
                <div class="text-center py-5">
                    <img src="../../../assets/images/no-results.svg" alt="Sin grupos" style="width: 150px; opacity: 0.5;" onerror="this.style.display='none'">
                    <div class="mt-4">
                        <i class='bx bx-folder-open' style="font-size: 4rem; color: #ddd;"></i>
                    </div>
                    <h3 class="mt-3 text-secondary">No hay grupos disponibles</h3>
                    <p class="text-muted">
                        No se encontraron grupos de interés activos para el curso 
                        <strong><?= htmlspecialchars($datosAcademicos['curso'] ?? '') ?></strong>.
                    </p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($gruposDisponibles as $grupo): 
                        $inscritos = $grupo['total_estudiantes'] ?? 0;
                        $capacidad = $grupo['capacidad_real'] ?? 35; // Default or fetched
                        $porcentaje = ($capacidad > 0) ? min(100, round(($inscritos / $capacidad) * 100)) : 0;
                        $lleno = $inscritos >= $capacidad;
                        $enGrupo = $grupo['en_grupo'] ?? false; // Recuperar flag del array
                    ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="group-card <?= $enGrupo ? 'border-success' : '' ?>" 
                                 data-group-id="<?= $grupo['IdGrupo_Interes'] ?>"
                                 style="<?= $enGrupo ? 'border: 2px solid #28a745; box-shadow: 0 8px 25px rgba(40, 167, 69, 0.15);' : '' ?>">
                                <div class="group-header">
                                    <h3 class="group-title"><?= htmlspecialchars($grupo['nombre_grupo']) ?></h3>
                                    <div class="text-muted small">
                                        <i class='bx bx-user-voice me-1'></i> Prof. <?= htmlspecialchars($grupo['nombre'] . ' ' . $grupo['apellido']) ?>
                                    </div>
                                    <?php if ($enGrupo): ?>
                                        <div class="status-full bg-success">Inscrito Actualmente</div>
                                    <?php elseif ($lleno): ?>
                                        <div class="status-full">Grupo Lleno</div>
                                    <?php endif; ?>
                                </div>
                                <div class="group-body">
                                    <div class="description-text">
                                        <?= htmlspecialchars($grupo['descripcion']) ?>
                                    </div>
                                    
                                    <div class="d-flex flex-wrap mb-3 gap-2">
                                        <div class="stat-badge badge-enrolled">
                                            <i class='bx bxs-user-check me-1'></i>
                                            <?= $inscritos ?> Inscritos
                                        </div>
                                        <div class="stat-badge badge-capacity">
                                            <i class='bx bxs-pie-chart-alt-2 me-1'></i>
                                            <?= $capacidad ?> cupos máx.
                                        </div>
                                    </div>

                                    <div class="progress-container">
                                        <div class="progress-label">
                                            <span>Ocupación</span>
                                            <span class="<?= $lleno ? 'text-danger' : 'text-success' ?> fw-bold"><?= $porcentaje ?>%</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar <?= $lleno ? 'bg-danger' : 'bg-success' ?>" role="progressbar" style="width: <?= $porcentaje ?>%"></div>
                                        </div>
                                    </div>

                                    <div class="mt-auto">
                                        <?php if ($enGrupo): ?>
                                             <button class="btn btn-enrolled" disabled>
                                                <i class='bx bx-check'></i> Inscrito
                                            </button>
                                        <?php elseif ($lleno): ?>
                                            <button class="btn btn-full" disabled>
                                                No hay cupos
                                            </button>
                                        <?php else: ?>
                                            <!-- Botón con confirmación -->
                                            <?php 
                                                $actionText = $tieneGrupoActivo ? "Cambiar a este Grupo" : "Inscribir Estudiante";
                                                $confirmTitle = $tieneGrupoActivo ? "¿Desea cambiar de grupo?" : "¿Confirmar inscripción?";
                                                $confirmText = $tieneGrupoActivo 
                                                    ? "El estudiante ya tiene un grupo. Al aceptar, se le dará de baja del anterior y se inscribirá en " . htmlspecialchars($grupo['nombre_grupo']) . "." 
                                                    : "¿Está seguro que desea inscribir al estudiante en el grupo " . htmlspecialchars($grupo['nombre_grupo']) . "?";
                                                
                                                $btnClass = $tieneGrupoActivo ? 'btn-change-action' : 'btn-enroll-action';
                                                $btnIcon = $tieneGrupoActivo ? "<i class='bx bx-transfer'></i>" : "<i class='bx bx-check-circle'></i>";
                                            ?>
                                            <button class="<?= $btnClass ?>" onclick="confirmarInscripcion(<?= $grupo['IdGrupo_Interes'] ?>, '<?= $confirmTitle ?>', '<?= $confirmText ?>', '<?= $actionText ?>')">
                                                <?= $btnIcon ?> <?= $actionText ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
if (isset($_SESSION['swal_success'])) {
    echo "<script>
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '" . $_SESSION['swal_success'] . "',
            confirmButtonColor: '#c90000'
        });
    </script>";
    unset($_SESSION['swal_success']);
}

if (isset($_SESSION['swal_error'])) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '" . $_SESSION['swal_error'] . "',
            confirmButtonColor: '#c90000'
        });
    </script>";
    unset($_SESSION['swal_error']);
}
?>

<script>
    function confirmarInscripcion(idGrupo, titulo, texto, botonTexto) {
        Swal.fire({
            title: titulo,
            text: texto,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#c90000',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, ' + botonTexto.toLowerCase(),
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const urlParams = new URLSearchParams(window.location.search);
                const idEstudiante = urlParams.get('id');
                window.location.href = '../../../controladores/InscripcionGrupoInteresController.php?action=procesar_inscripcion_representante&idGrupo=' + idGrupo + '&idEstudiante=' + idEstudiante;
            }
        });
    }

    // --- ACTUALIZACIÓN EN TIEMPO REAL ---
    document.addEventListener('DOMContentLoaded', function() {
        const POLL_INTERVAL = 3000; // 3 segundos

        function actualizarOcupacion() {
            // Recolectar IDs de los grupos mostrados en pantalla para consultar solo esos
            const cards = document.querySelectorAll('.group-card');
            if (cards.length === 0) return;

            const ids = Array.from(cards).map(card => card.dataset.groupId).join(',');

            fetch('../../../controladores/InscripcionGrupoInteresController.php?action=obtener_ocupacion&ids=' + ids)
                .then(response => response.json())
                .then(data => {
                    data.forEach(grupo => {
                        const id = grupo.id;
                        const inscritos = grupo.inscritos;
                        const capacidad = grupo.capacidad;
                        
                        // Actualizar UI para este grupo
                        const card = document.querySelector(`.group-card[data-group-id="${id}"]`);
                        if (!card) return;

                        // 1. Textos
                        const badgeInscritos = card.querySelector('.badge-enrolled');
                        if (badgeInscritos) badgeInscritos.innerHTML = `<i class='bx bxs-user-check me-1'></i> ${inscritos} Inscritos`;

                        // 2. Progreso
                        const porcentaje = capacidad > 0 ? Math.min(100, Math.round((inscritos / capacidad) * 100)) : 0;
                        const esLleno = inscritos >= capacidad;

                        const progressLabel = card.querySelector('.progress-label span:last-child');
                        const progressBar = card.querySelector('.progress-bar');
                        
                        if (progressLabel) {
                            progressLabel.textContent = `${porcentaje}%`;
                            progressLabel.className = esLleno ? 'text-danger fw-bold' : 'text-success fw-bold';
                        }
                        
                        if (progressBar) {
                            progressBar.style.width = `${porcentaje}%`;
                            progressBar.className = `progress-bar ${esLleno ? 'bg-danger' : 'bg-success'}`;
                        }

                        // 3. Estado "Lleno" y Botones
                        // Si está lleno y NO estoy inscrito, debo deshabilitar
                        // Chequear si el usuario ya está inscrito en ESTE grupo visualmente (clase border-success o btn-enrolled)
                        const estaInscritoAca = card.classList.contains('border-success');
                        const statusBadgeContainer = card.querySelector('.group-header');
                        
                        // Manejo de badge "Grupo Lleno"
                        let statusBadge = statusBadgeContainer.querySelector('.status-full');
                        if (esLleno && !estaInscritoAca) {
                            if (!statusBadge) {
                                // Crear badge si no existe
                                const div = document.createElement('div');
                                div.className = 'status-full';
                                div.textContent = 'Grupo Lleno';
                                statusBadgeContainer.appendChild(div);
                            } else if (statusBadge.textContent !== 'Grupo Lleno' && !statusBadge.classList.contains('bg-success')) {
                                // Si existe y no es el de "Inscrito", asegurar texto
                                statusBadge.textContent = 'Grupo Lleno';
                                statusBadge.className = 'status-full'; // Reset classes
                            }
                        } else if (!esLleno && !estaInscritoAca && statusBadge && !statusBadge.classList.contains('bg-success')) {
                            // Si ya no está lleno y no es badge de inscrito, remover
                            statusBadge.remove();
                        }

                        // Manejo del Botón
                        const btnContainer = card.querySelector('.mt-auto');
                        if (btnContainer) {
                            const btn = btnContainer.querySelector('button');
                            if (btn && !estaInscritoAca) { // Solo modificar si NO estoy inscrito aquí
                                if (esLleno) {
                                    // Cambiar a estado "No hay cupos"
                                    if (!btn.disabled || !btn.classList.contains('btn-full')) {
                                        btn.className = 'btn btn-full';
                                        btn.disabled = true;
                                        btn.innerHTML = 'No hay cupos';
                                        btn.onclick = null;
                                    }
                                } else {
                                    // Restaurar botón de inscripción si se liberó cupo
                                    // Comprobamos si no es ya un botón de acción válido
                                    if (btn.classList.contains('btn-full')) {
                                        // Recuperamos el estado original (Inscribir o Cambiar)
                                        // Necesitamos saber si el user tiene OTRO grupo (global state).
                                        // Como JS no sabe PHP state facilmente sin reload, podemos inferirlo:
                                        // Si hay ALGUNA card con border-success en la pagina, es "Cambiar", sino "Inscribir".
                                        const hayInscripcionActiva = document.querySelector('.group-card.border-success') !== null;
                                        const actionText = hayInscripcionActiva ? "Cambiar a este Grupo" : "Inscribir Estudiante";
                                        const confirmTitle = hayInscripcionActiva ? "¿Desea cambiar de grupo?" : "¿Confirmar inscripción?";
                                        const confirmText = hayInscripcionActiva 
                                            ? `El estudiante ya tiene un grupo. Al aceptar, se le dará de baja del anterior y se inscribirá en este grupo.`
                                            : `¿Está seguro que desea inscribir al estudiante en este grupo?`;
                                        const btnClass = hayInscripcionActiva ? 'btn-change-action' : 'btn-enroll-action';
                                        const btnIcon = hayInscripcionActiva ? "<i class='bx bx-transfer'></i>" : "<i class='bx bx-check-circle'></i>";

                                        btn.className = btnClass;
                                        btn.disabled = false;
                                        btn.innerHTML = `${btnIcon} ${actionText}`;
                                        
                                        // Re-asignar evento onclick. Nota: Los valores exactos de texto/nombre grupo se pierden un poco aqui si no los guardamos en data attrs.
                                        // Para simplificar, recargamos la pagina si un grupo lleno se vacia seria lo ideal, 
                                        // pero intentaremos asignar el evento generico.
                                        // Mejor approach: Guardar los params de confirmacion en data attributes del boton original 
                                        // cuando se renderiza PHP, asi podemos restaurarlos.
                                        
                                        // Buscar data attributes en el container o card si los hubieramos puesto.
                                        // Como no los pusimos, recargar si cambia de lleno a disponible es una opcion segura, 
                                        // O simplemente habilitarlo con textos genericos.
                                        btn.onclick = function() {
                                             confirmarInscripcion(id, confirmTitle, confirmText, actionText);
                                        };
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(err => console.error('Error actualizando ocupación:', err));
        }

        // Iniciar polling
        setInterval(actualizarOcupacion, POLL_INTERVAL);
    });
</script>
</body>
</html>
