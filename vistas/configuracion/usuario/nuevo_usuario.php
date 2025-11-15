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

// Manejo de alertas
$alert = $_SESSION['alert'] ?? null;
$message = $_SESSION['message'] ?? '';
unset($_SESSION['alert']);
unset($_SESSION['message']);

if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito($message ?: 'Operación realizada correctamente.');
            break;
        case 'error':
            $alerta = Notificaciones::advertencia($message ?: 'Ocurrió un error. Por favor verifique.');
            break;
        default:
            $alerta = null;
    }

    if ($alerta) {
        Notificaciones::mostrar($alerta);
    }
}

// === Cargar Roles desde el Modelo Perfil ===
$roles = [];

try {
    // Asegurarnos de que conexion.php define la clase Database
    require_once __DIR__ . '/../../../config/conexion.php';

    $database = new Database();
    $conexion = $database->getConnection();

    require_once __DIR__ . '/../../../modelos/Perfil.php';
    $perfil = new Perfil($conexion);
    $roles = $perfil->obtenerTodos();

} catch (Exception $e) {
    error_log("Error al cargar roles en nuevo_usuario.php: " . $e->getMessage());
    // Opcional: mostrar mensaje de advertencia
    $roles = []; // Dejar vacío si hay error
}

// === Cargar Status desde la base de datos (filtrados por uso) ===
$statusModel = null;
$statusesAcceso = [];
$statusesInstitucional = [];
try {
    require_once __DIR__ . '/../../../modelos/Status.php';
    $statusModel = new Status($conexion);
    $statusesAcceso = $statusModel->obtenerStatusAcceso();
    $statusesInstitucional = $statusModel->obtenerStatusInstitucional();
} catch (Exception $e) {
    error_log("Error al cargar statuses en nuevo_usuario.php: " . $e->getMessage());
    $statusesAcceso = [];
    $statusesInstitucional = [];
}

// Después de cargar los roles, añade esto:
require_once __DIR__ . '/../../../modelos/TipoTelefono.php';

// Luego carga los tipos de teléfono
try {
    $tipoTelefonoModel = new TipoTelefono($conexion);
    $tiposTelefono = $tipoTelefonoModel->obtenerTodos();
} catch (Exception $e) {
    error_log("Error al cargar tipos de teléfono: " . $e->getMessage());
    $tiposTelefono = [];
}
?>

<head>
    <title>UECFT Araure - Nuevo Usuario</title>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="card shadow-sm border-0" >
                        <div class="card-header bg-danger text-white text-center">
                            <h4 class="mb-0"><i class='bx bxs-user-plus'></i> Nuevo Usuario</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/PersonaController.php" method="POST" id="añadir">
                                <input type="hidden" name="action" value="crear">
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
                                                    pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                                    minlength="3" 
                                                    maxlength="40"
                                                    oninput="formatearTexto1()" 
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
                                                    pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                                    minlength="3" 
                                                    maxlength="40"
                                                    oninput="formatearTexto2()"
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
                                                    <option value="V">V</option>
                                                    <option value="E">E</option>
                                                </select>
                                                
                                                <!-- Número de cédula -->
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input" 
                                                    name="cedula" 
                                                    id="cedula" 
                                                    required 
                                                    minlength="7"
                                                    maxlength="8"
                                                    pattern="^[0-9]+"
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
                                                    required>
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
                                                    maxlength="20">
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
                                                    required 
                                                    maxlength="20">
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                                    <i class="bx bx-low-vision"></i>
                                                </button>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">La contraseña debe tener entre 4 y 20 dígitos.</p>
                                        </div>

                                        <!-- Confirmar Contraseña -->
                                        <div class="añadir__grupo" id="grupo__password2">
                                            <label for="password2" class="form-label">Repetir Contraseña *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-lock-alt'></i></span>
                                                <input 
                                                    type="password" 
                                                    class="form-control añadir__input" 
                                                    name="password2" 
                                                    id="password2" 
                                                    required 
                                                    maxlength="20">
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password2')">
                                                    <i class="bx bx-low-vision"></i>
                                                </button>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Las contraseñas deben coincidir.</p>
                                        </div>

                                        <!-- Status -->
                                        <div class="añadir__grupo" id="grupo__status_acceso">
                                            <label for="status_acceso" class="form-label">Status de Acceso *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-star'></i></span>
                                                <select
                                                    class="form-control añadir__input" 
                                                    name="status_acceso" 
                                                    id="status_acceso" 
                                                    required>
                                                    <?php foreach ($statusesAcceso as $status): ?>
                                                        <option value="<?= $status['IdStatus'] ?>" <?= $status['IdStatus'] == 1 ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($status['status']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">Debe seleccionar un status.</p>
                                        </div>

                                        <div class="añadir__grupo" id="grupo__status_institucional">
                                            <label for="status_institucional" class="form-label">Status Institucional *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-star'></i></span>
                                                <select 
                                                    class="form-control añadir__input" 
                                                    name="status_institucional" 
                                                    id="status_institucional" 
                                                    required>
                                                    <?php foreach ($statusesInstitucional as $status): ?>
                                                        <option value="<?= $status['IdStatus'] ?>" <?= $status['IdStatus'] == 1 ? 'selected' : '' ?>>
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
                                                <input 
                                                    type="text" 
                                                    id="roles-search" 
                                                    style="border: none; outline: none; flex-grow: 1; min-width: 120px;">
                                            </div>
                                            
                                            <!-- Select oculto para enviar datos -->
                                            <select 
                                                name="roles[]" 
                                                id="roles" 
                                                multiple 
                                                required 
                                                style="position: absolute; opacity: 0; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden;">
                                                <?php foreach ($roles as $rol): ?>
                                                    <option value="<?= $rol['IdPerfil'] ?>">
                                                        <?= htmlspecialchars($rol['nombre_perfil']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            
                                            <!-- <i class="añadir__validacion-estado fas fa-times-circle"></i> -->
                                        </div>
                                        <p class="añadir__input-error">Debe seleccionar al menos un rol.</p>
                                    </div>
                                </div>

                                <!-- Campos de teléfono -->
                                <div class="añadir__grupo" id="grupo__telefonos">
                                    <label class="form-label">Teléfono(s)</label>
                                    
                                    <div id="telefonos-container">
                                        <!-- Teléfono inicial -->
                                        <div class="telefono-item mb-3">
                                            <div class="input-group">
                                                <!-- Selector de tipo -->
                                                <select class="form-select añadir__input tipo-telefono" name="telefonos[0][tipo]"
                                                        style="max-width: 150px; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                                                    <?php foreach ($tiposTelefono as $tipo): ?>
                                                         <option value="<?= $tipo['IdTipo_Telefono'] ?>"
                                                                <?= $tipo['IdTipo_Telefono'] == 2 ? 'selected' : '' ?>
                                                                data-tipo="<?= $tipo['tipo_telefono'] ?>">
                                                            <?= htmlspecialchars($tipo['tipo_telefono']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <!-- Prefix selector -->
                                                <div class="position-relative" style="max-width: 90px;">
                                                    <input type="text" class="form-control buscador-input text-center fw-bold prefijo-telefono prefijo-input"
                                                           data-index="0" maxlength="4" data-prefijo-tipo="internacional"
                                                           onkeypress="return /[0-9+]/.test(event.key)"
                                                           oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                                                           style="border-radius: 0; border-right: none; background: #f8f9fa; color: #c90000;">
                                                    <input type="hidden" class="prefijo-hidden" name="telefonos[0][prefijo]">
                                                    <input type="hidden" class="prefijo-nombre-hidden" name="telefonos[0][prefijo_nombre]">
                                                    <div class="prefijo-resultados autocomplete-results d-none"></div>
                                                </div>

                                                <!-- Número de teléfono -->
                                                <input type="text"
                                                    class="form-control añadir__input numero-telefono"
                                                    name="telefonos[0][numero]"
                                                    placeholder="Ej: 4141234567"
                                                    pattern="^[0-9]+"
                                                    minlength="10"
                                                    maxlength="10"
                                                    onkeypress="return onlyNumber(event)"
                                                    style="border-top-left-radius: 0; border-bottom-left-radius: 0; border-top-right-radius: 0; border-bottom-right-radius: 0;" required>

                                                <button type="button" class="btn btn-outline-danger btn-eliminar-telefono" style="display: none;">
                                                    <i class='bx bx-trash'></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-success btn-agregar-telefono">
                                                    <i class='bx bx-plus'></i>
                                                </button>
                                            </div>
                                            <p class="añadir__input-error">El teléfono debe ser válido</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Botones para Volver y Guardar -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="usuario.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver a Usuarios
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Guardar Usuario
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
<script src="../../../assets/js/buscador_generico.js"></script>
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

    document.getElementById('status').addEventListener('mousedown', function(e) {
        e.preventDefault();
        this.blur();
        return false;
    });
</script>
</body>
</html>