<?php
require_once __DIR__ . '/../config/conexion.php';

class CursoSeccion {
    private $conn;
    public $IdCurso_Seccion;
    public $cantidad_estudiantes;
    public $IdCurso;
    public $IdSeccion;
    public $IdAula;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO curso_seccion (IdCurso, IdSeccion, IdAula, cantidad_estudiantes) 
                 VALUES (:IdCurso, :IdSeccion, :IdAula, :cantidad_estudiantes)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':IdCurso', $this->IdCurso);
        $stmt->bindParam(':IdSeccion', $this->IdSeccion);
        $stmt->bindParam(':IdAula', $this->IdAula, PDO::PARAM_INT);
        $stmt->bindParam(':cantidad_estudiantes', $this->cantidad_estudiantes, PDO::PARAM_INT);
        
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
                 cantidad_estudiantes = :cantidad_estudiantes
                 WHERE IdCurso_Seccion = :IdCurso_Seccion";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':IdCurso', $this->IdCurso);
        $stmt->bindParam(':IdSeccion', $this->IdSeccion);
        $stmt->bindParam(':IdAula', $this->IdAula, PDO::PARAM_INT);
        $stmt->bindParam(':cantidad_estudiantes', $this->cantidad_estudiantes);
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
            $this->cantidad_estudiantes = $row['cantidad_estudiantes'];
            $this->IdCurso = $row['IdCurso'];
            $this->IdSeccion = $row['IdSeccion'];
            $this->IdAula = $row['IdAula'];
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
}