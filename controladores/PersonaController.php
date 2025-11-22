<?php
// Verificación de sesión SOLO para acciones que lo requieran
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Acciones que NO requieren sesión
$accionesPublicas = ['verificarCedula', 'verificarAccesoRepresentantes', 'verificarCedulaRepresentante', 'obtenerPerfil', 'verificarCedulaCompleto', 'verificarCorreo'];

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
    case 'verificarAccesoRepresentantes':
        verificarAccesoRepresentantes();
        break;
    case 'verificarCedulaRepresentante':
        verificarCedulaRepresentante();
        break;
    case 'obtenerPerfil':
        obtenerPerfil();
        break;
    case 'verificarCedulaCompleto':
        verificarCedulaCompleto();
        break;
    case 'obtenerCursoSiguiente':
        obtenerCursoSiguienteEstudiante();
        break;
    case 'verificarCorreo':
        verificarCorreo();
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

function verificarAccesoRepresentantes() {
    header('Content-Type: application/json');

    try {
        // Verificar método
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Método no permitido');
        }

        // Obtener las cédulas del POST
        $cedulas = json_decode(file_get_contents('php://input'), true);

        if (!is_array($cedulas) || empty($cedulas)) {
            echo json_encode(['success' => true, 'representantesConAcceso' => []]);
            exit();
        }

        $database = new Database();
        $conexion = $database->getConnection();

        $representantesConAcceso = [];

        foreach ($cedulas as $item) {
            $cedula = $item['cedula'] ?? '';
            $nacionalidad = $item['nacionalidad'] ?? '';
            $nombre = $item['nombre'] ?? '';

            if (empty($cedula) || empty($nacionalidad)) {
                continue;
            }

            // Consultar si la persona existe y tiene IdEstadoAcceso = 1
            $sql = "SELECT p.IdPersona, p.nombre, p.apellido, p.IdEstadoAcceso
                    FROM persona p
                    WHERE p.cedula = :cedula
                    AND p.IdNacionalidad = :nacionalidad
                    AND p.IdEstadoAcceso = 1";

            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':cedula', $cedula);
            $stmt->bindParam(':nacionalidad', $nacionalidad, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $persona = $stmt->fetch(PDO::FETCH_ASSOC);
                $representantesConAcceso[] = [
                    'cedula' => $cedula,
                    'nacionalidad' => $nacionalidad,
                    'nombre' => $nombre,
                    'nombreCompleto' => $persona['nombre'] . ' ' . $persona['apellido']
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'representantesConAcceso' => $representantesConAcceso
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

/**
 * Verifica si una cédula de representante ya existe en el sistema
 * Retorna si existe, si tiene usuario y contraseña, y datos básicos de la persona
 */
function verificarCedulaRepresentante() {
    header('Content-Type: application/json');

    try {
        // Verificar método
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Método no permitido');
        }

        // Obtener los datos del POST
        $data = json_decode(file_get_contents('php://input'), true);
        $cedula = $data['cedula'] ?? '';
        $nacionalidad = $data['nacionalidad'] ?? '';

        if (empty($cedula) || empty($nacionalidad)) {
            echo json_encode([
                'existe' => false,
                'tieneAcceso' => false
            ]);
            exit();
        }

        $database = new Database();
        $conexion = $database->getConnection();

        // Consultar si la persona existe y verificar si tiene usuario y contraseña
        $sql = "SELECT p.IdPersona, p.nombre, p.apellido, p.cedula, p.IdNacionalidad,
                       n.nacionalidad,
                       p.usuario, p.password,
                       CASE
                           WHEN p.usuario IS NOT NULL AND p.usuario != ''
                                AND p.password IS NOT NULL AND p.password != ''
                           THEN 1
                           ELSE 0
                       END AS tiene_credenciales
                FROM persona p
                INNER JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
                WHERE p.cedula = :cedula
                AND p.IdNacionalidad = :nacionalidad";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->bindParam(':nacionalidad', $nacionalidad, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $persona = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'existe' => true,
                'tieneAcceso' => (bool)$persona['tiene_credenciales'],
                'persona' => [
                    'nombre' => $persona['nombre'],
                    'apellido' => $persona['apellido'],
                    'nombreCompleto' => $persona['nombre'] . ' ' . $persona['apellido'],
                    'nacionalidad' => $persona['nacionalidad'],
                    'cedula' => $persona['cedula']
                ]
            ]);
        } else {
            echo json_encode([
                'existe' => false,
                'tieneAcceso' => false
            ]);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'existe' => false,
            'tieneAcceso' => false,
            'error' => $e->getMessage()
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
    
     // Validar teléfonos con prefijo
    foreach ($telefonos as $tel) {
        if (!empty($tel['numero'])) {
            $numero = trim($tel['numero']);
            $idPrefijo = $tel['prefijo'] ?? null;

            // Validar que solo contenga números
            if (!preg_match('/^[0-9]+$/', $numero)) {
                manejarError('El número de teléfono solo puede contener dígitos');
            }

            // Validar que no empiece con 0
            if (substr($numero, 0, 1) === '0') {
                manejarError('El número de teléfono no puede empezar con 0');
            }

            // Validar longitud según el prefijo
            if ($idPrefijo) {
                require_once __DIR__ . '/../modelos/Prefijo.php';
                $prefijoModel = new Prefijo($conexion);
                $prefijoData = $prefijoModel->obtenerPorId($idPrefijo);

                if ($prefijoData && isset($prefijoData['max_digitos'])) {
                    $maxDigitos = (int)$prefijoData['max_digitos'];
                    if (strlen($numero) !== $maxDigitos) {
                        manejarError("El número de teléfono debe tener exactamente {$maxDigitos} dígitos para el prefijo {$prefijoData['codigo_prefijo']}");
                    }
                }
            } else {
                // Si no hay prefijo, validar longitud mínima general
                if (strlen($numero) < 10) {
                    manejarError('El número de teléfono debe contener al menos 10 dígitos');
                }
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

    // Crear conexión para validaciones de teléfono
    $database = new Database();
    $conexion = $database->getConnection();

     // Validar teléfonos con prefijo
    foreach ($telefonos as $tel) {
        if (!empty($tel['numero'])) {
            $numero = trim($tel['numero']);
            $idPrefijo = $tel['prefijo'] ?? null;

            // Validar que solo contenga números
            if (!preg_match('/^[0-9]+$/', $numero)) {
                manejarError('El número de teléfono solo puede contener dígitos', "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
            }

            // Validar que no empiece con 0
            if (substr($numero, 0, 1) === '0') {
                manejarError('El número de teléfono no puede empezar con 0', "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
            }

            // Validar longitud según el prefijo
            if ($idPrefijo) {
                require_once __DIR__ . '/../modelos/Prefijo.php';
                $prefijoModel = new Prefijo($conexion);
                $prefijoData = $prefijoModel->obtenerPorId($idPrefijo);

                if ($prefijoData && isset($prefijoData['max_digitos'])) {
                    $maxDigitos = (int)$prefijoData['max_digitos'];
                    if (strlen($numero) !== $maxDigitos) {
                        manejarError("El número de teléfono debe tener exactamente {$maxDigitos} dígitos para el prefijo {$prefijoData['codigo_prefijo']}", "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
                    }
                }
            } else {
                // Si no hay prefijo, validar longitud mínima general
                if (strlen($numero) < 10) {
                    manejarError('El número de teléfono debe contener al menos 10 dígitos', "../vistas/configuracion/usuario/editar_usuario.php?id=$id");
                }
            }
        }
    }

    try {

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
 * Obtiene el IdPerfil de una persona
 */
function obtenerPerfil() {
    header('Content-Type: application/json');

    try {
        $id = $_GET['id'] ?? '';

        if (empty($id) || !is_numeric($id)) {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            exit();
        }

        $database = new Database();
        $conexion = $database->getConnection();

        // Obtener el IdPerfil de la persona
        $sql = "SELECT IdPerfil FROM detalle_perfil WHERE IdPersona = :id LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => true,
                'IdPerfil' => $row['IdPerfil']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No se encontró perfil para esta persona'
            ]);
        }
        exit();

    } catch (Exception $e) {
        error_log("Error al obtener perfil: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener el perfil'
        ]);
        exit();
    }
}

/**
 * Verifica si una cédula existe y retorna información completa de la persona
 * Incluye validación para convertir estudiantes mayores de 18 en trabajadores
 */
function verificarCedulaCompleto() {
    header('Content-Type: application/json');

    try {
        $cedula = $_GET['cedula'] ?? '';
        $idNacionalidad = $_GET['idNacionalidad'] ?? '';

        if (empty($cedula) || empty($idNacionalidad)) {
            echo json_encode(['existe' => false]);
            exit();
        }

        if (!is_numeric($idNacionalidad)) {
            echo json_encode(['existe' => false, 'error' => 'ID de nacionalidad inválido']);
            exit();
        }

        $database = new Database();
        $conexion = $database->getConnection();

        // Buscar la persona con todos sus datos
        $sql = "SELECT
                    p.IdPersona,
                    p.nombre,
                    p.apellido,
                    p.cedula,
                    p.IdNacionalidad,
                    p.fecha_nacimiento,
                    p.IdSexo,
                    p.correo,
                    p.direccion,
                    p.usuario,
                    p.password,
                    p.IdEstadoAcceso,
                    n.nacionalidad,
                    s.sexo,
                    dp.IdPerfil,
                    pr.nombre_perfil,
                    TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad
                FROM persona p
                LEFT JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
                LEFT JOIN sexo s ON p.IdSexo = s.IdSexo
                LEFT JOIN detalle_perfil dp ON p.IdPersona = dp.IdPersona
                LEFT JOIN perfil pr ON dp.IdPerfil = pr.IdPerfil
                WHERE p.cedula = :cedula
                AND p.IdNacionalidad = :idNacionalidad
                LIMIT 1";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->bindParam(':idNacionalidad', $idNacionalidad, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $persona = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar si es estudiante y mayor de 18
            $esEstudiante = ($persona['IdPerfil'] == 3);
            $edad = (int)$persona['edad'];
            $puedeConvertirse = $esEstudiante && $edad >= 18;

            // Obtener teléfonos de la persona
            $sqlTelefonos = "SELECT IdTelefono, numero_telefono, IdPrefijo FROM telefono WHERE IdPersona = :idPersona";
            $stmtTel = $conexion->prepare($sqlTelefonos);
            $stmtTel->bindParam(':idPersona', $persona['IdPersona'], PDO::PARAM_INT);
            $stmtTel->execute();
            $telefonos = $stmtTel->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'existe' => true,
                'esEstudiante' => $esEstudiante,
                'edad' => $edad,
                'puedeConvertirse' => $puedeConvertirse,
                'persona' => [
                    'IdPersona' => $persona['IdPersona'],
                    'nombre' => $persona['nombre'],
                    'apellido' => $persona['apellido'],
                    'nombreCompleto' => $persona['nombre'] . ' ' . $persona['apellido'],
                    'cedula' => $persona['cedula'],
                    'nacionalidad' => $persona['nacionalidad'],
                    'IdNacionalidad' => $persona['IdNacionalidad'],
                    'fecha_nacimiento' => $persona['fecha_nacimiento'],
                    'IdSexo' => $persona['IdSexo'],
                    'sexo' => $persona['sexo'],
                    'correo' => $persona['correo'],
                    'direccion' => $persona['direccion'],
                    'usuario' => $persona['usuario'],
                    'tieneCredenciales' => !empty($persona['usuario']) && !empty($persona['password']),
                    'IdEstadoAcceso' => $persona['IdEstadoAcceso'],
                    'IdPerfil' => $persona['IdPerfil'],
                    'nombre_perfil' => $persona['nombre_perfil'],
                    'telefonos' => $telefonos
                ]
            ]);
        } else {
            echo json_encode(['existe' => false]);
        }

    } catch (Exception $e) {
        error_log("Error en verificarCedulaCompleto: " . $e->getMessage());
        echo json_encode([
            'existe' => false,
            'error' => 'Error al verificar la cédula'
        ]);
    }
    exit();
}

/**
 * Obtiene el curso siguiente para un estudiante (usado en inscripciones)
 */
function obtenerCursoSiguienteEstudiante() {
    header('Content-Type: application/json');

    try {
        $database = new Database();
        $conexion = $database->getConnection();

        $idEstudiante = $_GET['idEstudiante'] ?? 0;

        if (!$idEstudiante) {
            echo json_encode([
                'success' => false,
                'error' => 'ID de estudiante no proporcionado'
            ]);
            exit();
        }

        $personaModel = new Persona($conexion);

        // Obtener datos básicos del estudiante
        $estudiante = $personaModel->obtenerEstudiantePorId($idEstudiante);

        if (!$estudiante) {
            echo json_encode([
                'success' => false,
                'error' => 'Estudiante no encontrado'
            ]);
            exit();
        }

        // Obtener curso actual
        $cursoActual = $personaModel->obtenerCursoActual($idEstudiante);

        // Obtener curso siguiente
        $cursoSiguienteData = $personaModel->obtenerCursoSiguiente($idEstudiante);

        if ($cursoSiguienteData === null) {
            echo json_encode([
                'success' => false,
                'graduado' => true,
                'mensaje' => 'El estudiante ya completó todos los cursos disponibles'
            ]);
            exit();
        }

        echo json_encode([
            'success' => true,
            'estudiante' => [
                'IdPersona' => $estudiante['IdPersona'],
                'nombre' => $estudiante['nombre'],
                'apellido' => $estudiante['apellido'],
                'cedula' => $estudiante['cedula'],
                'nacionalidad' => $estudiante['nacionalidad']
            ],
            'cursoActual' => $cursoActual,
            'cursoSiguiente' => [
                'IdCurso' => $cursoSiguienteData['IdCurso'],
                'curso' => $cursoSiguienteData['curso'],
                'IdNivel' => $cursoSiguienteData['IdNivel']
            ],
            'secciones' => $cursoSiguienteData['secciones'],
            'seccionPorDefecto' => $cursoSiguienteData['seccionPorDefecto']
        ]);

    } catch (Exception $e) {
        error_log("Error en obtenerCursoSiguienteEstudiante: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener información del curso'
        ]);
    }
    exit();
}

/**
 * Verifica si un correo electrónico ya está registrado en el sistema
 * @return void Retorna JSON con el resultado de la verificación
 */
function verificarCorreo() {
    header('Content-Type: application/json');

    try {
        $correo = $_GET['correo'] ?? '';
        $idPersonaExcluir = $_GET['idPersona'] ?? null; // Para excluir en ediciones

        if (empty($correo)) {
            echo json_encode(['existe' => false]);
            exit();
        }

        // Validar formato de correo
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'existe' => false,
                'error' => 'Formato de correo inválido'
            ]);
            exit();
        }

        $database = new Database();
        $conexion = $database->getConnection();

        // Buscar si el correo ya existe (excluyendo opcionalmente una persona)
        if ($idPersonaExcluir && is_numeric($idPersonaExcluir)) {
            $sql = "SELECT p.IdPersona, p.nombre, p.apellido, p.cedula, n.nacionalidad
                    FROM persona p
                    LEFT JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
                    WHERE p.correo = :correo AND p.IdPersona != :idPersona
                    LIMIT 1";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':correo', $correo);
            $stmt->bindParam(':idPersona', $idPersonaExcluir, PDO::PARAM_INT);
        } else {
            $sql = "SELECT p.IdPersona, p.nombre, p.apellido, p.cedula, n.nacionalidad
                    FROM persona p
                    LEFT JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
                    WHERE p.correo = :correo
                    LIMIT 1";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':correo', $correo);
        }

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $persona = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'existe' => true,
                'persona' => [
                    'nombre' => $persona['nombre'],
                    'apellido' => $persona['apellido'],
                    'nombreCompleto' => $persona['nombre'] . ' ' . $persona['apellido'],
                    'cedula' => $persona['cedula'],
                    'nacionalidad' => $persona['nacionalidad']
                ]
            ]);
        } else {
            echo json_encode(['existe' => false]);
        }

    } catch (Exception $e) {
        error_log("Error en verificarCorreo: " . $e->getMessage());
        echo json_encode([
            'existe' => false,
            'error' => 'Error al verificar el correo'
        ]);
    }
    exit();
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