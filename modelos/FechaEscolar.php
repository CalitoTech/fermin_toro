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
        $consulta = "SELECT * FROM fecha_escolar WHERE activa = TRUE LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>