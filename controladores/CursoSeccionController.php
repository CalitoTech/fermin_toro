<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/CursoSeccion.php';
require_once __DIR__ . '/../modelos/Curso.php';
require_once __DIR__ . '/../modelos/Seccion.php';
require_once __DIR__ . '/../modelos/Aula.php';
require_once __DIR__ . '/../modelos/Nivel.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear': crearCursoSeccion(); break;
    case 'editar': editarCursoSeccion(); break;
    case 'eliminar': eliminarCursoSeccion(); break;
    case 'verificar_impacto': verificarImpactoDesactivacion(); break;
    default: manejarError('Acción no válida', '../vistas/registros/curso_seccion/curso_seccion.php');
}

function crearCursoSeccion() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/curso_seccion/nuevo_curso_seccion.php');
    }

    $idCurso = trim($_POST['curso'] ?? '');
    $idSeccion = trim($_POST['seccion'] ?? '');
    $idAula = trim($_POST['aula'] ?? '');
    $cantidadEstudiantes = trim($_POST['cantidad_estudiantes'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Validaciones básicas
    if (empty($idCurso)) manejarError('El campo curso es requerido');
    if (empty($idSeccion)) manejarError('El campo sección es requerido');

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $cursoSeccionModel = new CursoSeccion($conexion);

        // Validar combinación única de curso y sección
        $query = "SELECT IdCurso_Seccion FROM curso_seccion WHERE IdCurso = :idCurso AND IdSeccion = :idSeccion";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':idCurso', $idCurso);
        $stmt->bindParam(':idSeccion', $idSeccion);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('Esta combinación de curso y sección ya existe');
        }

        // Validar que el aula no esté en uso (si se asignó)
        if (!empty($idAula)) {
            $query = "SELECT IdCurso_Seccion FROM curso_seccion WHERE IdAula = :idAula";
            $stmt = $conexion->prepare($query);
            $stmt->bindParam(':idAula', $idAula);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                manejarError('El aula seleccionada ya está asignada a otro curso/sección');
            }
        }

        // Configurar y guardar
        $cursoSeccionModel->IdCurso = $idCurso;
        $cursoSeccionModel->IdSeccion = $idSeccion;
        $cursoSeccionModel->IdAula = empty($idAula) ? null : $idAula;
        $cursoSeccionModel->cantidad_estudiantes = empty($cantidadEstudiantes) ? 0 : (int)$cantidadEstudiantes;
        // Validar que no se active la sección "Inscripción"
        if ($activo == 1) {
            require_once __DIR__ . '/../modelos/Seccion.php';
            $seccionModel = new Seccion($conexion);
            $seccionDatos = $seccionModel->obtenerPorId($idSeccion);
            if ($seccionDatos && $seccionDatos['seccion'] === 'Inscripción') {
                throw new Exception("La sección 'Inscripción' está reservada para procesar solicitudes de nuevo ingreso (estudiantes sin asignación académica) y no debe activarse.");
            }
        }

        if (!$cursoSeccionModel->guardar()) {
            throw new Exception("Error al guardar el curso/sección");
        }

        // Actualizar contador en curso
        $cursoModel = new Curso($conexion);
        $cursoModel->actualizarCantidadSecciones($idCurso);

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Curso/Sección creado exitosamente';
        header("Location: ../vistas/registros/curso_seccion/curso_seccion.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear el curso/sección: ' . $e->getMessage());
    }
}

function verificarImpactoDesactivacion() {
    // Solo permitir JSON
    header('Content-Type: application/json');
    
    // Obtener datos
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['error' => 'ID requerido']);
        exit;
    }

    require_once __DIR__ . '/../modelos/CursoSeccion.php';
    require_once __DIR__ . '/../config/conexion.php';
    
    $database = new Database();
    $conn = $database->getConnection();
    $model = new CursoSeccion($conn);
    
    // Obtener estudiantes y IdCurso para validación
    $query = "SELECT COUNT(*) as total, IdCurso FROM inscripcion WHERE IdCurso_Seccion = :id AND IdStatus = 11";
    // Check if we need to join because inscripcion might not have IdCurso directly if design is normalized? 
    // Actually curso_seccion has IdCurso. Let's query from curso_seccion directly or join.
    // Wait, the previous query was on `inscripcion`. Does `inscripcion` have `IdCurso`? Usually it has `IdCurso_Seccion`.
    // Let's verify schema if possible, or join `curso_seccion`.
    
    // Better query:
    $queryInfo = "SELECT cs.IdCurso, (SELECT COUNT(*) FROM inscripcion i WHERE i.IdCurso_Seccion = cs.IdCurso_Seccion AND i.IdStatus = 11) as total 
                  FROM curso_seccion cs WHERE cs.IdCurso_Seccion = :id";
    
    $stmt = $conn->prepare($queryInfo);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo json_encode(['error' => 'Sección no encontrada']);
        exit;
    }
    
    // Validar orden de desactivación
    $validacion = $model->verificarOrdenDesactivacion($data['IdCurso'], $id);
    if (!$validacion['valido']) {
         echo json_encode(['error' => $validacion['mensaje']]);
         exit;
    }
    
    echo json_encode(['estudiantes' => $data['total']]);
    exit;
}

function editarCursoSeccion() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/curso_seccion/curso_seccion.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de curso/sección inválido', '../vistas/registros/curso_seccion/curso_seccion.php');
    }

    $idCurso = trim($_POST['curso'] ?? '');
    $idSeccion = trim($_POST['seccion'] ?? '');
    $idAula = trim($_POST['aula'] ?? '');
    $cantidadEstudiantes = trim($_POST['cantidad_estudiantes'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if (empty($idCurso)) manejarError("El campo curso es requerido", "../vistas/registros/curso_seccion/editar_curso_seccion.php?id=$id");
    if (empty($idSeccion)) manejarError("El campo sección es requerido", "../vistas/registros/curso_seccion/editar_curso_seccion.php?id=$id");

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $cursoSeccionModel = new CursoSeccion($conexion);

        // Verificar que existe
        if (!$cursoSeccionModel->obtenerPorId($id)) {
            manejarError('Curso/Sección no encontrado', '../vistas/registros/curso_seccion/curso_seccion.php');
        }

        // Validar combinación única (excluyendo el actual)
        $query = "SELECT IdCurso_Seccion FROM curso_seccion 
                 WHERE IdCurso = :idCurso AND IdSeccion = :idSeccion AND IdCurso_Seccion != :id";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':idCurso', $idCurso);
        $stmt->bindParam(':idSeccion', $idSeccion);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('Esta combinación de curso y sección ya está en uso', "../vistas/registros/curso_seccion/editar_curso_seccion.php?id=$id");
        }

        // Validar aula no usada por otros (si se asignó)
        if (!empty($idAula)) {
            $query = "SELECT IdCurso_Seccion FROM curso_seccion WHERE IdAula = :idAula AND IdCurso_Seccion != :id";
            $stmt = $conexion->prepare($query);
            $stmt->bindParam(':idAula', $idAula);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                manejarError('El aula seleccionada ya está asignada a otro curso/sección', "../vistas/registros/curso_seccion/editar_curso_seccion.php?id=$id");
            }
        }

        // Validar si es una desactivación y requiere reubicación
        $reorganizar = isset($_POST['reorganizar']) && $_POST['reorganizar'] === 'true';

        // Configurar y actualizar
        $cursoSeccionModel->IdCurso_Seccion = $id;
        $cursoSeccionModel->IdCurso = $idCurso;
        $cursoSeccionModel->IdSeccion = $idSeccion;
        $cursoSeccionModel->IdAula = empty($idAula) ? null : $idAula;
        $cursoSeccionModel->cantidad_estudiantes = empty($cantidadEstudiantes) ? 0 : (int)$cantidadEstudiantes;
        $cursoSeccionModel->activo = $activo;

        if ($activo == 1) {
            require_once __DIR__ . '/../modelos/Seccion.php';
            $seccionModel = new Seccion($conexion);
            $seccionDatos = $seccionModel->obtenerPorId($idSeccion);
            if ($seccionDatos && $seccionDatos['seccion'] === 'Inscripción') {
                throw new Exception("La sección 'Inscripción' está reservada para procesar solicitudes de nuevo ingreso (estudiantes sin asignación académica) y no debe activarse.");
            }
        }

        if ($activo == 0) {
             // Validar orden antes de proceder
             $validacion = $cursoSeccionModel->verificarOrdenDesactivacion($idCurso, $id);
             if (!$validacion['valido']) {
                  throw new Exception($validacion['mensaje']);
             }
        }

        if ($activo == 0 && $reorganizar) {
             // Realizar reubicación ANTES de desactivar (aunque el status se actualiza con actualizar())
             // Solo necesitamos saber que el ID actual va a "desactivarse".
             // La función reubicarEstudiantes excluye el ID pasado como "desactivar" y busca otros destinos.
             if (!$cursoSeccionModel->reubicarEstudiantes($idCurso, [$id])) {
                 throw new Exception("No se pudieron reubicar los estudiantes. Verifique que existan otras secciones activas.");
             }
        }

        if (!$cursoSeccionModel->actualizar()) {
            throw new Exception("Error al actualizar datos del curso/sección");
        }

        // Actualizar contador en curso
        $cursoModel = new Curso($conexion);
        $cursoModel->actualizarCantidadSecciones($idCurso);

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Curso/Sección actualizado correctamente';
        header("Location: ../vistas/registros/curso_seccion/editar_curso_seccion.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/registros/curso_seccion/editar_curso_seccion.php?id=$id");
    }
}

function eliminarCursoSeccion() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/registros/curso_seccion/curso_seccion.php');
    }

    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/curso_seccion/curso_seccion.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $cursoSeccionModel = new CursoSeccion($conexion);

        if (!$cursoSeccionModel->obtenerPorId($id)) {
            manejarError('Curso/Sección no encontrado', '../vistas/registros/curso_seccion/curso_seccion.php');
        }

        $cursoSeccionModel->IdCurso_Seccion = $id;

        if ($cursoSeccionModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error';
            $_SESSION['message'] = 'No se puede eliminar porque tiene estudiantes inscritos';
            header("Location: ../vistas/registros/curso_seccion/curso_seccion.php");
            exit();
        }

        if (!$cursoSeccionModel->eliminar()) {
            throw new Exception("Error al eliminar el curso/sección");
        }

        $_SESSION['alert'] = 'deleted';
        $_SESSION['message'] = 'Curso/Sección eliminado correctamente';
        header("Location: ../vistas/registros/curso_seccion/curso_seccion.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/registros/curso_seccion/curso_seccion.php');
    }
}

function manejarError(string $mensaje, string $urlRedireccion = '../vistas/registros/curso_seccion/nuevo_curso_seccion.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}