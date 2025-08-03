<?php
class Nivel {
    private $conexion;

    public function __construct($conexionPDO) {
        $this->conexion = $conexionPDO;
    }

    public function obtenerTodos() {
        $consulta = "SELECT IdNivel, nivel FROM nivel";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerRequisitos($idNivel) {
        $consulta = "SELECT IdRequisito, requisito, obligatorio 
                     FROM requisito 
                     WHERE IdNivel = ?";
        
        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute([$idNivel]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}