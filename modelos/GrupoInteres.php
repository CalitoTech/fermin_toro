<?php
require_once __DIR__ . '/../config/conexion.php';

class GrupoInteres {
    private $conn;
    public $IdGrupo_Interes;
    public $IdTipo_Grupo;
    public $IdProfesor;
    public $IdCurso;
    public $IdFecha_Escolar;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO grupo_interes SET 
            IdTipo_Grupo = :IdTipo_Grupo,
            IdProfesor = :IdProfesor,
            IdCurso = :IdCurso,
            IdFecha_Escolar = :IdFecha_Escolar";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->IdTipo_Grupo = htmlspecialchars(strip_tags($this->IdTipo_Grupo));
        $this->IdProfesor = htmlspecialchars(strip_tags($this->IdProfesor));
        $this->IdCurso = htmlspecialchars(strip_tags($this->IdCurso));
        $this->IdFecha_Escolar = htmlspecialchars(strip_tags($this->IdFecha_Escolar));

        // Vincular valores
        $stmt->bindParam(":IdTipo_Grupo", $this->IdTipo_Grupo);
        $stmt->bindParam(":IdProfesor", $this->IdProfesor);
        $stmt->bindParam(":IdCurso", $this->IdCurso);
        $stmt->bindParam(":IdFecha_Escolar", $this->IdFecha_Escolar);

        return $stmt->execute();
    }

    public function obtenerGrupoInteres() {
        $query = "SELECT gc.*, tg.nombre_grupo, tg.descripcion, p.nombre, p.apellido,
                gc.IdCurso, c.curso, gc.IdFecha_Escolar, fe.fecha_escolar,
                (SELECT COUNT(*) FROM inscripcion_grupo_interes admin WHERE admin.IdGrupo_Interes = gc.IdGrupo_Interes) as total_estudiantes
                 FROM grupo_interes gc
                 JOIN tipo_grupo_interes tg ON gc.IdTipo_Grupo = tg.IdTipo_Grupo
                 JOIN persona p ON gc.IdProfesor = p.IdPersona
                 JOIN curso c ON gc.IdCurso = c.IdCurso
                 JOIN fecha_escolar fe ON gc.IdFecha_Escolar = fe.IdFecha_Escolar
                 ORDER BY gc.IdGrupo_Interes DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorFechaEscolar($idFechaEscolar) {
        $query = "SELECT gc.*, tg.nombre_grupo, tg.descripcion, p.nombre, p.apellido, c.curso
                 FROM grupo_interes gc
                 JOIN tipo_grupo_interes tg ON gc.IdTipo_Grupo = tg.IdTipo_Grupo
                 JOIN persona p ON gc.IdProfesor = p.IdPersona
                 JOIN curso c ON gc.IdCurso = c.IdCurso
                 WHERE gc.IdFecha_Escolar = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idFechaEscolar);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM grupo_interes WHERE IdGrupo_Interes = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdGrupo_Interes = $row['IdGrupo_Interes'];
            $this->IdTipo_Grupo = $row['IdTipo_Grupo'];
            $this->IdProfesor = $row['IdProfesor'];
            $this->IdCurso = $row['IdCurso'];
            $this->IdFecha_Escolar = $row['IdFecha_Escolar'];
            return $row;
        }
        
        return false;
    }

    public function actualizar() {
        $query = "UPDATE grupo_interes SET 
            IdTipo_Grupo = :IdTipo_Grupo,
            IdProfesor = :IdProfesor,
            IdCurso = :IdCurso,
            IdFecha_Escolar = :IdFecha_Escolar
            WHERE IdGrupo_Interes = :IdGrupo_Interes";
            
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->IdTipo_Grupo = htmlspecialchars(strip_tags($this->IdTipo_Grupo));
        $this->IdProfesor = htmlspecialchars(strip_tags($this->IdProfesor));
        $this->IdCurso = htmlspecialchars(strip_tags($this->IdCurso));
        $this->IdFecha_Escolar = htmlspecialchars(strip_tags($this->IdFecha_Escolar));
        $this->IdGrupo_Interes = htmlspecialchars(strip_tags($this->IdGrupo_Interes));
        
        // Vincular valores
        $stmt->bindParam(":IdTipo_Grupo", $this->IdTipo_Grupo);
        $stmt->bindParam(":IdProfesor", $this->IdProfesor);
        $stmt->bindParam(":IdCurso", $this->IdCurso);
        $stmt->bindParam(":IdFecha_Escolar", $this->IdFecha_Escolar);
        $stmt->bindParam(":IdGrupo_Interes", $this->IdGrupo_Interes);
        
        return $stmt->execute();
    }

    public function eliminar() {
        if ($this->tieneDependencias()) {
            return false;
        }
        
        $query = "DELETE FROM grupo_interes WHERE IdGrupo_Interes = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->IdGrupo_Interes);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar si hay inscripciones en este grupo
        $query = "SELECT COUNT(*) as total FROM inscripcion_grupo_interes WHERE IdGrupo_Interes = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->IdGrupo_Interes);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] > 0;
    }
}
?>