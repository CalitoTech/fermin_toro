<?php
require_once __DIR__ . '/../config/conexion.php';

class FechaEscolar {
    private $conexion;

    public function __construct($conexionPDO) {
        $this->conexion = $conexionPDO;
    }

    /**
     * Obtiene el año escolar activo
     * @return array|null Datos del año escolar activo o null si no hay
     */
    public function obtenerActivo() {
        $consulta = "SELECT * FROM fecha_escolar WHERE fecha_activa = TRUE LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los años escolares ordenados por IdFechaEscolar DESC
     * @return array Lista de años escolares
     */
    public function obtenerTodos() {
        $consulta = "SELECT * FROM fecha_escolar ORDER BY IdFecha_Escolar DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>