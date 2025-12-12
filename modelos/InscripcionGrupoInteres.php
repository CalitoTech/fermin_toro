<?php
require_once __DIR__ . '/../config/conexion.php';

class InscripcionGrupoInteres {
    private $conn;
    public $IdInscripcion_Grupo;
    public $IdGrupo_Interes;
    public $IdEstudiante;
    public $IdInscripcion; // Linked to the general enrollment record

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO inscripcion_grupo_interes SET 
            IdGrupo_Interes = :IdGrupo_Interes,
            IdEstudiante = :IdEstudiante,
            IdInscripcion = :IdInscripcion";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->IdGrupo_Interes = htmlspecialchars(strip_tags($this->IdGrupo_Interes));
        $this->IdEstudiante = htmlspecialchars(strip_tags($this->IdEstudiante));
        $this->IdInscripcion = htmlspecialchars(strip_tags($this->IdInscripcion));

        // Vincular valores
        $stmt->bindParam(":IdGrupo_Interes", $this->IdGrupo_Interes);
        $stmt->bindParam(":IdEstudiante", $this->IdEstudiante);
        $stmt->bindParam(":IdInscripcion", $this->IdInscripcion);

        return $stmt->execute();
    }

    public function obtenerTodos() {
        // Retrieve enrollment info: Student Name, Group Name, Level, etc.
        $query = "SELECT ig.*, 
                         p.nombre, p.apellido, p.cedula,
                         tg.nombre_grupo,
                         n.nivel,
                         c.curso
                  FROM inscripcion_grupo_interes ig
                  JOIN persona p ON ig.IdEstudiante = p.IdPersona
                  JOIN grupo_interes gi ON ig.IdGrupo_Interes = gi.IdGrupo_Interes
                  JOIN tipo_grupo_interes tg ON gi.IdTipo_Grupo = tg.IdTipo_Grupo
                  JOIN nivel n ON tg.IdNivel = n.IdNivel
                  LEFT JOIN curso c ON gi.IdCurso = c.IdCurso
                  ORDER BY ig.IdInscripcion_Grupo DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM inscripcion_grupo_interes WHERE IdInscripcion_Grupo = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdInscripcion_Grupo = $row['IdInscripcion_Grupo'];
            $this->IdGrupo_Interes = $row['IdGrupo_Interes'];
            $this->IdEstudiante = $row['IdEstudiante'];
            $this->IdInscripcion = $row['IdInscripcion'];
            return $row;
        }
        
        return false;
    }

    public function actualizar() {
        $query = "UPDATE inscripcion_grupo_interes SET 
            IdGrupo_Interes = :IdGrupo_Interes,
            IdEstudiante = :IdEstudiante,
            IdInscripcion = :IdInscripcion
            WHERE IdInscripcion_Grupo = :IdInscripcion_Grupo";
            
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->IdGrupo_Interes = htmlspecialchars(strip_tags($this->IdGrupo_Interes));
        $this->IdEstudiante = htmlspecialchars(strip_tags($this->IdEstudiante));
        $this->IdInscripcion = htmlspecialchars(strip_tags($this->IdInscripcion));
        $this->IdInscripcion_Grupo = htmlspecialchars(strip_tags($this->IdInscripcion_Grupo));
        
        // Vincular valores
        $stmt->bindParam(":IdGrupo_Interes", $this->IdGrupo_Interes);
        $stmt->bindParam(":IdEstudiante", $this->IdEstudiante);
        $stmt->bindParam(":IdInscripcion", $this->IdInscripcion);
        $stmt->bindParam(":IdInscripcion_Grupo", $this->IdInscripcion_Grupo);
        
        return $stmt->execute();
    }

    public function eliminar() {
        $query = "DELETE FROM inscripcion_grupo_interes WHERE IdInscripcion_Grupo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->IdInscripcion_Grupo);
        return $stmt->execute();
    }
    
    // Check if student is already enrolled in a group of this type (optional validation)
    public function existeInscripcion($idEstudiante, $idGrupoInteres) {
        $query = "SELECT COUNT(*) as total FROM inscripcion_grupo_interes 
                  WHERE IdEstudiante = :idEstudiante AND IdGrupo_Interes = :idGrupo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":idEstudiante", $idEstudiante);
        $stmt->bindParam(":idGrupo", $idGrupoInteres);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] > 0;
    }
    public function obtenerEstudiantesConGrupo($idFechaEscolar) {
        $query = "SELECT DISTINCT ig.IdEstudiante
                  FROM inscripcion_grupo_interes ig
                  JOIN inscripcion i ON ig.IdInscripcion = i.IdInscripcion
                  WHERE i.IdFecha_Escolar = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idFechaEscolar);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>