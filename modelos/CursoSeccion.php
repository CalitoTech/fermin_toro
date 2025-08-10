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

    public function obtenerPorCurso($idCurso) {
        $query = "SELECT cs.*, s.seccion, a.aula, c.curso, n.nivel
                 FROM curso_seccion cs
                 JOIN seccion s ON cs.IdSeccion = s.IdSeccion
                 LEFT JOIN aula a ON cs.IdAula = a.IdAula
                 JOIN curso c ON cs.IdCurso = c.IdCurso
                 JOIN nivel n ON c.IdNivel = n.IdNivel
                 WHERE cs.IdCurso = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idCurso, PDO::PARAM_INT);
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

    public function obtenerDisponibles($idFechaEscolar) {
        $query = "SELECT cs.*, c.curso, s.seccion, n.nivel
                 FROM curso_seccion cs
                 JOIN curso c ON cs.IdCurso = c.IdCurso
                 JOIN seccion s ON cs.IdSeccion = s.IdSeccion
                 JOIN nivel n ON c.IdNivel = n.IdNivel
                 WHERE cs.IdCurso_Seccion NOT IN (
                     SELECT i.IdCurso_Seccion FROM inscripcion i 
                     WHERE i.IdFecha_Escolar = ?
                 )";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idFechaEscolar, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarCantidadEstudiantes($idCursoSeccion, $cantidad) {
        $query = "UPDATE curso_seccion SET cantidad_estudiantes = ? WHERE IdCurso_Seccion = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $cantidad, PDO::PARAM_INT);
        $stmt->bindParam(2, $idCursoSeccion, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function asignarAula($idCursoSeccion, $idAula) {
        $query = "UPDATE curso_seccion SET IdAula = ? WHERE IdCurso_Seccion = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idAula, PDO::PARAM_INT);
        $stmt->bindParam(2, $idCursoSeccion, PDO::PARAM_INT);
        return $stmt->execute();
    }
}