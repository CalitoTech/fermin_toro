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
                                <input type="hidden" name="action" value="crear" id="form-action">
                                <input type="hidden" name="IdPersona" id="IdPersona" value="">
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

    // === VALIDACIÓN EN TIEMPO REAL DE CÉDULA ===
    let cedulaTimer;
    const cedulaInput = document.getElementById('cedula');
    const nacionalidadSelect = document.getElementById('nacionalidad');

    async function verificarCedulaCompleto() {
        const cedula = cedulaInput.value.trim();
        const nacionalidadLetra = nacionalidadSelect.value; // V o E

        // Convertir V/E a IdNacionalidad (1=V, 2=E)
        const idNacionalidad = nacionalidadLetra === 'V' ? 1 : 2;

        if (cedula.length < 7) {
            return;
        }

        try {
            const response = await fetch(`../../../controladores/PersonaController.php?action=verificarCedulaCompleto&cedula=${cedula}&idNacionalidad=${idNacionalidad}`);
            const data = await response.json();

            if (data.existe) {
                // Verificar si puede convertirse (estudiante mayor de 18)
                if (data.puedeConvertirse) {
                    const result = await Swal.fire({
                        title: 'Estudiante Registrado',
                        html: `La cédula <strong>${nacionalidadLetra}-${cedula}</strong> le pertenece al estudiante <strong>${data.persona.nombreCompleto}</strong> de <strong>${data.edad} años</strong>.<br><br>¿Desea asignarle un puesto de trabajo a este estudiante?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#c90000',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, continuar',
                        cancelButtonText: 'Cancelar'
                    });

                    if (result.isConfirmed) {
                        // Cambiar a modo actualización
                        convertirAModoEdicion(data.persona);
                    } else {
                        // Limpiar el campo de cédula
                        cedulaInput.value = '';
                        cedulaInput.focus();
                    }
                } else {
                    // Cédula duplicada (no es estudiante o es menor de 18)
                    let mensaje = `La cédula ${nacionalidadLetra}-${cedula} ya está registrada`;

                    if (data.esEstudiante) {
                        mensaje += ` para el estudiante <strong>${data.persona.nombreCompleto}</strong> de ${data.edad} años.<br><br>No se puede asignar un puesto de trabajo a estudiantes menores de 18 años.`;
                    } else {
                        mensaje += ` para <strong>${data.persona.nombreCompleto}</strong>.`;
                    }

                    Swal.fire({
                        title: 'Cédula Duplicada',
                        html: mensaje,
                        icon: 'error',
                        confirmButtonColor: '#c90000'
                    });

                    cedulaInput.value = '';
                    cedulaInput.focus();
                }
            }
        } catch (error) {
            console.error('Error al verificar cédula:', error);
        }
    }

    function convertirAModoEdicion(persona) {
        // Cambiar el action del formulario
        document.getElementById('form-action').value = 'editar';
        document.getElementById('IdPersona').value = persona.IdPersona;

        // Cambiar el título
        document.querySelector('.card-header h4').innerHTML = '<i class="bx bxs-edit"></i> Editar Usuario (Ex-Estudiante)';

        // Pre-llenar el formulario
        document.getElementById('nombre').value = persona.nombre || '';
        document.getElementById('apellido').value = persona.apellido || '';
        document.getElementById('cedula').value = persona.cedula || '';
        document.getElementById('nacionalidad').value = persona.nacionalidad === 'V' ? 'V' : 'E';
        document.getElementById('correo').value = persona.correo || '';

        // Pre-llenar usuario si existe
        if (persona.usuario) {
            document.getElementById('usuario').value = persona.usuario;
        }

        // Pre-seleccionar el perfil del estudiante en los chips (IdPerfil 3)
        // El estudiante ya tiene IdPerfil = 3, pero podemos removerlo y dejar que el usuario seleccione los nuevos roles
        // Por ahora lo dejamos sin seleccionar para que el usuario elija el rol de trabajador

        // Deshabilitar campos que no deben cambiar
        document.getElementById('cedula').readOnly = true;
        document.getElementById('nacionalidad').disabled = true;

        // Cambiar el texto del botón
        const submitBtn = document.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="bx bxs-save"></i> Actualizar Usuario';

        // Pre-llenar teléfonos si existen
        if (persona.telefonos && persona.telefonos.length > 0) {
            const telefonosContainer = document.getElementById('telefonos-container');
            telefonosContainer.innerHTML = ''; // Limpiar teléfonos existentes

            persona.telefonos.forEach((tel, index) => {
                // Crear el HTML del teléfono
                const telefonoHTML = `
                    <div class="telefono-item mb-3">
                        <div class="input-group">
                            <select class="form-select añadir__input tipo-telefono" name="telefonos[${index}][tipo]"
                                    style="max-width: 150px; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                                <option value="1">Personal</option>
                                <option value="2" selected>Celular</option>
                                <option value="3">Trabajo</option>
                            </select>
                            <div class="position-relative" style="max-width: 90px;">
                                <input type="text" class="form-control buscador-input text-center fw-bold prefijo-telefono prefijo-input"
                                       data-index="${index}" maxlength="4" data-prefijo-tipo="internacional"
                                       value="+58"
                                       onkeypress="return /[0-9+]/.test(event.key)"
                                       oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                                       style="border-radius: 0; border-right: none; background: #f8f9fa; color: #c90000;">
                                <input type="hidden" class="prefijo-hidden" name="telefonos[${index}][prefijo]" value="${tel.IdPrefijo || 1}">
                                <input type="hidden" class="prefijo-nombre-hidden" name="telefonos[${index}][prefijo_nombre]">
                                <div class="prefijo-resultados autocomplete-results d-none"></div>
                            </div>
                            <input type="text"
                                class="form-control añadir__input numero-telefono"
                                name="telefonos[${index}][numero]"
                                value="${tel.numero_telefono}"
                                placeholder="Ej: 4141234567"
                                pattern="^[0-9]+"
                                minlength="10"
                                maxlength="10"
                                onkeypress="return onlyNumber(event)"
                                style="border-top-left-radius: 0; border-bottom-left-radius: 0; border-top-right-radius: 0; border-bottom-right-radius: 0;" required>
                            <button type="button" class="btn btn-outline-danger btn-eliminar-telefono" ${index === 0 ? 'style="display: none;"' : ''}>
                                <i class='bx bx-trash'></i>
                            </button>
                            <button type="button" class="btn btn-outline-success btn-agregar-telefono" ${index !== persona.telefonos.length - 1 ? 'style="display: none;"' : ''}>
                                <i class='bx bx-plus'></i>
                            </button>
                        </div>
                        <p class="añadir__input-error">El teléfono debe ser válido</p>
                    </div>
                `;
                telefonosContainer.insertAdjacentHTML('beforeend', telefonoHTML);
            });

            // Si no hay teléfonos, agregar uno vacío
            if (persona.telefonos.length === 0) {
                agregarTelefonoVacio();
            }
        }

        // Mostrar alerta de éxito
        Swal.fire({
            title: 'Datos Cargados',
            text: 'Los datos del estudiante han sido cargados. Ahora puede asignar roles de trabajador y completar la información.',
            icon: 'success',
            confirmButtonColor: '#c90000',
            timer: 3000
        });
    }

    function agregarTelefonoVacio() {
        const telefonosContainer = document.getElementById('telefonos-container');
        const index = telefonosContainer.querySelectorAll('.telefono-item').length;

        const telefonoHTML = `
            <div class="telefono-item mb-3">
                <div class="input-group">
                    <select class="form-select añadir__input tipo-telefono" name="telefonos[${index}][tipo]"
                            style="max-width: 150px; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                        <option value="1">Personal</option>
                        <option value="2" selected>Celular</option>
                        <option value="3">Trabajo</option>
                    </select>
                    <div class="position-relative" style="max-width: 90px;">
                        <input type="text" class="form-control buscador-input text-center fw-bold prefijo-telefono prefijo-input"
                               data-index="${index}" maxlength="4" data-prefijo-tipo="internacional"
                               value="+58"
                               onkeypress="return /[0-9+]/.test(event.key)"
                               oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                               style="border-radius: 0; border-right: none; background: #f8f9fa; color: #c90000;">
                        <input type="hidden" class="prefijo-hidden" name="telefonos[${index}][prefijo]" value="1">
                        <input type="hidden" class="prefijo-nombre-hidden" name="telefonos[${index}][prefijo_nombre]">
                        <div class="prefijo-resultados autocomplete-results d-none"></div>
                    </div>
                    <input type="text"
                        class="form-control añadir__input numero-telefono"
                        name="telefonos[${index}][numero]"
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
        `;
        telefonosContainer.insertAdjacentHTML('beforeend', telefonoHTML);
    }

    // === VALIDACIONES EN TIEMPO REAL ===

    // Validación de cédula
    if (cedulaInput) {
        cedulaInput.addEventListener('blur', function() {
            const cedula = this.value.trim();
            const grupo = document.getElementById('grupo__cedula');
            const errorMsg = grupo.querySelector('.añadir__input-error');

            // Validar longitud
            if (cedula.length < 7 || cedula.length > 8) {
                grupo.classList.remove('añadir__grupo-correcto');
                grupo.classList.add('añadir__grupo-incorrecto');
                errorMsg.textContent = 'La cédula debe tener entre 7 y 8 dígitos numéricos';
                return;
            }

            // Si la validación básica pasa, verificar duplicados
            clearTimeout(cedulaTimer);
            cedulaTimer = setTimeout(verificarCedulaCompleto, 300);
        });
    }

    // También validar cuando cambia la nacionalidad
    if (nacionalidadSelect) {
        nacionalidadSelect.addEventListener('change', function() {
            if (cedulaInput.value.trim().length >= 7) {
                clearTimeout(cedulaTimer);
                cedulaTimer = setTimeout(verificarCedulaCompleto, 300);
            }
        });
    }

    // Validación de nombre
    const nombreInput = document.getElementById('nombre');
    if (nombreInput) {
        nombreInput.addEventListener('blur', function() {
            const nombre = this.value.trim();
            const grupo = document.getElementById('grupo__nombre');
            const errorMsg = grupo.querySelector('.añadir__input-error');

            if (nombre.length < 3 || nombre.length > 40) {
                grupo.classList.remove('añadir__grupo-correcto');
                grupo.classList.add('añadir__grupo-incorrecto');
                errorMsg.textContent = 'El nombre debe tener entre 3 y 40 letras';
            } else if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/.test(nombre)) {
                grupo.classList.remove('añadir__grupo-correcto');
                grupo.classList.add('añadir__grupo-incorrecto');
                errorMsg.textContent = 'El nombre solo puede contener letras y espacios';
            } else {
                grupo.classList.remove('añadir__grupo-incorrecto');
                grupo.classList.add('añadir__grupo-correcto');
            }
        });
    }

    // Validación de apellido
    const apellidoInput = document.getElementById('apellido');
    if (apellidoInput) {
        apellidoInput.addEventListener('blur', function() {
            const apellido = this.value.trim();
            const grupo = document.getElementById('grupo__apellido');
            const errorMsg = grupo.querySelector('.añadir__input-error');

            if (apellido.length < 3 || apellido.length > 40) {
                grupo.classList.remove('añadir__grupo-correcto');
                grupo.classList.add('añadir__grupo-incorrecto');
                errorMsg.textContent = 'El apellido debe tener entre 3 y 40 letras';
            } else if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/.test(apellido)) {
                grupo.classList.remove('añadir__grupo-correcto');
                grupo.classList.add('añadir__grupo-incorrecto');
                errorMsg.textContent = 'El apellido solo puede contener letras y espacios';
            } else {
                grupo.classList.remove('añadir__grupo-incorrecto');
                grupo.classList.add('añadir__grupo-correcto');
            }
        });
    }

    // Validación de correo
    const correoInput = document.getElementById('correo');
    if (correoInput) {
        correoInput.addEventListener('blur', function() {
            const correo = this.value.trim();
            const grupo = document.getElementById('grupo__correo');
            const errorMsg = grupo.querySelector('.añadir__input-error');

            if (correo.length > 0) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(correo)) {
                    grupo.classList.remove('añadir__grupo-correcto');
                    grupo.classList.add('añadir__grupo-incorrecto');
                    errorMsg.textContent = 'El correo electrónico no tiene un formato válido';
                } else {
                    grupo.classList.remove('añadir__grupo-incorrecto');
                    grupo.classList.add('añadir__grupo-correcto');
                }
            }
        });
    }

    // Validación de usuario
    const usuarioInput = document.getElementById('usuario');
    if (usuarioInput) {
        usuarioInput.addEventListener('blur', function() {
            const usuario = this.value.trim();
            const grupo = document.getElementById('grupo__usuario');
            const errorMsg = grupo.querySelector('.añadir__input-error');

            if (usuario.length < 4 || usuario.length > 20) {
                grupo.classList.remove('añadir__grupo-correcto');
                grupo.classList.add('añadir__grupo-incorrecto');
                errorMsg.textContent = 'El usuario debe tener entre 4 y 20 caracteres';
            } else if (!/^[a-zA-Z0-9_-]+$/.test(usuario)) {
                grupo.classList.remove('añadir__grupo-correcto');
                grupo.classList.add('añadir__grupo-incorrecto');
                errorMsg.textContent = 'El usuario solo puede contener letras, números, guiones y guiones bajos';
            } else {
                grupo.classList.remove('añadir__grupo-incorrecto');
                grupo.classList.add('añadir__grupo-correcto');
            }
        });
    }

    // Validación de contraseña
    const passwordInput = document.getElementById('password');
    const password2Input = document.getElementById('password2');

    if (passwordInput) {
        passwordInput.addEventListener('blur', function() {
            const password = this.value.trim();
            const grupo = document.getElementById('grupo__password');
            const errorMsg = grupo.querySelector('.añadir__input-error');

            if (password.length < 4 || password.length > 20) {
                grupo.classList.remove('añadir__grupo-correcto');
                grupo.classList.add('añadir__grupo-incorrecto');
                errorMsg.textContent = 'La contraseña debe tener entre 4 y 20 caracteres';
            } else {
                grupo.classList.remove('añadir__grupo-incorrecto');
                grupo.classList.add('añadir__grupo-correcto');
            }
        });
    }

    // Validación de confirmar contraseña
    if (password2Input) {
        password2Input.addEventListener('blur', function() {
            const password = passwordInput.value.trim();
            const password2 = this.value.trim();
            const grupo = document.getElementById('grupo__password2');
            const errorMsg = grupo.querySelector('.añadir__input-error');

            if (password !== password2) {
                grupo.classList.remove('añadir__grupo-correcto');
                grupo.classList.add('añadir__grupo-incorrecto');
                errorMsg.textContent = 'Las contraseñas no coinciden';
            } else if (password2.length > 0) {
                grupo.classList.remove('añadir__grupo-incorrecto');
                grupo.classList.add('añadir__grupo-correcto');
            }
        });
    }

    // Validación de teléfonos en tiempo real
    document.addEventListener('DOMContentLoaded', function() {
        // Función para validar un campo de teléfono
        function validarTelefono(telefonoInput) {
            const numero = telefonoInput.value.trim();
            const container = telefonoInput.closest('.telefono-item');

            if (!container) return;

            const prefijoInputId = telefonoInput.name.replace(/\[(\d+)\]\[numero\]/, '[$1][prefijo]');
            const prefijoHiddenElement = document.querySelector(`input[name="${prefijoInputId}"]`);

            // Buscar el input visible del prefijo (el del buscador)
            const prefijoVisibleInput = container.querySelector('.prefijo-telefono');

            // Limpiar validaciones previas
            telefonoInput.classList.remove('is-invalid', 'is-valid');
            let errorSpan = container.querySelector('.invalid-feedback');
            if (errorSpan) {
                errorSpan.remove();
            }

            if (numero.length === 0) {
                return; // No validar si está vacío
            }

            // Validación 1: Solo números
            if (!/^[0-9]+$/.test(numero)) {
                telefonoInput.classList.add('is-invalid');
                errorSpan = document.createElement('div');
                errorSpan.className = 'invalid-feedback d-block';
                errorSpan.textContent = 'El teléfono solo puede contener números';
                telefonoInput.parentElement.appendChild(errorSpan);
                return;
            }

            // Validación 2: No puede empezar con 0
            if (numero.startsWith('0')) {
                telefonoInput.classList.add('is-invalid');
                errorSpan = document.createElement('div');
                errorSpan.className = 'invalid-feedback d-block';
                errorSpan.textContent = 'El teléfono no puede empezar con 0';
                telefonoInput.parentElement.appendChild(errorSpan);
                return;
            }

            // Validación 3: Validar longitud según max_digitos del prefijo
            let maxDigitos = null;

            if (prefijoVisibleInput) {
                maxDigitos = prefijoVisibleInput.getAttribute('data-max-digitos');
            }

            if (maxDigitos) {
                maxDigitos = parseInt(maxDigitos);

                if (numero.length !== maxDigitos) {
                    telefonoInput.classList.add('is-invalid');
                    errorSpan = document.createElement('div');
                    errorSpan.className = 'invalid-feedback d-block';
                    errorSpan.textContent = `El teléfono debe tener exactamente ${maxDigitos} dígitos`;
                    telefonoInput.parentElement.appendChild(errorSpan);
                    return;
                }
            } else {
                // Fallback: validar mínimo 7 dígitos si no hay prefijo
                if (numero.length < 7) {
                    telefonoInput.classList.add('is-invalid');
                    errorSpan = document.createElement('div');
                    errorSpan.className = 'invalid-feedback d-block';
                    errorSpan.textContent = 'El teléfono debe tener al menos 7 dígitos';
                    telefonoInput.parentElement.appendChild(errorSpan);
                    return;
                }
            }

            // Si todo está bien
            telefonoInput.classList.add('is-valid');
        }

        // Agregar event listeners a todos los inputs de teléfono existentes
        function agregarValidacionTelefonos() {
            const telefonosInputs = document.querySelectorAll('input[name^="telefonos"][name$="[numero]"]');
            telefonosInputs.forEach(input => {
                // Validar en blur
                input.addEventListener('blur', function() {
                    validarTelefono(this);
                });

                // También validar cuando cambia el prefijo
                const container = input.closest('.telefono-item');
                if (container) {
                    const prefijoInput = container.querySelector('.prefijo-telefono');
                    if (prefijoInput) {
                        prefijoInput.addEventListener('itemSeleccionado', function() {
                            validarTelefono(input);
                        });
                    }
                }
            });
        }

        // Ejecutar al cargar
        agregarValidacionTelefonos();

        // Re-ejecutar cuando se agreguen nuevos teléfonos
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    agregarValidacionTelefonos();
                }
            });
        });

        const telefonosContainer = document.getElementById('telefonos-container');
        if (telefonosContainer) {
            observer.observe(telefonosContainer, { childList: true });
        }
    });
</script>
</body>
</html>