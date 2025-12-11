<?php
session_start();
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Persona.php';
require_once __DIR__ . '/../../../modelos/Representante.php';
require_once __DIR__ . '/../../../modelos/Telefono.php';

$database = new Database();
$conexion = $database->getConnection();

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

// === VERIFICACIÓN DE ID ===
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: representado.php");
    exit();
}

$idEstudiante = intval($_GET['id']);
$idRepresentante = $_SESSION['idPersona'];

$personaModel = new Persona($conexion);
$representanteModel = new Representante($conexion);
$telefonoModel = new Telefono($conexion);

// Verificar que el estudiante sea realmente representado por este usuario
$estudiantesRepresentados = $representanteModel->obtenerEstudiantesPorRepresentante($idRepresentante);
$esRepresentado = false;
foreach ($estudiantesRepresentados as $est) {
    if ($est['IdEstudiante'] == $idEstudiante) {
        $esRepresentado = true;
        break;
    }
}

if (!$esRepresentado) {
    header("Location: representado.php");
    exit();
}

// Obtener datos del estudiante
$estudiante = $personaModel->obtenerEstudiantePorId($idEstudiante);
if (!$estudiante) {
    header("Location: representado.php");
    exit();
}

// Obtener el año escolar activo
$queryAnoActivo = "SELECT IdFecha_Escolar, fecha_escolar FROM fecha_escolar WHERE fecha_activa = 1 LIMIT 1";
$stmtAnoActivo = $conexion->prepare($queryAnoActivo);
$stmtAnoActivo->execute();
$anoEscolarActivo = $stmtAnoActivo->fetch(PDO::FETCH_ASSOC);
$idAnoActivo = $anoEscolarActivo ? $anoEscolarActivo['IdFecha_Escolar'] : null;
$nombreAnoActivo = $anoEscolarActivo ? $anoEscolarActivo['fecha_escolar'] : 'Sin año activo';

// Obtener sección actual del estudiante según el año escolar activo
$seccionActual = $personaModel->obtenerSeccionActualEstudiante($idEstudiante, $idAnoActivo);
$discapacidades = $personaModel->obtenerDiscapacidadesEstudiante($idEstudiante);
$telefonos = $telefonoModel->obtenerPorPersona($idEstudiante);

// Obtener todos los representantes del estudiante
$todosRepresentantes = $representanteModel->obtenerPorEstudiante($idEstudiante);
// Filtrar para excluir al usuario actual
$otrosRepresentantes = array_filter($todosRepresentantes, function($rep) use ($idRepresentante) {
    return $rep['IdPersona'] != $idRepresentante;
});

// === FUNCIÓN DE LIMPIEZA ===
function mostrar($valor, $texto = 'No registrado') {
    return !empty(trim($valor)) ? htmlspecialchars($valor) : '<span class="text-muted">' . $texto . '</span>';
}

// Función para calcular edad
function calcularEdad($fechaNacimiento) {
    if (empty($fechaNacimiento)) return null;
    $fecha = new DateTime($fechaNacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha);
    return $edad->y;
}
?>

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<style>
        .readonly-badge {
            background: #6c757d;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #c90000;
        }

        .info-item strong {
            display: block;
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item span {
            font-size: 1rem;
            color: #333;
        }

        /* 🔹 Color blanco para el span de edad */
        .info-item .badge.bg-secondary {
            color: #fff !important;
        }

        /* 🔹 Color blanco para el span del status */
        .info-item .badge {
            color: #fff !important;
        }

        .section-divider {
            border-top: 2px solid #e9ecef;
            margin: 2rem 0;
        }

        .alert-readonly {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-readonly i {
            color: #ffc107;
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }

        /* 🔹 FOTO DE PERFIL */
        .profile-photo-container {
            position: relative;
            width: 180px;
            height: 180px;
            margin: 0 auto 1.5rem;
        }

        .profile-photo-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            border: 4px solid #fff;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .profile-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-photo-default {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .profile-photo-default i {
            font-size: 5rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .profile-photo-edit {
            position: absolute;
            bottom: 8px;
            right: 8px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #c90000;
            border: 3px solid #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(201, 0, 0, 0.3);
        }

        .profile-photo-edit:hover {
            background: #a00000;
            transform: scale(1.1);
        }

        .profile-photo-edit i {
            color: #fff;
            font-size: 1.1rem;
        }

        .profile-photo-name {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .profile-photo-role {
            text-align: center;
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 1rem;
        }

        /* Modal de foto */
        .modal-backdrop.show {
            opacity: 0.7;
        }

        .photo-preview-container {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 1.5rem auto;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #e9ecef;
        }

        .photo-preview-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .photo-upload-area:hover {
            border-color: #c90000;
            background: #fff;
        }

        .photo-upload-area i {
            font-size: 3rem;
            color: #c90000;
            margin-bottom: 1rem;
        }
</style>


<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center mb-4">
                <div class="col-12">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div>
                            <h2 class="mb-1">
                                Información del Estudiante
                                <span class="readonly-badge ms-2">
                                    <i class='bx bx-lock-alt'></i> Solo Lectura
                                </span>
                            </h2>
                            <p class="text-muted mb-0">
                                <?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?>
                            </p>
                        </div>
                        <div>
                            <a href="representado.php" class="btn btn-secondary">
                                <i class='bx bx-arrow-back me-1'></i> Volver a Mis Representados
                            </a>
                        </div>
                    </div>

                    <!-- Alerta de Solo Lectura -->
                    <div class="alert-readonly">
                        <i class='bx bx-info-circle'></i>
                        <strong>Vista de Solo Lectura:</strong> Esta información es solo para consulta. Si necesitas actualizar algún dato, contacta con la administración de la institución.
                    </div>

                    <!-- FOTO DE PERFIL -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body text-center py-4">
                            <div class="profile-photo-container">
                                <div class="profile-photo-wrapper">
                                    <?php if (!empty($estudiante['foto_perfil']) && file_exists(__DIR__ . '/../../../' . $estudiante['foto_perfil'])): ?>
                                        <img src="<?= htmlspecialchars('../../../' . $estudiante['foto_perfil']) ?>"
                                             alt="Foto de perfil"
                                             class="profile-photo">
                                    <?php else: ?>
                                        <div class="profile-photo-default">
                                            <i class='bx bx-user'></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="profile-photo-edit" data-bs-toggle="modal" data-bs-target="#modalFotoPerfil" title="Cambiar foto">
                                    <i class='bx bx-camera'></i>
                                </div>
                            </div>
                            <div class="profile-photo-name">
                                <?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?>
                            </div>
                            <div class="profile-photo-role">
                                <i class='bx bx-id-card me-1'></i>
                                <?php
                                    if (!empty($estudiante['cedula'])) {
                                        echo htmlspecialchars($estudiante['nacionalidad']) . '-' . number_format($estudiante['cedula'], 0, '', '.');
                                    } else {
                                        echo 'Estudiante';
                                    }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- INFORMACIÓN PERSONAL -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white d-flex align-items-center">
                            <i class='bx bx-user-circle me-2'></i> Información Personal
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <strong><i class='bx bx-user me-1'></i> Nombre Completo:</strong>
                                    <span><?= mostrar($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-id-card me-1'></i> Cédula:</strong>
                                    <span>
                                        <?php
                                            if (!empty($estudiante['cedula'])) {
                                                echo mostrar($estudiante['nacionalidad'], '') . ' ' . number_format($estudiante['cedula'], 0, '', '.');
                                            } else {
                                                echo mostrar('');
                                            }
                                        ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-male-female me-1'></i> Sexo:</strong>
                                    <span><?= mostrar($estudiante['sexo']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-cake me-1'></i> Fecha de Nacimiento:</strong>
                                    <span>
                                        <?php
                                            if (!empty($estudiante['fecha_nacimiento'])) {
                                                $edad = calcularEdad($estudiante['fecha_nacimiento']);
                                                echo date('d/m/Y', strtotime($estudiante['fecha_nacimiento']));
                                                if ($edad) {
                                                    echo ' <span class="badge bg-secondary ms-2">' . $edad . ' años</span>';
                                                }
                                            } else {
                                                echo mostrar('');
                                            }
                                        ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-map-pin me-1'></i> Lugar de Nacimiento:</strong>
                                    <span><?= mostrar($estudiante['lugar_nacimiento'] ?? '') ?></span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-envelope me-1'></i> Correo Electrónico:</strong>
                                    <span><?= mostrar($estudiante['correo']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- INFORMACIÓN DE CONTACTO -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white d-flex align-items-center">
                            <i class='bx bx-phone me-2'></i> Información de Contacto
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <strong><i class='bx bx-map me-1'></i> Urbanismo:</strong>
                                    <span><?= mostrar($estudiante['urbanismo']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-home me-1'></i> Dirección:</strong>
                                    <span><?= mostrar($estudiante['direccion']) ?></span>
                                </div>
                                <?php if (!empty($telefonos)): ?>
                                    <?php foreach ($telefonos as $tel): ?>
                                        <div class="info-item">
                                            <strong><i class='bx bx-phone me-1'></i> <?= htmlspecialchars($tel['tipo_telefono']) ?>:</strong>
                                            <span><?= htmlspecialchars($tel['numero_telefono']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="info-item">
                                        <strong><i class='bx bx-phone me-1'></i> Teléfonos:</strong>
                                        <span class="text-muted">No registrado</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- INFORMACIÓN ACADÉMICA -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white d-flex align-items-center">
                            <i class='bx bxs-graduation me-2'></i> Información Académica - <?= htmlspecialchars($nombreAnoActivo) ?>
                        </div>
                        <div class="card-body">
                            <?php if ($seccionActual): ?>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <strong><i class='bx bx-layer me-1'></i> Nivel:</strong>
                                        <span><?= htmlspecialchars($seccionActual['nivel']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <strong><i class='bx bx-book me-1'></i> Curso:</strong>
                                        <span><?= htmlspecialchars($seccionActual['curso']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <strong><i class='bx bx-group me-1'></i> Sección:</strong>
                                        <span><?= htmlspecialchars($seccionActual['seccion']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <strong><i class='bx bx-door-open me-1'></i> Aula:</strong>
                                        <span><?= mostrar($seccionActual['aula'] ?? '') ?></span>
                                    </div>
                                    <div class="info-item">
                                        <strong><i class='bx bx-calendar me-1'></i> Año Escolar:</strong>
                                        <span><?= htmlspecialchars($seccionActual['fecha_escolar']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <strong><i class='bx bx-info-circle me-1'></i> Status:</strong>
                                        <span>
                                            <?php
                                                $statusClass = 'secondary';
                                                $statusText = $seccionActual['status'] ?? 'No registrado';
                                                if (stripos($statusText, 'Inscrito') !== false) $statusClass = 'success';
                                                elseif (stripos($statusText, 'Rechazado') !== false) $statusClass = 'danger';
                                                elseif (stripos($statusText, 'Pendiente') !== false) $statusClass = 'warning';
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>">
                                                <?= htmlspecialchars($statusText) ?>
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">
                                    <i class='bx bx-info-circle me-2'></i>
                                    <strong>Sin inscripción para el año escolar <?= htmlspecialchars($nombreAnoActivo) ?></strong><br>
                                    El estudiante no tiene una inscripción registrada para el año escolar activo.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- OTROS REPRESENTANTES -->
                    <?php if (!empty($otrosRepresentantes)): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-danger text-white d-flex align-items-center">
                                <i class='bx bx-group me-2'></i> Otros Representantes del Estudiante
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th><i class='bx bx-user me-1'></i> Nombre Completo</th>
                                                <th><i class='bx bx-id-card me-1'></i> Cédula</th>
                                                <th><i class='bx bx-heart me-1'></i> Parentesco</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($otrosRepresentantes as $rep): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($rep['nombre'] . ' ' . $rep['apellido']) ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php
                                                            if (!empty($rep['cedula'])) {
                                                                echo htmlspecialchars($rep['nacionalidad']) . ' ' . number_format($rep['cedula'], 0, '', '.');
                                                            } else {
                                                                echo '<span class="text-muted">No registrado</span>';
                                                            }
                                                        ?>
                                                    </td>
                                                    <td><?= mostrar($rep['parentesco']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- DISCAPACIDADES -->
                    <?php if (!empty($discapacidades)): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-danger text-white d-flex align-items-center">
                                <i class='bx bx-heart me-2'></i> Condiciones Especiales
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Tipo de Condición</th>
                                                <th>Descripción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($discapacidades as $disc): ?>
                                                <tr>
                                                    <td>
                                                        <i class='bx bx-heart text-danger me-2'></i>
                                                        <strong><?= htmlspecialchars($disc['tipo_discapacidad']) ?></strong>
                                                    </td>
                                                    <td><?= htmlspecialchars($disc['descripcion']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Botón Volver -->
                    <div class="text-center mt-4">
                        <a href="representado.php" class="btn btn-lg btn-danger">
                            <i class='bx bx-arrow-back me-2'></i>
                            Volver a Mis Representados
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal para cambiar foto de perfil -->
<div class="modal fade" id="modalFotoPerfil" tabindex="-1" aria-labelledby="modalFotoPerfilLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalFotoPerfilLabel">
                    <i class='bx bx-camera me-2'></i>Cambiar Foto de Perfil
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formFotoPerfil" enctype="multipart/form-data">
                    <input type="hidden" name="idEstudiante" value="<?= $idEstudiante ?>">

                    <!-- Vista previa -->
                    <div class="photo-preview-container" id="photoPreviewContainer">
                        <?php if (!empty($estudiante['foto_perfil']) && file_exists(__DIR__ . '/../../../' . $estudiante['foto_perfil'])): ?>
                            <img src="<?= htmlspecialchars('../../../' . $estudiante['foto_perfil']) ?>"
                                 alt="Vista previa"
                                 id="photoPreview">
                        <?php else: ?>
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Crect fill='%23667eea' width='200' height='200'/%3E%3Ctext fill='white' font-size='80' font-family='Arial' x='50%25' y='50%25' text-anchor='middle' dy='.3em'%3E%3F%3C/text%3E%3C/svg%3E"
                                 alt="Vista previa"
                                 id="photoPreview">
                        <?php endif; ?>
                    </div>

                    <!-- Área de carga -->
                    <div class="photo-upload-area" onclick="document.getElementById('inputFoto').click()">
                        <i class='bx bx-cloud-upload'></i>
                        <p class="mb-2"><strong>Haz clic para seleccionar una foto</strong></p>
                        <p class="text-muted mb-0" style="font-size: 0.85rem;">
                            Formatos permitidos: JPG, JPEG, PNG (Máx. 2MB)
                        </p>
                    </div>

                    <input type="file"
                           id="inputFoto"
                           name="foto"
                           accept="image/jpeg,image/jpg,image/png"
                           style="display: none;"
                           onchange="previewPhoto(this)">

                    <div id="errorFoto" class="alert alert-danger mt-3" style="display: none;"></div>
                    <div id="successFoto" class="alert alert-success mt-3" style="display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class='bx bx-x me-1'></i>Cancelar
                </button>
                <button type="button" class="btn btn-danger" onclick="uploadPhoto()" id="btnGuardarFoto">
                    <i class='bx bx-save me-1'></i>Guardar Foto
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedFile = null;

function previewPhoto(input) {
    const errorDiv = document.getElementById('errorFoto');
    const successDiv = document.getElementById('successFoto');
    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';

    if (input.files && input.files[0]) {
        const file = input.files[0];

        // Validar tipo de archivo
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!validTypes.includes(file.type)) {
            errorDiv.textContent = 'Por favor selecciona una imagen válida (JPG, JPEG o PNG)';
            errorDiv.style.display = 'block';
            input.value = '';
            return;
        }

        // Validar tamaño (2MB máximo)
        if (file.size > 2 * 1024 * 1024) {
            errorDiv.textContent = 'La imagen no debe superar los 2MB';
            errorDiv.style.display = 'block';
            input.value = '';
            return;
        }

        selectedFile = file;

        // Mostrar vista previa
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

function uploadPhoto() {
    const errorDiv = document.getElementById('errorFoto');
    const successDiv = document.getElementById('successFoto');
    const btnGuardar = document.getElementById('btnGuardarFoto');

    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';

    if (!selectedFile) {
        errorDiv.textContent = 'Por favor selecciona una foto primero';
        errorDiv.style.display = 'block';
        return;
    }

    const formData = new FormData(document.getElementById('formFotoPerfil'));

    // Deshabilitar botón
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Guardando...';

    fetch('../../../controladores/estudiante/actualizar_foto.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            successDiv.textContent = data.message;
            successDiv.style.display = 'block';

            // Actualizar la foto en la página
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            errorDiv.textContent = data.message || 'Error al subir la foto';
            errorDiv.style.display = 'block';
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="bx bx-save me-1"></i>Guardar Foto';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorDiv.textContent = 'Error al procesar la solicitud';
        errorDiv.style.display = 'block';
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<i class="bx bx-save me-1"></i>Guardar Foto';
    });
}

// Resetear el formulario cuando se cierra el modal
document.getElementById('modalFotoPerfil').addEventListener('hidden.bs.modal', function () {
    selectedFile = null;
    document.getElementById('inputFoto').value = '';
    document.getElementById('errorFoto').style.display = 'none';
    document.getElementById('successFoto').style.display = 'none';
    document.getElementById('btnGuardarFoto').disabled = false;
    document.getElementById('btnGuardarFoto').innerHTML = '<i class="bx bx-save me-1"></i>Guardar Foto';
});
</script>

<?php include '../../layouts/footer.php'; ?>
