<?php
require_once __DIR__ . '/../config/conexion.php';
class Aula {
    private $conexion;
    public $IdAula;
    public $capacidad;
    public $IdCurso;
    public $IdSeccion;

    public function __construct($conexionPDO) {
        $this->conexion = $conexionPDO;
    }

    public function obtenerPorCurso($idCurso) {
        $query = "SELECT a.*, s.seccion 
                 FROM aula a
                 JOIN seccion s ON a.IdSeccion = s.IdSeccion
                 WHERE a.IdCurso = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(1, $idCurso);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerDisponibles($idFechaEscolar) {
        $query = "SELECT a.*, c.curso, s.seccion 
                 FROM aula a
                 JOIN curso c ON a.IdCurso = c.IdCurso
                 JOIN seccion s ON a.IdSeccion = s.IdSeccion
                 WHERE a.IdAula NOT IN (
                     SELECT i.IdAula FROM inscripcion i 
                     WHERE i.IdFecha_Escolar = ?
                 )";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(1, $idFechaEscolar);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>