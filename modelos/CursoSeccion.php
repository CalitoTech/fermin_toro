<?php
require_once __DIR__ . '/../config/conexion.php';

class CursoSeccion {
    private $conn;
    public $IdCurso_Seccion;
    public $cantidad_estudiantes;
    public $IdCurso;
    public $IdSeccion;

    public $IdAula;
    public $activo;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO curso_seccion (IdCurso, IdSeccion, IdAula, activo)
                 VALUES (:IdCurso, :IdSeccion, :IdAula, :activo)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':IdCurso', $this->IdCurso);
        $stmt->bindParam(':IdSeccion', $this->IdSeccion);
        $stmt->bindParam(':IdAula', $this->IdAula, PDO::PARAM_INT);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            $this->IdCurso_Seccion = $this->conn->lastInsertId();
            return $this->IdCurso_Seccion;
        }
        return false;
    }

    public function actualizar() {
        $query = "UPDATE curso_seccion SET
                 IdCurso = :IdCurso,
                 IdSeccion = :IdSeccion,
                 IdAula = :IdAula,
                 activo = :activo
                 WHERE IdCurso_Seccion = :IdCurso_Seccion";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':IdCurso', $this->IdCurso);
        $stmt->bindParam(':IdSeccion', $this->IdSeccion);
        $stmt->bindParam(':IdAula', $this->IdAula, PDO::PARAM_INT);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);
        $stmt->bindParam(':IdCurso_Seccion', $this->IdCurso_Seccion);

        return $stmt->execute();
    }

    public function eliminar() {
        if ($this->tieneDependencias()) {
            return false;
        }

        $query = "DELETE FROM curso_seccion WHERE IdCurso_Seccion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdCurso_Seccion);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        $query = "SELECT COUNT(*) as total FROM inscripcion WHERE IdCurso_Seccion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdCurso_Seccion);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($resultado['total'] > 0);
    }

    public function obtenerPorId($id) {
        $query = "SELECT cs.*, c.curso, s.seccion, a.aula, n.nivel
                 FROM curso_seccion cs
                 JOIN curso c ON cs.IdCurso = c.IdCurso
                 JOIN seccion s ON cs.IdSeccion = s.IdSeccion
                 LEFT JOIN aula a ON cs.IdAula = a.IdAula
                 JOIN nivel n ON c.IdNivel = n.IdNivel
                 WHERE cs.IdCurso_Seccion = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->IdCurso_Seccion = $row['IdCurso_Seccion'];
            $this->IdCurso = $row['IdCurso'];
            $this->IdSeccion = $row['IdSeccion'];
            $this->IdAula = $row['IdAula'];
            $this->activo = $row['activo'];
            return $row;
        }

        return false;
    }

    public function obtenerTodos() {
        $query = "SELECT cs.*, c.curso, s.seccion, a.aula, n.nivel
                 FROM curso_seccion cs
                 JOIN curso c ON cs.IdCurso = c.IdCurso
                 JOIN seccion s ON cs.IdSeccion = s.IdSeccion
                 LEFT JOIN aula a ON cs.IdAula = a.IdAula
                 JOIN nivel n ON c.IdNivel = n.IdNivel
                 ORDER BY n.nivel, c.curso, s.seccion";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorNivel($idNivel) {
        $query = "SELECT cs.*, c.curso, s.seccion, a.aula
                 FROM curso_seccion cs
                 JOIN curso c ON cs.IdCurso = c.IdCurso
                 JOIN seccion s ON cs.IdSeccion = s.IdSeccion
                 LEFT JOIN aula a ON cs.IdAula = a.IdAula
                 WHERE c.IdNivel = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idNivel, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerPorCursoYSeccion($idCurso, $idSeccion) {
        $query = "SELECT cs.*, s.seccion, a.aula, c.curso, n.nivel
                 FROM curso_seccion cs
                 JOIN seccion s ON cs.IdSeccion = s.IdSeccion
                 LEFT JOIN aula a ON cs.IdAula = a.IdAula
                 JOIN curso c ON cs.IdCurso = c.IdCurso
                 JOIN nivel n ON c.IdNivel = n.IdNivel
                 WHERE cs.IdCurso = ? AND cs.IdSeccion = ?
                 LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idCurso, PDO::PARAM_INT);
        $stmt->bindParam(2, $idSeccion, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerCursosSecciones($idPersona) {
        // Obtener todos los perfiles del usuario
        $sqlPerfiles = "SELECT IdPerfil FROM detalle_perfil WHERE IdPersona = :idPersona";
        $stmtPerfiles = $this->conn->prepare($sqlPerfiles);
        $stmtPerfiles->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);
        $stmtPerfiles->execute();
        $perfilesUsuario = $stmtPerfiles->fetchAll(PDO::FETCH_COLUMN);

        // Determinar si tiene algún perfil con acceso total
        $perfilesAutorizadosTotales = [1, 6, 7]; // Administrador, Director, Control de Estudios
        $tieneAccesoTotal = !empty(array_intersect($perfilesUsuario, $perfilesAutorizadosTotales));

        // Determinar qué niveles puede ver (por IdNivel)
        $nivelesPermitidos = [];

        if (in_array(8, $perfilesUsuario)) $nivelesPermitidos[] = 1; // Inicial
        if (in_array(9, $perfilesUsuario)) $nivelesPermitidos[] = 2; // Primaria
        if (in_array(10, $perfilesUsuario)) $nivelesPermitidos[] = 3; // Media General

        // Construir condición WHERE según los permisos
        $filtroNivel = "";

        if (!$tieneAccesoTotal && !empty($nivelesPermitidos)) {
            // Generar lista segura para el filtro
            $nivelesIn = implode(",", array_map('intval', $nivelesPermitidos));
            $filtroNivel = "WHERE c.IdNivel IN ($nivelesIn)";
        }

        $query = "
            SELECT
                cs.IdCurso_Seccion,
                cs.IdCurso,
                cs.IdSeccion,
                cs.IdCurso,
                cs.IdSeccion,
                cs.IdAula,
                cs.activo,
                c.curso,
                s.seccion,
                COALESCE(a.aula, 'Sin Asignar') AS aula,
                n.nivel,
                (
                    SELECT COUNT(DISTINCT i.IdEstudiante)
                    FROM inscripcion i
                    INNER JOIN fecha_escolar fe ON i.IdFecha_Escolar = fe.IdFecha_Escolar
                    WHERE i.IdCurso_Seccion = cs.IdCurso_Seccion
                    AND fe.fecha_activa = TRUE
                    AND i.IdStatus = 11
                ) AS cantidad_estudiantes
            FROM curso_seccion cs
            JOIN curso c ON cs.IdCurso = c.IdCurso
            JOIN seccion s ON cs.IdSeccion = s.IdSeccion
            LEFT JOIN aula a ON cs.IdAula = a.IdAula
            JOIN nivel n ON c.IdNivel = n.IdNivel
            $filtroNivel
            ORDER BY n.IdNivel, c.curso, s.seccion
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerSeccionesExcedentes($idCurso, $cantidadObjetivo) {
        // Obtener todas las secciones activas alfabéticas ordenadas descendente (Z -> A)
        $query = "SELECT cs.IdCurso_Seccion, cs.activo, s.seccion,
                  (SELECT COUNT(*) FROM inscripcion i WHERE i.IdCurso_Seccion = cs.IdCurso_Seccion AND i.IdStatus = 11) as num_estudiantes
                  FROM curso_seccion cs 
                  JOIN seccion s ON cs.IdSeccion = s.IdSeccion 
                  WHERE cs.IdCurso = :idCurso 
                  AND cs.activo = 1
                  AND s.seccion != 'Inscripción'
                  ORDER BY s.seccion DESC"; // Las últimas del alfabeto primero
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idCurso', $idCurso);
        $stmt->execute();
        $activas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalActivas = count($activas);
        
        if ($cantidadObjetivo >= $totalActivas) {
            return []; // No hay que reducir nada
        }

        $aEliminar = array_slice($activas, 0, $totalActivas - $cantidadObjetivo);
        return $aEliminar;
    }

    public function reubicarEstudiantes($idCurso, $idsSeccionesDesactivar) {
        if (empty($idsSeccionesDesactivar)) return true;

        // 1. Obtener estudiantes afectados
        $placeholders = implode(',', array_fill(0, count($idsSeccionesDesactivar), '?'));
        $queryEst = "SELECT IdInscripcion FROM inscripcion WHERE IdCurso_Seccion IN ($placeholders) AND IdStatus = 11";
        $stmtEst = $this->conn->prepare($queryEst);
        $stmtEst->execute($idsSeccionesDesactivar);
        //$estudiantes = $stmtEst->fetchAll(PDO::FETCH_ASSOC); // Trae arrays
        $estudiantesIds = $stmtEst->fetchAll(PDO::FETCH_COLUMN);

        if (empty($estudiantesIds)) return true; // Nada que reubicar

        // 2. Obtener secciones destino (Activas y NO incluídas en las desactivar)
        // Nota: Si estamos en "editar curso", las "desactivar" son las que sobran. Las otras se quedan.
        // Si estamos en "editar seccion", la "desactivar" es la actual. Las otras activas son destino.
        
        $queryDest = "SELECT IdCurso_Seccion FROM curso_seccion cs
                      JOIN seccion s ON cs.IdSeccion = s.IdSeccion
                      WHERE cs.IdCurso = ? 
                      AND cs.activo = 1 
                      AND s.seccion != 'Inscripción'
                      AND cs.IdCurso_Seccion NOT IN ($placeholders)";
        
        // params: idCurso + idsSeccionesDesactivar
        $params = array_merge([$idCurso], $idsSeccionesDesactivar);
        $stmtDest = $this->conn->prepare($queryDest);
        $stmtDest->execute($params);
        $destinos = $stmtDest->fetchAll(PDO::FETCH_COLUMN);

        if (empty($destinos)) {
            // No hay dónde moverlos. (Caso extremo: desactivando la última sección con alumnos)
            // Podríamos crear una excepción o moverlos a "Inscripción" temporalmente?
            // Por ahora retornamos false
            return false;
        }

        // 3. Distribución equitativa (Round Robin)
        // Mezclar estudiantes para aleatoriedad
        shuffle($estudiantesIds);
        
        $countDest = count($destinos);
        $updates = [];
        
        foreach ($estudiantesIds as $index => $idInscripcion) {
            $destinoId = $destinos[$index % $countDest];
            // Preparar update
            $updates[] = "UPDATE inscripcion SET IdCurso_Seccion = $destinoId WHERE IdInscripcion = $idInscripcion";
        }

        // Ejecutar updates (idealmente en transacción, pero vamos simple por now)
        try {
            $this->conn->beginTransaction();
            foreach ($updates as $sql) {
                $this->conn->exec($sql);
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function obtenerEstudiantesPorSeccion($idCursoSeccion) {
        $query = "SELECT p.nombre, p.apellido, c.curso, s.seccion 
                  FROM inscripcion i
                  JOIN persona p ON i.IdEstudiante = p.IdPersona
                  JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
                  JOIN curso c ON cs.IdCurso = c.IdCurso
                  JOIN seccion s ON cs.IdSeccion = s.IdSeccion
                  WHERE i.IdCurso_Seccion = :id AND i.IdStatus = 11";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $idCursoSeccion);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function sincronizarSecciones($idCurso, $cantidadObjetivo) {
        require_once __DIR__ . '/Seccion.php';
        $seccionModel = new Seccion($this->conn);

        // 1. Asegurar sección "Inscripción" (Activo = 0 por defecto si no existe)
        $idInscripcion = $seccionModel->obtenerOcrearPorNombre('Inscripción');
        $this->asegurarCursoSeccion($idCurso, $idInscripcion, 0);

        // 2. Lógica para secciones alfabéticas (A, B, C...)
        $alfabeto = range('A', 'Z');
        
        // Obtener estado actual de todas las secciones del curso
        $query = "SELECT cs.IdCurso_Seccion, cs.activo, s.seccion 
                  FROM curso_seccion cs 
                  JOIN seccion s ON cs.IdSeccion = s.IdSeccion 
                  WHERE cs.IdCurso = :idCurso";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idCurso', $idCurso);
        $stmt->execute();
        $actuales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapaActuales = []; 
        foreach ($actuales as $row) {
            $mapaActuales[$row['seccion']] = $row;
        }

        // Activar o Crear secciones necesarias
        for ($i = 0; $i < $cantidadObjetivo; $i++) {
            $letra = $alfabeto[$i];
            
            if (isset($mapaActuales[$letra])) {
                // Existe, verificar si está activa
                if ($mapaActuales[$letra]['activo'] == 0) {
                    $this->actualizarEstado($mapaActuales[$letra]['IdCurso_Seccion'], 1);
                }
            } else {
                // No existe, crearla activa
                $idSeccionLetra = $seccionModel->obtenerOcrearPorNombre($letra);
                $this->crearCursoSeccionRapido($idCurso, $idSeccionLetra, 1);
            }
        }

        // Desactivar excedentes
        foreach ($mapaActuales as $sec => $data) {
             if ($sec == 'Inscripción') continue;
             
             // Si es una letra mayúscula simple
             if (strlen($sec) == 1 && ctype_upper($sec)) {
                 $index = ord($sec) - ord('A');
                 if ($index >= $cantidadObjetivo) {
                     // Es excedente, desactivar si está activa
                     if ($data['activo'] == 1) {
                         $this->actualizarEstado($data['IdCurso_Seccion'], 0);
                     }
                 }
             }
        }
    }

    private function asegurarCursoSeccion($idCurso, $idSeccion, $activo) {
        $query = "SELECT IdCurso_Seccion FROM curso_seccion WHERE IdCurso = :c AND IdSeccion = :s";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':c', $idCurso);
        $stmt->bindParam(':s', $idSeccion);
        $stmt->execute();
        if (!$stmt->fetch()) {
            $this->crearCursoSeccionRapido($idCurso, $idSeccion, $activo);
        }
    }

    private function crearCursoSeccionRapido($idCurso, $idSeccion, $activo) {
        $query = "INSERT INTO curso_seccion (IdCurso, IdSeccion, activo) VALUES (:c, :s, :a)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':c', $idCurso);
        $stmt->bindParam(':s', $idSeccion);
        $stmt->bindParam(':a', $activo, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function actualizarEstado($id, $activo) {
         $query = "UPDATE curso_seccion SET activo = :a WHERE IdCurso_Seccion = :id";
         $stmt = $this->conn->prepare($query);
         $stmt->bindParam(':a', $activo, PDO::PARAM_INT);
         $stmt->bindParam(':id', $id);
         $stmt->execute();
    }

    public function contarSeccionesActivas($idCurso) {
        $query = "SELECT COUNT(*) as total 
                  FROM curso_seccion cs
                  JOIN seccion s ON cs.IdSeccion = s.IdSeccion
                  WHERE cs.IdCurso = :idCurso 
                  AND cs.activo = 1 
                  AND s.seccion != 'Inscripción'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idCurso', $idCurso);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function verificarOrdenDesactivacion($idCurso, $idCursoSeccion) {
        // 1. Verificar si es la sección "Inscripción"
        $queryInsc = "SELECT s.seccion FROM curso_seccion cs 
                      JOIN seccion s ON cs.IdSeccion = s.IdSeccion 
                      WHERE cs.IdCurso_Seccion = :id";
        $stmtInsc = $this->conn->prepare($queryInsc);
        $stmtInsc->bindParam(':id', $idCursoSeccion);
        $stmtInsc->execute();
        $seccionActual = $stmtInsc->fetch(PDO::FETCH_ASSOC);

        if ($seccionActual && $seccionActual['seccion'] === 'Inscripción') {
            return ['valido' => false, 'mensaje' => 'No se puede modificar manualmente el estado de la sección "Inscripción".'];
        }

        // 2. Obtener todas las secciones activas alfabéticas (A, B, C...)
        $query = "SELECT cs.IdCurso_Seccion, s.seccion 
                  FROM curso_seccion cs 
                  JOIN seccion s ON cs.IdSeccion = s.IdSeccion 
                  WHERE cs.IdCurso = :idCurso 
                  AND cs.activo = 1 
                  AND s.seccion != 'Inscripción'
                  ORDER BY s.seccion DESC"; // Z -> A
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idCurso', $idCurso);
        $stmt->execute();
        $activas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($activas)) {
            // No hay secciones activas (raro si estamos intentando desactivar una que supuestamente está activa)
            return ['valido' => true]; 
        }

        // La primera en la lista es la mayor alfabéticamente (Z, Y, X...)
        $ultimaSeccion = $activas[0];

        // Si la sección que queremos desactivar NO es la última
        $ultimaId = $ultimaSeccion['IdCurso_Seccion'];
        
        // Verifica si la sección a desactivar está en la lista de activas y no es la última
        $esActiva = false;
        foreach ($activas as $activa) {
            if ($activa['IdCurso_Seccion'] == $idCursoSeccion) {
                $esActiva = true;
                break;
            }
        }

        if ($esActiva && $ultimaId != $idCursoSeccion) {
             return [
                'valido' => false, 
                'mensaje' => "No se puede desactivar esta sección. Debe mantener el orden alfabético y desactivar primero la sección '{$ultimaSeccion['seccion']}'."
            ];
        }

        return ['valido' => true];
    }
}