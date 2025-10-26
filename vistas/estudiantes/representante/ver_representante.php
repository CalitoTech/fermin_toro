<?php
session_start();
require_once __DIR__ . '/../../../config/conexion.php';

$database = new Database();
$conexion = $database->getConnection();

// === VERIFICACIÓN DE SESIÓN ===
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            title: "Acceso Denegado",
            text: "Por favor, debes iniciar sesión",
            icon: "warning",
            confirmButtonText: "Aceptar",
            confirmButtonColor: "#c90000"
        }).then(() => {
            window.location.href = "../../login/login.php";
        });
    </script>';
    session_destroy();
    exit();
}

// === VERIFICACIÓN DE ID ===
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: representante.php");
    exit();
}

$idPersona = intval($_GET['id']);

// === CONSULTA DEL REPRESENTANTE (estructura consistente) ===
$query = "
    SELECT 
        p.IdPersona,
        p.cedula,
        p.nombre,
        p.apellido,
        n.nacionalidad,
        sx.sexo,
        p.correo,
        p.direccion,
        u.urbanismo,
        r.IdRepresentante,
        par.parentesco,
        r.ocupacion,
        r.lugar_trabajo,
        ea.status AS estado_acceso,
        ei.status AS estado_institucional,
        GROUP_CONCAT(DISTINCT tel.numero_telefono SEPARATOR ' || ') AS numeros,
        GROUP_CONCAT(DISTINCT tipo_tel.tipo_telefono SEPARATOR ' || ') AS tipos,
        MAX(dp.IdPerfil = 5) AS contacto_emergencia
    FROM representante AS r
    INNER JOIN persona AS p ON p.IdPersona = r.IdPersona
    LEFT JOIN nacionalidad AS n ON n.IdNacionalidad = p.IdNacionalidad
    LEFT JOIN sexo AS sx ON sx.IdSexo = p.IdSexo
    LEFT JOIN urbanismo AS u ON u.IdUrbanismo = p.IdUrbanismo
    LEFT JOIN parentesco AS par ON par.IdParentesco = r.IdParentesco
    LEFT JOIN telefono AS tel ON p.IdPersona = tel.IdPersona
    LEFT JOIN tipo_telefono AS tipo_tel ON tipo_tel.IdTipo_Telefono = tel.IdTipo_Telefono
    LEFT JOIN status AS ea ON ea.IdStatus = p.IdEstadoAcceso
    LEFT JOIN status AS ei ON ei.IdStatus = p.IdEstadoInstitucional
    LEFT JOIN detalle_perfil AS dp ON dp.IdPersona = p.IdPersona
    WHERE p.IdPersona = :id
    GROUP BY p.IdPersona
";
$stmt = $conexion->prepare($query);
$stmt->bindParam(':id', $idPersona, PDO::PARAM_INT);
$stmt->execute();
$representante = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no existe
if (!$representante) {
    header("Location: representante.php");
    exit();
}

// === ESTUDIANTES ASOCIADOS ===
$queryEstudiantes = "
    SELECT 
        p.IdPersona AS IdEstudiante,
        p.cedula,
        n.nacionalidad,
        p.nombre,
        p.apellido,
        p.fecha_nacimiento,
        sx.sexo,
        u.urbanismo,
        p.direccion
    FROM representante r
    INNER JOIN persona p ON p.IdPersona = r.IdEstudiante
    LEFT JOIN nacionalidad n ON n.IdNacionalidad = p.IdNacionalidad
    LEFT JOIN sexo sx ON sx.IdSexo = p.IdSexo
    LEFT JOIN urbanismo u ON u.IdUrbanismo = p.IdUrbanismo
    WHERE r.IdPersona = :id
    ORDER BY p.apellido, p.nombre
";
$stmtEst = $conexion->prepare($queryEstudiantes);
$stmtEst->bindParam(':id', $idPersona, PDO::PARAM_INT);
$stmtEst->execute();
$estudiantes = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

// === FUNCIONES DE LIMPIEZA ===
function mostrar($valor, $texto = 'No registrado') {
    return htmlspecialchars(!empty(trim($valor)) ? $valor : $texto);
}
?>

<head>
    <title>UECFT Araure - Ver Representante</title>
    <link rel="stylesheet" href="../../../assets/css/ver_representante.css">
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div>
                            <h2 class="mb-1">Detalles del Representante</h2>
                            <p class="text-muted mb-0">
                                Representante de <strong><?= count($estudiantes) ?></strong> estudiante(s)
                            </p>
                        </div>
                        <div>
                            <a href="representante.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Volver
                            </a>
                            <a href="editar_representante.php?id=<?= $representante['IdPersona'] ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                        </div>
                    </div>

                    <!-- INFORMACIÓN PERSONAL -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white d-flex align-items-center">
                            <i class="fas fa-user-tie me-2"></i> Información del Representante
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <strong>Nombre completo:</strong>
                                    <span><?= mostrar($representante['nombre'] . ' ' . $representante['apellido'], 'No especificado') ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Cédula:</strong>
                                    <span><?= mostrar(($representante['nacionalidad'] ? $representante['nacionalidad'] . ' ' : '') . $representante['cedula'], 'No registrada') ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Sexo:</strong>
                                    <span><?= mostrar($representante['sexo'], 'No especificado') ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Parentesco:</strong>
                                    <span><?= mostrar($representante['parentesco'], 'No especificado') ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Correo electrónico:</strong>
                                    <span><?= mostrar($representante['correo']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Teléfonos:</strong>
                                    <span>
                                        <?php
                                        if (!empty($representante['numeros'])) {
                                            $nums = explode(' || ', $representante['numeros']);
                                            $tipos = explode(' || ', $representante['tipos']);
                                            foreach ($nums as $index => $num) {
                                                $tipo = $tipos[$index] ?? 'Teléfono';
                                                echo '<div><i class="fas fa-phone me-1 text-danger"></i>' . mostrar($tipo) . ': ' . mostrar($num) . '</div>';
                                            }
                                        } else {
                                            echo 'No registrados';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <strong>Dirección:</strong>
                                    <span><?= mostrar($representante['direccion'], 'No especificada') ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Urbanismo:</strong>
                                    <span><?= mostrar($representante['urbanismo'], 'No especificado') ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Ocupación:</strong>
                                    <span><?= mostrar($representante['ocupacion']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Lugar de trabajo:</strong>
                                    <span><?= mostrar($representante['lugar_trabajo']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Estado de acceso:</strong>
                                    <span><?= mostrar($representante['estado_acceso'], 'Desconocido') ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Estado institucional:</strong>
                                    <span><?= mostrar($representante['estado_institucional'], 'Desconocido') ?></span>
                                </div>

                                <?php if ($representante['contacto_emergencia'] == 1): ?>
                                <div class="info-item">
                                    <strong>Contacto de emergencia:</strong>
                                    <span><i class="fas fa-check text-success"></i> Es contacto de emergencia</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ESTUDIANTES -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white d-flex align-items-center">
                            <i class="fas fa-child me-2"></i> Estudiantes a Cargo
                        </div>
                        <div class="card-body">
                            <?php if (count($estudiantes) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nombre Completo</th>
                                            <th>Cédula</th>
                                            <th>Sexo</th>
                                            <th>Fecha Nacimiento</th>
                                            <th>Sección</th>
                                            <th class="text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($estudiantes as &$e) {
                                            // Consulta para obtener curso y sección actuales
                                            $queryCurso = "
                                                SELECT 
                                                    c.curso,
                                                    s.seccion
                                                FROM inscripcion i
                                                INNER JOIN curso_seccion cs ON cs.IdCurso_Seccion = i.IdCurso_Seccion
                                                INNER JOIN curso c ON c.IdCurso = cs.IdCurso
                                                INNER JOIN seccion s ON s.IdSeccion = cs.IdSeccion
                                                WHERE i.IdEstudiante = :idEstudiante
                                                ORDER BY i.IdInscripcion DESC
                                                LIMIT 1
                                            ";
                                            $stmtCurso = $conexion->prepare($queryCurso);
                                            $stmtCurso->bindParam(':idEstudiante', $e['IdEstudiante'], PDO::PARAM_INT);
                                            $stmtCurso->execute();
                                            $cursoActual = $stmtCurso->fetch(PDO::FETCH_ASSOC);
                                            
                                            $e['curso_actual'] = $cursoActual 
                                                ? $cursoActual['curso'] . ' "' . $cursoActual['seccion'] . '"' 
                                                : 'No inscrito actualmente';
                                        }
                                        unset($e);
                                        ?>

                                        <?php foreach ($estudiantes as $e): ?>
                                        <tr>
                                            <td><?= mostrar($e['nombre'] . ' ' . $e['apellido'], 'No registrado') ?></td>
                                            <td><?= mostrar(($e['nacionalidad'] ? $e['nacionalidad'] . ' ' : '') . $e['cedula'], 'No registrada') ?></td>
                                            <td><?= mostrar($e['sexo'], 'No especificado') ?></td>
                                            <td><?= $e['fecha_nacimiento'] ? date('d/m/Y', strtotime($e['fecha_nacimiento'])) : 'No registrada' ?></td>
                                            <td><?= mostrar($e['curso_actual']) ?></td>
                                            <td class="text-center">
                                                <a href="../estudiante/ver_estudiante.php?id=<?= $e['IdEstudiante'] ?>" 
                                                class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-user-slash fa-3x mb-3"></i>
                                <p class="mb-0">Este representante no tiene estudiantes registrados a su cargo.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
