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

// Obtener ID del usuario a editar
$idUsuario = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idUsuario <= 0) {
    header("Location: usuario.php");
    exit();
}

// Incluir Notificaciones
require_once __DIR__ . '/../../../controladores/Notificaciones.php';

// Manejo de alertas
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito("El usuario se actualizó correctamente.");
            break;
        case 'error':
            $alerta = Notificaciones::advertencia("Error al actualizar el usuario.");
            break;
        default:
            $alerta = null;
    }

    if ($alerta) {
        Notificaciones::mostrar($alerta);
    }
}

// Cargar modelos y datos
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Persona.php';
require_once __DIR__ . '/../../../modelos/Perfil.php';
require_once __DIR__ . '/../../../modelos/DetallePerfil.php';
require_once __DIR__ . '/../../../modelos/Status.php';
require_once __DIR__ . '/../../../modelos/TipoTelefono.php';
require_once __DIR__ . '/../../../modelos/Telefono.php';

$database = new Database();
$conexion = $database->getConnection();

$personaModel = new Persona($conexion);
$usuario = $personaModel->obtenerPorId($idUsuario); // Cargar datos

// Para guardar cambios:
if ($personaModel->actualizar()) {
    // Actualización exitosa
}

// Para cambiar contraseña (si se proporcionó):
if (!empty($_POST['password'])) {
    $personaModel->actualizarPassword($_POST['password']);
}
if (!$usuario) {
    header("Location: usuario.php");
    exit();
}

// Cargar datos relacionados
$perfilModel = new Perfil($conexion);
$roles = $perfilModel->obtenerTodos();

$detallePerfilModel = new DetallePerfil($conexion);
$rolesUsuario = $detallePerfilModel->obtenerPorPersona($idUsuario);

$statusModel = new Status($conexion);
$statuses = $statusModel->obtenerTodos();

$tipoTelefonoModel = new TipoTelefono($conexion);
$tiposTelefono = $tipoTelefonoModel->obtenerTodos();

$telefonoModel = new Telefono($conexion);
$telefonosUsuario = $telefonoModel->obtenerPorPersona($idUsuario);
?>

<head>
    <title>UECFT Araure - Editar Usuario</title>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-danger text-white text-center">
                            <h4 class="mb-0"><i class='bx bxs-user-edit'></i> Editar Usuario</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/PersonaController.php?action=editar" method="POST" id="editar">
                                <input type="hidden" name="id" value="<?= $idUsuario ?>">
                                
                                <div class="row">
                                    <!-- Columna Izquierda -->
                                    <div class="col-md-6">
                                        <!-- Nombre -->
                                        <div class="añadir__grupo" id="grupo__nombre">
                                            <label for="nombre" class="form-label">Nombre *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-user'></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input" 
                                                    name="nombre" 
                                                    id="nombre" 
                                                    required 
                                                    maxlength="40"
                                                    value="<?= htmlspecialchars($usuario['nombre']) ?>"
                                                    onkeypress="return onlyText(event)">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">El nombre debe tener entre 3 y 40 letras.</p>
                                        </div>

                                        <!-- Apellido -->
                                        <div class="añadir__grupo" id="grupo__apellido">
                                            <label for="apellido" class="form-label">Apellido *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-user'></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input" 
                                                    name="apellido" 
                                                    id="apellido" 
                                                    required 
                                                    maxlength="40"
                                                    value="<?= htmlspecialchars($usuario['apellido']) ?>"
                                                    onkeypress="return onlyText(event)">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">El apellido debe tener entre 3 y 40 letras.</p>
                                        </div>

                                        <!-- Cédula -->
                                        <div class="añadir__grupo" id="grupo__cedula">
                                            <label for="cedula" class="form-label">Cédula *</label>
                                            <div class="input-group">
                                                <!-- Nacionalidad (V/E) -->
                                                <select 
                                                    name="nacionalidad" 
                                                    id="nacionalidad" 
                                                    class="form-select form-select-sm"
                                                    style="
                                                        border-top-right-radius: 0;
                                                        border-bottom-right-radius: 0;
                                                        border-right: none;
                                                        max-width: 60px;
                                                        text-align: center;
                                                        font-weight: bold;
                                                        background: #f8f9fa;
                                                        color: #c90000;
                                                        font-size: 0.9rem;
                                                    ">
                                                    <option value="V" <?= $usuario['IdNacionalidad'] == 1 ? 'selected' : '' ?>>V</option>
                                                    <option value="E" <?= $usuario['IdNacionalidad'] == 2 ? 'selected' : '' ?>>E</option>
                                                </select>
                                                
                                                <!-- Número de cédula -->
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input" 
                                                    name="cedula" 
                                                    id="cedula" 
                                                    required 
                                                    maxlength="8"
                                                    pattern="^[0-9]+$"
                                                    value="<?= htmlspecialchars($usuario['cedula']) ?>"
                                                    onkeypress="return onlyNumber(event)"
                                                    style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                                
                                                <!-- Icono de documento -->
                                                <span class="input-group-text">
                                                    <i class='bx bxs-id-card' style="color: #c90000;"></i>
                                                </span>
                                                
                                                <!-- Icono de estado (✔️/❌) -->
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">La cédula debe tener entre 7 y 8 números.</p>
                                        </div>

                                        <!-- Correo -->
                                        <div class="añadir__grupo" id="grupo__correo">
                                            <label for="correo" class="form-label">Correo</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-envelope'></i></span>
                                                <input 
                                                    type="email" 
                                                    class="form-control añadir__input" 
                                                    name="correo" 
                                                    id="correo" 
                                                    maxlength="50"
                                                    value="<?= htmlspecialchars($usuario['correo']) ?>">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">El correo no es válido.</p>
                                        </div>
                                    </div>

                                    <!-- Columna Derecha -->
                                    <div class="col-md-6">
                                        <!-- Usuario -->
                                        <div class="añadir__grupo" id="grupo__usuario">
                                            <label for="usuario" class="form-label">Usuario *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-user'></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input" 
                                                    name="usuario" 
                                                    id="usuario" 
                                                    required 
                                                    maxlength="20"
                                                    value="<?= htmlspecialchars($usuario['usuario']) ?>">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">El usuario debe tener entre 4 y 20 dígitos (letras, números, guion).</p>
                                        </div>

                                        <!-- Contraseña -->
                                        <div class="añadir__grupo" id="grupo__password">
                                            <label for="password" class="form-label">Contraseña *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-lock-alt'></i></span>
                                                <input 
                                                    type="password" 
                                                    class="form-control añadir__input" 
                                                    name="password" 
                                                    id="password" 
                                                    maxlength="20"
                                                    placeholder="Dejar vacío para no cambiar">
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                                    <i class="bx bx-low-vision"></i>
                                                </button>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">La contraseña debe tener entre 4 y 20 dígitos.</p>
                                        </div>

                                        <!-- Confirmar Contraseña -->
                                        <div class="añadir__grupo" id="grupo__password2">
                                            <label for="password2" class="form-label">Repetir Contraseña</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-lock-alt'></i></span>
                                                <input 
                                                    type="password" 
                                                    class="form-control añadir__input" 
                                                    name="password2" 
                                                    id="password2" 
                                                    maxlength="20"
                                                    placeholder="Dejar vacío para no cambiar">
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password2')">
                                                    <i class="bx bx-low-vision"></i>
                                                </button>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Las contraseñas deben coincidir.</p>
                                        </div>

                                        <!-- Status -->
                                        <div class="añadir__grupo" id="grupo__status">
                                            <label for="status" class="form-label">Status *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-star'></i></span>
                                                <select 
                                                    class="form-control añadir__input" 
                                                    name="status" 
                                                    id="status" 
                                                    required>
                                                    <?php foreach ($statuses as $status): ?>
                                                        <option value="<?= $status['IdStatus'] ?>" 
                                                            <?= $status['IdStatus'] == $usuario['IdStatus'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($status['status']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Debe seleccionar un status.</p>
                                        </div>
                                    </div>

                                    <!-- Roles -->
                                    <div class="añadir__grupo" id="grupo__roles">
                                        <label for="roles" class="form-label">Rol(es) *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class='bx bxs-group'></i></span>
                                            
                                            <!-- Contenedor de chips (etiquetas seleccionadas) -->
                                            <div class="chips-container" style="flex-grow: 1; min-height: 45px; padding: 8px; border: 1px solid #ddd; border-radius: 6px; background: white; display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                                                <?php 
                                                $rolesSeleccionados = [];
                                                foreach ($rolesUsuario as $rolUsuario) {
                                                    $rolesSeleccionados[] = $rolUsuario['IdPerfil'];
                                                    echo '<div class="chip" onclick="removeChip('.$rolUsuario['IdPerfil'].')">';
                                                    echo htmlspecialchars($rolUsuario['nombre_perfil']);
                                                    echo '<i class="bx bx-x" onclick="removeChip('.$rolUsuario['IdPerfil'].')"></i>';
                                                    echo '</div>';
                                                }
                                                ?>
                                                <input 
                                                    type="text" 
                                                    id="roles-search" 
                                                    style="border: none; outline: none; flex-grow: 1; min-width: 120px;">
                                            </div>
                                            
                                            <!-- Select oculto para enviar datos -->
                                            <select name="roles[]" id="roles" multiple required style="display: none;">
                                                <?php foreach ($roles as $rol): ?>
                                                    <option value="<?= $rol['IdPerfil'] ?>" 
                                                        <?= in_array($rol['IdPerfil'], array_column($rolesUsuario, 'IdPerfil')) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($rol['nombre_perfil']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <p class="añadir__input-error">Debe seleccionar al menos un rol.</p>
                                    </div>
                                </div>

                                <!-- Campos de teléfono -->
                                <div class="añadir__grupo" id="grupo__telefonos">
                                    <label class="form-label">Teléfono(s)</label>
                                    
                                    <div id="telefonos-container">
                                        <?php if (!empty($telefonosUsuario)): ?>
                                            <?php foreach ($telefonosUsuario as $index => $tel): ?>
                                                <div class="telefono-item mb-3">
                                                    <div class="input-group">
                                                        <!-- Selector de tipo -->
                                                        <select class="form-select añadir__input tipo-telefono" name="telefonos[<?= $index ?>][tipo]"
                                                                style="max-width: 150px; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                                                            <?php foreach ($tiposTelefono as $tipo): ?>
                                                                <option value="<?= $tipo['IdTipo_Telefono'] ?>" 
                                                                    <?= $tipo['IdTipo_Telefono'] == $tel['IdTipo_Telefono'] ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($tipo['tipo_telefono']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        
                                                        <!-- Número de teléfono -->
                                                        <input type="text" 
                                                            class="form-control añadir__input numero-telefono" 
                                                            name="telefonos[<?= $index ?>][numero]"
                                                            value="<?= htmlspecialchars($tel['numero_telefono']) ?>"
                                                            placeholder="Ej: 04141234567"
                                                            maxlength="15"
                                                            style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                                        
                                                        <!-- Botones -->
                                                        <button type="button" class="btn btn-outline-danger btn-eliminar-telefono" <?= $index == 0 ? 'style="display: none;"' : '' ?>>
                                                            <i class='bx bx-trash'></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-success btn-agregar-telefono" <?= $index != count($telefonosUsuario) - 1 ? 'style="display: none;"' : '' ?>>
                                                            <i class='bx bx-plus'></i>
                                                        </button>
                                                    </div>
                                                    <p class="añadir__input-error">El teléfono debe ser válido</p>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <!-- Si no hay teléfonos, mostrar un campo vacío -->
                                            <div class="telefono-item mb-3">
                                                <div class="input-group">
                                                    <select class="form-select añadir__input tipo-telefono" name="telefonos[0][tipo]"
                                                            style="max-width: 150px; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                                                        <?php foreach ($tiposTelefono as $tipo): ?>
                                                            <option value="<?= $tipo['IdTipo_Telefono'] ?>" <?= $tipo['IdTipo_Telefono'] == 2 ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($tipo['tipo_telefono']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <input type="text" 
                                                        class="form-control añadir__input numero-telefono" 
                                                        name="telefonos[0][numero]"
                                                        placeholder="Ej: 04141234567"
                                                        maxlength="15"
                                                        style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                                    <button type="button" class="btn btn-outline-danger btn-eliminar-telefono" style="display: none;">
                                                        <i class='bx bx-trash'></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success btn-agregar-telefono">
                                                        <i class='bx bx-plus'></i>
                                                    </button>
                                                </div>
                                                <p class="añadir__input-error">El teléfono debe ser válido</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Botón -->
                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Actualizar Usuario
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>

<script src="../../../assets/js/validacion.js"></script>
<script src="../../../assets/js/formulario.js"></script>
<script src="../../../assets/js/usuario_chips.js"></script>
<script src="../../../assets/js/telefonos.js"></script>

<script>
    function togglePassword(id) {
        const input = document.getElementById(id);
        const icon = input.nextElementSibling.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bx-low-vision', 'bx-show');
        } else {
            input.type = 'password';
            icon.classList.replace('bx-show', 'bx-low-vision');
        }
    }

    // Inicializar chips de roles seleccionados
    document.addEventListener('DOMContentLoaded', function() {
        // Validar que existan roles seleccionados
        if (document.querySelectorAll('.chip').length > 0) {
            const grupoRoles = document.getElementById('grupo__roles');
            grupoRoles.classList.add('añadir__grupo-correcto');
            
            // Actualizar icono de validación
            const icon = grupoRoles.querySelector('.añadir__validacion-estado');
            if (icon) {
                icon.className = 'añadir__validacion-estado fas fa-check-circle';
                icon.style.color = '#1ed12d';
            }
        }

        // Inicializar telefonos existentes
        const telefonosContainer = document.getElementById('telefonos-container');
        if (telefonosContainer.querySelectorAll('.telefono-item').length > 0) {
            telefonosContainer.querySelectorAll('.telefono-item').forEach(item => {
                const numero = item.querySelector('.numero-telefono').value;
                if (numero.trim() !== '') {
                    item.classList.remove('añadir__grupo-incorrecto');
                    item.classList.add('añadir__grupo-correcto');
                }
            });
        }
    });

    // Función global para eliminar chips (necesaria para los chips precargados)
    function removeChip(id) {
        const chip = document.querySelector(`.chip i[onclick="removeChip(${id})"]`)?.parentElement;
        if (chip) {
            chip.remove();
            
            // Actualizar el select oculto
            const option = document.querySelector(`#roles option[value="${id}"]`);
            if (option) option.selected = false;
            
            // Validar
            const grupo = document.getElementById('grupo__roles');
            if (document.querySelectorAll('.chip').length === 0) {
                grupo.classList.remove('añadir__grupo-correcto');
                grupo.classList.add('añadir__grupo-incorrecto');
                const icon = grupo.querySelector('.añadir__validacion-estado');
                icon.className = 'añadir__validacion-estado fas fa-times-circle';
                icon.style.color = '#bb2929';
                grupo.querySelector('.añadir__input-error').classList.add('añadir__input-error-activo');
            }
        }
    }
</script>
</body>
</html>