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

// === VERIFICACIÓN DEL ID EN URL ===
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: representante.php");
    exit();
}

// === INCLUSIÓN DE DEPENDENCIAS ===
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Persona.php';
require_once __DIR__ . '/../../../modelos/Sexo.php';
require_once __DIR__ . '/../../../modelos/Urbanismo.php';
require_once __DIR__ . '/../../../modelos/Nacionalidad.php';
require_once __DIR__ . '/../../../modelos/Parentesco.php';
require_once __DIR__ . '/../../../modelos/Representante.php';
require_once __DIR__ . '/../../../modelos/TipoTelefono.php';
require_once __DIR__ . '/../../../modelos/Telefono.php'; // 🔹 NUEVO
require_once __DIR__ . '/../../../controladores/Notificaciones.php';

// === CONEXIÓN Y MODELOS ===
$database = new Database();
$conexion = $database->getConnection();

$personaModel = new Persona($conexion);
$representanteModel = new Representante($conexion);
$tipoTelefonoModel = new TipoTelefono($conexion);
$telefonoModel = new Telefono($conexion); // 🔹 NUEVO

// Modelos auxiliares
$sexoModel = new Sexo($conexion);
$urbanismoModel = new Urbanismo($conexion);
$nacionalidadModel = new Nacionalidad($conexion);
$parentescoModel = new Parentesco($conexion);

// === CARGA DE DATOS ===
$representante = $representanteModel->obtenerPorIdPersona($id);
$persona = $personaModel->obtenerPorId($id);
$telefonos = $telefonoModel->obtenerPorPersona($id); // 🔹 TELÉFONOS ASOCIADOS

if (!$representante || !$persona) {
    header("Location: representante.php");
    exit();
}

// === CARGA DE CATÁLOGOS ===
$nacionalidades = $nacionalidadModel->obtenerTodos();
$sexos = $sexoModel->obtenerTodos();
$urbanismos = $urbanismoModel->obtenerTodos();
$parentescos = $parentescoModel->obtenerTodos();
$tiposTelefono = $tipoTelefonoModel->obtenerTodos();

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

// === FUNCIÓN AUXILIAR ===
function selected($a, $b) { return $a == $b ? 'selected' : ''; }

?>

<head>
    <title>Editar Representante</title>
    <link rel="stylesheet" href="../../../assets/css/ver_representante.css">
    <link rel="stylesheet" href="../../../assets/css/foto_perfil.css">
    <style>
        .form-label { font-weight: 600; color: #333; }
        .add-phone { color: #c90000; cursor: pointer; font-size: 0.9rem; }
        .remove-phone { color: #dc3545; cursor: pointer; margin-left: 8px; }
    </style>
</head>

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<section class="home-section">
    <div class="main-content container">
        <div class="row justify-content-center mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h2 class="mb-1">Editar Representante</h2>
                    <a href="representante.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <!-- FOTO DE PERFIL -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center py-4">
                        <div class="profile-photo-container">
                            <div class="profile-photo-wrapper">
                                <?php if (!empty($persona['foto_perfil']) && file_exists(__DIR__ . '/../../../' . $persona['foto_perfil'])): ?>
                                    <img src="<?= htmlspecialchars('../../../' . $persona['foto_perfil']) ?>"
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
                            <?= htmlspecialchars($persona['nombre'] . ' ' . $persona['apellido']) ?>
                        </div>
                        <div class="profile-photo-role">
                            <i class='bx bx-id-card me-1'></i>
                            <?php
                                if (!empty($persona['cedula'])) {
                                    $nac = '';
                                    foreach ($nacionalidades as $n) {
                                        if ($n['IdNacionalidad'] == $persona['IdNacionalidad']) {
                                            $nac = $n['nacionalidad'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($nac) . '-' . number_format($persona['cedula'], 0, '', '.');
                                } else {
                                    echo 'Representante';
                                }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- FORMULARIO -->
                <form action="../../../controladores/RepresentanteController.php" method="POST" id="editar" class="card shadow-sm p-4">
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="row g-3">
                        <!-- DATOS PERSONA -->
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($persona['nombre']) ?>"
                                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                   minlength="3" maxlength="40">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Apellido</label>
                            <input type="text" name="apellido" class="form-control" required value="<?= htmlspecialchars($persona['apellido']) ?>"
                                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                   minlength="3" maxlength="40">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Cédula</label>
                            <input type="text" name="cedula" class="form-control" required value="<?= htmlspecialchars($persona['cedula']) ?>"
                                   pattern="[0-9]+"
                                   minlength="7" maxlength="8">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Nacionalidad</label>
                            <select name="idNacionalidad" class="form-select">
                                <?php foreach ($nacionalidades as $n): ?>
                                    <option value="<?= $n['IdNacionalidad'] ?>" <?= selected($n['IdNacionalidad'], $persona['IdNacionalidad']) ?>>
                                        <?= $n['nacionalidad'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Sexo</label>
                            <select name="idSexo" class="form-select">
                                <option value="">Seleccione</option>
                                <?php foreach ($sexos as $s): ?>
                                    <option value="<?= $s['IdSexo'] ?>" <?= selected($s['IdSexo'], $persona['IdSexo']) ?>>
                                        <?= $s['sexo'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Correo</label>
                            <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($persona['correo']) ?>"
                                   minlength="10" maxlength="50">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Parentesco</label>
                            <select name="idParentesco" class="form-select">
                                <?php foreach ($parentescos as $p): ?>
                                    <option value="<?= $p['IdParentesco'] ?>" <?= selected($p['IdParentesco'], $representante['IdParentesco']) ?>>
                                        <?= $p['parentesco'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Urbanismo</label>
                            <select name="urbanismo" class="form-select">
                                <?php foreach ($urbanismos as $u): ?>
                                    <option value="<?= $u['IdUrbanismo'] ?>" <?= selected($u['IdUrbanismo'], $persona['IdUrbanismo']) ?>>
                                        <?= $u['urbanismo'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($persona['direccion']) ?>">
                        </div>

                        <!-- DATOS LABORALES -->
                        <div class="col-md-6">
                            <label class="form-label">Ocupación</label>
                            <input type="text" name="ocupacion" class="form-control" value="<?= htmlspecialchars($representante['ocupacion']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Lugar de trabajo</label>
                            <input type="text" name="lugar_trabajo" class="form-control" value="<?= htmlspecialchars($representante['lugar_trabajo']) ?>">
                        </div>

                        <!-- TELÉFONOS -->
                        <div class="col-12">
                            <label class="form-label">Teléfonos</label>
                            <div id="telefonos-container">
                                <?php if (!empty($telefonos)): ?>
                                    <?php foreach ($telefonos as $t): ?>
                                        <div class="row g-2 mb-2 phone-group">
                                            <div class="col-md-6">
                                                <input type="text" name="telefono[]" class="form-control" placeholder="Número" value="<?= htmlspecialchars($t['numero_telefono']) ?>"
                                                       pattern="[0-9]+" minlength="10" maxlength="10">
                                            </div>
                                            <div class="col-md-4">
                                                <select name="tipo_telefono[]" class="form-select">
                                                    <?php foreach ($tiposTelefono as $tipo): ?>
                                                        <option value="<?= $tipo['IdTipo_Telefono'] ?>" <?= selected($tipo['IdTipo_Telefono'], $t['IdTipo_Telefono']) ?>>
                                                            <?= $tipo['tipo_telefono'] ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-center">
                                                <i class="fas fa-minus-circle remove-phone"></i>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="row g-2 mb-2 phone-group">
                                        <div class="col-md-6">
                                            <input type="text" name="telefono[]" class="form-control" placeholder="Número"
                                                   pattern="[0-9]+" minlength="10" maxlength="10">
                                        </div>
                                        <div class="col-md-4">
                                            <select name="tipo_telefono[]" class="form-select">
                                                <option value="">Tipo</option>
                                                <?php foreach ($tiposTelefono as $tipo): ?>
                                                    <option value="<?= $tipo['IdTipo_Telefono'] ?>"><?= $tipo['tipo_telefono'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-center">
                                            <i class="fas fa-minus-circle remove-phone"></i>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="add-phone mt-2"><i class="fas fa-plus-circle me-1"></i> Añadir teléfono</div>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="fas fa-save me-2"></i>Guardar cambios
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>

<script src="../../../assets/js/validaciones_solicitud.js"></script>
<script src="../../../assets/js/validaciones_representante.js"></script>
<script>
document.querySelector('.add-phone').addEventListener('click', () => {
    const container = document.getElementById('telefonos-container');
    const html = `
        <div class="row g-2 mb-2 phone-group">
            <div class="col-md-6">
                <input type="text" name="telefono[]" class="form-control" placeholder="Número" pattern="[0-9]+" minlength="10" maxlength="10">
            </div>
            <div class="col-md-4">
                <select name="tipo_telefono[]" class="form-select">
                    <option value="">Tipo</option>
                    <?php foreach ($tiposTelefono as $t): ?>
                        <option value="<?= $t['IdTipo_Telefono'] ?>"><?= $t['tipo_telefono'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-center">
                <i class="fas fa-minus-circle remove-phone"></i>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
});

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-phone')) {
        e.target.closest('.phone-group').remove();
    }
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
                    <input type="hidden" name="idRepresentante" value="<?= $id ?>">

                    <!-- Vista previa -->
                    <div class="photo-preview-container" id="photoPreviewContainer">
                        <?php if (!empty($persona['foto_perfil']) && file_exists(__DIR__ . '/../../../' . $persona['foto_perfil'])): ?>
                            <img src="<?= htmlspecialchars('../../../' . $persona['foto_perfil']) ?>"
                                 alt="Vista previa"
                                 id="photoPreview">
                        <?php else: ?>
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Crect fill='%23667eea' width='200' height='200'/%3E%3Ctext fill='white' font-size='80' font-family='Arial' x='50%25' y='50%25' text-anchor='middle' dy='.3em'%3E%3F%3C/text%3E%3C/svg%3E"
                                 alt="Vista previa"
                                 id="photoPreview">
                        <?php endif; ?>
                    </div>

                    <!-- Área de carga -->
                    <div class="photo-upload-area" onclick="document.getElementById('inputFotoRep').click()">
                        <i class='bx bx-cloud-upload'></i>
                        <p class="mb-2"><strong>Haz clic para seleccionar una foto</strong></p>
                        <p class="text-muted mb-0" style="font-size: 0.85rem;">
                            Formatos permitidos: JPG, JPEG, PNG (Máx. 2MB)
                        </p>
                    </div>

                    <input type="file"
                           id="inputFotoRep"
                           name="foto"
                           accept="image/jpeg,image/jpg,image/png"
                           style="display: none;"
                           onchange="previewPhotoRep(this)">

                    <div id="errorFoto" class="alert alert-danger mt-3" style="display: none;"></div>
                    <div id="successFoto" class="alert alert-success mt-3" style="display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class='bx bx-x me-1'></i>Cancelar
                </button>
                <button type="button" class="btn btn-danger" onclick="uploadPhotoRep()" id="btnGuardarFoto">
                    <i class='bx bx-save me-1'></i>Guardar Foto
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedFileRep = null;

function previewPhotoRep(input) {
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

        selectedFileRep = file;

        // Mostrar vista previa
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

function uploadPhotoRep() {
    const errorDiv = document.getElementById('errorFoto');
    const successDiv = document.getElementById('successFoto');
    const btnGuardar = document.getElementById('btnGuardarFoto');

    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';

    if (!selectedFileRep) {
        errorDiv.textContent = 'Por favor selecciona una foto primero';
        errorDiv.style.display = 'block';
        return;
    }

    const formData = new FormData(document.getElementById('formFotoPerfil'));

    // Deshabilitar botón
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Guardando...';

    fetch('../../../controladores/usuario/actualizar_foto.php', {
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
    selectedFileRep = null;
    document.getElementById('inputFotoRep').value = '';
    document.getElementById('errorFoto').style.display = 'none';
    document.getElementById('successFoto').style.display = 'none';
    document.getElementById('btnGuardarFoto').disabled = false;
    document.getElementById('btnGuardarFoto').innerHTML = '<i class="bx bx-save me-1"></i>Guardar Foto';
});
</script>
