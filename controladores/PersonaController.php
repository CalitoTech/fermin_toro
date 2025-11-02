<?php
// Verificación de sesión SOLO para acciones que lo requieran
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Acciones que NO requieren sesión
$accionesPublicas = ['verificarCedula'];

if (!in_array($action, $accionesPublicas)) {
    session_start();
    if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
        header("Location: ../vistas/login/login.php");
        exit();
    }
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../controladores/Validaciones.php';
require_once __DIR__ . '/../modelos/Persona.php';
require_once __DIR__ . '/../modelos/DetallePerfil.php';
require_once __DIR__ . '/../modelos/Telefono.php';

// Determinar acción (crear o editar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearUsuario();
        break;
    case 'editar':
        editarUsuario();
        break;
    case 'eliminar':
        eliminarUsuario();
        break;
    case 'verificarCedula':
        verificarCedula();
        break;
    default:
        header("Location: ../vistas/configuracion/usuario/usuario.php");
        exit();
}

function verificarCedula() {
    header('Content-Type: application/json');
    
    try {
        // Verificar método
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            throw new Exception('Método no permitido');
        }

        // Obtener parámetros
        $cedula = $_GET['cedula'] ?? '';
        $idNacionalidad = $_GET['idNacionalidad'] ?? '';
        
        if (empty($cedula) || empty($idNacionalidad)) {
            throw new Exception('La cédula y el ID de nacionalidad son requeridos');
        }

        // Validar que idNacionalidad sea numérico
        if (!is_numeric($idNacionalidad)) {
            throw new Exception('El ID de nacionalidad debe ser numérico');
        }

        $database = new Database();
        $conexion = $database->getConnection();

        // Buscar directamente la persona con el IdNacionalidad proporcionado
        $sql = "SELECT p.*, i.IdInscripcion, s.status AS estado
            FROM persona p
            LEFT JOIN inscripcion i ON i.IdEstudiante = p.IdPersona
            LEFT JOIN status s ON i.IdStatus = s.IdStatus
            WHERE p.cedula = :cedula AND p.IdNacionalidad = :idNacionalidad";
        $stmt = $conexion->prepare($sql);
        
        if (!$stmt) {
            $errorInfo = $conexion->errorInfo();
            throw new Exception('Error al preparar consulta: ' . json_encode($errorInfo));
        }

        $stmt->bindParam(':cedula', $cedula);
        $stmt->bindParam(':idNacionalidad', $idNacionalidad, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception('Error al ejecutar consulta: ' . json_encode($errorInfo));
        }

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'inscrito' => true,
                'estado'   => $row['estado'] // inscrito, pendiente, aprobado, etc.
            ]);
        } else {
            echo json_encode(['inscrito' => false]);
        }
        
    } catch (Exception $e) {
    echo json_encode([
        "error" => "Excepción en verificarCedula: " . $e->getMessage(),
        "existe" => false
    ]);
}
    exit();
}

function crearUsuario() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../vistas/configuracion/usuario/nuevo_usuario.php");
        exit();
    }

    // Obtener y limpiar datos
    $nacionalidad = $_POST['nacionalidad'] ?? '';
    $cedula = trim($_POST['cedula'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password2 = trim($_POST['password2'] ?? '');
    $status_acceso = $_POST['status_acceso'] ?? 1;
    $status_institucional = $_POST['status_institucional'] ?? 1;
    $roles = $_POST['roles'] ?? [];
    $telefonos = $_POST['telefonos'] ?? [];

    // === Validaciones ===
    
    // 1. Validar campos requeridos
    $camposRequeridos = [
        'nacionalidad' => $nacionalidad,
        'cedula' => $cedula,
        'nombre' => $nombre,
        'apellido' => $apellido,
        'usuario' => $usuario,
        'password' => $password,
        'password2' => $password2,
        'roles' => $roles
    ];
    
    foreach ($camposRequeridos as $campo => $valor) {
        if (empty($valor)) {
            manejarError("El campo $campo es requerido");
        }
    }

    // 2. Validar formatos con la clase Validaciones
    if (!Validaciones::validarCampo($nombre, 'nombre')) {
        manejarError('El nombre debe contener solo letras y espacios (3-40 caracteres)');
    }
    
    if (!Validaciones::validarCampo($apellido, 'nombre')) {
        manejarError('El apellido debe contener solo letras y espacios (3-40 caracteres)');
    }
    
    if (!Validaciones::validarCampo($cedula, 'cedula')) {
        manejarError('La cédula debe tener 7 u 8 dígitos numéricos');
    }
    
    if (!empty($correo) && !Validaciones::validarCampo($correo, 'correo')) {
        manejarError('El correo electrónico no tiene un formato válido');
    }
    
    if (!Validaciones::validarCampo($usuario, 'usuario')) {
        manejarError('El usuario debe tener entre 4 y 20 caracteres (letras, números, guiones y guiones bajos)');
    }
    
    if (!Validaciones::validarCampo($password, 'password')) {
        manejarError('La contraseña debe tener entre 4 y 20 caracteres');
    }
    
    if ($password !== $password2) {
        manejarError('Las contraseñas no coinciden');
    }
    
    if (empty($roles)) {
        manejarError('Debe seleccionar al menos un rol');
    }
    
     // Validar teléfonos (versión simplificada)
    foreach ($telefonos as $tel) {
        if (!empty($tel['numero'])) {
            $digitos = preg_replace('/[^0-9]/', '', $tel['numero']);
            if (strlen($digitos) < 11) {
                manejarError('El número de teléfono debe contener al menos 11 dígitos');
            }
        }
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();

        // Verificar duplicados
        $persona = new Persona($conexion);
        
        // Verificar usuario duplicado
        $stmt = $conexion->prepare("SELECT IdPersona FROM persona WHERE usuario = :usuario");
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El nombre de usuario ya existe');
        }

        // Verificar cédula duplicada
        $stmt = $conexion->prepare("SELECT IdPersona FROM persona WHERE cedula = :cedula");
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('La cédula ya está registrada');
        }

        // Configurar datos de la persona
        $persona->IdNacionalidad = $nacionalidad === 'V' ? 1 : 2;
        $persona->cedula = $cedula;
        $persona->nombre = $nombre;
        $persona->apellido = $apellido;
        $persona->correo = !empty($correo) ? $correo : null;
        $persona->direccion = null;
        $persona->IdSexo = null;
        $persona->IdUrbanismo = null;
        $persona->IdEstadoAcceso = $status_acceso;
        $persona->IdEstadoInstitucional = $status_institucional;

        // Iniciar transacción
        $conexion->beginTransaction();

        try {
            // 1. Guardar datos básicos de la persona
            $idPersona = $persona->guardar();
            if (!$idPersona) {
                throw new Exception("Error al guardar la persona");
            }

            // 2. Crear credenciales de usuario
            if (!$persona->crearCredenciales($idPersona, $usuario, $password)) {
                throw new Exception("Error al crear credenciales de usuario");
            }

            // 3. Guardar roles
            $detallePerfil = new DetallePerfil($conexion);
            foreach ($roles as $idPerfil) {
                $detallePerfil->IdPersona = $idPersona;
                $detallePerfil->IdPerfil = $idPerfil;
                if (!$detallePerfil->guardar()) {
                    throw new Exception("Error al asignar roles");
                }
            }

            // 4. Guardar teléfonos
            if (!empty($telefonos)) {
                $telefonoModel = new Telefono($conexion);
                foreach ($telefonos as $tel) {
                    if (!empty(trim($tel['numero']))) {
                        $telefonoModel->IdPersona = $idPersona;
                        $telefonoModel->numero_telefono = trim($tel['numero']);
                        $telefonoModel->IdTipo_Telefono = (int)$tel['tipo'];
                        
                        if (!$telefonoModel->guardar()) {
                            throw new Exception("Error al guardar teléfono");
                        }
                    }
                }
            }

            // Confirmar transacción
            $conexion->commit();

            $_SESSION['alert'] = 'success';
            $_SESSION['message'] = 'Usuario creado exitosamente';
            header("Location: ../vistas/configuracion/usuario/usuario.php");
            exit();

        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error al crear usuario: " . $e->getMessage());
            manejarError('Error al crear el usuario: ' . $e->getMessage());
        }

    } catch (Exception $e) {
        error_log("Error general al crear usuario: " . $e->getMessage());
        manejarError('Error interno del servidor');
    }
}

function editarUsuario() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/configuracion/usuario/usuario.php');
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de usuario inválido', '../vistas/configuracion/usuario/usuario.php');
    }

    // Obtener y limpiar datos
    $nacionalidad = $_POST['nacionalidad'] ?? '';
    $cedula = trim($_POST['cedula'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $status_acceso = $_POST['status_acceso'] ?? 1;
    $status_institucional = $_POST['status_institucional'] ?? 1;
    $roles = $_POST['roles'] ?? [];
    $telefonos = $_POST['telefonos'] ?? [];
    $password = $_POST['password'] ?? '';

    // === Validaciones ===
    
    // 1. Validar campos requeridos
    $camposRequeridos = [
        'nacionalidad' => $nacionalidad,
        'cedula' => $cedula,
        'nombre' => $nombre,
        'apellido' => $apellido,
        'usuario' => $usuario,
        'status_acceso' => $status_acceso,
        'status_institucional' => $status_institucional,
        'roles' => $roles
    ];
    
    foreach ($camposRequeridos as $campo => $valor) {
        if (empty($valor)) {
            manejarError("El campo $campo es requerido", "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
        }
    }

    // 2. Validar formatos con la clase Validaciones
    if (!Validaciones::validarCampo($nombre, 'nombre')) {
        manejarError('El nombre debe contener solo letras y espacios (3-40 caracteres)', "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
    }
    
    if (!Validaciones::validarCampo($apellido, 'nombre')) {
        manejarError('El apellido debe contener solo letras y espacios (3-40 caracteres)', "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
    }
    
    if (!Validaciones::validarCampo($cedula, 'cedula')) {
        manejarError('La cédula debe tener 7 u 8 dígitos numéricos', "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
    }
    
    if (!empty($correo) && !Validaciones::validarCampo($correo, 'correo')) {
        manejarError('El correo electrónico no tiene un formato válido', "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
    }
    
    if (!Validaciones::validarCampo($usuario, 'usuario')) {
        manejarError('El usuario debe tener entre 4 y 20 caracteres (letras, números, guiones y guiones bajos)', "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
    }
    
    if (!empty($password) && !Validaciones::validarCampo($password, 'password')) {
        manejarError('La contraseña debe tener entre 4 y 20 caracteres', "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
    }
    
    if (empty($roles)) {
        manejarError('Debe seleccionar al menos un rol', "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
    }
    
     // Validar teléfonos (versión simplificada)
    foreach ($telefonos as $tel) {
        if (!empty($tel['numero'])) {
            $digitos = preg_replace('/[^0-9]/', '', $tel['numero']);
            if (strlen($digitos) < 11) {
                manejarError('El número de teléfono debe contener al menos 11 dígitos', "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
            }
        }
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();

        // Verificar que el usuario existe
        $persona = new Persona($conexion);
        if (!$persona->obtenerPorId($id)) {
            manejarError('Usuario no encontrado', '../vistas/configuracion/usuario/usuario.php');
        }

        // Verificar duplicados (excluyendo al usuario actual)
        $stmt = $conexion->prepare("SELECT IdPersona FROM persona WHERE (usuario = :usuario OR cedula = :cedula) AND IdPersona != :id");
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El usuario o cédula ya están en uso por otro registro', "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
        }

        // Configurar datos actualizados
        $persona->IdNacionalidad = $nacionalidad === 'V' ? 1 : 2;
        $persona->cedula = $cedula;
        $persona->nombre = $nombre;
        $persona->apellido = $apellido;
        $persona->correo = !empty($correo) ? $correo : null;
        $persona->usuario = $usuario;
        $persona->IdEstadoAcceso = (int)$status_acceso;
        $persona->IdEstadoInstitucional = (int)$status_institucional;

        // Iniciar transacción
        $conexion->beginTransaction();

        try {
            // 1. Actualizar datos básicos
            if (!$persona->actualizar()) {
                throw new Exception("Error al actualizar datos básicos");
            }

            // 2. Actualizar contraseña si se proporcionó
            if (!empty($password)) {
                if (!$persona->actualizarPassword($password)) {
                    throw new Exception("Error al actualizar contraseña");
                }
            }

            // 3. Actualizar roles
            $detallePerfil = new DetallePerfil($conexion);
            if (!$detallePerfil->actualizarRoles($id, $roles, false)) {
                throw new Exception("Error al actualizar roles");
            }

            // 4. Actualizar teléfonos
            $telefonoModel = new Telefono($conexion);
            if (!$telefonoModel->actualizarTelefonos($id, $telefonos, false)) {
                throw new Exception("Error al actualizar teléfonos");
            }

            // Confirmar transacción
            $conexion->commit();

            $_SESSION['alert'] = 'success';
            $_SESSION['message'] = 'Usuario actualizado correctamente';
            header("Location: ../vistas/configuracion/usuario/editar_usuario.php?id=$id");
            exit();

        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error en transacción de actualización: " . $e->getMessage());
            manejarError($e->getMessage(), "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
        }

    } catch (Exception $e) {
        error_log("Error general en edición: " . $e->getMessage());
        manejarError('Error interno del servidor', "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
    }
}

function eliminarUsuario() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header("Location: ../vistas/configuracion/usuario/usuario.php?error=metodo_no_permitido");
        exit();
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        header("Location: ../vistas/configuracion/usuario/usuario.php?error=id_invalido");
        exit();
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();

        // Verificar que el usuario existe
        $persona = new Persona($conexion);
        if (!$persona->obtenerPorId($id)) {
            header("Location: ../vistas/configuracion/usuario/usuario.php?error=usuario_no_encontrado");
            exit();
        }

        // Iniciar transacción
        $conexion->beginTransaction();

        try {
            // 1. Eliminar teléfonos asociados
            $telefonoModel = new Telefono($conexion);
            $telefonoModel->eliminarPorPersona($id);

            // 2. Eliminar roles asociados
            $detallePerfil = new DetallePerfil($conexion);
            $detallePerfil->eliminarPorPersona($id);

            // 3. Eliminar la persona
            if (!$persona->eliminar()) {
                throw new Exception("Error al eliminar el usuario");
            }

            // Confirmar transacción
            $conexion->commit();

            header("Location: ../vistas/configuracion/usuario/usuario.php?deleted=1");
            exit();

        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error al eliminar usuario: " . $e->getMessage());
            header("Location: ../vistas/configuracion/usuario/usuario.php?error=operacion_fallida");
            exit();
        }

    } catch (Exception $e) {
        error_log("Error general al eliminar usuario: " . $e->getMessage());
        header("Location: ../vistas/configuracion/usuario/usuario.php?error=error_interno");
        exit();
    }
}

/**
 * Maneja los errores de forma consistente
 * @param string $mensaje Mensaje de error a mostrar
 * @param string $urlRedireccion URL a la que redirigir (opcional)
 */
function manejarError(string $mensaje, string $urlRedireccion = '../vistas/configuracion/usuario/nuevo_usuario.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}