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

require_once '../../../controladores/ContrasenaController.php';
require_once '../../../config/conexion.php';
require_once '../../../modelos/Persona.php';

$controller = new ContrasenaController();
$controller->manejarSolicitud();
$credenciales = $controller->obtenerCredenciales();
$alerta = $controller->alerta;

// Obtener datos del usuario para mostrar la foto actual
$database = new Database();
$conexion = $database->getConnection();
$personaModel = new Persona($conexion);
$usuario = $personaModel->obtenerPorId($_SESSION['idPersona']);
?>

<head>
    <title>UECFT Araure - Configuración de Cuenta</title>
</head>
<body>

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<style>
    .account-settings-header {
        background: linear-gradient(135deg, #c90000 0%, #a00000 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px 12px 0 0;
        text-align: center;
    }

    .account-settings-header h2 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 700;
    }

    .account-settings-header p {
        margin: 0.5rem 0 0 0;
        opacity: 0.95;
    }

    .settings-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .settings-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .settings-section {
        padding: 2rem;
        border-bottom: 1px solid #e9ecef;
    }

    .settings-section:last-child {
        border-bottom: none;
    }

    .section-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-title i {
        color: #c90000;
        font-size: 1.5rem;
    }

    /* Foto de Perfil */
    .profile-photo-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.5rem;
    }

    .profile-photo-preview {
        position: relative;
        width: 180px;
        height: 180px;
    }

    .profile-photo-wrapper {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        border: 5px solid #f8f9fa;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }

    .profile-photo-img {
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

    .photo-upload-btn {
        background: #c90000;
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 25px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .photo-upload-btn:hover {
        background: #a00000;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(201, 0, 0, 0.3);
    }

    .photo-info {
        text-align: center;
        color: #666;
        font-size: 0.9rem;
    }

    /* Formulario */
    .form-group-modern {
        margin-bottom: 1.5rem;
    }

    .form-group-modern label {
        display: block;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .input-wrapper {
        position: relative;
    }

    .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
        font-size: 1.2rem;
        z-index: 1;
    }

    .form-control-modern {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 3rem;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-control-modern:focus {
        outline: none;
        border-color: #c90000;
        box-shadow: 0 0 0 3px rgba(201, 0, 0, 0.1);
    }

    .toggle-password-btn {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        font-size: 1.2rem;
        padding: 0;
        z-index: 1;
    }

    .toggle-password-btn:hover {
        color: #c90000;
    }

    .input-help {
        font-size: 0.85rem;
        color: #666;
        margin-top: 0.5rem;
    }

    .input-error {
        font-size: 0.85rem;
        color: #dc3545;
        margin-top: 0.5rem;
        display: none;
    }

    .form-group-modern.error .form-control-modern {
        border-color: #dc3545;
    }

    .form-group-modern.error .input-error {
        display: block;
    }

    .form-group-modern.success .form-control-modern {
        border-color: #28a745;
    }

    .btn-save-changes {
        background: #c90000;
        color: white;
        border: none;
        padding: 1rem 2.5rem;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0 auto;
    }

    .btn-save-changes:hover {
        background: #a00000;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(201, 0, 0, 0.3);
    }

    .section-divider {
        border: none;
        border-top: 2px solid #e9ecef;
        margin: 2rem 0;
    }

    @media (max-width: 768px) {
        .settings-section {
            padding: 1.5rem;
        }

        .profile-photo-preview {
            width: 150px;
            height: 150px;
        }
    }
</style>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="settings-container">

            <!-- Mostrar alerta si existe -->
            <?php if ($alerta): ?>
                <?php Notificaciones::mostrar($alerta); ?>
            <?php endif; ?>

            <div class="settings-card">
                <!-- Header -->
                <div class="account-settings-header">
                    <h2><i class='bx bx-cog'></i> Configuración de Cuenta</h2>
                    <p>Administra tu foto de perfil, usuario y contraseña</p>
                </div>

                <!-- Sección: Foto de Perfil -->
                <div class="settings-section">
                    <h3 class="section-title">
                        <i class='bx bx-camera'></i>
                        Foto de Perfil
                    </h3>
                    <div class="profile-photo-section">
                        <div class="profile-photo-preview">
                            <div class="profile-photo-wrapper">
                                <?php if (!empty($usuario['foto_perfil']) && file_exists(__DIR__ . '/../../../' . $usuario['foto_perfil'])): ?>
                                    <img src="<?= htmlspecialchars('../../../' . $usuario['foto_perfil']) ?>"
                                         alt="Foto de perfil"
                                         class="profile-photo-img"
                                         id="currentProfilePhoto">
                                <?php else: ?>
                                    <div class="profile-photo-default" id="defaultProfilePhoto">
                                        <i class='bx bx-user'></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button type="button" class="photo-upload-btn" onclick="openPhotoModal()">
                            <i class='bx bx-upload'></i>
                            Cambiar Foto de Perfil
                        </button>
                        <p class="photo-info">JPG, JPEG o PNG. Tamaño máximo: 2MB</p>
                    </div>
                </div>

                <!-- Sección: Credenciales de Acceso -->
                <div class="settings-section">
                    <h3 class="section-title">
                        <i class='bx bx-user-check'></i>
                        Credenciales de Acceso
                    </h3>

                    <form action="" method="POST" id="formCredenciales" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Usuario -->
                                <div class="form-group-modern" id="grupo__usuario">
                                    <label for="usuario">Nombre de Usuario</label>
                                    <div class="input-wrapper">
                                        <i class='bx bx-user input-icon'></i>
                                        <input
                                            type="text"
                                            class="form-control-modern"
                                            name="usuario"
                                            id="usuario"
                                            value="<?= htmlspecialchars($credenciales['usuario']) ?>"
                                            placeholder="<?= htmlspecialchars($credenciales['usuario']) ?>"
                                            maxlength="20">
                                    </div>
                                    <p class="input-help">Usuario actual: <strong><?= htmlspecialchars($credenciales['usuario']) ?></strong>. Modifícalo si deseas cambiarlo.</p>
                                    <p class="input-error">El usuario debe tener entre 4 y 20 caracteres. Solo letras, números y guion bajo.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- Contraseña Actual -->
                                <div class="form-group-modern" id="grupo__password3">
                                    <label for="password3">Contraseña Actual *</label>
                                    <div class="input-wrapper">
                                        <i class='bx bx-lock-alt input-icon'></i>
                                        <input
                                            type="password"
                                            class="form-control-modern"
                                            name="password3"
                                            id="password3"
                                            placeholder="Ingresa tu contraseña actual"
                                            required
                                            maxlength="20">
                                        <button class="toggle-password-btn" type="button" onclick="togglePassword('password3')">
                                            <i class='bx bx-hide'></i>
                                        </button>
                                    </div>
                                    <p class="input-error">La contraseña actual es requerida</p>
                                </div>
                            </div>
                        </div>

                        <hr class="section-divider">

                        <div class="row">
                            <div class="col-md-6">
                                <!-- Nueva Contraseña -->
                                <div class="form-group-modern" id="grupo__password">
                                    <label for="password">Nueva Contraseña *</label>
                                    <div class="input-wrapper">
                                        <i class='bx bx-lock input-icon'></i>
                                        <input
                                            type="password"
                                            class="form-control-modern"
                                            name="password"
                                            id="password"
                                            placeholder="Ingresa nueva contraseña"
                                            required
                                            maxlength="20">
                                        <button class="toggle-password-btn" type="button" onclick="togglePassword('password')">
                                            <i class='bx bx-hide'></i>
                                        </button>
                                    </div>
                                    <p class="input-help">Mínimo 4 caracteres</p>
                                    <p class="input-error">La contraseña debe tener entre 4 y 20 caracteres</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- Confirmar Contraseña -->
                                <div class="form-group-modern" id="grupo__password2">
                                    <label for="password2">Confirmar Nueva Contraseña *</label>
                                    <div class="input-wrapper">
                                        <i class='bx bx-lock-open input-icon'></i>
                                        <input
                                            type="password"
                                            class="form-control-modern"
                                            name="password2"
                                            id="password2"
                                            placeholder="Repite la nueva contraseña"
                                            required
                                            maxlength="20">
                                        <button class="toggle-password-btn" type="button" onclick="togglePassword('password2')">
                                            <i class='bx bx-hide'></i>
                                        </button>
                                    </div>
                                    <p class="input-error">Las contraseñas no coinciden</p>
                                </div>
                            </div>
                        </div>

                        <!-- Botón -->
                        <div class="text-center mt-4">
                            <button type="submit" class="btn-save-changes">
                                <i class='bx bx-save'></i>
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
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
                    <!-- Vista previa -->
                    <div style="width: 200px; height: 200px; margin: 1.5rem auto; border-radius: 50%; overflow: hidden; border: 4px solid #e9ecef;">
                        <img src="<?= !empty($usuario['foto_perfil']) && file_exists(__DIR__ . '/../../../' . $usuario['foto_perfil']) ? htmlspecialchars('../../../' . $usuario['foto_perfil']) : 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\'%3E%3Crect fill=\'%23667eea\' width=\'200\' height=\'200\'/%3E%3Ctext fill=\'white\' font-size=\'80\' font-family=\'Arial\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3E%3F%3C/text%3E%3C/svg%3E' ?>"
                             alt="Vista previa"
                             id="photoPreviewModal"
                             style="width: 100%; height: 100%; object-fit: cover;">
                    </div>

                    <!-- Área de carga -->
                    <div style="border: 2px dashed #dee2e6; border-radius: 12px; padding: 2rem; text-align: center; background: #f8f9fa; cursor: pointer; transition: all 0.3s ease;"
                         onclick="document.getElementById('inputFotoModal').click()"
                         onmouseover="this.style.borderColor='#c90000'; this.style.background='#fff'"
                         onmouseout="this.style.borderColor='#dee2e6'; this.style.background='#f8f9fa'">
                        <i class='bx bx-cloud-upload' style="font-size: 3rem; color: #c90000; margin-bottom: 1rem; display: block;"></i>
                        <p style="margin-bottom: 0.5rem;"><strong>Haz clic para seleccionar una foto</strong></p>
                        <p style="color: #666; font-size: 0.85rem; margin: 0;">
                            Formatos permitidos: JPG, JPEG, PNG (Máx. 2MB)
                        </p>
                    </div>

                    <input type="file"
                           id="inputFotoModal"
                           name="foto"
                           accept="image/jpeg,image/jpg,image/png"
                           style="display: none;"
                           onchange="previewPhotoModal(this)">

                    <div id="errorFotoModal" class="alert alert-danger mt-3" style="display: none;"></div>
                    <div id="successFotoModal" class="alert alert-success mt-3" style="display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class='bx bx-x me-1'></i>Cancelar
                </button>
                <button type="button" class="btn btn-danger" onclick="uploadPhotoModal()" id="btnGuardarFotoModal">
                    <i class='bx bx-save me-1'></i>Guardar Foto
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../../layouts/footer.php'; ?>

<script src="../../../assets/js/formulario.js"></script>
<script src="../../../assets/js/validacion.js"></script>
<script>
// === FUNCIONES PARA FOTO DE PERFIL ===
let selectedFileModal = null;

function openPhotoModal() {
    const modal = new bootstrap.Modal(document.getElementById('modalFotoPerfil'));
    modal.show();
}

function previewPhotoModal(input) {
    const errorDiv = document.getElementById('errorFotoModal');
    const successDiv = document.getElementById('successFotoModal');
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

        selectedFileModal = file;

        // Mostrar vista previa
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreviewModal').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

function uploadPhotoModal() {
    const errorDiv = document.getElementById('errorFotoModal');
    const successDiv = document.getElementById('successFotoModal');
    const btnGuardar = document.getElementById('btnGuardarFotoModal');

    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';

    if (!selectedFileModal) {
        errorDiv.textContent = 'Por favor selecciona una foto primero';
        errorDiv.style.display = 'block';
        return;
    }

    const formData = new FormData();
    formData.append('foto', selectedFileModal);
    formData.append('idPersona', <?= $_SESSION['idPersona'] ?>);

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
    selectedFileModal = null;
    document.getElementById('inputFotoModal').value = '';
    document.getElementById('errorFotoModal').style.display = 'none';
    document.getElementById('successFotoModal').style.display = 'none';
    document.getElementById('btnGuardarFotoModal').disabled = false;
    document.getElementById('btnGuardarFotoModal').innerHTML = '<i class="bx bx-save me-1"></i>Guardar Foto';
});

// === FUNCIONES PARA CONTRASEÑA ===
function togglePassword(id) {
    const input = document.getElementById(id);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bx-hide');
        icon.classList.add('bx-show');
    } else {
        input.type = 'password';
        icon.classList.remove('bx-show');
        icon.classList.add('bx-hide');
    }
}

// === VALIDACIONES ADICIONALES PARA ESTA PÁGINA ===
const usuarioActual = '<?= htmlspecialchars($credenciales['usuario']) ?>';

// Validar usuario en tiempo real
document.getElementById('usuario').addEventListener('input', function() {
    const grupo = document.getElementById('grupo__usuario');
    const valor = this.value.trim();
    const regex = /^[a-zA-Z0-9_-]{4,20}$/;

    if (valor === '' || valor === usuarioActual) {
        grupo.classList.remove('error', 'success');
    } else if (!regex.test(valor)) {
        grupo.classList.add('error');
        grupo.classList.remove('success');
    } else {
        grupo.classList.add('success');
        grupo.classList.remove('error');
    }
});

// Validar nueva contraseña en tiempo real (longitud mínima)
document.getElementById('password').addEventListener('input', function() {
    const grupo = document.getElementById('grupo__password');
    const valor = this.value;

    if (valor === '') {
        grupo.classList.remove('error', 'success');
    } else if (valor.length >= 4 && valor.length <= 20) {
        grupo.classList.add('success');
        grupo.classList.remove('error');
    } else {
        grupo.classList.add('error');
        grupo.classList.remove('success');
    }

    // Re-validar password2 si tiene valor
    const password2 = document.getElementById('password2').value;
    if (password2 !== '') {
        document.getElementById('password2').dispatchEvent(new Event('input'));
    }

    // Validar si password3 es requerido
    validatePassword3Requirement();
});

// Validar coincidencia de contraseñas en tiempo real
document.getElementById('password2').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const password2 = this.value;
    const grupo = document.getElementById('grupo__password2');

    if (password2 === '') {
        grupo.classList.remove('error', 'success');
    } else if (password === password2 && password !== '') {
        grupo.classList.add('success');
        grupo.classList.remove('error');
    } else {
        grupo.classList.add('error');
        grupo.classList.remove('success');
    }

    // Validar si password3 es requerido
    validatePassword3Requirement();
});

// Validar contraseña actual condicionalmente
document.getElementById('password3').addEventListener('input', function() {
    validatePassword3Requirement();
});

// Función para validar si password3 es requerido
function validatePassword3Requirement() {
    const password = document.getElementById('password').value;
    const password2 = document.getElementById('password2').value;
    const password3Input = document.getElementById('password3');
    const grupo = document.getElementById('grupo__password3');

    // Si alguno de los campos de nueva contraseña tiene valor, password3 es requerido
    if (password !== '' || password2 !== '') {
        if (password3Input.value === '') {
            grupo.classList.add('error');
            grupo.classList.remove('success');
        } else {
            grupo.classList.add('success');
            grupo.classList.remove('error');
        }
    } else {
        // Ambos campos de nueva contraseña vacíos, volver a estado neutral
        grupo.classList.remove('error', 'success');
    }
}

// Prevenir el envío del formulario si hay errores
document.getElementById('formCredenciales').addEventListener('submit', function (e) {
    const password3 = document.getElementById('password3').value;
    const password = document.getElementById('password').value;
    const password2 = document.getElementById('password2').value;
    const usuario = document.getElementById('usuario').value.trim();

    // Verificar si se está intentando cambiar el usuario o la contraseña
    const cambiaUsuario = usuario !== '' && usuario !== usuarioActual;
    const cambiaPassword = password !== '' || password2 !== '';

    // Si no hay cambios, mostrar advertencia
    if (!cambiaUsuario && !cambiaPassword) {
        e.preventDefault();
        Swal.fire({
            icon: 'info',
            title: 'Sin cambios',
            text: 'No has realizado ningún cambio.',
            confirmButtonColor: '#c90000'
        });
        return;
    }

    // Si intenta cambiar contraseña o usuario, requiere contraseña actual
    if ((cambiaPassword || cambiaUsuario) && password3 === '') {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Contraseña actual requerida',
            text: 'Para realizar cambios en tu cuenta, debes ingresar tu contraseña actual por seguridad.',
            confirmButtonColor: '#c90000'
        });
        return;
    }

    // Si se intenta cambiar la contraseña, validar que coincidan
    if (password !== '' && password !== password2) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Las contraseñas no coinciden',
            text: 'La nueva contraseña y su confirmación deben ser iguales.',
            confirmButtonColor: '#c90000'
        });
        return;
    }

    // Si se intenta cambiar la contraseña, validar longitud
    if (password !== '' && (password.length < 4 || password.length > 20)) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Contraseña inválida',
            text: 'La nueva contraseña debe tener entre 4 y 20 caracteres.',
            confirmButtonColor: '#c90000'
        });
        return;
    }

    // Validar usuario si se cambió
    if (usuario !== '' && usuario !== usuarioActual) {
        const regex = /^[a-zA-Z0-9_-]{4,20}$/;
        if (!regex.test(usuario)) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Usuario inválido',
                text: 'El usuario debe tener entre 4 y 20 caracteres. Solo se permiten letras, números, guion y guion bajo.',
                confirmButtonColor: '#c90000'
            });
            return;
        }
    }

    // Si todo está bien, el formulario se envía
});
</script>