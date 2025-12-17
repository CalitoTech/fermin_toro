<?php
require_once __DIR__ . '/../config/conexion.php';
class Seccion {
    private $conn;
    public $IdSeccion;
    public $seccion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO seccion (seccion) VALUES (:seccion)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':seccion', $this->seccion);
        
        if ($stmt->execute()) {
            $this->IdSeccion = $this->conn->lastInsertId();
            return $this->IdSeccion;
        }
        return false;
    }
    
    public function actualizar() {
        $query = "UPDATE seccion SET seccion = :seccion WHERE IdSeccion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':seccion', $this->seccion);
        $stmt->bindParam(':id', $this->IdSeccion);
        return $stmt->execute();
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM seccion WHERE IdSeccion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdSeccion);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar si el seccion está siendo usado en la tabla curso_seccion
        $query = "SELECT COUNT(*) as total FROM curso_seccion WHERE IdSeccion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdSeccion);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($resultado['total'] > 0);
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM seccion";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM seccion WHERE IdSeccion = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdSeccion = $row['IdSeccion'];
            $this->seccion = $row['seccion'];
            return $row;
        }
        
        return false;
    }

    public function obtenerDisponiblesConCupo($idCurso, $idUrbanismo, $idCursoSeccionActual = null) {
        $query = "SELECT 
                    cs.IdCurso_Seccion,
                    s.seccion,
                    a.aula,
                    a.capacidad,
                    (SELECT COUNT(*) FROM inscripcion i2 
                    WHERE i2.IdCurso_Seccion = cs.IdCurso_Seccion 
                    AND i2.IdStatus = 11) as estudiantes_actuales,
                    (SELECT COUNT(*) FROM inscripcion i3 
                    INNER JOIN persona e ON i3.IdEstudiante = e.IdPersona
                    WHERE i3.IdCurso_Seccion = cs.IdCurso_Seccion 
                    AND i3.IdStatus = 11 
                    AND e.IdUrbanismo = :id_urbanismo) as mismos_urbanismo
                FROM curso_seccion cs
                INNER JOIN seccion s ON cs.IdSeccion = s.IdSeccion
                LEFT JOIN aula a ON cs.IdAula = a.IdAula
                WHERE cs.IdCurso = :id_curso
                AND cs.activo = 1
                AND s.seccion != 'Inscripción'
                " . ($idCursoSeccionActual ? "AND cs.IdCurso_Seccion != :id_curso_seccion_actual" : "") . "
                ORDER BY s.seccion";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_curso', $idCurso, PDO::PARAM_INT);
        $stmt->bindParam(':id_urbanismo', $idUrbanismo, PDO::PARAM_INT);
        if ($idCursoSeccionActual) {
            $stmt->bindParam(':id_curso_seccion_actual', $idCursoSeccionActual, PDO::PARAM_INT);
        }
        $stmt->execute();
        $secciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular máximo urbanismo y filtrar con cupo
        $maxMismosUrbanismo = 0;
        foreach ($secciones as $s) {
            if (($s['mismos_urbanismo'] ?? 0) > $maxMismosUrbanismo) {
                $maxMismosUrbanismo = $s['mismos_urbanismo'];
            }
        }

        $seccionesConCupo = array_filter($secciones, function ($sec) {
            if (empty($sec['capacidad'])) return true;
            return (int)$sec['estudiantes_actuales'] < (int)$sec['capacidad'];
        });

        return [
            'todas' => $secciones,
            'con_cupo' => array_values($seccionesConCupo),
            'max_urbanismo' => $maxMismosUrbanismo,
            'hay_recomendada' => $maxMismosUrbanismo > 0
        ];
    }

    public function obtenerOcrearPorNombre($nombre) {
        // Buscar si existe
        $query = "SELECT IdSeccion FROM seccion WHERE seccion = :nombre LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['IdSeccion'];
        }

        // Si no existe, crear
        $query = "INSERT INTO seccion (seccion) VALUES (:nombre)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
}
?>