<?php
session_start();
require_once __DIR__ . '/../../../controladores/Notificaciones.php';
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Persona.php';
require_once __DIR__ . '/../../../modelos/Nacionalidad.php';
require_once __DIR__ . '/../../../modelos/Sexo.php';
require_once __DIR__ . '/../../../modelos/Urbanismo.php';
require_once __DIR__ . '/../../../modelos/TipoTelefono.php';
require_once __DIR__ . '/../../../modelos/TipoDiscapacidad.php';
// === CONEXIÓN ===
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
    header("Location: estudiante.php");
    exit();
}
$id = intval($_GET['id']);

// === INSTANCIAS DE MODELOS ===
$personaModel = new Persona($conexion);
$nacionalidadModel = new Nacionalidad($conexion);
$sexoModel = new Sexo($conexion);
$urbanismoModel = new Urbanismo($conexion);
$tipoTelefonoModel = new TipoTelefono($conexion);
$tipoDiscapacidadModel = new TipoDiscapacidad($conexion);

// === OBTENER DATOS ===
$estudiante = $personaModel->obtenerEstudiantePorId($id);
if (!$estudiante) {
    header("Location: estudiante.php");
    exit();
}

$seccionActual = $personaModel->obtenerSeccionActualEstudiante($id);
$discapacidades = $personaModel->obtenerDiscapacidadesEstudiante($id);
$nacionalidades = $nacionalidadModel->obtenerTodos();
$sexos = $sexoModel->obtenerTodos();
$urbanismos = $urbanismoModel->obtenerTodos();
$tiposTelefono = $tipoTelefonoModel->obtenerTodos();
$tiposDiscapacidad = $tipoDiscapacidadModel->obtenerTodos();

// === TELÉFONOS EXISTENTES ===
require_once __DIR__ . '/../../../modelos/Telefono.php';
$telefonoModel = new Telefono($conexion);
$telefonos = $telefonoModel->obtenerPorPersona($id);

// === HISTORIAL DE CAMBIOS ===
require_once __DIR__ . '/../../../modelos/HistorialCambios.php';
$historialModel = new HistorialCambios($conexion);
$ultimoCambio = $historialModel->obtenerUltimoCambioPersona($id);

if ($ultimoCambio) {
    $nombreModificador = htmlspecialchars($ultimoCambio['usuario_nombre'] . ' ' . $ultimoCambio['usuario_apellido']);
    $fechaModificacion = date('d/m/Y H:i', strtotime($ultimoCambio['fecha_cambio']));
} else {
    $nombreModificador = 'Sin cambios registrados';
    $fechaModificacion = 'Sin cambios registrados';
}

// === ALERTAS ===
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

if ($alert) {
    $alerta = match ($alert) {
        'success' => Notificaciones::exito("El representante se actualizó correctamente."),
        'error' => Notificaciones::advertencia("Error al actualizar el representante."),
        default => null
    };
    if ($alerta) Notificaciones::mostrar($alerta);
}

// === FUNCIONES UTILES ===
function selected($a, $b) { return $a == $b ? 'selected' : ''; }
function esc($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>UECFT Araure - Editar Estudiante</title>
    <link rel="stylesheet" href="../../../assets/css/ver_representante.css">
    <link rel="stylesheet" href="../../../assets/css/foto_perfil.css">
    <style>
        .telefonos-list .telefono-row, .discap-list .disc-row { display:flex; gap:8px; align-items:center; margin-bottom:8px; }
        .telefono-row input[type="text"], .disc-row input[type="text"], .disc-row select { width:100%; }
        .btn-small { padding:.25rem .5rem; font-size:.85rem; }
        .actions-col { width:120px; text-align:center; }
    </style>
</head>
<body>
<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<section class="home-section">
    <div class="main-content">
        <div class="container">
            <h2 class="mb-4">Editar Estudiante</h2>

            <!-- 🚫 Sin lógica de actualización, solo muestra formulario -->
            <form id="form-editar-estudiante" method="POST" action="../../../controladores/EstudianteController.php">
                <input type="hidden" name="accion" value="actualizar_estudiante">
                <input type="hidden" name="IdPersona" value="<?= esc($id) ?>">

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

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <i class="fas fa-user-graduate me-2"></i> Datos personales
                    </div>
                    <div class="card-body info-grid">
                        <div class="info-item">
                            <strong>Nombre:</strong>
                            <input type="text" name="nombre" class="form-control" value="<?= esc($estudiante['nombre']) ?>"
                                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                   minlength="3" maxlength="40"
                                   required>
                        </div>
                        <div class="info-item">
                            <strong>Apellido:</strong>
                            <input type="text" name="apellido" class="form-control" value="<?= esc($estudiante['apellido']) ?>"
                                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                   minlength="3" maxlength="40"
                                   required>
                        </div>
                        <div class="info-item">
                            <strong>Cédula:</strong>
                            <input type="text" name="cedula" class="form-control" id="cedulaEstudiante" value="<?= esc($estudiante['cedula']) ?>"
                                   pattern="[0-9]+"
                                   minlength="7" maxlength="11">
                        </div>
                        <div class="info-item">
                            <strong>Correo:</strong>
                            <input type="email" name="correo" class="form-control" value="<?= esc($estudiante['correo']) ?>"
                                   minlength="10" maxlength="50">
                        </div>
                        <div class="info-item">
                            <strong>Dirección:</strong>
                            <input type="text" name="direccion" class="form-control" value="<?= esc($estudiante['direccion']) ?>">
                        </div>
                        <div class="info-item">
                            <strong>Fecha de nacimiento:</strong>
                            <input type="date" name="fecha_nacimiento" class="form-control" value="<?= esc($estudiante['fecha_nacimiento']) ?>">
                        </div>
                        <div class="info-item">
                            <strong>Nacionalidad:</strong>
                            <select name="IdNacionalidad" class="form-select">
                                <?php foreach ($nacionalidades as $n): ?>
                                    <option value="<?= $n['IdNacionalidad'] ?>" <?= selected($estudiante['IdNacionalidad'], $n['IdNacionalidad']) ?>><?= esc($n['nacionalidad']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="info-item">
                            <strong>Sexo:</strong>
                            <select name="IdSexo" class="form-select">
                                <?php foreach ($sexos as $s): ?>
                                    <option value="<?= $s['IdSexo'] ?>" <?= selected($estudiante['IdSexo'], $s['IdSexo']) ?>><?= esc($s['sexo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="info-item">
                            <strong>Urbanismo:</strong>
                            <select name="IdUrbanismo" class="form-select">
                                <?php foreach ($urbanismos as $u): ?>
                                    <option value="<?= $u['IdUrbanismo'] ?>" <?= selected($estudiante['IdUrbanismo'], $u['IdUrbanismo']) ?>><?= esc($u['urbanismo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="info-item">
                            <strong>Sección actual:</strong>
                            <input type="text" class="form-control" 
                                value="<?= $seccionActual ? esc($seccionActual['curso'].' "'.$seccionActual['seccion'].'"') : 'No inscrito actualmente' ?>" 
                                readonly>
                        </div>
                    </div>
                </div>

                <!-- Teléfonos -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-phone me-2"></i> Teléfonos</div>
                        <button type="button" id="btn-add-phone" class="btn btn-light btn-small">Agregar teléfono</button>
                    </div>
                    <div class="card-body">
                        <div id="telefonos-container" class="telefonos-list">
                            <?php if (!empty($telefonos)): ?>
                                <?php foreach ($telefonos as $t): ?>
                                    <div class="telefono-row">
                                        <select name="phone_tipo[]" class="form-select" style="width:180px;">
                                            <?php foreach ($tiposTelefono as $tt): ?>
                                                <option value="<?= $tt['IdTipo_Telefono'] ?>" <?= selected($t['IdTipo_Telefono'], $tt['IdTipo_Telefono']) ?>><?= esc($tt['tipo_telefono']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="phone_numero[]" class="form-control" value="<?= esc($t['numero_telefono']) ?>" placeholder="Número" style="min-width: 250px;"
                                               pattern="[0-9]+" minlength="10" maxlength="10">
                                        <button type="button" class="btn btn-outline-danger btn-small btn-remove-phone">Eliminar</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="telefono-row">
                                    <select name="phone_tipo[]" class="form-select" style="width:180px;">
                                        <option value="">Seleccione</option>
                                        <?php foreach ($tiposTelefono as $tt): ?>
                                            <option value="<?= $tt['IdTipo_Telefono'] ?>"><?= esc($tt['tipo_telefono']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="phone_numero[]" class="form-control" placeholder="Número" style="min-width: 250px;"
                                           pattern="[0-9]+" minlength="10" maxlength="10">
                                    <button type="button" class="btn btn-outline-danger btn-small btn-remove-phone">Eliminar</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Discapacidades -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-wheelchair me-2"></i> Discapacidad(es)</div>
                        <button type="button" id="btn-add-disc" class="btn btn-light btn-small">Agregar discapacidad</button>
                    </div>
                    <div class="card-body">
                        <div id="discap-container" class="discap-list">
                            <?php if (!empty($discapacidades)): ?>
                                <?php foreach ($discapacidades as $d): ?>
                                    <div class="disc-row">
                                        <select name="disc_tipo[]" class="form-select" style="width:200px;">
                                            <?php foreach ($tiposDiscapacidad as $td): ?>
                                                <option value="<?= $td['IdTipo_Discapacidad'] ?>" <?= selected($d['IdTipo_Discapacidad'], $td['IdTipo_Discapacidad']) ?>><?= esc($td['tipo_discapacidad']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="disc_text[]" class="form-control" value="<?= esc($d['discapacidad']) ?>" placeholder="Descripción / detalle">
                                        <button type="button" class="btn btn-outline-danger btn-small btn-remove-disc">Eliminar</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="disc-row">
                                    <select name="disc_tipo[]" class="form-select" style="width:200px;">
                                        <option value="">Seleccione</option>
                                        <?php foreach ($tiposDiscapacidad as $td): ?>
                                            <option value="<?= $td['IdTipo_Discapacidad'] ?>"><?= esc($td['tipo_discapacidad']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="disc_text[]" class="form-control" placeholder="Descripción / detalle">
                                    <button type="button" class="btn btn-outline-danger btn-small btn-remove-disc">Eliminar</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- BOTONES -->
                <div class="d-flex justify-content-end gap-2 mb-5">
                    <a href="estudiante.php" class="btn btn-secondary">Volver</a>
                    <button type="submit" class="btn btn-danger">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>

<script src="../../../assets/js/validaciones_solicitud.js"></script>
<script src="../../../assets/js/validaciones_estudiante.js"></script>
<script>
// --- Validación de cédula según edad ---
document.addEventListener('DOMContentLoaded', () => {
    const fechaNacimientoInput = document.querySelector('input[name="fecha_nacimiento"]');
    const cedulaInput = document.getElementById('cedulaEstudiante');

    if (fechaNacimientoInput && cedulaInput) {
        // Función para calcular edad y ajustar validación de cédula
        const actualizarValidacionCedula = () => {
            const fechaNacimiento = fechaNacimientoInput.value;
            if (!fechaNacimiento) return;

            const fechaNac = new Date(fechaNacimiento + 'T00:00:00');
            const hoy = new Date();
            let edad = hoy.getFullYear() - fechaNac.getFullYear();
            const mes = hoy.getMonth() - fechaNac.getMonth();

            if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNac.getDate())) {
                edad--;
            }

            // Ajustar validación según edad
            if (edad < 10) {
                // Cédula escolar: 10-11 dígitos
                cedulaInput.setAttribute('minlength', '10');
                cedulaInput.setAttribute('maxlength', '11');
            } else {
                // Cédula normal: 7-8 dígitos
                cedulaInput.setAttribute('minlength', '7');
                cedulaInput.setAttribute('maxlength', '8');
            }
        };

        // Ejecutar al cargar y al cambiar fecha
        actualizarValidacionCedula();
        fechaNacimientoInput.addEventListener('change', actualizarValidacionCedula);
    }

    // --- Dinámica de teléfonos y discapacidades ---
    const telContainer = document.getElementById('telefonos-container');
    document.getElementById('btn-add-phone').addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'telefono-row';
        row.innerHTML = `
            <select name="phone_tipo[]" class="form-select" style="width:180px;">
                <option value="">Seleccione</option>
                <?php foreach ($tiposTelefono as $tt): ?>
                    <option value="<?= $tt['IdTipo_Telefono'] ?>"><?= esc($tt['tipo_telefono']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="phone_numero[]" class="form-control" placeholder="Número" style="min-width: 250px;" pattern="[0-9]+" minlength="10" maxlength="10">
            <button type="button" class="btn btn-outline-danger btn-small btn-remove-phone">Eliminar</button>
        `;
        telContainer.appendChild(row);
    });
    telContainer.addEventListener('click', e => {
        if (e.target.classList.contains('btn-remove-phone')) e.target.closest('.telefono-row').remove();
    });

    const discContainer = document.getElementById('discap-container');
    document.getElementById('btn-add-disc').addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'disc-row';
        row.innerHTML = `
            <select name="disc_tipo[]" class="form-select" style="width:200px;">
                <option value="">Seleccione</option>
                <?php foreach ($tiposDiscapacidad as $td): ?>
                    <option value="<?= $td['IdTipo_Discapacidad'] ?>"><?= esc($td['tipo_discapacidad']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="disc_text[]" class="form-control" placeholder="Descripción / detalle">
            <button type="button" class="btn btn-outline-danger btn-small btn-remove-disc">Eliminar</button>
        `;
        discContainer.appendChild(row);
    });
    discContainer.addEventListener('click', e => {
        if (e.target.classList.contains('btn-remove-disc')) e.target.closest('.disc-row').remove();
    });
});
</script>

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
                    <input type="hidden" name="idEstudiante" value="<?= $id ?>">

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
                           onchange="previewPhotoEdit(this)">

                    <div id="errorFoto" class="alert alert-danger mt-3" style="display: none;"></div>
                    <div id="successFoto" class="alert alert-success mt-3" style="display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class='bx bx-x me-1'></i>Cancelar
                </button>
                <button type="button" class="btn btn-danger" onclick="uploadPhotoEdit()" id="btnGuardarFoto">
                    <i class='bx bx-save me-1'></i>Guardar Foto
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedFileEdit = null;

function previewPhotoEdit(input) {
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

        selectedFileEdit = file;

        // Mostrar vista previa
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

function uploadPhotoEdit() {
    const errorDiv = document.getElementById('errorFoto');
    const successDiv = document.getElementById('successFoto');
    const btnGuardar = document.getElementById('btnGuardarFoto');

    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';

    if (!selectedFileEdit) {
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
    selectedFileEdit = null;
    document.getElementById('inputFoto').value = '';
    document.getElementById('errorFoto').style.display = 'none';
    document.getElementById('successFoto').style.display = 'none';
    document.getElementById('btnGuardarFoto').disabled = false;
    document.getElementById('btnGuardarFoto').innerHTML = '<i class="bx bx-save me-1"></i>Guardar Foto';
});
</script>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
