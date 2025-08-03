<?php
require_once __DIR__ . '/../config/conexion.php';

class Curso {
    private $conexion;

    public function __construct($conexionPDO) {
        $this->conexion = $conexionPDO;
    }

    public function obtenerTodos() {
        $consulta = "SELECT c.IdCurso, c.curso, n.IdNivel, n.nivel as nombre_nivel
                     FROM curso c
                     JOIN nivel n ON c.IdNivel = n.IdNivel
                     ORDER BY n.IdNivel, c.curso";
        
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorNivel($idNivel) {
        $consulta = "SELECT c.IdCurso, c.curso, n.nivel as nombre_nivel
                     FROM curso c
                     JOIN nivel n ON c.IdNivel = n.IdNivel
                     WHERE c.IdNivel = :idNivel";
        
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':idNivel', $idNivel, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerConSecciones($idNivel) {
        $consulta = "SELECT c.IdCurso, c.curso, s.IdSeccion, s.seccion
                     FROM curso c
                     LEFT JOIN aula a ON c.IdCurso = a.IdCurso
                     LEFT JOIN seccion s ON a.IdSeccion = s.IdSeccion
                     WHERE c.IdNivel = :idNivel";
        
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':idNivel', $idNivel, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}